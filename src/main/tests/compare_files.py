import pandas as pd
import numpy as np
import math

XLS_PATH  = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"
XLSX_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/20260327093746_all_dakar_bl_extract2-gra0226sb_-mis___jour.xlsx"

KEY_COLS = ["BL Number", "BLItem YardItemNumber"]

# ── helpers ──────────────────────────────────────────────────────────────────

def normalize(v):
    """Return a canonical Python value suitable for equality checks."""
    if v is None:
        return None
    if isinstance(v, float) and math.isnan(v):
        return None
    if isinstance(v, str):
        v = v.strip()
        if v == "":
            return None
        # try coerce to float
        try:
            return float(v)
        except ValueError:
            return v
    if isinstance(v, (int, np.integer)):
        return float(v)
    if isinstance(v, (float, np.floating)):
        return float(v)
    return v

def values_equal(a, b):
    na, nb = normalize(a), normalize(b)
    if na is None and nb is None:
        return True
    if na is None or nb is None:
        return False
    if isinstance(na, float) and isinstance(nb, float):
        return math.isclose(na, nb, rel_tol=1e-9, abs_tol=1e-12)
    return na == nb

def both_null(a, b):
    return normalize(a) is None and normalize(b) is None

# ── load files ────────────────────────────────────────────────────────────────

print("=" * 80)
print("LOADING FILES")
print("=" * 80)

df_xls  = pd.read_excel(XLS_PATH,  dtype=str, engine="xlrd")
df_xlsx = pd.read_excel(XLSX_PATH, dtype=str, engine="openpyxl")

# Strip column names
df_xls.columns  = [c.strip() for c in df_xls.columns]
df_xlsx.columns = [c.strip() for c in df_xlsx.columns]

print(f"\nXLS  columns ({len(df_xls.columns)}): {list(df_xls.columns)}")
print(f"\nXLSX columns ({len(df_xlsx.columns)}): {list(df_xlsx.columns)}")

# ── key presence ──────────────────────────────────────────────────────────────

print("\n" + "=" * 80)
print("KEY PRESENCE CHECK")
print("=" * 80)

for kc in KEY_COLS:
    in_xls  = kc in df_xls.columns
    in_xlsx = kc in df_xlsx.columns
    print(f"  '{kc}': XLS={in_xls}, XLSX={in_xlsx}")

# Build composite key (as strings, stripped)
def make_key(row, cols):
    return tuple(str(row[c]).strip() if pd.notna(row[c]) else "" for c in cols)

keys_xls  = [make_key(r, KEY_COLS) for _, r in df_xls.iterrows()]
keys_xlsx = [make_key(r, KEY_COLS) for _, r in df_xlsx.iterrows()]

set_xls  = set(keys_xls)
set_xlsx = set(keys_xlsx)

print(f"\nTotal rows – XLS: {len(df_xls)}, XLSX: {len(df_xlsx)}")
print(f"Unique keys – XLS: {len(set_xls)}, XLSX: {len(set_xlsx)}")

only_in_xls  = sorted(set_xls  - set_xlsx)
only_in_xlsx = sorted(set_xlsx - set_xls)
in_both      = set_xls & set_xlsx

print(f"\nKeys in XLS  but NOT in XLSX ({len(only_in_xls)}):")
for k in only_in_xls[:20]:
    print(f"  {k}")
if len(only_in_xls) > 20:
    print(f"  ... and {len(only_in_xls) - 20} more")

print(f"\nKeys in XLSX but NOT in XLS  ({len(only_in_xlsx)}):")
for k in only_in_xlsx[:20]:
    print(f"  {k}")
if len(only_in_xlsx) > 20:
    print(f"  ... and {len(only_in_xlsx) - 20} more")

print(f"\nKeys present in BOTH: {len(in_both)}")

# ── column sets ───────────────────────────────────────────────────────────────

print("\n" + "=" * 80)
print("COLUMN COMPARISON")
print("=" * 80)

cols_xls  = set(df_xls.columns)
cols_xlsx = set(df_xlsx.columns)
common    = cols_xls & cols_xlsx
only_xls  = cols_xls - cols_xlsx
only_xlsx = cols_xlsx - cols_xls

print(f"\nColumns ONLY in XLS  ({len(only_xls)}): {sorted(only_xls)}")
print(f"Columns ONLY in XLSX ({len(only_xlsx)}): {sorted(only_xlsx)}")
print(f"Columns in BOTH      ({len(common)}): {sorted(common)}")

# ── build lookup maps keyed by composite key ─────────────────────────────────

# If duplicate keys exist, keep the first occurrence
xls_map  = {}
for _, row in df_xls.iterrows():
    k = make_key(row, KEY_COLS)
    if k not in xls_map:
        xls_map[k] = row

xlsx_map = {}
for _, row in df_xlsx.iterrows():
    k = make_key(row, KEY_COLS)
    if k not in xlsx_map:
        xlsx_map[k] = row

# ── per-column comparison ─────────────────────────────────────────────────────

print("\n" + "=" * 80)
print("PER-COLUMN COMPARISON (matched rows only)")
print("=" * 80)

# Sort columns: key cols first, then rest alphabetically
sorted_common = KEY_COLS + sorted(common - set(KEY_COLS))

for col in sorted_common:
    matches    = 0
    mismatches = 0
    nulls      = 0
    examples   = []   # (key, xls_val, xlsx_val)

    for k in in_both:
        xls_val  = xls_map[k].get(col)  if col in xls_map[k].index  else None
        xlsx_val = xlsx_map[k].get(col) if col in xlsx_map[k].index else None

        if both_null(xls_val, xlsx_val):
            nulls += 1
        elif values_equal(xls_val, xlsx_val):
            matches += 1
        else:
            mismatches += 1
            if len(examples) < 5:
                examples.append((k, xls_val, xlsx_val))

    total = matches + mismatches + nulls
    status = "OK" if mismatches == 0 else "MISMATCH"
    print(f"\n  [{status}] {col}")
    print(f"    matched rows: {total}  |  same: {matches}  |  diff: {mismatches}  |  both-null: {nulls}")
    if examples:
        print(f"    Sample mismatches (key | xls | xlsx):")
        for key, xv, yv in examples:
            print(f"      key={key}")
            print(f"        XLS : {repr(xv)}")
            print(f"        XLSX: {repr(yv)}")

print("\n" + "=" * 80)
print("END OF REPORT")
print("=" * 80)
