# Contributing to LF Label Generator

This guide covers everything a new developer needs to understand, modify, and extend the project. Read `PRD.md` first for the full product context; this document focuses on the code.

---

## Project Philosophy

- **No framework, no build step.** Everything is plain PHP, Python, and vanilla JavaScript. A new contributor with basic web skills should be able to read any file without a toolchain.
- **Parser is the source of truth.** The PHP/JS UI trusts the JSON that `parse_packing_slip.py` emits. If label data is wrong, fix the parser first.
- **Version every change.** Bump the version string in all five files and add a row to `CHANGELOG.md` (newest entry at the top). The version badge in the UI footer (bottom-right of every page) must match.

---

## Code Map

### `parse_packing_slip.py`

The most important file. Key functions:

| Function | What it does |
|---|---|
| `normalise_part(raw)` | Splits a raw part number into `(display, customer, base_part, revision)` |
| `detect_container_type(col_a)` | Returns `"carton"`, `"pallet"`, or `"wooden_case"` from the row's Column A text |
| `parse_packing_slip(filepath)` | Orchestrates the full parse: reads Excel → builds raw groups → merges → detects nonstd → returns JSON dict |

Configuration tables at the top of the file (`CUSTOMER_MAP`, `EXCEPTIONS`, `KNOWN_PARTS`, `LABELS_PER_UNIT`) are the first place to look when a new customer or part type needs to be supported.

### `review.php`

A single-file PHP+JS application. Key JavaScript functions:

| Function | What it does |
|---|---|
| `parseSlips()` | Submits uploaded files to `parse_slip.php`, stores results in `slipData` |
| `renderResultsCore(data)` | Builds the review table from parsed JSON |
| `buildRow(g, rowNum, fname)` | Renders one table row (and its optional nonstd sub-row) |
| `buildPreviewParams(g, rowNum, safeFname)` | Constructs the `preview.php` query string for a given row |
| `boxCountForGroup(g, allGrps, safeFname, rowNum)` | Returns how many box serials this row consumes |
| `refreshSerialRanges(safeFname)` | Recalculates all `#X–Y` serial range spans after any edit |
| `printRow(btn, rowNum, safeFname)` | Opens the preview URL in a new tab |

### `preview.php`

Pure renderer. Reads URL query parameters and outputs a `@page`-sized HTML document. No state, no database. The label layout is built with CSS `position: absolute` inside fixed-dimension `div` elements.

### `parse_slip.php`

Thin PHP bridge. Validates the upload, writes the file to a temp path, shells out to Python, and returns stdout as JSON. The only interesting parts are the timeout settings (`set_time_limit`) and the error handling around the `exec()` call.

### `index.php`

The manual entry form. Tab switching is handled in vanilla JS. On submit it builds a `preview.php` URL and redirects. No server-side processing beyond the initial page render.

---

## Versioning Convention

Every PR that changes behaviour must:

1. Increment the patch version across all five files:
   - `// review.php v1.XX`
   - `// index.php v1.XX`
   - `// parse_slip.php — server-side packing slip parser for v1.XX`
   - `// preview.php v1.XX`
   - `"""parse_packing_slip.py — Evolved packing slip parser (v2, v1.XX)"""`
2. Update the footer string `LF Label Generator&nbsp;v1.XX` in `index.php` and `review.php`.
3. Add a new row at the **top** of the changelog table in `CHANGELOG.md` (the Revision History table was moved out of `README.md` in v1.40).

Use the existing entries in `CHANGELOG.md` as style references.

---

## Common Extension Scenarios

### New customer prefix

```python
# parse_packing_slip.py → CUSTOMER_MAP
"XYZ": "Full Customer Name",
```

### New co-packed part

```python
# parse_packing_slip.py → EXCEPTIONS
"XYZ-1234": {"labels_per_carton": 2, "pcs_per_label": 6},
```
The key is `base_part` **without** revision letter.

### New bare-numeric part

```python
# parse_packing_slip.py → KNOWN_PARTS
"987654321": {"customer": "Customer Name", "base_part": "987654321", "revision": None},
```

### New revision format

The regex `-([A-Z]\d+|[A-Z])$` covers `A`, `B1`, `C12`. If a new format appears (e.g. `-Rev2`), update **both** occurrences in `normalise_part` — the TBCTW branch (around line 222) and the standard branch (around line 233). Add a test case to the smoke-test block at the end of `normalise_part` verification.

---

## Debugging Tips

**Parser output doesn't look right?**  
Run the parser directly and inspect the JSON:
```bash
python3 parse_packing_slip.py "PACKING (PO22642).xlsx" | python3 -m json.tool | less
```

**Serial numbers are wrong in the review table?**  
Open the browser console and add a `console.log` inside `refreshSerialRanges`. Check that `boxCountForGroup` returns the expected value for the problem row.

**`nonstd_remainder` not being detected?**  
The detection loop requires consecutive rows in `label_groups` after merging. Check whether the part has multiple rows with **identical** `(base_part, container_type, pcs_per_box)` keys — those would have been merged into one row before the nonstd scan runs, so no consecutive pair exists to compare.

**XHR returns garbled JSON?**  
Check `parse_slip.php` for PHP warnings printed before the JSON output. Any stray `echo` or warning text before the JSON will corrupt the response. Set `error_reporting(0)` at the top of `parse_slip.php` during debugging, or use `ob_start()` / `ob_end_clean()` to suppress accidental output.

**Labels print at wrong size?**  
Confirm the browser's print dialog has:
- Paper size: **Custom — 6×4 in** (box) or **12×4 in** (pallet)
- Margins: **None**
- Headers/Footers: **Off**
- Scale: **100% / Default**

Chrome and Edge are reliable. Firefox and Safari may require additional `@page` CSS adjustments.

---

## File Checklist for a New Release

- [ ] All five version strings bumped
- [ ] Both footer strings bumped (`index.php`, `review.php`)
- [ ] `CHANGELOG.md` row added (newest entry at top)
- [ ] `PACKING (PO22642).xlsx` re-parsed; 703 total labels confirmed
- [ ] CT-103072 and HHD-8643 sub-rows present with correct serial ranges
- [ ] ECS-9562-B1 preview URL contains `revision=B1`
- [ ] No `console.error` output in the browser dev tools during a normal parse+print flow
