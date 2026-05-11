# LF Label Generator

**Lake Forest Industries — Warehouse Receiving Label System**

A local PHP web application that generates printable box and pallet labels for inbound shipments received from floor-loaded shipping containers. Labels are designed to be visually identical to the Microsoft Word labels previously used by the team.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Label Types](#label-types)
- [Non-Standard Boxes](#non-standard-boxes)
- [Printing](#printing)
- [Revision History](#revision-history)
- [Known Limitations](#known-limitations)

---

## Requirements

- **PHP 7.4+** (PHP 8.x recommended)
- Any local web server that can serve PHP:
  - [XAMPP](https://www.apachefriends.org/) (Windows/macOS/Linux)
  - [Laravel Herd](https://herd.laravel.com/) (macOS)
  - PHP's built-in server: `php -S 0.0.0.0:8080` from the project root
- A modern web browser (Chrome, Firefox, Edge, Safari)
- **Python 3.8+** with `openpyxl` installed (`pip3 install openpyxl`) — required for packing slip parsing
- Network access from warehouse workstations (if running on a shared machine)

---

## Installation

1. Copy the `lf-labels/` folder to your web server's document root
   (e.g., `C:\xampp\htdocs\lf-labels\` on XAMPP).

2. Ensure the `assets/fonts/` directory contains the following font files:
   - `CenturyGothic.ttf` — Century Gothic Regular
   - `CenturyGothic-Bold.ttf` — Century Gothic Bold

   > **Note:** Century Gothic is a licensed Microsoft font. Copy it from a
   > Windows machine (`C:\Windows\Fonts\GOTHIC.TTF` and `GOTHICB.TTF`)
   > or use the `.ttc` version with appropriate extraction.

3. Ensure `assets/logo.svg` is present (the Lake Forest Industries logo).

4. Start your PHP server and navigate to `http://localhost:8080/lf-labels/`
   (or the appropriate host/port for your setup).

---

## Usage

### Opening the App

Navigate to the app URL in any browser on your network. The main form has two tabs:

- **📦 Box Labels** — for individual boxes received from a container
- **🏗️ Pallet Labels** — for full pallets (single-part or mixed)

### Generating Box Labels

1. Fill in: Customer Name, NA Number, Part Number, Received Date
2. Enter Total Boxes and Standard Qty Per Box
3. Set Label Copies Per Box (typically 1)
4. *(Optional)* Add non-standard box entries (see below)
5. Click **Generate & Preview Labels** — labels open in a new browser tab

### Generating Pallet Labels

1. Fill in the customer, NA number, date, and pallet details
2. For mixed pallets (two part numbers on one pallet), check the mixed pallet checkbox and fill in the second part number section
3. Click **Generate & Preview Labels**

---

## Project Structure

```
lf-labels/
├── index.php               # Main entry form (UI)
├── review.php              # Packing slip upload & review UI (v1.29)
├── parse_slip.php          # PHP → Python parser bridge (v1.29)
├── parse_packing_slip.py   # Python packing slip parser (v1.29)
├── preview.php             # Label renderer (outputs printable HTML)
├── README.md               # This file
└── assets/
    ├── logo.svg            # Lake Forest Industries logo
    └── fonts/
        ├── CenturyGothic.ttf
        └── CenturyGothic-Bold.ttf
```

**Versioned backups** (kept for reference, not served to users):
- `index.v1.10.php`, `preview.v1.10.php`, etc.

---

## Label Types

### Standard Box Label (6" × 4" landscape)

| Field | Description |
|-------|-------------|
| Customer | Customer/company name |
| Part Number | Barcode + human-readable part number |
| NA Number | Internal shipment ID (e.g., NA-222610) |
| Box X of Y | Box number within the shipment |
| Qty | Quantity of parts in the box |
| Received Date | Date parts were received |

### Pallet Label (12" × 4" landscape)

Includes all box label fields plus pallet number, total pallets, total quantity, and boxes-per-pallet detail. Mixed pallets show two part numbers side-by-side with a "MIXED PALLET" stamp.

---

## Non-Standard Boxes

A **non-standard box** is one that holds a different quantity than the rest of the shipment (typically a partial last box).

Non-standard labels are visually distinct from standard labels in two ways:
1. **Mirrored layout** — the barcode column and the qty/shipment-ID column swap sides
2. **Arrow quantity** — the quantity is displayed as `◄ qty ► pcs` in a larger font (29pt)
3. **5 copies printed** by default (making the odd box easy to identify on the pallet)

### Multiple Non-Standard Boxes

The form supports any number of non-standard boxes. A single empty entry row is shown by default. As soon as both the **Box Number** and **Non-Standard Qty** fields of a row are filled in, a new empty row appears automatically below it. This continues indefinitely, allowing you to enter as many non-standard boxes as needed.

---

## Printing

1. After clicking **Generate & Preview**, labels open in a new browser tab
2. Use **Ctrl+P** (Windows/Linux) or **Cmd+P** (macOS) to open the print dialog
3. Set paper size to match the label stock:
   - Box labels: **6" × 4"** landscape
   - Pallet labels: **12" × 4"** landscape
4. Set margins to **None** (or 0)
5. Disable headers/footers in the print dialog

> **Recommended browser for printing:** Google Chrome or Microsoft Edge.
> Both handle `@page` CSS sizing more reliably than Firefox or Safari.

---

## Revision History

| Version | Date | Changes |
|---------|------|---------|
| v1.0 | 2026-05-02 | Initial build: box and pallet label layout, JsBarcode integration, PHP form |
| v1.1 | 2026-05-02 | Page size, landscape orientation, label padding and footer |
| v1.2 | 2026-05-02 | Typography matched to Word reference; Impact/Century Gothic/Arial fonts |
| v1.3 | 2026-05-03 | Local Century Gothic font via `@font-face`; logo height corrected |
| v1.4 | 2026-05-03 | Customer name Century Gothic Bold; NA# Impact font; part# centered |
| v1.5 | 2026-05-03 | Pixel-based font sizes; barcode height increased to 1.25in |
| v1.6 | 2026-05-03 | Pixel-based font sizes further adjusted |
| v1.7 | 2026-05-03 | All font sizes switched to `pt` values matching Word document spec |
| v1.8 | 2026-05-03 | Right column (shipment ID/qty) aligned to barcode top; extra spacing before Qty label |
| v1.9 | 2026-05-03 | Right column shifted right 0.1in; barcode nudge (reverted in v1.10) |
| v1.10 | 2026-05-03 | Barcode position reverted to v1.8 baseline; part number font size 26pt → 30pt |
| v1.11 | 2026-05-04 | Non-standard labels mirrored (barcode right, qty left); non-standard qty 22pt → 29pt; multiple non-standard boxes supported via dynamic form rows and `nonstd_json` parameter |
| v1.12 | 2026-05-04 | Serial tag barcode added to box label footer (format `YYDDDSSSS`); received date line-height tightened; starting sequence # input added to form |
| v1.13 | 2026-05-04 | Sequence number tied to box number (not print order); footer anchored to bottom edge; serial number human-readable text 7pt → 15pt |
| v1.14 | 2026-05-04 | Footer layout fixed: bl-body overflow:hidden prevents content bleeding into footer; footer given fixed height; serial number text centered under barcode |
| v1.15 | 2026-05-04 | Stray closing div removed from footer HTML; reverted v1.14 overflow/height changes; serial text-align:center kept |
| v1.16 | 2026-05-04 | Auto-scaling qty text: inline fitText JS shrinks font-size proportionally until qty fits within right column without overflowing |
| **v1.18** | **2026-05-11** | **Packing slip upload & review UI (`review.php`); PHP→Python parser bridge (`parse_slip.php`); `parse_packing_slip.py` bundled in project root; pallet copies default corrected 5→4; `unit_label` param for wooden case labels; `revision` sub-text below box label barcode** |
| v1.19 | 2026-05-11 | Fixed `parse_packing_slip.py` hardcoded `Sheet2` worksheet name — now falls back to `worksheets[0]` if `Sheet2` does not exist, supporting packing slips with non-standard sheet names |
| **v1.20** | **2026-05-11** | **Fixed infinite recursion in `review.php`: `_origRender` was being assigned after `renderResults` was redeclared, causing `renderResults → _origRender → renderResults → …` stack overflow on every parse. Fixed by capturing original function as `_coreRender` before redeclaration** |
| **v1.21** | **2026-05-11** | **Fixed `parse_slip.php` broken braces caused by editor corruption; added `set_time_limit(120)` and `ini_set('max_execution_time', 120)` to prevent PHP execution timeout on large packing slips** |
| **v1.22** | **2026-05-11** | **Fixed click event bubbling on drop-zone: moved file input outside drop-zone div, replaced inline `onclick` with JS `addEventListener` + `stopPropagation()`; switched fetch to `r.text()` + safe `JSON.parse()` for clearer error diagnostics** |
| **v1.23** | **2026-05-11** | **Replaced `fetch()` with `XMLHttpRequest` to work around a Chromium/Edge + XAMPP localhost bug where `fetch()` promise rejects with "Maximum call stack size exceeded" even when the server returns a valid response** |
| **v1.24** | **2026-05-11** | **Added `try/catch` around `renderResults()` call to surface silent JS errors; added `xhr.onloadend` fallback to guarantee spinner always hides; added `xhr.ontimeout` handler with 90s timeout** |
| **v1.25** | **2026-05-11** | **Fixed root cause of "Maximum call stack size exceeded": renamed original render function to `renderResultsCore` and data-store function to `renderResultsData`; replaced fragile `const _coreRender` interception pattern with a single clean `renderResults` entry point that calls both directly** |
| **v1.26** | **2026-05-11** | **Label generation improvements: (1) barcode now encodes `base_part` (strips customer prefix + revision, e.g. `ENC-1726` not `TBC-ENC-1726-E`); (2) Rev. text below barcode increased from 9pt → 14pt; (3) sequence starts at 0001 for first entry and increments continuously across all groups in Print All; (4) `t013_prepped` flag now renders "Prepped for T013" beside revision on the label** |
| **v1.27** | **2026-05-11** | **Non-std box fixes: parser now detects remainder rows (same base_part, fewer boxes) and flags them `nonstd_remainder` with `nonstd_box_num`; review.php folds them into parent print job via `nonstd_json`; total_boxes now includes remainder count; nonstd rows use arrow-style template in preview.php. T013 label: changed to black, wrapped in parentheses. T013 badge: grey/black for B&W printing.** |
| **v1.28** | **2026-05-11** | **Non-std box grouping overhaul: nonstd_remainder rows now render as a sub-row under their parent in review.php (shared single Print button). buildPreviewParams generates correct nonstd_json array format `[{box,qty,copies}]` matching preview.php parser. Total boxes column reflects merged std+nonstd count. Removed Print All button. printRow passes fname context to buildPreviewParams for sibling lookup.** |
| **v1.29** | **2026-05-11** | **review.php: (1) Shipment ID auto-populated from PO number (PO22643→NA-22643); label renamed from "NA Number (Shipment ID)" to "Shipment ID". (2) Part number column shows base_part (e.g. ENC-1726 not TBC-ENC-1726-E). (3) Rows sorted by customer asc then base_part asc; nonstd siblings stay attached; slipData updated to match sorted order.** |
| v1.17 | **2026-05-04** | **Fixed JS syntax error in v1.16: barcode IIFE and fitText IIFE were merged incorrectly, dropping the closing `})()` on the barcode block and killing all JsBarcode calls** |

---

## Serial Tag Format

Each box label now includes a 9-digit serial barcode in the bottom-left, below the received date.

| Digits | Content | Example |
|--------|---------|--------|
| 1–2 | 2-digit year from received date | `26` |
| 3–5 | Day of year (1–366), zero-padded | `124` (May 4) |
| 6–9 | Sequence number, zero-padded | `0001` |

**Example:** A label for the 1st box received on May 4, 2026 → `261240001`

The **Starting Sequence #** field on the form sets digits 6–9 for the first box in the batch. Each subsequent unique box number increments the sequence by 1. All copies of the same box share the same serial number.

> **Future:** A logging system will track the daily sequence counter automatically so users no longer need to enter a starting number manually.

---

## Known Limitations

- **Font licensing:** Century Gothic is a proprietary Microsoft font. The TTF files are not included in this repository. You must supply them from a licensed Windows installation.
- **Browser printing:** Print output quality varies slightly by browser. Chrome/Edge are recommended for consistent `@page` sizing.
- **No database:** All data is passed via URL query parameters. There is no job history or saved records.
- **Local network only:** The app is designed for use on a local warehouse network. It is not hardened for public internet exposure.
- **PHP required:** This is not a static site. A PHP runtime is required to serve the pages.
