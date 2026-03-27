import struct, math, sys
from collections import defaultdict, Counter

try:
    import xlrd
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'xlrd==1.2.0'], stdout=subprocess.DEVNULL)
    import xlrd

TXT_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"

def strip_leading_zeros(s):
    if not s: return '0'
    r = s.lstrip('0')
    return r if r else '0'

def is_positive_numeric(s):
    if s in (None, '', '0'): return False
    try: return float(s) > 0
    except: return False

def round_to(v, d): return round(v, d)

def format_double(d):
    s = f'{d:.6f}'
    s = s.rstrip('0').rstrip('.')
    return s if s else '0'

def format_num(d):
    if d == math.floor(d) and not math.isinf(d):
        return str(int(d))
    return str(d)

def parse_double(s):
    if not s: return 0.0
    try: return float(s)
    except: return 0.0

FIELDS = {
    'bl_number':               (62,  20),
    'bl_volume':               (293, 12),
    'bl_volume_roro':          (1308,12),
    'bl_weight':               (281, 12),
    'bl_weight_alt':           (1296,12),
    'blitem_yard_item_number': (126, 20),
    'transport_mode':          (61,   1),
    'blitem_commodity_weight': (281, 12),
}

def from_line(raw, charset):
    def read_f(offset, length):
        if offset >= len(raw): return ''
        end = min(offset + length, len(raw))
        return raw[offset:end].decode(charset, errors='replace').strip()

    data = {k: read_f(o, l) for k, (o, l) in FIELDS.items()}

    # Weight: offset 281 primary, 1296 fallback, divisor = 1_000_000
    raw_w281  = strip_leading_zeros(data.get('bl_weight', ''))
    raw_w1296 = strip_leading_zeros(data.get('bl_weight_alt', ''))
    weight = 0.0
    if is_positive_numeric(raw_w281):
        weight = round_to(float(raw_w281) / 1_000_000.0, 6)
    elif is_positive_numeric(raw_w1296):
        weight = round_to(float(raw_w1296) / 1_000_000.0, 6)
    weight_str = format_double(weight) if weight > 0 else ''
    data['bl_weight']               = weight_str
    data['blitem_commodity_weight'] = weight_str

    # Volume: offset 293 primary, 1308 fallback, divisor = 1000
    raw_v293  = strip_leading_zeros(data.get('bl_volume', ''))
    raw_v1308 = strip_leading_zeros(data.get('bl_volume_roro', ''))
    vol = 0.0
    if is_positive_numeric(raw_v293):
        vol = round_to(float(raw_v293) / 1000.0, 3)
    elif is_positive_numeric(raw_v1308):
        vol = round_to(float(raw_v1308) / 1000.0, 3)
    vol_str = format_double(vol) if vol > 0 else ''
    data['bl_volume']               = vol_str
    data['blitem_commodity_volume'] = vol_str

    data.pop('bl_volume_roro', None)
    data.pop('bl_weight_alt', None)
    return data

def aggregate(records):
    bl_counts  = Counter(r['bl_number'] for r in records)
    bl_volumes = defaultdict(float)
    bl_weights = defaultdict(float)
    for r in records:
        bl_volumes[r['bl_number']] += parse_double(r.get('bl_volume',''))
        bl_weights[r['bl_number']] += parse_double(r.get('bl_weight',''))

    for r in records:
        bl = r['bl_number']
        count = bl_counts[bl]
        if count > 1:
            total_vol = round_to(bl_volumes[bl], 6)
            if total_vol > 0:
                r['bl_volume'] = format_double(total_vol)
            total_wgt = round_to(bl_weights[bl], 6)
            if total_wgt > 0:
                r['bl_weight'] = format_double(total_wgt)
    return records

def xls_exporter(data):
    # Weight: tonnes → kg (×1000)
    raw_weight = parse_double(data.get('bl_weight',''))
    data['bl_weight'] = format_num(round_to(raw_weight * 1000, 2)) if raw_weight > 0 else None

    raw_item_weight = parse_double(data.get('blitem_commodity_weight',''))
    is_vehicle = data.get('blitem_yard_item_type') == 'VEHICULE'
    item_weight_kg = 0.0
    if raw_item_weight > 0:
        item_weight_kg = round_to(raw_item_weight * 1000, 2)
        data['blitem_commodity_weight'] = format_num(item_weight_kg)
    else:
        data['blitem_commodity_weight'] = '0' if is_vehicle else None

    # Volume
    raw_volume = parse_double(data.get('bl_volume',''))
    data['bl_volume'] = format_num(round_to(raw_volume, 3)) if raw_volume > 0 else '0'

    raw_item_vol = parse_double(data.get('blitem_commodity_volume',''))
    item_type = data.get('blitem_yard_item_type','')
    if raw_item_vol > 0:
        data['blitem_commodity_volume'] = format_num(round_to(raw_item_vol, 3))
    else:
        data['blitem_commodity_volume'] = '0' if item_type in ('CONTENEUR','VEHICULE') else None

    # Commodity
    if is_vehicle:
        if item_weight_kg <= 0:       commodity = 'VEH 0-1500Kgs'
        elif item_weight_kg <= 1500:  commodity = 'VEH 0-1500Kgs'
        elif item_weight_kg <= 3000:  commodity = 'VEH 1501-3000Kgs'
        elif item_weight_kg <= 6000:  commodity = 'VEH 3001-6000Kgs'
        elif item_weight_kg <= 9000:  commodity = 'VEH 6001-9000Kgs'
        elif item_weight_kg <= 30000: commodity = 'VEH 9001-30000Kgs'
        else:                         commodity = 'VEH +30000Kgs'
        data['blitem_commodity'] = commodity
    return data

# ── LOAD TXT ──────────────────────────────────────────────────────────────────
print("=== LOADING TXT ===")
with open(TXT_PATH, 'rb') as f:
    raw_bytes = f.read()

# detect charset
charset = 'UTF-8'
try:
    raw_bytes.decode('utf-8')
    print("Charset: UTF-8")
except UnicodeDecodeError:
    charset = 'windows-1252'
    print("Charset: windows-1252 (fallback)")

# split into lines (keep as bytes)
lines = raw_bytes.split(b'\n')
print(f"Total raw lines: {len(lines)}")

records = []
for line in lines:
    line = line.rstrip(b'\r')
    if not line:
        continue
    r = from_line(line, charset)
    if r['bl_number']:  # skip blank bl_number
        records.append(r)

print(f"Records after from_line (non-empty bl_number): {len(records)}")

# Stats BEFORE aggregation
weight_before = sum(1 for r in records if parse_double(r.get('bl_weight','')) > 0)
volume_before = sum(1 for r in records if parse_double(r.get('bl_volume','')) > 0)
print(f"Records with weight > 0 BEFORE aggregation: {weight_before}")
print(f"Records with volume > 0 BEFORE aggregation: {volume_before}")

# Show sample raw weight/volume from TXT
print("\n=== SAMPLE RAW DATA (first 5 records) ===")
for i, r in enumerate(records[:5]):
    print(f"  [{i}] bl_number={r['bl_number']!r} bl_weight={r['bl_weight']!r} bl_volume={r['bl_volume']!r} item_num={r['blitem_yard_item_number']!r}")

# Sort by bl_number
records.sort(key=lambda r: r['bl_number'])

# Aggregate
records = aggregate(records)
weight_after = sum(1 for r in records if parse_double(r.get('bl_weight','')) > 0)
volume_after = sum(1 for r in records if parse_double(r.get('bl_volume','')) > 0)
print(f"\nRecords with weight > 0 AFTER aggregation: {weight_after}")
print(f"Records with volume > 0 AFTER aggregation: {volume_after}")

# Apply xls_exporter
for r in records:
    xls_exporter(r)

# Build lookup: (bl_number, item_number) -> record
sim_lookup = {}
for r in records:
    key = (r['bl_number'].strip(), r['blitem_yard_item_number'].strip())
    sim_lookup[key] = r

print(f"\nSimulated records total: {len(records)}")
print(f"Unique keys in sim: {len(sim_lookup)}")

# ── LOAD XLS ──────────────────────────────────────────────────────────────────
print("\n=== LOADING XLS ===")
wb = xlrd.open_workbook(XLS_PATH)
ws = wb.sheets()[0]
print(f"Sheet: {ws.name}, rows={ws.nrows}, cols={ws.ncols}")

# Find header row
header_row = None
headers = {}
for row_idx in range(min(5, ws.nrows)):
    row = [str(ws.cell_value(row_idx, c)).strip() for c in range(ws.ncols)]
    if any('BL' in h or 'bl' in h.lower() for h in row):
        header_row = row_idx
        headers = {h: i for i, h in enumerate(row)}
        break

if header_row is None:
    print("Could not find header row, trying row 0")
    header_row = 0
    headers = {str(ws.cell_value(0, c)).strip(): c for c in range(ws.ncols)}

print(f"Header row index: {header_row}")
print(f"Headers found: {list(headers.keys())}")

# Map header names to expected columns
def find_col(headers, *candidates):
    for c in candidates:
        if c in headers: return headers[c]
    # case-insensitive
    cl = {k.lower(): v for k, v in headers.items()}
    for c in candidates:
        if c.lower() in cl: return cl[c.lower()]
    return None

col_bl_number   = find_col(headers, 'BLNumber', 'BL Number', 'bl_number', 'BL')
col_item_number = find_col(headers, 'BLItem Yard Item Number', 'Item Number', 'blitem_yard_item_number', 'Yard Item Number')
col_bl_weight   = find_col(headers, 'BLWeight', 'BL Weight', 'bl_weight')
col_item_weight = find_col(headers, 'BLItem Commodity Weight', 'Commodity Weight', 'blitem_commodity_weight')
col_bl_volume   = find_col(headers, 'BLVolume', 'BL Volume', 'bl_volume')
col_item_volume = find_col(headers, 'BLItem Commodity Volume', 'Commodity Volume', 'blitem_commodity_volume')
col_commodity   = find_col(headers, 'BLItem Commodity', 'Commodity', 'blitem_commodity')

print(f"\nColumn indices:")
print(f"  bl_number={col_bl_number}, item_number={col_item_number}")
print(f"  bl_weight={col_bl_weight}, item_weight={col_item_weight}")
print(f"  bl_volume={col_bl_volume}, item_volume={col_item_volume}")
print(f"  commodity={col_commodity}")

def cell_str(ws, row, col):
    if col is None: return None
    v = ws.cell_value(row, col)
    if v is None or v == '': return None
    if isinstance(v, float):
        if v == math.floor(v) and not math.isinf(v):
            return str(int(v))
        return str(v)
    return str(v).strip()

# Load XLS rows
xls_rows = []
for row_idx in range(header_row + 1, ws.nrows):
    bl_num  = cell_str(ws, row_idx, col_bl_number)
    item_num = cell_str(ws, row_idx, col_item_number)
    if not bl_num:
        continue
    xls_rows.append({
        'bl_number':                 (bl_num or '').strip(),
        'blitem_yard_item_number':   (item_num or '').strip(),
        'bl_weight':                 cell_str(ws, row_idx, col_bl_weight),
        'blitem_commodity_weight':   cell_str(ws, row_idx, col_item_weight),
        'bl_volume':                 cell_str(ws, row_idx, col_bl_volume),
        'blitem_commodity_volume':   cell_str(ws, row_idx, col_item_volume),
        'blitem_commodity':          cell_str(ws, row_idx, col_commodity),
    })

print(f"XLS rows loaded: {len(xls_rows)}")

# ── COMPARE ───────────────────────────────────────────────────────────────────
print("\n=== COMPARISON ===")

COMPARE_COLS = [
    ('bl_weight',               'BLWeight'),
    ('blitem_commodity_weight', 'BLItem Commodity Weight'),
    ('bl_volume',               'BLVolume'),
    ('blitem_commodity_volume', 'BLItem Commodity Volume'),
    ('blitem_commodity',        'BLItem Commodity'),
]

stats = {col: {'match': 0, 'mismatch': 0, 'missing_in_sim': 0, 'missing_in_xls': 0, 'examples': []} for col, _ in COMPARE_COLS}

matched_keys = 0
unmatched_keys = 0
xls_only_keys = []

for xrow in xls_rows:
    key = (xrow['bl_number'], xrow['blitem_yard_item_number'])
    if key not in sim_lookup:
        unmatched_keys += 1
        if len(xls_only_keys) < 5:
            xls_only_keys.append(key)
        continue
    matched_keys += 1
    srow = sim_lookup[key]

    for col, label in COMPARE_COLS:
        xval = xrow.get(col)
        sval = srow.get(col)
        # normalize None vs '0' for volume (XLS may use 0 while sim uses None)
        # normalize to string for comparison
        xval_s = str(xval) if xval is not None else 'None'
        sval_s = str(sval) if sval is not None else 'None'

        if xval_s == sval_s:
            stats[col]['match'] += 1
        else:
            stats[col]['mismatch'] += 1
            if len(stats[col]['examples']) < 3:
                stats[col]['examples'].append({
                    'key': key,
                    'sim': sval,
                    'xls': xval,
                })

print(f"Keys matched (sim & xls): {matched_keys}")
print(f"Keys in XLS but NOT in sim: {unmatched_keys}")
if xls_only_keys:
    print(f"  Examples of unmatched XLS keys: {xls_only_keys}")

# Keys in sim but not in XLS
xls_key_set = {(r['bl_number'], r['blitem_yard_item_number']) for r in xls_rows}
sim_only = [k for k in sim_lookup if k not in xls_key_set]
print(f"Keys in sim but NOT in XLS: {len(sim_only)}")

print("\n=== COLUMN COMPARISON RESULTS ===")
for col, label in COMPARE_COLS:
    s = stats[col]
    total = s['match'] + s['mismatch']
    print(f"\n--- {label} ---")
    print(f"  Matched rows evaluated: {total}")
    print(f"  Exact matches: {s['match']}")
    print(f"  Mismatches:    {s['mismatch']}")
    if s['examples']:
        print(f"  Example mismatches (up to 3):")
        for ex in s['examples']:
            print(f"    key={ex['key']}")
            print(f"      sim={ex['sim']!r}  xls={ex['xls']!r}")

# ── WEIGHT DEEP DIVE ──────────────────────────────────────────────────────────
print("\n=== WEIGHT DEEP DIVE ===")
weight_xls_has_value = sum(1 for r in xls_rows if r['bl_weight'] not in (None, '0', ''))
weight_sim_has_value = sum(1 for r in records if r.get('bl_weight') not in (None, '0', ''))
print(f"XLS rows where BLWeight has a value (not None/0/''): {weight_xls_has_value}")
print(f"Sim records where bl_weight has a value: {weight_sim_has_value}")

# Check raw bytes at offset 281 for first few lines
print("\n=== RAW BYTE CHECK (offset 281, len 12) for first 10 lines ===")
line_count = 0
for line in raw_bytes.split(b'\n'):
    line = line.rstrip(b'\r')
    if not line: continue
    if len(line) > 281:
        raw_w = line[281:293]
        raw_v = line[293:305] if len(line) > 293 else b''
        bl_num = line[62:82].decode(charset, errors='replace').strip()
        print(f"  bl={bl_num!r} w281={raw_w!r} v293={raw_v!r}")
    else:
        bl_num = line[62:82].decode(charset, errors='replace').strip() if len(line) > 62 else '?'
        print(f"  bl={bl_num!r} line_len={len(line)} (too short for offset 281)")
    line_count += 1
    if line_count >= 10:
        break

print("\n=== DONE ===")
