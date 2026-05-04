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
| **v1.11** | **2026-05-04** | **Non-standard labels mirrored (barcode right, qty left); non-standard qty 22pt → 29pt; multiple non-standard boxes supported via dynamic form rows and `nonstd_json` parameter** |

---

## Known Limitations

- **Font licensing:** Century Gothic is a proprietary Microsoft font. The TTF files are not included in this repository. You must supply them from a licensed Windows installation.
- **Browser printing:** Print output quality varies slightly by browser. Chrome/Edge are recommended for consistent `@page` sizing.
- **No database:** All data is passed via URL query parameters. There is no job history or saved records.
- **Local network only:** The app is designed for use on a local warehouse network. It is not hardened for public internet exposure.
- **PHP required:** This is not a static site. A PHP runtime is required to serve the pages.
