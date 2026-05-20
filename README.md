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


## Prerequisites / Environment Setup

### Python

`parse_packing_slip.py` is called by PHP via `shell_exec()`. Python must be installed and reachable by the web server process.

**Required pip packages:**
```
pip install openpyxl
```

#### Windows 11

- Install Python 3.10+ from [python.org](https://www.python.org/downloads/) — **not** the Microsoft Store stub (the Store version does not work reliably with `shell_exec()`).
- During installation, check **"Add Python to PATH"**.
- If your server runs under Apache/XAMPP, the PATH available to PHP's subprocess may differ from your terminal PATH. Hardcode the Python path in `parse_slip.php` if needed:
  ```php
  $python = 'C:\\Python311\\python.exe';
  ```

#### macOS

- `python` may point to Python 2 or a no-op stub. Use `python3`.
- Homebrew Python is typically at `/opt/homebrew/bin/python3`; system Python at `/usr/local/bin/python3`.
- Web server processes (Apache, nginx) may not inherit your terminal PATH. Hardcode if `shell_exec()` returns empty output:
  ```php
  $python = '/opt/homebrew/bin/python3';
  ```

#### Linux

- `python3` is usually `/usr/bin/python3`. Confirm with `which python3`.
- The web server user (`www-data` or equivalent) must have read access to the script.

---

## Known Parts Autocomplete

`known_parts.json` must live in the **same directory as `index.php`** on your server. PHP loads it at page load:

```php
$known_parts = json_decode(file_get_contents(__DIR__ . '/known_parts.json'), true) ?? [];
```

The decoded array is embedded as an inline JS constant (`KP`) — autocomplete works with zero AJAX requests.

**Customer / Part cascade:** Typing a customer filters the Part Number suggestions to that customer's known parts. Typing a Part Number reverse-populates the Customer field if the part is found in `KP`.

**Qty smart-fill:** If a part has one known standard qty, the Qty field pre-fills. If it has multiple, a dropdown appears. Entering an unrecognised qty triggers an inline warning banner.

### Rebuilding known_parts.json

```bash
python3 build_known_parts.py --input ./packing-slip-exports/ --output ./known_parts.json
```

Run this whenever you want the autocomplete data to reflect new shipment history.

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
├── review.php              # Packing slip upload & review UI (v1.49)
├── parse_slip.php          # PHP → Python parser bridge (v1.49)
├── parse_packing_slip.py   # Python packing slip parser (v1.49)
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

Full version history has been moved to [`CHANGELOG.md`](CHANGELOG.md).

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
