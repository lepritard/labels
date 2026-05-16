# LF Label Generator — Product Requirements Document

**Project:** LF Label Generator  
**Owner:** Lake Forest Industries — Warehouse Receiving  
**Current Version:** v1.37  
**Last Updated:** 2026-05-16  
**Status:** Active Development

---

## 1. Purpose & Background

Lake Forest Industries receives inbound shipments from floor-loaded shipping containers at their warehouse. Each box and pallet must carry a printed identification label before it can be inducted into inventory. Prior to this system, operators typed label data into a Microsoft Word template and printed from there — a process that was slow, error-prone, and required manual serial number tracking.

The LF Label Generator is a locally-hosted PHP web application that replaces the Word workflow. It generates printable box and pallet labels that are visually identical to the Word originals, adds a machine-readable serial tag barcode to every label, and includes a packing slip upload feature that pre-fills all label data automatically from the supplier's Excel file.

---

## 2. Users

| Role | Description | Primary Touch Points |
|---|---|---|
| **Receiving Operator** | Warehouse staff who receive containers, scan boxes, and print labels at the point of receipt | Packing slip upload → review → print |
| **Receiving Supervisor** | Reviews label counts, starting serial numbers, and approves print batches | Review UI serial number fields |
| **Developer / IT** | Maintains the application, adds new customers and exceptions, deploys updates | All source files; `CUSTOMER_MAP`, `EXCEPTIONS`, `KNOWN_PARTS` in parser |

---

## 3. Goals

- **G1 — Eliminate manual data entry.** Parse supplier packing slips automatically so operators only need to upload a file and press Print.
- **G2 — Pixel-accurate label reproduction.** Output labels that match the approved Word template in font, layout, and barcode position so existing QA sign-off is preserved.
- **G3 — Accurate serial tracking.** Assign a unique, non-colliding 9-digit serial barcode to every box, including non-standard remainder boxes and qty-variant boxes.
- **G4 — Low barrier to deployment.** Run on any Windows or macOS machine with XAMPP or PHP's built-in server — no cloud dependency, no database setup.
- **G5 — Maintainability.** Code must be readable and self-documenting enough for a new developer (with PHP and Python experience) to onboard without face-to-face handoff.

---

## 4. Non-Goals

- This system does **not** write to any inventory database or WMS.
- This system is **not** designed for public internet exposure. It is a trusted-network tool only.
- This system does **not** manage the daily serial counter automatically (future work — see Section 10).
- This system does **not** generate shipping manifests, BOLs, or any documentation other than box/pallet labels.

---

## 5. System Architecture

The application consists of five files that collaborate at request time. There is no database and no persistent state beyond the browser session.

```
Browser (operator)
    │
    ├─ GET /index.php          ← Manual box / pallet label form
    │
    ├─ GET /review.php         ← Packing slip upload, review, and print UI
    │       │
    │       └─ POST /parse_slip.php   ← PHP bridge: receives uploaded .xlsx,
    │               │                   invokes Python, returns JSON
    │               └─ parse_packing_slip.py  ← Python parser (openpyxl)
    │
    └─ GET /preview.php        ← Label renderer: returns printable HTML page
```

### 5.1 Data Flow — Packing Slip Path

1. Operator drags `.xlsx` packing slip onto the upload zone in `review.php`.
2. `review.php` POSTs the file to `parse_slip.php` via `XMLHttpRequest`.
3. `parse_slip.php` saves the file to a temp location and shells out to `parse_packing_slip.py`.
4. The Python parser reads the Excel file (Sheet2 or first worksheet), extracts metadata and per-part label groups, and writes JSON to stdout.
5. `parse_slip.php` captures stdout and returns it as `application/json`.
6. `review.php` renders the JSON into a review table with editable fields (units, pcs/unit, received date, starting serial).
7. Operator reviews, adjusts if needed, and clicks **Print** on each row.
8. `review.php` constructs a `preview.php` URL with all label parameters and opens it in a new tab.
9. `preview.php` renders the printable label page. The operator uses the browser's Print dialog.

### 5.2 Data Flow — Manual Path

1. Operator fills in the form on `index.php` (customer, part number, NA number, qty, etc.).
2. On submit, `index.php` constructs a `preview.php` URL and redirects.
3. `preview.php` renders the label page.

---

## 6. File Reference

| File | Language | Role |
|---|---|---|
| `index.php` | PHP + HTML/CSS/JS | Manual label entry form; two-tab UI (Box Labels, Pallet Labels) |
| `review.php` | PHP + HTML/CSS/JS | Packing slip upload, review table, serial allocation, print dispatch |
| `parse_slip.php` | PHP | Thin bridge: saves upload, invokes Python, streams JSON response |
| `parse_packing_slip.py` | Python 3 | Core parser: reads `.xlsx`, normalises part numbers, detects non-standard boxes, outputs JSON |
| `preview.php` | PHP + HTML/CSS | Label renderer: accepts URL params, outputs a `@page`-sized printable HTML document |

---

## 7. Parser — `parse_packing_slip.py`

### 7.1 Input

A New Arch ENTERPRISE packing slip `.xlsx` file. The parser reads the second worksheet (`Sheet2`), falling back to the first worksheet if `Sheet2` does not exist.

### 7.2 Output JSON Schema

```json
{
  "meta": {
    "po":           "PO22643",
    "date":         "APR.,15, 2026",
    "container_no": "CSNU6534955",
    "seal":         "OOLKBA5958"
  },
  "label_groups": [
    {
      "customer":        "TurboChef",
      "part_number":     "TBC-ENC-1726-E",
      "base_part":       "ENC-1726",
      "revision":        "E",
      "pcs_per_box":     4,
      "num_labels":      41,
      "container_type":  "carton",
      "labels_per_unit": 1,
      "total_labels":    41,
      "flags":           [],
      "pallet_group":    null,
      "nonstd_box_num":  null,
      "nonstd_copies":   null,
      "total_boxes":     null
    }
  ]
}
```

### 7.3 Part Number Normalisation (`normalise_part`)

The function `normalise_part(raw_part)` is the parser's primary translation layer. It returns a 4-tuple: `(display_part, customer, base_part, revision)`.

**Revision detection** — The trailing suffix is tested against `-([A-Z]\d+|[A-Z])$`. This matches:
- Single capital letter suffixes: `ENC-1726-E` → revision `E`
- Letter + digit suffixes: `ECS-9562-B1` → revision `B1`
- No match: bare numeric suffixes like `ECS-9586-1` are **not** treated as revisions

**Customer prefix stripping** — `base_part` used in barcodes and exceptions lookup strips the leading customer code (e.g. `TBC-ENC-1726-E` → `ENC-1726`, `APS-B22519-34` → `B22519-34`).

**TBC sub-prefixes** — `HHD`, `ECS`, `ENC`, `ECO`, `HHS`, `HCT` are all TurboChef product lines. They are mapped to `"TurboChef"` directly in `CUSTOMER_MAP`.

**TBCTW typo** — Some packing slips prefix TurboChef parts with `TBCTW-` instead of `TBC-TW-`. The parser corrects this silently.

**Bare numeric parts** — If a part number is all digits, it is looked up in `KNOWN_PARTS`.

### 7.4 Non-Standard Box Detection

After all rows are parsed and same-key rows are merged, the parser runs a consecutive-pair scan:

```
For each consecutive pair (prev, cur) with the same base_part + carton type:
    IF cur.num_labels < prev.num_labels   → classic non-std remainder
    OR cur.pcs_per_box ≠ prev.pcs_per_box → qty-variant non-std box
    THEN flag cur as nonstd_remainder
         set cur.nonstd_box_num = prev.num_labels + 1
         set cur.nonstd_copies  = 5
         set prev.total_boxes   = prev.num_labels + cur.num_labels
```

`nonstd_copies = 5` means five labels are printed for the non-standard box, all sharing the same serial number.

### 7.5 Configuration Tables

These are the three lookup tables that developers are most likely to need to extend:

**`CUSTOMER_MAP`** — maps 2–5 character part-number prefixes to customer display names.

**`EXCEPTIONS`** — maps `base_part` values to co-packed inner-box rules:
```python
"ENC-1833": {"labels_per_carton": 2, "pcs_per_label": 3}
```
When a part is in `EXCEPTIONS`, each outer carton generates `labels_per_carton` labels, each showing `pcs_per_label` pieces.

**`KNOWN_PARTS`** — maps bare numeric part numbers to `{customer, base_part, revision}`.

---

## 8. Review UI — `review.php`

### 8.1 Review Table Columns

| Column | Editable | Description |
|---|---|---|
| `#` | No | Display row number (skips non-std sub-rows) |
| Customer | No | From parser |
| Part Number | No | `base_part` with revision badge |
| Type | No | `CARTON` / `PALLET` / `WOODEN CASE` badge |
| Units | Yes | Number of boxes/pallets (drives serial count) |
| Pcs / Unit | Yes | Pieces per box |
| Labels × Each | No | Computed (1 for cartons, 4 for pallets/wooden cases) |
| Total Labels | No | Computed `units × labels_per_unit` |
| Serials | No | Live `#X–Y` range, recalculated on every edit |
| Actions | — | Per-row **Print** button |

### 8.2 Non-Standard Sub-Rows

When a `label_group` is flagged `nonstd_remainder`, it renders as an indented sub-row under its parent, sharing the parent's Print button. The sub-row shows:
- `NON-STD BOX` badge
- Box number (e.g. "box 47 of 47")
- Editable pcs field
- `× 5 copies` annotation

### 8.3 Serial Allocation

Serials are allocated by `boxCountForGroup()`, which returns the number of box serials consumed by a given row:

- `container_type ≠ carton` → 0
- `co_packed_secondary` flag → 0
- `nonstd_remainder` flag → 0 (counted by the parent)
- Normal carton without nonstd sibling → `num_labels`
- Normal carton with nonstd sibling → `nonstd_box_num` (total including remainder)

`refreshSerialRanges()` iterates all rows in display order and accumulates a running offset from the per-slip **Starting Serial #** input to produce the `#X–Y` range shown in the Serials column.

### 8.4 Print Dispatch (`buildPreviewParams`)

When Print is clicked, `buildPreviewParams(g, rowNum, safeFname)` constructs the `preview.php` query string:

| Param | Source |
|---|---|
| `type` | `"box"` (carton) or `"pallet"` |
| `customer` | `g.customer` |
| `na_number` | Shipment ID input |
| `part_number` | `g.base_part` |
| `revision` | `g.revision` |
| `received_date` | Date picker input |
| `std_qty` | Pcs/unit input |
| `copies` | `g.labels_per_unit` |
| `seq_start` | From serial-range span data attribute |
| `total_boxes` | From `boxCountForGroup` (includes nonstd) |
| `nonstd_json` | JSON array `[{box, qty, copies}]` when nonstd sibling exists |

---

## 9. Label Renderer — `preview.php`

`preview.php` accepts the parameters above and renders a `@page`-sized HTML document. Each label is a fixed-dimension `div` containing:

- **Customer name** (Century Gothic Bold, top-left)
- **Part number barcode** (JsBarcode CODE128, centre) + human-readable text below
- **Revision** sub-text (if `revision` param present)
- **NA Number** (Impact font, right column)
- **Box X of Y** counter (right column)
- **Qty / Pcs per box** (right column)
- **Received Date** (bottom-left)
- **Serial tag barcode** (CODE128, bottom-left) encoding `YYDDDSSSS`
- **Lake Forest Industries logo** (bottom-right)

For **non-standard boxes**, the `nonstd_json` parameter is parsed. Each entry `{box, qty, copies}` generates `copies` additional label pages with the non-standard qty and box number, all sharing the same serial number.

### 9.1 Serial Tag Format

```
Digits 1–2 : 2-digit year from received date          (e.g. 26)
Digits 3–5 : Day of year, zero-padded 001–366          (e.g. 124 for May 4)
Digits 6–9 : Sequence number from seq_start, zero-pad  (e.g. 0001)
```

Box N in a batch receives serial `seq_start + (N - 1)`. Non-standard boxes that share a serial get `copies` identical labels.

---

## 10. Roadmap & Known Limitations

### Known Limitations

| Issue | Impact | Notes |
|---|---|---|
| Century Gothic font not bundled | Setup required per machine | Must copy from a licensed Windows installation |
| No persistent serial counter | Operator must set starting serial manually each session | Risk of duplicate serials if wrong number entered |
| No job history / audit log | Cannot reconstruct which labels were printed for a shipment | All data lives in URL params only |
| Browser print variance | Minor layout differences between Chrome and Firefox | Chrome/Edge recommended |
| Local network only | Not hardened for internet | Acceptable for current use case |

### Planned Enhancements

- **Serial counter persistence** — track the daily sequence high-water mark in a flat file or SQLite so operators never set it manually.
- **Print confirmation logging** — write a log entry (timestamp, operator, part, serial range) on each print action.
- **Customer/exception admin UI** — a settings page to add/edit `CUSTOMER_MAP` and `EXCEPTIONS` without editing Python source.
- **LAB2FAB and bare-numeric part expansion** — extend `KNOWN_PARTS` as new numeric-only part numbers are encountered.
- **Automatic `Sheet2` detection** — more robust worksheet selection for suppliers who vary their Excel layout.

---

## 11. Development Guide

### Adding a New Customer

1. Open `parse_packing_slip.py`.
2. Add the customer's part-number prefix to `CUSTOMER_MAP`:
   ```python
   "XYZ": "Customer Display Name",
   ```
3. If the customer uses co-packed inner boxes, add an entry to `EXCEPTIONS`:
   ```python
   "XYZ-1234": {"labels_per_carton": 2, "pcs_per_label": 6},
   ```
4. Re-parse a sample packing slip to verify the output JSON.

### Adding a Bare-Numeric Part

Add an entry to `KNOWN_PARTS`:
```python
"123456789": {"customer": "Customer Name", "base_part": "123456789", "revision": None},
```

### Extending the Revision Regex

The current regex is `-([A-Z]\d+|[A-Z])$`. If a new revision format is encountered (e.g. `-Rev2` or `-001`), update **both** occurrences in `normalise_part` — one in the TBCTW branch and one in the standard branch.

### Deploying an Update

1. Replace the changed files in the server document root.
2. No restart is required — PHP serves files statically per request.
3. Clear browser cache on operator workstations if UI behaviour does not change after update.

### Local Development Setup

```bash
# Install Python dependency
pip3 install openpyxl

# Start PHP built-in server from project root
php -S 0.0.0.0:8080

# Open in browser
open http://localhost:8080/
```

---

## 12. Testing Checklist

Use packing slip `PACKING (PO22642).xlsx` as the reference test file. After any parser change, verify:

| Check | Expected |
|---|---|
| A23968 (3 rows, 50 pcs each) | 3 independent carton rows; serials sequential |
| B22519-40 (5 rows, 50 pcs) | All independent; row 8 has non-std sub-row (box 47 of 47, 50 pcs) |
| CT-103072 (1×300 pcs + 1×80 pcs) | Row 13 shows 2 serials; sub-row "NON-STD BOX box 2 of 2 · 80 pcs" |
| HHD-8643 (1×10 pcs + 1×8 pcs) | Row 25 shows 2 serials; sub-row "NON-STD BOX box 2 of 2 · 8 pcs" |
| ECS-9562-B1 | `part_number=ECS-9562`, `revision=B1` in preview URL |
| ECS-9586-1 | `part_number=ECS-9586-1`, no revision |
| Total label count | 703 box labels |
