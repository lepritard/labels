#!/usr/bin/env python3
"""
parse_packing_slip.py  —  Evolved packing slip parser (v2, v1.29)

Reads a New Arch ENTERPRISE packing slip .xlsx (Sheet2) and returns
structured JSON describing every label group to be printed.

Supports:
  - Standard carton rows  ("Carton A  (PART :N PCS / CARTON)")
  - Co-packed secondary part numbers (col A blank, col B = secondary PN)
  - Pallet rows           ("Pallet A  (PART: N PCS / PALLET)")
  - Wooden case rows      ("Wooden case A  (PART: N PCS / WOODEN CASE)")
  - Mixed shipments (cartons + pallets + wooden cases in one file)
  - "PALLET N" annotation in col G (carton groups assigned to pallets)
  - "T013 PREPPED" flag in col G
  - TBCTW- prefix typo correction
  - Bare numeric part numbers via KNOWN_PARTS map
  - EXCEPTIONS dict for co-packed inner-box label counts
  - Revision stripping for EXCEPTIONS lookup (keeps revision in output)

Output JSON schema (one object per file):
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
      "base_part":       "ENC-1726",          // revision stripped
      "revision":        "E",                 // None if no revision
      "pcs_per_box":     4,
      "num_labels":      41,
      "container_type":  "carton",            // carton | pallet | wooden_case
      "labels_per_unit": 1,                   // 1 for cartons, 4 for pallets/wooden cases
      "total_labels":    41,                  // num_labels * labels_per_unit
      "flags":           [],                  // ["t013_prepped", "co_packed_secondary", ...]
      "pallet_group":    null                 // "PALLET 1" or null
    },
    ...
  ]
}
"""

import re
import json
import sys
import openpyxl
from collections import defaultdict
from pathlib import Path


# ---------------------------------------------------------------------------
# Configuration tables — edit these as new customers / exceptions are found
# ---------------------------------------------------------------------------

CUSTOMER_MAP = {
    "TBC":    "TurboChef",
    "TAY":    "Taylor Company",
    "APS":    "Avtron Power Solutions",
    "AII":    "Appliance Innovation",
    "DWG":    "DAWGS",
    "JJP":    "Jersey Jack Pinball",
    "MCS":    "Middleby Coffee Solutions",
    "BPI":    "Berryman Products",
    "CPI":    "Commercial Plastics",
    "SCI":    "Stericycle",
    "FRE":    "Frymaster",
    "IB":     "IB Products",
    "NGC":    "Manitowoc",
    "HCW":    "Henny Penny",
    "HHD":    "TurboChef",       # HHD prefix is TurboChef product line
    "HCT":    "TurboChef",
    "HHS":    "TurboChef",
    "ECO":    "TurboChef",
    "ECS":    "TurboChef",
    "ENC":    "TurboChef",
    "SKU":    "Unknown",
    "TBCTW":  "TurboChef",       # typo variant — handled specially below
}

# Parts identified solely by their full part number (no alphabetic prefix pattern)
KNOWN_PARTS = {
    "400009838": {"customer": "LAB2FAB", "base_part": "400009838", "revision": None},
}

# Co-packed inner-box rules.
# Key = base part number WITHOUT revision letter (e.g. "ENC-1833", not "TBC-ENC-1833-B")
# labels_per_carton: how many inner boxes (= labels) exist inside each outer carton
# pcs_per_label: pieces in each inner box
EXCEPTIONS = {
    "ENC-1725": {"labels_per_carton": 2, "pcs_per_label": 2},
    "ENC-1833": {"labels_per_carton": 2, "pcs_per_label": 3},
    "ENC-1724": {"labels_per_carton": 2, "pcs_per_label": 6},
    "HHD-8301": {"labels_per_carton": 3, "pcs_per_label": 2},
}

# Labels printed per physical unit
LABELS_PER_UNIT = {
    "carton":      1,
    "pallet":      4,
    "wooden_case": 4,
}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _s(cell):
    """Return cell value as stripped string, or empty string."""
    return str(cell).strip() if cell is not None else ""


def extract_meta(rows):
    meta = {"po": None, "date": None, "container_no": None, "seal": None}
    for row in rows[:12]:
        for cell in row:
            v = _s(cell)
            if not v:
                continue
            m = re.search(r"No\.?:\s*(\S+)", v)
            if m and meta["po"] is None:
                meta["po"] = m.group(1)
            m = re.search(r"Date:\s*(.+)", v)
            if m and meta["date"] is None:
                meta["date"] = m.group(1).strip()
            m = re.search(r"CONTAINER\s*NO[:\s]+(\S+)", v, re.IGNORECASE)
            if m and meta["container_no"] is None:
                meta["container_no"] = m.group(1).strip()
            m = re.search(r"SEAL\s*NUMBER\s*[:\s]+(\S+)", v, re.IGNORECASE)
            if m and meta["seal"] is None:
                meta["seal"] = m.group(1).strip()
    return meta


def find_header_row(rows):
    for i, row in enumerate(rows):
        vals = [_s(v).upper() for v in row]
        if any("DESCRIPTION" in v for v in vals) and any("QUANTITY" in v for v in vals):
            return i
    return None


def find_data_end(rows, start):
    for i, row in enumerate(rows[start:], start):
        v = _s(row[0]).upper()
        if v.startswith("TOTAL") or v.startswith("SAY TOTAL"):
            return i
    return len(rows)


def detect_container_type(col_a):
    a = col_a.lower()
    if a.startswith("pallet"):
        return "pallet"
    if a.startswith("wooden case"):
        return "wooden_case"
    if a.startswith("carton"):
        return "carton"
    return "carton"  # default


def parse_qty(val):
    """Extract integer from a cell that may be int, float, or string like '10 PALLETS'."""
    if val is None:
        return None
    if isinstance(val, (int, float)):
        return int(val)
    m = re.search(r"(\d[\d,]*)", str(val).replace(",", ""))
    return int(m.group(1)) if m else None


def pcs_from_label_text(part_number, label_text):
    """
    Extract pieces-per-box from the verbose col A label text.
    Tries to match the part number then find the PCS figure after it.
    Falls back to any single PCS figure in the text.
    """
    if not label_text:
        return None
    esc = re.escape(part_number)
    # e.g. "TBC-ENC-1726-E :4 PCS / CARTON"
    m = re.search(esc + r"[^0-9]{0,80}?(\d[\d,]*)\s*PCS", label_text, re.IGNORECASE)
    if m:
        return int(m.group(1).replace(",", ""))
    # Fallback: any PCS figures
    nums = re.findall(r"(\d[\d,]*)\s*PCS", label_text, re.IGNORECASE)
    if len(nums) == 1:
        return int(nums[0].replace(",", ""))
    return None


def normalise_part(raw_part):
    """
    Given a raw part number string from col B, return:
      (display_part, customer, base_for_exceptions, revision)

    Handles:
      - Standard "PREFIX-REST-REV" format
      - TBCTW typo → TBC prefix, display as "TW-REST-REV"
      - Bare numeric parts via KNOWN_PARTS
      - TBC sub-prefixes (ENC, HHD, etc.) mapped to TurboChef directly
    """
    raw = raw_part.strip()

    # Bare numeric
    if re.match(r"^\d+$", raw):
        if raw in KNOWN_PARTS:
            kp = KNOWN_PARTS[raw]
            return raw, kp["customer"], kp["base_part"], kp["revision"]
        return raw, "Unknown", raw, None

    # TBCTW typo: e.g. TBCTW-I1-9988-B → display "TW-I1-9988-B", customer TurboChef
    if raw.upper().startswith("TBCTW-"):
        display = raw[3:]          # strip "TBC" → "TW-I1-9988-B"
        rest = display             # e.g. "TW-I1-9988-B"
        # strip revision from base key
        rev_m = re.search(r"-([A-Z])$", rest)
        revision = rev_m.group(1) if rev_m else None
        base = rest[:-2] if revision else rest
        return display, "TurboChef", base, revision

    # Standard: split on first dash to get prefix
    # Extract leading alphabetic characters as the customer prefix
    prefix_m = re.match(r'^([A-Za-z]+)', raw)
    prefix = prefix_m.group(1).upper() if prefix_m else raw.split('-')[0].upper()

    # Revision is last token if it's a single capital letter
    rev_m = re.search(r"-([A-Z])$", raw)
    revision = rev_m.group(1) if rev_m else None
    base = raw[:-2] if revision else raw   # strip "-X" suffix for EXCEPTIONS key

    # Strip "TBC-" prefix for EXCEPTIONS lookup (e.g. "TBC-ENC-1833-B" → "ENC-1833")
    exc_key = base
    if exc_key.upper().startswith("TBC-"):
        exc_key = exc_key[4:]

    customer = CUSTOMER_MAP.get(prefix, f"Unknown ({prefix})")

    return raw, customer, exc_key, revision


# ---------------------------------------------------------------------------
# Core parser
# ---------------------------------------------------------------------------

def parse_packing_slip(filepath):
    wb = openpyxl.load_workbook(filepath, data_only=True)
    # Try Sheet2 first (New Arch default); fall back to first sheet
    ws = wb["Sheet2"] if "Sheet2" in wb.sheetnames else wb.worksheets[0]
    rows = list(ws.iter_rows(values_only=True))

    meta = extract_meta(rows)
    header_row = find_header_row(rows)
    if header_row is None:
        raise ValueError(f"Could not find header row in {filepath}")

    data_end = find_data_end(rows, header_row + 1)
    label_groups = []

    pending_secondary = []   # accumulates secondary PNs until next primary row

    def flush_secondary(primary_group, secondary_pns):
        """Attach secondary (co-packed) PNs to the primary group's carton count."""
        for sec_pn in secondary_pns:
            display, customer, exc_key, revision = normalise_part(sec_pn)
            exc = EXCEPTIONS.get(exc_key, {})
            lpc = exc.get("labels_per_carton", 1)
            ppl = exc.get("pcs_per_label", None)
            num_outer = primary_group["num_labels"]
            total = num_outer * lpc

            label_groups.append({
                "customer":        customer,
                "part_number":     display,
                "base_part":       exc_key,
                "revision":        revision,
                "pcs_per_box":     ppl,
                "num_labels":      total,
                "container_type":  "carton",
                "labels_per_unit": 1,
                "total_labels":    total,
                "flags":           ["co_packed_secondary"],
                "pallet_group":    primary_group.get("pallet_group"),
            })

    last_primary = None

    for row in rows[header_row + 1:data_end]:
        col_a = _s(row[0])
        col_b = _s(row[1])
        col_c = row[2] if len(row) > 2 else None
        col_g = _s(row[6]) if len(row) > 6 else ""

        # Skip blank rows
        if not col_a and not col_b:
            continue

        # Secondary (co-packed) row: col A blank, col B has part number
        if not col_a and col_b:
            pending_secondary.append(col_b)
            continue

        # Primary row — first flush any pending secondary PNs onto the PREVIOUS primary
        if pending_secondary and last_primary is not None:
            flush_secondary(last_primary, pending_secondary)
            pending_secondary = []

        # Parse primary row
        ctype = detect_container_type(col_a)
        qty_units = parse_qty(col_c)
        if qty_units is None:
            continue

        display, customer, exc_key, revision = normalise_part(col_b)
        pcs = pcs_from_label_text(col_b, col_a)

        # Flags
        flags = []
        pallet_group = None
        if "T013" in col_g.upper():
            flags.append("t013_prepped")
        pallet_m = re.search(r"PALLET\s*(\d+)", col_g, re.IGNORECASE)
        if pallet_m:
            pallet_group = f"PALLET {pallet_m.group(1)}"

        lpu = LABELS_PER_UNIT.get(ctype, 1)
        total_labels = qty_units * lpu

        group = {
            "customer":        customer,
            "part_number":     display,
            "base_part":       exc_key,
            "revision":        revision,
            "pcs_per_box":     pcs,
            "num_labels":      qty_units,
            "container_type":  ctype,
            "labels_per_unit": lpu,
            "total_labels":    total_labels,
            "flags":           flags,
            "pallet_group":    pallet_group,
        }

        label_groups.append(group)
        last_primary = group

    # Flush any trailing secondary PNs
    if pending_secondary and last_primary is not None:
        flush_secondary(last_primary, pending_secondary)

    # ── Post-process: detect remainder (non-standard) boxes ──────────────
    # When consecutive carton groups share the same base_part, the one with
    # the smaller num_labels is a remainder box (non-standard qty).
    # Mark it with "nonstd_remainder" flag and record nonstd_box_num.
    for i in range(1, len(label_groups)):
        cur = label_groups[i]
        prev = label_groups[i - 1]
        if (cur["container_type"] == "carton"
                and prev["container_type"] == "carton"
                and cur["base_part"] == prev["base_part"]
                and cur["num_labels"] < prev["num_labels"]
                and "nonstd_remainder" not in cur["flags"]
                and "co_packed_secondary" not in cur["flags"]):
            cur["flags"].append("nonstd_remainder")
            cur["nonstd_box_num"] = prev["num_labels"] + 1
            cur["nonstd_copies"] = 5
            # Merge total into the parent so total_labels is accurate
            prev["total_boxes"] = prev["num_labels"] + cur["num_labels"]

    return {"meta": meta, "label_groups": label_groups}


# ---------------------------------------------------------------------------
# CLI entry point
# ---------------------------------------------------------------------------

def main():
    if len(sys.argv) < 2:
        print("Usage: python parse_packing_slip.py <file.xlsx> [file2.xlsx ...]")
        sys.exit(1)

    results = {}
    for path in sys.argv[1:]:
        try:
            data = parse_packing_slip(path)
            results[Path(path).name] = data
        except Exception as e:
            results[Path(path).name] = {"error": str(e)}

    print(json.dumps(results, indent=2))


if __name__ == "__main__":
    main()
