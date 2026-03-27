import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

# Try to import xlrd for .xls reading
try:
    import xlrd
    HAS_XLRD = True
except ImportError:
    HAS_XLRD = False
    print("WARNING: xlrd not installed, will try openpyxl for XLS or skip")

TXT_FILE = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_FILE = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"

# ── 1. Read TXT ──────────────────────────────────────────────────────────────
print("=" * 80)
print("STEP 1 – Reading TXT file (Windows-1252)")
print("=" * 80)

with open(TXT_FILE, "rb") as f:
    raw = f.read()

try:
    text = raw.decode("windows-1252")
except Exception as e:
    print(f"Decode error: {e}")
    sys.exit(1)

lines = text.splitlines()
print(f"Total lines: {len(lines)}")

valid_lines = []
for i, line in enumerate(lines):
    if len(line) >= 400 and line[:5].isalnum():
        valid_lines.append((i + 1, line))

print(f"Valid lines (len>=400, first 5 chars alnum): {len(valid_lines)}")


def extract(line, start, length, label):
    """0-based start index, Python slice."""
    chunk = line[start:start + length]
    stripped = chunk.strip().lstrip("0") or "0"
    print(f"  {label:30s} | raw='{chunk}' | stripped='{stripped}'")
    return chunk, stripped


print()
print("=" * 80)
print("STEP 2 – Field extraction for first 5 valid lines")
print("=" * 80)
print("NOTE: Using 0-based Python offsets (Java byte offset N → Python index N-1 if 1-based,")
print("      but we treat the Java offsets as 0-based unless stated otherwise.)")
print()

# Java offsets as described (treating as 0-based inclusive start, length chars):
# bl_weight      : 281-292  → start=281, len=12   (0-based)
# bl_weight_alt  : 1296-1307→ start=1296, len=12
# bl_volume      : 293-304  → start=293, len=12
# bl_volume_roro : 1308-1319→ start=1308, len=12
# transport_mode : 61       → start=61, len=1
# bl_number      : 62-81    → start=62, len=20

first5 = valid_lines[:5]
for lineno, line in first5:
    print(f"--- Line {lineno}  (total chars: {len(line)}) ---")
    extract(line, 281, 12, "bl_weight [281-292]")
    extract(line, 1296, 12, "bl_weight_alt [1296-1307]")
    extract(line, 293, 12, "bl_volume [293-304]")
    extract(line, 1308, 12, "bl_volume_roro [1308-1319]")
    extract(line, 61, 1, "transport_mode [61]")
    _, bl_num = extract(line, 62, 20, "bl_number [62-81]")
    print()

# ── 3. Read XLS ──────────────────────────────────────────────────────────────
print("=" * 80)
print("STEP 3 – Reading XLS file")
print("=" * 80)

xls_rows = []
if HAS_XLRD:
    try:
        wb = xlrd.open_workbook(XLS_FILE)
        ws = wb.sheet_by_index(0)
        # Find header row
        header = None
        header_row_idx = 0
        for r in range(min(5, ws.nrows)):
            row_vals = [str(ws.cell_value(r, c)).strip() for c in range(ws.ncols)]
            if any("BL" in v.upper() or "WEIGHT" in v.upper() or "VOLUME" in v.upper() for v in row_vals):
                header = row_vals
                header_row_idx = r
                break
        if header is None:
            header = [str(ws.cell_value(0, c)).strip() for c in range(ws.ncols)]
            header_row_idx = 0

        print(f"Header row {header_row_idx}: {header[:15]}")
        print()

        for r in range(header_row_idx + 1, min(header_row_idx + 6, ws.nrows)):
            row = {header[c]: ws.cell_value(r, c) for c in range(min(len(header), ws.ncols))}
            xls_rows.append(row)
            # Try to find BL Number, Weight, Volume columns
            bl_col = next((k for k in row if "BL" in k.upper() and ("NUM" in k.upper() or "NO" in k.upper() or k.upper() == "BL")), None)
            wt_col = next((k for k in row if "WEIGHT" in k.upper() or "POIDS" in k.upper()), None)
            vl_col = next((k for k in row if "VOLUME" in k.upper()), None)
            bl_val = row.get(bl_col, "?") if bl_col else list(row.values())[0]
            wt_val = row.get(wt_col, "N/A") if wt_col else "N/A"
            vl_val = row.get(vl_col, "N/A") if vl_col else "N/A"
            print(f"  Row {r}: BL={bl_val}  Weight={wt_val}  Volume={vl_val}")
            if r == header_row_idx + 1:
                xls_bl1 = str(bl_val).strip()
                xls_wt1 = wt_val
                xls_vl1 = vl_val
    except Exception as e:
        print(f"xlrd error: {e}")
        HAS_XLRD = False

if not HAS_XLRD:
    print("Skipping XLS read (xlrd not available). Setting xls_bl1 from first valid TXT line.")
    xls_bl1 = valid_lines[0][1][62:82].strip().lstrip("0") if valid_lines else ""
    xls_wt1 = None
    xls_vl1 = None

# ── 4. Scan ALL offsets 270-1350 in chunks of 12 for the matching BL ─────────
print()
print("=" * 80)
print("STEP 4 – Scan ALL byte positions 270-1350 (step 12) for weight/volume")
print(f"         BL Number from XLS row 1: '{xls_bl1}'")
print("=" * 80)

# Find the line in TXT matching xls_bl1
target_line = None
target_lineno = None
for lineno, line in valid_lines:
    bl_in_line = line[62:82].strip()
    if xls_bl1 and (xls_bl1 in bl_in_line or bl_in_line in xls_bl1):
        target_line = line
        target_lineno = lineno
        break

if target_line is None and valid_lines:
    print(f"No exact match for '{xls_bl1}', using first valid line instead.")
    target_lineno, target_line = valid_lines[0]

if target_line:
    print(f"Using line {target_lineno}, length={len(target_line)}")
    print(f"  BL number field [62:82]: '{target_line[62:82]}'")
    if xls_wt1 is not None:
        print(f"  XLS Weight: {xls_wt1}  XLS Volume: {xls_vl1}")
    print()
    print(f"{'Offset':>8}  {'Raw (12 chars)':14}  {'Stripped':>14}  {'As int':>14}")
    print("-" * 60)
    for start in range(270, 1351, 12):
        if start + 12 > len(target_line):
            chunk = target_line[start:] if start < len(target_line) else ""
            stripped = chunk.strip().lstrip("0") or "0"
            try:
                val = int(stripped)
            except ValueError:
                val = None
            print(f"{start:8d}  '{chunk:<12}'  {stripped:>14}  {'?':>14}")
            break
        chunk = target_line[start:start + 12]
        stripped = chunk.strip().lstrip("0") or "0"
        try:
            val = int(stripped)
        except ValueError:
            val = None
        flag = ""
        if val is not None and val > 0:
            flag = " <-- NON-ZERO"
            if xls_wt1 is not None:
                try:
                    wt_f = float(str(xls_wt1))
                    for divisor in [1, 10, 100, 1000, 10000, 100000, 1000000]:
                        if abs(val / divisor - wt_f) < 0.01:
                            flag += f" *** MATCHES weight/{divisor} ***"
                except Exception:
                    pass
        print(f"{start:8d}  '{chunk}'  {stripped:>14}  {str(val) if val is not None else 'NaN':>14}{flag}")
else:
    print("No valid lines found in TXT.")

print()
print("=" * 80)
print("DONE")
print("=" * 80)
