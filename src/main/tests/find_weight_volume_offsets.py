#!/usr/bin/env python3
"""
Reverse-engineer weight and volume field offsets in fixed-width TXT file
by comparing against known values from XLS.
"""

import xlrd
from collections import defaultdict

TXT_PATH = "C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_PATH = "C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"

# ─────────────────────────────────────────────
# STEP 1  Build lookup from XLS
# ─────────────────────────────────────────────
print("=" * 70)
print("STEP 1 – Loading XLS …")

wb = xlrd.open_workbook(XLS_PATH)
ws = wb.sheet_by_index(0)

# Print header row so we know column names
headers = [str(ws.cell_value(0, c)).strip() for c in range(ws.ncols)]
print(f"  Columns ({ws.ncols}): {headers}")
print(f"  Rows (including header): {ws.nrows}")

# Identify columns by name (case-insensitive)
col_map = {h.lower(): i for i, h in enumerate(headers)}
print("  Column map (lowercased):")
for k, v in col_map.items():
    print(f"    [{v:2d}] {k!r}")

# Expect something like:
#   BL Number, BLItem YardItemNumber,
#   BLWeight, BLItem Commodity Weight, BLItem Commodity Volume, BLVolume
# Adjust these keys to match actual header names:
def find_col(keywords):
    """Return first column index whose header contains ALL keywords."""
    for h_lower, idx in col_map.items():
        if all(kw.lower() in h_lower for kw in keywords):
            return idx
    return None

col_bl_number        = find_col(["bl number"])
# "BLItem YardItemNumber" is the chassis/container number used as key from TXT
col_yard_item        = find_col(["blitem", "yarditemnumber"])
col_bl_weight        = find_col(["blweight"])          # total BL weight
col_item_weight      = find_col(["blitem", "commodity weight"])
col_item_volume      = find_col(["blitem", "commodity volume"])
col_bl_volume        = find_col(["blvolume"])          # total BL volume

print(f"\n  col_bl_number   = {col_bl_number}")
print(f"  col_yard_item   = {col_yard_item}")
print(f"  col_bl_weight   = {col_bl_weight}")
print(f"  col_item_weight = {col_item_weight}")
print(f"  col_item_volume = {col_item_volume}")
print(f"  col_bl_volume   = {col_bl_volume}")

xls_lookup = {}   # (bl_number_str, yard_item_str) -> dict

for r in range(1, ws.nrows):
    def cell(c):
        if c is None:
            return None
        v = ws.cell_value(r, c)
        return v

    bl_num   = str(cell(col_bl_number)).strip()  if col_bl_number   is not None else ""
    yard_item = str(cell(col_yard_item)).strip()  if col_yard_item   is not None else ""

    def safe_float(c):
        if c is None:
            return 0.0
        v = cell(c)
        try:
            return float(v)
        except (ValueError, TypeError):
            return 0.0

    xls_lookup[(bl_num, yard_item)] = {
        "bl_weight":   safe_float(col_bl_weight),
        "item_weight": safe_float(col_item_weight),
        "item_volume": safe_float(col_item_volume),
        "bl_volume":   safe_float(col_bl_volume),
    }

print(f"\n  XLS entries loaded: {len(xls_lookup)}")
# Show a sample
sample = list(xls_lookup.items())[:3]
for k, v in sample:
    print(f"  key={k}  vals={v}")

# ─────────────────────────────────────────────
# STEP 2  Parse TXT lines
# ─────────────────────────────────────────────
print("\n" + "=" * 70)
print("STEP 2 – Parsing TXT …")

with open(TXT_PATH, "rb") as f:
    raw = f.read()

lines_raw = raw.split(b"\n")
lines = []
for lr in lines_raw:
    lr = lr.rstrip(b"\r")
    if len(lr) < 400:
        continue
    prefix = lr[:5].decode("windows-1252", errors="replace").strip()
    if prefix and prefix.isalnum():
        lines.append(lr)

print(f"  Total qualifying lines: {len(lines)}")
if lines:
    print(f"  First line length: {len(lines[0])}")
    print(f"  First line prefix (0-30): {lines[0][:30]}")

# Extract BL Number (offset 62, len 20) and YardItemNumber (offset 126, len 20)
matched_lines = []   # list of (line_bytes, xls_vals, bl_num, yard_item)
unmatched_count = 0

for lr in lines:
    def extract(off, length):
        chunk = lr[off:off+length]
        return chunk.decode("windows-1252", errors="replace").strip()

    bl_num    = extract(62, 20)
    yard_item = extract(126, 20)
    key = (bl_num, yard_item)

    if key in xls_lookup:
        matched_lines.append((lr, xls_lookup[key], bl_num, yard_item))
    else:
        unmatched_count += 1

print(f"  Matched lines: {len(matched_lines)}")
print(f"  Unmatched lines: {unmatched_count}")
if matched_lines:
    bl0, yi0 = matched_lines[0][2], matched_lines[0][3]
    print(f"  Sample match: BL={bl0!r}, YardItem={yi0!r}, XLS={matched_lines[0][1]}")

# ─────────────────────────────────────────────
# Helper: scan a single line for a raw integer value
# ─────────────────────────────────────────────
def scan_line_for_value(line_bytes, target_int, field_width=12, max_offset=2000):
    """
    Scan every byte position up to max_offset.
    At each position read `field_width` bytes, decode as ASCII digits (ignore non-digit),
    strip leading zeros, compare to target_int.
    Returns list of offsets where match found.
    """
    hits = []
    limit = min(len(line_bytes), max_offset)
    for off in range(limit - field_width + 1):
        chunk = line_bytes[off:off + field_width]
        # Keep only digit characters
        digits = bytes(b for b in chunk if 48 <= b <= 57)  # '0'-'9'
        if not digits:
            continue
        try:
            val = int(digits)
        except ValueError:
            continue
        if abs(val - target_int) <= 1:
            hits.append(off)
    return hits


# ─────────────────────────────────────────────
# STEP 3  Find weight offset
# ─────────────────────────────────────────────
print("\n" + "=" * 70)
print("STEP 3 – Finding weight offset …")

# Collect lines with non-zero item_weight
weight_lines = [(lr, vals, bl, yi) for (lr, vals, bl, yi) in matched_lines if vals["item_weight"] > 0]
print(f"  Lines with non-zero item_weight: {len(weight_lines)}")

# Also try bl_weight
bl_weight_lines = [(lr, vals, bl, yi) for (lr, vals, bl, yi) in matched_lines if vals["bl_weight"] > 0]
print(f"  Lines with non-zero bl_weight:   {len(bl_weight_lines)}")

def find_offset_candidates(record_list, value_key, divisors, label, max_records=10):
    """
    For each record, for each divisor, scan all offsets for the raw int.
    Returns a dict: offset -> count of records where it matched.
    """
    offset_counts = defaultdict(lambda: defaultdict(int))  # offset -> divisor -> count
    records_used = 0
    for lr, vals, bl, yi in record_list[:max_records]:
        expected_float = vals[value_key]
        if expected_float <= 0:
            continue
        records_used += 1
        found_any = False
        for div_name, div in divisors:
            raw = round(expected_float * div)
            hits = scan_line_for_value(lr, raw, field_width=12, max_offset=min(len(lr), 2000))
            for h in hits:
                offset_counts[h][div_name] += 1
                found_any = True
        if not found_any:
            print(f"    No hits for BL={bl!r} YardItem={yi!r} expected_{value_key}={expected_float}")
    print(f"  Records scanned: {records_used}")
    return offset_counts

weight_divisors = [
    ("×1000 (g)",      1000),
    ("×1000000 (mg)",  1000000),
    ("×1 (kg)",        1),
    ("×100 (hg)",      100),
    ("×10",            10),
]

print("\n  --- Item Weight candidates ---")
wt_offset_counts = find_offset_candidates(weight_lines, "item_weight", weight_divisors, "item_weight", max_records=10)

# Sort by total count
wt_sorted = sorted(wt_offset_counts.items(), key=lambda x: -sum(x[1].values()))
print(f"  Top 20 offset candidates (offset: {{divisor: count}}):")
for off, dcounts in wt_sorted[:20]:
    print(f"    offset {off:5d}: {dict(dcounts)}")

print("\n  --- BL Weight candidates (fallback) ---")
blwt_offset_counts = find_offset_candidates(bl_weight_lines, "bl_weight", weight_divisors, "bl_weight", max_records=10)
blwt_sorted = sorted(blwt_offset_counts.items(), key=lambda x: -sum(x[1].values()))
print(f"  Top 20 offset candidates (offset: {{divisor: count}}):")
for off, dcounts in blwt_sorted[:20]:
    print(f"    offset {off:5d}: {dict(dcounts)}")

# ─────────────────────────────────────────────
# STEP 4  Find volume offset
# ─────────────────────────────────────────────
print("\n" + "=" * 70)
print("STEP 4 – Finding volume offset …")

volume_lines = [(lr, vals, bl, yi) for (lr, vals, bl, yi) in matched_lines if vals["item_volume"] > 0]
print(f"  Lines with non-zero item_volume: {len(volume_lines)}")

bl_vol_lines = [(lr, vals, bl, yi) for (lr, vals, bl, yi) in matched_lines if vals["bl_volume"] > 0]
print(f"  Lines with non-zero bl_volume:   {len(bl_vol_lines)}")

volume_divisors = [
    ("×1000",    1000),
    ("×100",     100),
    ("×10",      10),
    ("×1",       1),
    ("×1000000", 1000000),
]

print("\n  --- Item Volume candidates ---")
vol_offset_counts = find_offset_candidates(volume_lines, "item_volume", volume_divisors, "item_volume", max_records=10)
vol_sorted = sorted(vol_offset_counts.items(), key=lambda x: -sum(x[1].values()))
print(f"  Top 20 offset candidates (offset: {{divisor: count}}):")
for off, dcounts in vol_sorted[:20]:
    print(f"    offset {off:5d}: {dict(dcounts)}")

print("\n  --- BL Volume candidates (fallback) ---")
blvol_offset_counts = find_offset_candidates(bl_vol_lines, "bl_volume", volume_divisors, "bl_volume", max_records=10)
blvol_sorted = sorted(blvol_offset_counts.items(), key=lambda x: -sum(x[1].values()))
print(f"  Top 20 offset candidates (offset: {{divisor: count}}):")
for off, dcounts in blvol_sorted[:20]:
    print(f"    offset {off:5d}: {dict(dcounts)}")

# ─────────────────────────────────────────────
# STEP 5  Verify top candidates against ALL matched records
# ─────────────────────────────────────────────
print("\n" + "=" * 70)
print("STEP 5 – Verification against ALL matched records …")

def read_field_int(line_bytes, offset, length=12):
    chunk = line_bytes[offset:offset+length]
    digits = bytes(b for b in chunk if 48 <= b <= 57)
    if not digits:
        return None
    try:
        return int(digits)
    except ValueError:
        return None

def verify_offset(record_list, value_key, offset, divisor_val, divisor_name, field_length=12, tolerance=1):
    correct = 0
    wrong = []
    for lr, vals, bl, yi in record_list:
        expected_float = vals[value_key]
        raw_found = read_field_int(lr, offset, field_length)
        if raw_found is None:
            if expected_float == 0:
                correct += 1
            else:
                wrong.append((bl, yi, expected_float, None, "no digits"))
            continue
        expected_raw = round(expected_float * divisor_val)
        if abs(raw_found - expected_raw) <= tolerance:
            correct += 1
        else:
            wrong.append((bl, yi, expected_float, raw_found, f"expected_raw={expected_raw}"))
    return correct, wrong

# Determine best weight candidate
def pick_best(sorted_candidates, min_hits=5):
    for off, dcounts in sorted_candidates:
        total = sum(dcounts.values())
        if total >= min_hits:
            best_div_name = max(dcounts, key=dcounts.get)
            return off, best_div_name, dcounts[best_div_name]
    return None, None, 0

# Map divisor names to values
div_name_to_val = {
    "×1000 (g)":      1000,
    "×1000000 (mg)":  1000000,
    "×1 (kg)":        1,
    "×100 (hg)":      100,
    "×10":            10,
    "×1000":          1000,
    "×100":           100,
    "×10":            10,
    "×1":             1,
    "×1000000":       1000000,
}

print("\n--- Verifying Item Weight ---")
best_wt_off, best_wt_div_name, best_wt_hits = pick_best(wt_sorted)
if best_wt_off is not None:
    best_wt_div_val = div_name_to_val.get(best_wt_div_name, 1000)
    print(f"  Top candidate: offset={best_wt_off}, divisor={best_wt_div_name} ({best_wt_div_val}), hits={best_wt_hits}/10")
    correct, wrong = verify_offset(matched_lines, "item_weight", best_wt_off, best_wt_div_val, best_wt_div_name)
    print(f"  Correct: {correct}/{len(matched_lines)}")
    print(f"  Wrong:   {len(wrong)}")
    print(f"  First 5 mismatches:")
    for bl, yi, exp, found, note in wrong[:5]:
        print(f"    BL={bl!r} YardItem={yi!r} expected_kg={exp}, raw_found={found} ({note})")
else:
    print("  No consistent candidate found for item_weight.")

print("\n--- Verifying BL Weight ---")
best_blwt_off, best_blwt_div_name, best_blwt_hits = pick_best(blwt_sorted)
if best_blwt_off is not None:
    best_blwt_div_val = div_name_to_val.get(best_blwt_div_name, 1000)
    print(f"  Top candidate: offset={best_blwt_off}, divisor={best_blwt_div_name} ({best_blwt_div_val}), hits={best_blwt_hits}/10")
    correct, wrong = verify_offset(matched_lines, "bl_weight", best_blwt_off, best_blwt_div_val, best_blwt_div_name)
    print(f"  Correct: {correct}/{len(matched_lines)}")
    print(f"  Wrong:   {len(wrong)}")
    print(f"  First 5 mismatches:")
    for bl, yi, exp, found, note in wrong[:5]:
        print(f"    BL={bl!r} YardItem={yi!r} expected_kg={exp}, raw_found={found} ({note})")
else:
    print("  No consistent candidate found for bl_weight.")

print("\n--- Verifying Item Volume ---")
best_vol_off, best_vol_div_name, best_vol_hits = pick_best(vol_sorted)
if best_vol_off is not None:
    best_vol_div_val = div_name_to_val.get(best_vol_div_name, 1000)
    print(f"  Top candidate: offset={best_vol_off}, divisor={best_vol_div_name} ({best_vol_div_val}), hits={best_vol_hits}/10")
    correct, wrong = verify_offset(matched_lines, "item_volume", best_vol_off, best_vol_div_val, best_vol_div_name)
    print(f"  Correct: {correct}/{len(matched_lines)}")
    print(f"  Wrong:   {len(wrong)}")
    print(f"  First 5 mismatches:")
    for bl, yi, exp, found, note in wrong[:5]:
        print(f"    BL={bl!r} YardItem={yi!r} expected_m3={exp}, raw_found={found} ({note})")
else:
    print("  No consistent candidate found for item_volume.")

print("\n--- Verifying BL Volume ---")
best_blvol_off, best_blvol_div_name, best_blvol_hits = pick_best(blvol_sorted)
if best_blvol_off is not None:
    best_blvol_div_val = div_name_to_val.get(best_blvol_div_name, 1000)
    print(f"  Top candidate: offset={best_blvol_off}, divisor={best_blvol_div_name} ({best_blvol_div_val}), hits={best_blvol_hits}/10")
    correct, wrong = verify_offset(matched_lines, "bl_volume", best_blvol_off, best_blvol_div_val, best_blvol_div_name)
    print(f"  Correct: {correct}/{len(matched_lines)}")
    print(f"  Wrong:   {len(wrong)}")
    print(f"  First 5 mismatches:")
    for bl, yi, exp, found, note in wrong[:5]:
        print(f"    BL={bl!r} YardItem={yi!r} expected_m3={exp}, raw_found={found} ({note})")
else:
    print("  No consistent candidate found for bl_volume.")

# ─────────────────────────────────────────────
# BONUS: Show raw bytes around best candidates for first matched line
# ─────────────────────────────────────────────
print("\n" + "=" * 70)
print("BONUS – Raw byte context around candidates (first matched line) …")
if matched_lines:
    lr0, vals0, bl0, yi0 = matched_lines[0]
    print(f"  BL={bl0!r}, YardItem={yi0!r}")
    print(f"  XLS vals: {vals0}")
    print(f"  Line length: {len(lr0)}")

    for label, best_off in [
        ("item_weight", best_wt_off),
        ("bl_weight",   best_blwt_off),
        ("item_volume", best_vol_off),
        ("bl_volume",   best_blvol_off),
    ]:
        if best_off is not None:
            ctx_start = max(0, best_off - 5)
            ctx_end   = min(len(lr0), best_off + 20)
            chunk = lr0[ctx_start:ctx_end]
            try:
                decoded = chunk.decode("windows-1252", errors="replace")
            except Exception:
                decoded = repr(chunk)
            print(f"\n  [{label}] offset={best_off}")
            print(f"    bytes[{ctx_start}:{ctx_end}] = {decoded!r}")

print("\n" + "=" * 70)
print("DONE")
