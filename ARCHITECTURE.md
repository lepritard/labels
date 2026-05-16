# LF Label Generator — Architecture Reference

This document describes the runtime data flow, key data structures, and design decisions for developers who need to understand the system at a deeper level. It complements the PRD (product requirements) and CONTRIBUTING guide (how to make changes).

---

## Request Lifecycle

### Manual Label (index.php → preview.php)

```
1. Operator fills index.php form
2. Form submits GET to preview.php with all params in the URL
3. preview.php reads $_GET, renders label HTML, returns to browser
4. Browser auto-triggers window.print()
```

No server state is written. The URL itself is the complete job descriptor and can be bookmarked, shared, or reprinted at any time.

### Packing Slip (review.php → parse_slip.php → Python → review.php → preview.php)

```
1.  Operator drops .xlsx onto review.php upload zone
2.  review.php JS sends FormData POST to parse_slip.php (XMLHttpRequest)
3.  parse_slip.php:
      a. Validates file extension
      b. move_uploaded_file() to sys_get_temp_dir()
      c. exec("python3 parse_packing_slip.py <path>", $out, $rc)
      d. Echoes implode("\n", $out) as application/json
      e. Deletes temp file
4.  review.php receives JSON string, calls JSON.parse()
5.  renderResultsCore() builds review table; slipData[fname] = parsed object
6.  Operator edits fields; refreshSerialRanges() runs on every change
7.  Operator clicks Print → buildPreviewParams() → window.open(url)
8.  preview.php renders label page (same as manual path)
```

### Why XMLHttpRequest instead of fetch()?

A Chromium/XAMPP localhost interaction caused `fetch()` to reject with "Maximum call stack size exceeded" despite a valid server response. `XMLHttpRequest` bypasses this bug. See v1.23 changelog entry.

---

## Key Data Structures

### `slipData` (review.php, client-side)

In-memory store keyed by filename. Populated once after parse; mutated only by `renderResultsData()`.

```javascript
slipData = {
  "PACKING (PO22642).xlsx": {
    meta: { po, date, container_no, seal },
    label_groups: [ /* array of group objects, sorted */ ]
  }
}
```

### Label Group Object

```javascript
{
  customer:        "TurboChef",
  part_number:     "TBC-ENC-1726-E",
  base_part:       "ENC-1726",       // used in barcode + EXCEPTIONS lookup
  revision:        "E",              // null if absent
  pcs_per_box:     4,
  num_labels:      41,               // number of physical boxes
  container_type:  "carton",
  labels_per_unit: 1,                // 1=carton, 4=pallet/wooden_case
  total_labels:    41,
  flags:           [],               // ["nonstd_remainder", "t013_prepped", ...]
  pallet_group:    null,             // "PALLET 1" or null
  nonstd_box_num:  null,             // set by parser for nonstd rows
  nonstd_copies:   null,             // always 5 when set
  total_boxes:     null              // set on parent when nonstd sibling exists
}
```

### `preview.php` URL Parameters

```
type          box | pallet | wooden_case
customer      Customer display name
na_number     Shipment ID (e.g. NA-22642)
part_number   base_part (no customer prefix, no revision)
revision      Optional revision string (e.g. E, B1)
received_date YYYY-MM-DD
std_qty       Pieces per standard box
copies        Labels per physical unit (1 for cartons, 4 for pallets)
seq_start     Starting serial number for this batch
total_boxes   Total box count including nonstd
nonstd_json   JSON array: [{"box":"47","qty":"50","copies":"5"}]
```

---

## Non-Standard Box Model

A "non-standard box" (nonstd) is the final box in a shipment that holds a different quantity than the standard boxes. There are two kinds:

| Kind | Detection | Example |
|---|---|---|
| **Remainder** | `cur.num_labels < prev.num_labels` | 4 full boxes of 40 + 1 box of 22 |
| **Qty-variant** | `cur.pcs_per_box ≠ prev.pcs_per_box` | 1 box of 300 pcs + 1 box of 80 pcs |

Both are flagged `nonstd_remainder` by the parser and rendered as sub-rows in `review.php`. Both use `nonstd_copies=5` (five identical labels with the same serial). The distinction between the two kinds was intentionally collapsed at the parser level (v1.36) so that `review.php` only needs one code path.

### Why 5 copies?

The non-standard box label must be physically applied to the box and also to the pallet, the packing list, and potentially the receiving paperwork. Five copies covers all use cases without requiring the operator to print twice.

---

## Serial Number Design

Serials are allocated in `review.php` entirely client-side. The allocation order matches the visual order of the review table (customer asc → base_part asc within each slip). The algorithm:

```
running = startingSerial (from per-slip input)
for each row in display order:
    n = boxCountForGroup(row)
    if n > 0:
        assign range [running, running + n - 1] to row
        running += n
```

`boxCountForGroup` ensures:
- `nonstd_remainder` rows consume 0 serials (their count is absorbed by the parent via `nonstd_box_num`)
- Pallet/wooden-case rows consume 0 serials (they don't get serial tag barcodes)
- Co-packed secondaries consume 0 serials (inner-box labels share the outer box serial)

The `seq_start` value passed to `preview.php` is the start of the range for that specific row, so `preview.php` can independently compute each box's serial without any shared state.

---

## CSS Print Architecture

Labels are rendered in `preview.php` using `@page` rules:

```css
@page { size: 6in 4in landscape; margin: 0; }
.label { width: 6in; height: 4in; position: relative; page-break-after: always; }
```

All internal positioning uses absolute pixel or inch values derived from the original Word template measurements. Font sizes are in `pt` to match Word output exactly.

JsBarcode is loaded from CDN and invoked once per label via a per-element IIFE after the DOM is ready. The serial barcode uses the same CODE128 symbology as the part number barcode.

---

## Design Decisions Log

| Decision | Rationale |
|---|---|
| Python for parsing, not PHP | `openpyxl` is significantly more capable than PHP's xlsx libraries for reading complex Excel files. The exec() bridge adds one process fork per upload — acceptable for a warehouse tool used a few times per shift. |
| XMLHttpRequest over fetch() | XAMPP + Chromium localhost bug; see v1.23. |
| No database | Simplicity and zero-setup deployment. Serial tracking is the one area where persistence would add real value (see Roadmap). |
| All label params in URL | Makes every print job fully reproducible from the URL alone. The downside is URL length — not an issue for local use. |
| `nonstd_remainder` flag on second row, not first | The parent row drives the Print button and serial allocation. Making the second row the flagged sub-row keeps the parent's `num_labels` correct and avoids touching the first row's data. |
| `copies=5` hardcoded for nonstd | Warehouse process requirement. Sufficient for all current use cases. If a customer needs a different count it can be exposed as an editable field in the review UI. |
