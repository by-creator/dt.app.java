import sys, math
sys.stdout.reconfigure(encoding='utf-8')
from collections import defaultdict, Counter

try:
    import xlrd
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'xlrd==1.2.0'], stdout=subprocess.DEVNULL)
    import xlrd

TXT_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis a jour.TXT"
XLS_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"

# Try the actual file path with the special character
import os
TXT_ACTUAL = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis \u00e0 jour.TXT"
# Use glob to find it
import glob
txt_candidates = glob.glob(r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/*.TXT")
if txt_candidates:
    TXT_PATH = txt_candidates[0]
    print(f"TXT file found: {TXT_PATH}")
else:
    print("No TXT found!")
    sys.exit(1)

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

def from_line(raw, charset):
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

    def read_f(offset, length):
        if offset >= len(raw): return ''
        end = min(offset + length, len(raw))
        return raw[offset:end].decode(charset, errors='replace').strip()

    data = {k: read_f(o, l) for k, (o, l) in FIELDS.items()}

    # Weight
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

    # Volume
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

    raw_volume = parse_double(data.get('bl_volume',''))
    data['bl_volume'] = format_num(round_to(raw_volume, 3)) if raw_volume > 0 else '0'

    raw_item_vol = parse_double(data.get('blitem_commodity_volume',''))
    item_type = data.get('blitem_yard_item_type','')
    if raw_item_vol > 0:
        data['blitem_commodity_volume'] = format_num(round_to(raw_item_vol, 3))
    else:
        data['blitem_commodity_volume'] = '0' if item_type in ('CONTENEUR','VEHICULE') else None

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
print("=== STEP 1: PARSE TXT ===")
with open(TXT_PATH, 'rb') as f:
    raw_bytes = f.read()

charset = 'UTF-8'
try:
    raw_bytes.decode('utf-8')
    print("Charset: UTF-8")
except UnicodeDecodeError:
    charset = 'windows-1252'
    print("Charset: windows-1252 (fallback)")

lines = raw_bytes.split(b'\n')
print(f"Total raw lines: {len(lines)}")

records = []
for line in lines:
    line = line.rstrip(b'\r')
    if not line:
        continue
    r = from_line(line, charset)
    if r['bl_number']:
        records.append(r)

print(f"Records parsed (non-empty bl_number): {len(records)}")

weight_before = sum(1 for r in records if parse_double(r.get('bl_weight','')) > 0)
volume_before = sum(1 for r in records if parse_double(r.get('bl_volume','')) > 0)
print(f"Records with weight > 0 BEFORE aggregation: {weight_before}")
print(f"Records with volume > 0 BEFORE aggregation: {volume_before}")
print(f"Records with weight = 0/empty BEFORE aggregation: {len(records) - weight_before}")
print(f"Records with volume = 0/empty BEFORE aggregation: {len(records) - volume_before}")

# ── STEP 2: SORT + AGGREGATE ──────────────────────────────────────────────────
print("\n=== STEP 2: SORT + AGGREGATE ===")
records.sort(key=lambda r: r['bl_number'])
records = aggregate(records)

weight_after = sum(1 for r in records if parse_double(r.get('bl_weight','')) > 0)
volume_after = sum(1 for r in records if parse_double(r.get('bl_volume','')) > 0)
print(f"Records with weight > 0 AFTER aggregation: {weight_after}")
print(f"Records with volume > 0 AFTER aggregation: {volume_after}")

# ── STEP 3: XLS EXPORTER ──────────────────────────────────────────────────────
print("\n=== STEP 3: XLS EXPORTER ===")
for r in records:
    xls_exporter(r)

weight_after_export = sum(1 for r in records if r.get('bl_weight') not in (None, '', '0'))
volume_after_export = sum(1 for r in records if r.get('bl_volume') not in (None, '', '0'))
print(f"Records with bl_weight (non-None/0) after xls_exporter: {weight_after_export}")
print(f"Records with bl_volume (non-None/0) after xls_exporter: {volume_after_export}")

# Build sim lookup by (bl_number, blitem_yard_item_number)
sim_lookup = {}
sim_dupes = []
for r in records:
    key = (r['bl_number'].strip(), r['blitem_yard_item_number'].strip())
    if key in sim_lookup:
        sim_dupes.append(key)
    sim_lookup[key] = r
print(f"Total simulated records: {len(records)}")
print(f"Unique (BL, ItemNum) keys in sim: {len(sim_lookup)}")
print(f"Duplicate keys in sim: {len(sim_dupes)}")

# ── LOAD XLS ──────────────────────────────────────────────────────────────────
print("\n=== STEP 4: LOAD XLS ===")
wb = xlrd.open_workbook(XLS_PATH)
ws = wb.sheets()[0]
print(f"Sheet: {ws.name}, rows={ws.nrows}, cols={ws.ncols}")

# Column indices (confirmed from inspection)
COL_BL_NUMBER   = 0
COL_ITEM_NUMBER = 37
COL_BL_WEIGHT   = 23
COL_ITEM_WEIGHT = 45
COL_BL_VOLUME   = 22
COL_ITEM_VOLUME = 44
COL_COMMODITY   = 41
COL_ITEM_TYPE   = 35

def cell_str(ws, row, col):
    v = ws.cell_value(row, col)
    if v is None or v == '': return None
    if isinstance(v, float):
        if v == math.floor(v) and not math.isinf(v):
            return str(int(v))
        return str(v)
    return str(v).strip()

xls_rows = []
for row_idx in range(1, ws.nrows):
    bl_num   = str(ws.cell_value(row_idx, COL_BL_NUMBER)).strip()
    item_num = str(ws.cell_value(row_idx, COL_ITEM_NUMBER)).strip()
    if not bl_num or bl_num == '':
        continue
    xls_rows.append({
        'bl_number':               bl_num,
        'blitem_yard_item_number': item_num,
        'bl_weight':               cell_str(ws, row_idx, COL_BL_WEIGHT),
        'blitem_commodity_weight': cell_str(ws, row_idx, COL_ITEM_WEIGHT),
        'bl_volume':               cell_str(ws, row_idx, COL_BL_VOLUME),
        'blitem_commodity_volume': cell_str(ws, row_idx, COL_ITEM_VOLUME),
        'blitem_commodity':        cell_str(ws, row_idx, COL_COMMODITY),
        'blitem_yard_item_type':   cell_str(ws, row_idx, COL_ITEM_TYPE),
    })

print(f"XLS data rows loaded: {len(xls_rows)}")

# Build XLS lookup
xls_lookup = {}
xls_dupes = []
for r in xls_rows:
    key = (r['bl_number'], r['blitem_yard_item_number'])
    if key in xls_lookup:
        xls_dupes.append(key)
    xls_lookup[key] = r
print(f"Unique (BL, ItemNum) keys in XLS: {len(xls_lookup)}")
print(f"Duplicate keys in XLS: {len(xls_dupes)}")

# ── COMPARE ───────────────────────────────────────────────────────────────────
print("\n=== STEP 5: COMPARE ===")

COMPARE_COLS = [
    ('bl_weight',               'BLWeight'),
    ('blitem_commodity_weight', 'BLItem Commodity Weight'),
    ('bl_volume',               'BLVolume'),
    ('blitem_commodity_volume', 'BLItem Commodity Volume'),
    ('blitem_commodity',        'BLItem Commodity'),
]

stats = {col: {'match': 0, 'mismatch': 0, 'examples': []} for col, _ in COMPARE_COLS}

matched_keys = 0
unmatched_in_xls = 0
xls_only_keys = []

for xrow in xls_rows:
    key = (xrow['bl_number'], xrow['blitem_yard_item_number'])
    if key not in sim_lookup:
        unmatched_in_xls += 1
        if len(xls_only_keys) < 5:
            xls_only_keys.append(key)
        continue
    matched_keys += 1
    srow = sim_lookup[key]

    for col, label in COMPARE_COLS:
        xval = xrow.get(col)
        sval = srow.get(col)
        # Normalize for comparison
        xval_s = str(xval) if xval is not None else 'None'
        sval_s = str(sval) if sval is not None else 'None'

        if xval_s == sval_s:
            stats[col]['match'] += 1
        else:
            stats[col]['mismatch'] += 1
            if len(stats[col]['examples']) < 5:
                stats[col]['examples'].append({
                    'key': key,
                    'sim': sval,
                    'xls': xval,
                })

xls_key_set = set(xls_lookup.keys())
sim_only_keys = [k for k in sim_lookup if k not in xls_key_set]

print(f"Keys matched (sim & xls): {matched_keys}")
print(f"Keys in XLS but NOT in sim: {unmatched_in_xls}")
if xls_only_keys:
    print(f"  First 5 unmatched XLS keys: {xls_only_keys}")
print(f"Keys in sim but NOT in XLS: {len(sim_only_keys)}")

print("\n=== COLUMN-BY-COLUMN RESULTS ===")
for col, label in COMPARE_COLS:
    s = stats[col]
    total = s['match'] + s['mismatch']
    pct = 100*s['match']/total if total > 0 else 0
    print(f"\n--- {label} ---")
    print(f"  Total compared: {total}")
    print(f"  Exact matches:  {s['match']} ({pct:.1f}%)")
    print(f"  Mismatches:     {s['mismatch']}")
    if s['examples']:
        print(f"  Example mismatches (up to 5):")
        for ex in s['examples']:
            print(f"    key={ex['key']}")
            print(f"      sim_value={ex['sim']!r}")
            print(f"      xls_value={ex['xls']!r}")

# ── DETAILED ANALYSIS: Where is weight lost? ──────────────────────────────────
print("\n=== WEIGHT LOSS ANALYSIS ===")

# Check: How many XLS rows have weight > 0 but sim has None/0?
w_xls_has_sim_none = []
w_xls_none_sim_has = []
for xrow in xls_rows:
    key = (xrow['bl_number'], xrow['blitem_yard_item_number'])
    if key not in sim_lookup:
        continue
    srow = sim_lookup[key]
    xw = parse_double(xrow.get('bl_weight') or '0')
    sw = parse_double(srow.get('bl_weight') or '0')
    if xw > 0 and sw == 0:
        w_xls_has_sim_none.append({'key': key, 'xls_w': xrow['bl_weight'], 'sim_w': srow.get('bl_weight')})
    if sw > 0 and xw == 0:
        w_xls_none_sim_has.append({'key': key, 'xls_w': xrow['bl_weight'], 'sim_w': srow.get('bl_weight')})

print(f"Cases where XLS has weight but sim=0/None: {len(w_xls_has_sim_none)}")
for ex in w_xls_has_sim_none[:5]:
    print(f"  key={ex['key']}  xls={ex['xls_w']!r}  sim={ex['sim_w']!r}")

print(f"Cases where sim has weight but XLS=0/None: {len(w_xls_none_sim_has)}")
for ex in w_xls_none_sim_has[:5]:
    print(f"  key={ex['key']}  xls={ex['xls_w']!r}  sim={ex['sim_w']!r}")

# ── VOLUME LOSS ANALYSIS ──────────────────────────────────────────────────────
print("\n=== VOLUME LOSS ANALYSIS ===")
v_xls_has_sim_zero = []
for xrow in xls_rows:
    key = (xrow['bl_number'], xrow['blitem_yard_item_number'])
    if key not in sim_lookup:
        continue
    srow = sim_lookup[key]
    xv = parse_double(xrow.get('bl_volume') or '0')
    sv = parse_double(srow.get('bl_volume') or '0')
    if xv > 0 and sv == 0:
        v_xls_has_sim_zero.append({'key': key, 'xls_v': xrow['bl_volume'], 'sim_v': srow.get('bl_volume')})

print(f"Cases where XLS has volume but sim=0: {len(v_xls_has_sim_zero)}")
for ex in v_xls_has_sim_zero[:5]:
    print(f"  key={ex['key']}  xls={ex['xls_v']!r}  sim={ex['sim_v']!r}")

# ── KEY MISMATCH ROOT CAUSE ────────────────────────────────────────────────────
print("\n=== KEY MISMATCH ROOT CAUSE ===")
print("TXT item numbers (first 10 records):")
for r in sorted(records, key=lambda x: x['bl_number'])[:10]:
    print(f"  bl={r['bl_number']!r}  item={r['blitem_yard_item_number']!r}  weight={r.get('bl_weight')!r}  volume={r.get('bl_volume')!r}")

print("\nXLS rows (first 10):")
for r in xls_rows[:10]:
    print(f"  bl={r['bl_number']!r}  item={r['blitem_yard_item_number']!r}  weight={r.get('bl_weight')!r}  volume={r.get('bl_volume')!r}")

# Look for matching BL numbers between the two sources
txt_bls = {r['bl_number'] for r in records}
xls_bls = {r['bl_number'] for r in xls_rows}
common_bls = txt_bls & xls_bls
print(f"\nTotal unique BL numbers in TXT: {len(txt_bls)}")
print(f"Total unique BL numbers in XLS: {len(xls_bls)}")
print(f"Common BL numbers: {len(common_bls)}")

# For common BLs, check if item numbers match
print("\nFor shared BL numbers - item number comparison (first 10 BLs):")
for bl in sorted(common_bls)[:10]:
    txt_items = [r['blitem_yard_item_number'] for r in records if r['bl_number'] == bl]
    xls_items = [r['blitem_yard_item_number'] for r in xls_rows if r['bl_number'] == bl]
    print(f"  BL={bl}")
    print(f"    TXT items: {txt_items}")
    print(f"    XLS items: {xls_items}")

print("\n=== DONE ===")
