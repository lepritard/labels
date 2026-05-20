# Changelog — LF Label Generator

## [1.49.3] – 2026-05-19

### Fixed
- `preview.php` — logo completely missing from rendered output. During the v1.48→v1.49 revert, the logo path was incorrectly changed from `assets/logo.svg` to `assets/logo.png` (the file on the server is an SVG), and the MIME type was set to `image/png` instead of `image/svg+xml`. Both errors meant `file_exists()` returned false and `$logo_img` was always `''`. Fixed: path restored to `assets/logo.svg`, MIME type corrected to `image/svg+xml`.
- `index.php`, `review.php` — version footer strings updated to `v1.49.3`.


## [1.49.2] – 2026-05-19

### Fixed
- `preview.php` — Received Date was printed on every label even when Starting Sequence # was `0` (serial tags disabled). Root cause: `$received_date` defaulted to `date('Y-m-d')` (today) regardless of `seq_start`, and the `Received:` line was rendered outside the `$omit_serial` guard.
  - `$received_date` is now set to `''` when `$omit_serial` is true; the today-fallback only applies when serial tags are active
  - Both the box-label and pallet-label `Received:` lines are now wrapped in guards — box labels use `!$omit_serial && $received_date !== ''`, pallet labels use `$received_date !== ''`
- `index.php`, `review.php` — version footer strings updated to `v1.49.2`


## [1.49.1] – 2026-05-19

### Fixed
- `known_parts.json` — trailing comma after the last entry caused a JSON parse error; PHP's `json_decode()` returned `null`, so `KP` was embedded as an empty object `{}` in the page and all autocomplete was silently non-functional. Comma removed; file now passes strict JSON validation.
- `index.php`, `preview.php`, `review.php` — version footer strings still read `v1.48`; updated to `v1.49`.


## [1.49] – 2026-05-19

### Added
- `index.php` — Customer / Part Number **autocomplete cascade** powered by `known_parts.json`
  - `known_parts.json` lives in the same directory as `index.php`; PHP loads it at page load via `file_get_contents(__DIR__ . '/known_parts.json')` and embeds it as inline JS constant `KP` — zero AJAX, no extra round-trips
  - Customer field gets a `<datalist>` of all known customers; typing narrows the Part Number datalist to that customer's parts only
  - Selecting/typing a Part Number reverse-populates Customer when the part is found in `KP`
  - Autofilled fields receive a subtle green border so the operator can see what was populated automatically
  - Graceful degradation: if `known_parts.json` is missing or malformed, all fields fall back to plain text inputs with no errors
- `index.php` — **Qty smart-fill / dropdown** driven by `known_parts.json`
  - 1 known `std_qty` → Qty field pre-fills and stays editable
  - 2+ known `std_qtys` → Qty field becomes a `<select>` with all known values plus an *Other…* option that swaps back to a free-type input
  - Entering a qty not in `std_qtys` triggers a yellow inline warning banner with **Yes — add as NON-STD box**, **No — use as standard**, and **Dismiss** options
- `index.php` — Shipment ID field is now **optional** (`required` removed)
- `index.php` — Received Date now **hidden by default**; animates into view only when Starting Sequence # ≥ 1
- `index.php` — Starting Sequence # **defaults to `0`**; hint explains that entering ≥ 1 enables serial tags and Received Date
- `known_parts.json` — **now included in the release zip** (must live in the same directory as `index.php`)
- `build_known_parts.py` — new helper script to rebuild `known_parts.json` from historical packing-slip JSON exports
- `README.md` — new **Prerequisites / Environment Setup** section: Python on Windows 11, macOS, Linux; `shell_exec()` PATH caveats; required pip packages
- `README.md` — new **Known Parts Autocomplete** section and updated Project Structure table
- `CONTRIBUTING.md` — new **Local Dev Environment** section with Python / `parse_slip.php` bridge debugging tips
- `CONTRIBUTING.md` — Code Map updated; `preview.php` logo note warns against `<symbol>`/`<use>` revert
- `ARCHITECTURE.md` — Design Decisions Log: Python/openpyxl rationale, `shell_exec()` sensitivity note, logo embedding history

### Changed
- `preview.php` — **Reverts v1.48 SVG optimisation** back to per-label base64 `<img>` (v1.47 behaviour)
  - v1.48 used `<symbol>` + `<use>` to reduce HTML size; Chrome's PDF renderer does not resolve `<symbol>`/`<use>` references → logos were blank in all Chrome-produced PDFs
  - Base64 `<img>` is slightly larger HTML but renders correctly in every environment tested


All notable changes to this project are documented here in reverse-chronological order.

---

| Version | Date | Changes |
|---------|------|---------|
| **v1.48** | **2026-05-19** | **`preview.php`: logo now embedded once per page using SVG `<symbol>` + `<use>` instead of a repeated base64 `data:` URI on every label. PHP reads `assets/logo.svg`, extracts the `viewBox`, strips the outer `<svg>` tags, wraps the inner content in a hidden `<symbol id="lf-logo">` block, and injects it once after `<body>`. Every label references it with `<svg><use href="#lf-logo"/></svg>` (~50 bytes) instead of the full base64 string (~4 KB+) repeated per label. No changes to any other file; no functional changes to label output.** |
| **v1.47** | **2026-05-18** | **Bugfix for v1.46 nonstd_remainders values. (1) `parse_packing_slip.py`: in the inverted co-pack reconciliation, `nonstd_box_num` was incorrectly set to the primary's count (170) instead of the secondary's final total (339). Fixed by moving the `nonstd_remainders` append to after the secondary total is updated. (2) `nonstd_copies` was set to `n_inv` (number of inverted boxes, typically 1) instead of the correct fixed value of 5. (3) `review.php`: main row for `has_nonstd_remainder` groups now shows a `+NON-STD` badge so the non-standard box is visible without expanding the sub-row.** |
| **v1.46** | **2026-05-18** | **`review.php`: rendering support for `has_nonstd_remainder` / `nonstd_remainders[]` on co-packed secondary groups. `renderRow` now generates NON-STD sub-rows from the inline `nonstd_remainders` array (rendered *before* the standard row so NON-STD labels print first). `boxCountForGroup` updated to return `total_labels` for `has_nonstd_remainder` groups (which already includes nonstd boxes). `buildPreviewParams` extended to build `nonstd_json` from `nonstd_remainders[]` when no legacy sibling row exists. No changes to `parse_packing_slip.py`, `parse_slip.php`, or `preview.php`.** |
| **v1.45** | **2026-05-18** | **Parser: three fixes. (1) Inverted co-pack reconciliation (post-process step 0): when the supplier lists a remainder co-packed carton with part order reversed, the parser detects the symmetric pair by index-bounded scan and merges the remainder into the original primary's count, attaching a `nonstd_remainders` entry on the secondary for any differing pcs quantity. (2) col A lookback for secondary pcs: `flush_secondary` now searches the primary row's col A description text for the secondary part number's pcs when the EXCEPTIONS table has no entry, so quantities encoded in merged cells are captured directly. (3) CUSTOMER_MAP corrections: `IB` -> `DAWGS`, `NGC` -> `TurboChef`, `HCW` -> `TurboChef`. PHP files and README version strings bumped; no logic changes to PHP.** |
| **v1.44** | **2026-05-17** | **Hotfix: `FRE` prefix now preserved in `base_part` (barcode value). The prefix-stripping logic in `normalise_part` previously stripped any known customer prefix, so `FRE-1160` barcoded as `1160`. Prefixes in `OMIT_CUSTOMER_PREFIXES` are now excluded from stripping — the prefix is part of the part identity for these parts, not a customer code. No PHP changes.** |
| **v1.43** | **2026-05-17** | **Hotfix for v1.42 regression. `normalise_part` now returns `omit_customer` as a 5th return value (boolean) instead of evaluating `OMIT_CUSTOMER_PREFIXES` at the call site, where `prefix` was out of scope. Both call sites (primary row and secondary/co-packed row) updated to unpack 5 values. Secondary groups now also correctly inherit `omit_customer`. No changes to `review.php`, `preview.php`, or any PHP file.** |
| **v1.42** | **2026-05-17** | **Two features. (1) `FRE` prefix reclassified: parser now maps `FRE` → `TurboChef` (was `Frymaster`) and emits `omit_customer: true` for all FRE groups. `review.php` passes `omit_customer=1` to `preview.php`, which suppresses the `Customer:` line on both box and pallet labels. Future prefixes needing the same treatment can be added to `OMIT_CUSTOMER_PREFIXES` in `parse_packing_slip.py`. (2) `preview.php` page title changed from `Labels — [CUSTOMER]` to `PART_NUMBER (N box/boxes)` for unique, PDF-friendly tab/file names.** |
| **v1.41** | **2026-05-16** | **Fix: `parse_packing_slip.py` now handles two new part-number formats. (1) Trailing ` REV<N>` / `-REV<N>` suffix (e.g. `TBC-CT-104546 REV2`) is pre-stripped and captured as the revision before any other parsing, covering variants like `-rev1`, ` rev 2`, `-REV-A`. (2) T-series tooling suffix (e.g. `TBC-ECO-9447-B-T013`) is reordered so the T-designator appends to the base part (`ECO-9447-T013`) and the preceding letter becomes the revision (`B`). No PHP or JS changes.** |
| **v1.40** | **2026-05-16** | **Fix: quantity unit on box and pallet labels now correctly reads `1 pc` (singular) instead of `1 pcs`. Applies to standard, non-standard, and pallet label qty displays. Changelog extracted from README into `CHANGELOG.md`.** |
| v1.17 | **2026-05-04** | **Fixed JS syntax error in v1.16: barcode IIFE and fitText IIFE were merged incorrectly, dropping the closing `})()` on the barcode block and killing all JsBarcode calls** |
| **v1.37** | **2026-05-16** | **`parse_packing_slip.py`: extended revision regex in `normalise_part` from `-([A-Z])$` to `-([A-Z]\d+|[A-Z])$` so that alphanumeric suffixes like `-B1`, `-C2` are correctly split into `base_part` + `revision` (e.g. `ECS-9562-B1` → `part_number=ECS-9562&revision=B1`). No PHP changes.** |
| **v1.38** | **2026-05-16** | **Fix: co-packed secondary rows (ENC-1725, ENC-1833) now inherit the parent row's serial instead of hard-coding #0001. Feature: passing `seq_start=0` (blank starting serial) suppresses the serial tag and barcode from the printed label in `preview.php`.** |
| **v1.39** | **2026-05-16** | **Fix: co-packed secondary part numbers (e.g. ENC-1725, ENC-1833) are now treated as independent carton rows with their own sequential serial range. Parser (`parse_packing_slip.py`) now emits `co_packed_parent` field on secondary groups. `review.php` sort attaches each secondary immediately after its parent; `boxCountForGroup` counts secondaries' `num_labels` like any carton row; `printRow` walk-back removed.** |
| **v1.36** | **2026-05-16** | **`parse_packing_slip.py`: expanded `nonstd_remainder` detection to trigger on `pcs_per_box` difference as well as `num_labels` difference. CT-103072 / HHD-8643 style qty-variants (same num_labels, different pcs) now correctly emit `nonstd_remainder` flag with `nonstd_box_num` and `nonstd_copies=5`. `review.php` reverted to clean v1.33 JS — no JS changes required.** |
| **v1.35** | 2026-05-16 | `review.php`: attempted JS-only qty-variant merge fix. Introduced dead-code bug in `boxCountForGroup` (ns always null for qty-variants). Superseded by v1.36. |
| **v1.34** | 2026-05-16 | *(skipped — superseded immediately by v1.35)* |
| **v1.33** | **2026-05-16** | **index.php + review.php: fixed-position version badge (bottom-right corner) showing `LF Label Generator v1.33 · <page>`. CSS `.version-footer` class added. preview.php intentionally excluded — any footer element would render as an extra printed label. No functional changes.** |
| **v1.32** | **2026-05-11** | **review.php: per-slip Starting Serial # input (default 1). Serials column shows live #X–Y range per carton row. Co-packed secondaries, nonstd sub-rows, pallets, wooden cases count 0 box serials. printRow reads seq_start from serial-range span. recalcRow triggers a full serial-range refresh. Fixed escaped-backtick JS syntax error that broke upload/drop zone.** |
| **v1.31** | **2026-05-11** | **(1) review.php now shows the same app header + 3-tab bar as index.php, with "Upload Packing Slip" highlighted as the active tab; index.php tab links use #hash anchors so back-navigation restores the correct tab. (2) parse_packing_slip.py merges non-consecutive rows for the same part/type/pcs into one entry before nonstd detection; pallets, wooden cases, and co-packed secondaries are never auto-merged.** |
| **v1.30** | **2026-05-11** | **4 fixes: (1) Shipment ID regex unescaped (\\d→\d) so auto-populate now works. (2) parse_packing_slip.py strips customer prefix from base_part for ALL prefixed parts (APS-B22519-34→B22519-34, TAY-056649→056649, etc.), not just TBC. (3) Received Date picker added to review.php slip header row (local date, editable). (4) buildPreviewParams reads received_date from that picker instead of JS UTC clock.** |
| **v1.29** | **2026-05-11** | **review.php: (1) Shipment ID auto-populated from PO number (PO22643→NA-22643); label renamed from "NA Number (Shipment ID)" to "Shipment ID". (2) Part number column shows base_part (e.g. ENC-1726 not TBC-ENC-1726-E). (3) Rows sorted by customer asc then base_part asc; nonstd siblings stay attached; slipData updated to match sorted order.** |
| **v1.28** | **2026-05-11** | **Non-std box grouping overhaul: nonstd_remainder rows now render as a sub-row under their parent in review.php (shared single Print button). buildPreviewParams generates correct nonstd_json array format `[{box,qty,copies}]` matching preview.php parser. Total boxes column reflects merged std+nonstd count. Removed Print All button. printRow passes fname context to buildPreviewParams for sibling lookup.** |
| **v1.27** | **2026-05-11** | **Non-std box fixes: parser now detects remainder rows (same base_part, fewer boxes) and flags them `nonstd_remainder` with `nonstd_box_num`; review.php folds them into parent print job via `nonstd_json`; total_boxes now includes remainder count; nonstd rows use arrow-style template in preview.php. T013 label: changed to black, wrapped in parentheses. T013 badge: grey/black for B&W printing.** |
| **v1.26** | **2026-05-11** | **Label generation improvements: (1) barcode now encodes `base_part` (strips customer prefix + revision, e.g. `ENC-1726` not `TBC-ENC-1726-E`); (2) Rev. text below barcode increased from 9pt → 14pt; (3) sequence starts at 0001 for first entry and increments continuously across all groups in Print All; (4) `t013_prepped` flag now renders "Prepped for T013" beside revision on the label** |
| **v1.25** | **2026-05-11** | **Fixed root cause of "Maximum call stack size exceeded": renamed original render function to `renderResultsCore` and data-store function to `renderResultsData`; replaced fragile `const _coreRender` interception pattern with a single clean `renderResults` entry point that calls both directly** |
| **v1.24** | **2026-05-11** | **Added `try/catch` around `renderResults()` call to surface silent JS errors; added `xhr.onloadend` fallback to guarantee spinner always hides; added `xhr.ontimeout` handler with 90s timeout** |
| **v1.23** | **2026-05-11** | **Replaced `fetch()` with `XMLHttpRequest` to work around a Chromium/Edge + XAMPP localhost bug where `fetch()` promise rejects with "Maximum call stack size exceeded" even when the server returns a valid response** |
| **v1.22** | **2026-05-11** | **Fixed click event bubbling on drop-zone: moved file input outside drop-zone div, replaced inline `onclick` with JS `addEventListener` + `stopPropagation()`; switched fetch to `r.text()` + safe `JSON.parse()` for clearer error diagnostics** |
| **v1.21** | **2026-05-11** | **Fixed `parse_slip.php` broken braces caused by editor corruption; added `set_time_limit(120)` and `ini_set('max_execution_time', 120)` to prevent PHP execution timeout on large packing slips** |
| **v1.20** | **2026-05-11** | **Fixed infinite recursion in `review.php`: `_origRender` was being assigned after `renderResults` was redeclared, causing `renderResults → _origRender → renderResults → …` stack overflow on every parse. Fixed by capturing original function as `_coreRender` before redeclaration** |
| v1.19 | 2026-05-11 | Fixed `parse_packing_slip.py` hardcoded `Sheet2` worksheet name — now falls back to `worksheets[0]` if `Sheet2` does not exist, supporting packing slips with non-standard sheet names |
| **v1.18** | **2026-05-11** | **Packing slip upload & review UI (`review.php`); PHP→Python parser bridge (`parse_slip.php`); `parse_packing_slip.py` bundled in project root; pallet copies default corrected 5→4; `unit_label` param for wooden case labels; `revision` sub-text below box label barcode** |
| v1.16 | 2026-05-04 | Auto-scaling qty text: inline fitText JS shrinks font-size proportionally until qty fits within right column without overflowing |
| v1.15 | 2026-05-04 | Stray closing div removed from footer HTML; reverted v1.14 overflow/height changes; serial text-align:center kept |
| v1.14 | 2026-05-04 | Footer layout fixed: bl-body overflow:hidden prevents content bleeding into footer; footer given fixed height; serial number text centered under barcode |
| v1.13 | 2026-05-04 | Sequence number tied to box number (not print order); footer anchored to bottom edge; serial number human-readable text 7pt → 15pt |
| v1.12 | 2026-05-04 | Serial tag barcode added to box label footer (format `YYDDDSSSS`); received date line-height tightened; starting sequence # input added to form |
| v1.11 | 2026-05-04 | Non-standard labels mirrored (barcode right, qty left); non-standard qty 22pt → 29pt; multiple non-standard boxes supported via dynamic form rows and `nonstd_json` parameter |
| v1.10 | 2026-05-03 | Barcode position reverted to v1.8 baseline; part number font size 26pt → 30pt |
| v1.9 | 2026-05-03 | Right column shifted right 0.1in; barcode nudge (reverted in v1.10) |
| v1.8 | 2026-05-03 | Right column (shipment ID/qty) aligned to barcode top; extra spacing before Qty label |
| v1.7 | 2026-05-03 | All font sizes switched to `pt` values matching Word document spec |
| v1.6 | 2026-05-03 | Pixel-based font sizes further adjusted |
| v1.5 | 2026-05-03 | Pixel-based font sizes; barcode height increased to 1.25in |
| v1.4 | 2026-05-03 | Customer name Century Gothic Bold; NA# Impact font; part# centered |
| v1.3 | 2026-05-03 | Local Century Gothic font via `@font-face`; logo height corrected |
| v1.2 | 2026-05-02 | Typography matched to Word reference; Impact/Century Gothic/Arial fonts |
| v1.1 | 2026-05-02 | Page size, landscape orientation, label padding and footer |
| v1.0 | 2026-05-02 | Initial build: box and pallet label layout, JsBarcode integration, PHP form |
