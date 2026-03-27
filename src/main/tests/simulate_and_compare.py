#!/usr/bin/env python3
"""
Simulate Java EdiRecord.fromLine() + XlsExporter logic,
then compare against the expected XLS.
"""
import math
import sys
import xlrd

TXT_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"
CHARSET = "windows-1252"

# ---------------------------------------------------------------------------
# EdiRecord.fromLine() simulation
# ---------------------------------------------------------------------------
FIELDS = {
    'bl_number':              (62,   20),
    'bl_volume':              (293,  12),
    'bl_volume_roro':         (1308, 12),
    'bl_weight':              (281,  12),
    'bl_weight_alt':          (1296, 12),
    'blitem_yard_item_number':(126,  20),
    'blitem_barcode':         (126,  20),
    'blitem_chassis_number':  (126,  20),
    'blitem_commodity_weight':(281,  12),
    'blitem_commodity_volume': None,
    'transport_mode':         (61,   1),
}

def read_field(raw, offset, length):
    if offset >= len(raw):
        return ''
    end = min(offset + length, len(raw))
    return raw[offset:end].decode(CHARSET, errors='replace').strip()

def strip_leading_zeros(s):
    if not s:
        return '0'
    r = s.lstrip('0')
    return r if r else '0'

def is_positive_numeric(s):
    if s in (None, '', '0'):
        return False
    try:
        return float(s) > 0
    except Exception:
        return False

def parse_line(raw_bytes):
    data = {}
    for field, pos in FIELDS.items():
        if pos is None:
            data[field] = ''
        else:
            data[field] = read_field(raw_bytes, pos[0], pos[1])

    # Weight
    raw_w281  = strip_leading_zeros(data.get('bl_weight', ''))
    raw_w1296 = strip_leading_zeros(data.get('bl_weight_alt', ''))
    weight = 0.0
    if is_positive_numeric(raw_w281):
        weight = round(float(raw_w281) / 1_000_000.0, 6)
    elif is_positive_numeric(raw_w1296):
        weight = round(float(raw_w1296) / 1_000_000.0, 6)
    data['bl_weight'] = str(weight) if weight > 0 else ''
    data['blitem_commodity_weight'] = data['bl_weight']

    # Volume
    raw_v293  = strip_leading_zeros(data.get('bl_volume', ''))
    raw_v1308 = strip_leading_zeros(data.get('bl_volume_roro', ''))
    vol = 0.0
    if is_positive_numeric(raw_v293):
        vol = round(float(raw_v293) / 1000.0, 3)
    elif is_positive_numeric(raw_v1308):
        vol = round(float(raw_v1308) / 1000.0, 3)
    data['bl_volume'] = str(vol) if vol > 0 else ''
    data['blitem_commodity_volume'] = data['bl_volume']

    # Transport mode / yard item type
    mode = data.get('transport_mode', '').strip()
    yard_type = 'VEHICULE' if mode in ('R', 'M') else 'CONTENEUR'
    data['yard_item_type']      = yard_type
    data['blitem_yard_item_type'] = yard_type

    # Commodity (EdiRecord level, in tonnes)
    if yard_type == 'VEHICULE' and weight > 0:
        if   weight <= 1.5:  commodity = 'VEH 0-1500Kgs'
        elif weight <= 3.0:  commodity = 'VEH 1501-3000Kgs'
        elif weight <= 6.0:  commodity = 'VEH 3001-6000Kgs'
        elif weight <= 9.0:  commodity = 'VEH 6001-9000Kgs'
        elif weight <= 30.0: commodity = 'VEH 9001-30000Kgs'
        else:                commodity = 'VEH +30000Kgs'
        data['blitem_commodity'] = commodity
    else:
        data['blitem_commodity'] = ''

    return data

# ---------------------------------------------------------------------------
# XlsExporter simulation
# ---------------------------------------------------------------------------
def format_num(d):
    if d == math.floor(d) and not math.isinf(d):
        return str(int(d))
    return str(d)

def parse_double(s):
    if not s:
        return 0.0
    try:
        return float(s)
    except Exception:
        return 0.0

def xls_exporter_transform(data):
    data = dict(data)  # copy

    # Weight: tonnes -> kg
    raw_weight = parse_double(data.get('bl_weight', ''))
    data['bl_weight'] = format_num(round(raw_weight * 1000, 2)) if raw_weight > 0 else None

    raw_item_weight = parse_double(data.get('blitem_commodity_weight', ''))
    is_vehicle = data.get('blitem_yard_item_type') == 'VEHICULE'
    item_weight_kg = 0.0
    if raw_item_weight > 0:
        item_weight_kg = round(raw_item_weight * 1000, 2)
        data['blitem_commodity_weight'] = format_num(item_weight_kg)
    else:
        data['blitem_commodity_weight'] = '0' if is_vehicle else None

    # Volume
    raw_volume = parse_double(data.get('bl_volume', ''))
    data['bl_volume'] = format_num(round(raw_volume, 3)) if raw_volume > 0 else '0'

    raw_item_vol = parse_double(data.get('blitem_commodity_volume', ''))
    item_type = data.get('blitem_yard_item_type', '')
    if raw_item_vol > 0:
        data['blitem_commodity_volume'] = format_num(round(raw_item_vol, 3))
    else:
        data['blitem_commodity_volume'] = '0' if item_type in ('CONTENEUR', 'VEHICULE') else None

    # Commodity override (in kg this time)
    if is_vehicle:
        if   item_weight_kg <= 0:     commodity = 'VEH 0-1500Kgs'
        elif item_weight_kg <= 1500:  commodity = 'VEH 0-1500Kgs'
        elif item_weight_kg <= 3000:  commodity = 'VEH 1501-3000Kgs'
        elif item_weight_kg <= 6000:  commodity = 'VEH 3001-6000Kgs'
        elif item_weight_kg <= 9000:  commodity = 'VEH 6001-9000Kgs'
        elif item_weight_kg <= 30000: commodity = 'VEH 9001-30000Kgs'
        else:                         commodity = 'VEH +30000Kgs'
        data['blitem_commodity'] = commodity

    # Leading-zeros bug: identifier fields written via Double.parseDouble() -> strips leading zeros
    for field in ('blitem_yard_item_number', 'blitem_barcode', 'blitem_chassis_number'):
        v = data.get(field, '')
        if v:
            try:
                data[field + '_as_written'] = str(int(float(v)))
            except Exception:
                data[field + '_as_written'] = v
        else:
            data[field + '_as_written'] = v

    return data

# ---------------------------------------------------------------------------
# Parse TXT file
# ---------------------------------------------------------------------------
def load_txt_records(path):
    records = []
    with open(path, 'rb') as f:
        for raw_line in f:
            raw = raw_line.rstrip(b'\r\n')
            if not raw:
                continue
            parsed = parse_line(raw)
            transformed = xls_exporter_transform(parsed)
            records.append(transformed)
    return records

# ---------------------------------------------------------------------------
# Parse XLS file
# ---------------------------------------------------------------------------
def normalise_xls_value(v):
    """Return a string comparable to what the simulation produces."""
    if v is None or v == '':
        return None
    s = str(v).strip()
    if s == '':
        return None
    # If it looks like a float with .0, strip it
    try:
        fv = float(s)
        if fv == math.floor(fv):
            return str(int(fv))
        return s
    except Exception:
        return s

def load_xls(path):
    wb = xlrd.open_workbook(path)
    ws = wb.sheets()[0]

    # Read header row
    headers = [str(ws.cell_value(0, c)).strip() for c in range(ws.ncols)]
    print(f"XLS headers ({len(headers)}): {headers}")

    rows = []
    for r in range(1, ws.nrows):
        row = {}
        for c, h in enumerate(headers):
            cv = ws.cell_value(r, c)
            ct = ws.cell_type(r, c)
            if ct == xlrd.XL_CELL_EMPTY:
                row[h] = None
            elif ct == xlrd.XL_CELL_NUMBER:
                row[h] = cv  # keep as float
            else:
                row[h] = str(cv).strip() if cv != '' else None
        rows.append(row)
    return headers, rows

# ---------------------------------------------------------------------------
# Main comparison
# ---------------------------------------------------------------------------
def main():
    print("Loading TXT records...")
    sim_records = load_txt_records(TXT_PATH)
    print(f"  Parsed {len(sim_records)} records from TXT")

    print("Loading XLS records...")
    headers, xls_rows = load_xls(XLS_PATH)
    print(f"  Loaded {len(xls_rows)} rows from XLS")

    # Show first few XLS rows raw to understand column names
    print("\n--- First 3 XLS rows (raw) ---")
    for row in xls_rows[:3]:
        print(row)

    # Show first few sim records
    print("\n--- First 3 SIM records ---")
    for r in sim_records[:3]:
        print({k: v for k, v in r.items() if k not in ('blitem_yard_item_number_as_written',
                                                         'blitem_barcode_as_written',
                                                         'blitem_chassis_number_as_written')})

    # -----------------------------------------------------------------------
    # Try to identify column mapping by examining headers
    # -----------------------------------------------------------------------
    # Common XLS column names (case-insensitive search)
    def find_col(headers, *candidates):
        hl = [h.lower() for h in headers]
        for c in candidates:
            cl = c.lower()
            for i, h in enumerate(hl):
                if cl in h:
                    return headers[i]
        return None

    col_bl_number   = find_col(headers, 'bl number', 'blnumber', 'bl_number', 'numero bl', 'num bl')
    col_bl_weight   = find_col(headers, 'bl weight', 'blweight', 'bl_weight', 'poids bl')
    col_bl_volume   = find_col(headers, 'bl volume', 'blvolume', 'bl_volume', 'volume bl')
    col_item_weight = find_col(headers, 'commodity weight', 'item weight', 'item_weight', 'poids')
    col_item_volume = find_col(headers, 'commodity volume', 'item volume', 'item_volume', 'volume')
    col_commodity   = find_col(headers, 'commodity', 'marchandise')
    col_yard_num    = find_col(headers, 'yard item number', 'yard_item_number', 'item number')

    print(f"\n--- Column mapping ---")
    print(f"  BL Number   -> {col_bl_number}")
    print(f"  BL Weight   -> {col_bl_weight}")
    print(f"  BL Volume   -> {col_bl_volume}")
    print(f"  Item Weight -> {col_item_weight}")
    print(f"  Item Volume -> {col_item_volume}")
    print(f"  Commodity   -> {col_commodity}")
    print(f"  Yard Num    -> {col_yard_num}")

    # Build XLS lookup by BL number
    xls_by_bl = {}
    if col_bl_number:
        for row in xls_rows:
            raw_bl = row.get(col_bl_number)
            if raw_bl is not None:
                bl_str = str(raw_bl).strip()
                # strip trailing .0 if numeric
                try:
                    bl_str = str(int(float(bl_str)))
                except Exception:
                    pass
                xls_by_bl[bl_str] = row

    print(f"\nXLS BL numbers (first 10): {list(xls_by_bl.keys())[:10]}")
    print(f"SIM BL numbers (first 10): {[r['bl_number'] for r in sim_records[:10]]}")

    # -----------------------------------------------------------------------
    # Comparison stats
    # -----------------------------------------------------------------------
    total = len(sim_records)
    matched_bl = 0

    # BL Weight stats
    weight_correct = 0
    weight_none    = 0
    weight_mismatch= 0
    weight_no_xls  = 0  # XLS has no value but sim has value

    # BL Volume stats
    volume_correct = 0
    volume_zero_sim_but_xls_has_value = 0
    volume_mismatch = 0

    # Item commodity stats
    commodity_correct  = 0
    commodity_mismatch = 0

    # Item weight stats
    item_weight_correct  = 0
    item_weight_mismatch = 0

    # Leading zeros
    leading_zero_affected = 0

    mismatches_detail = []

    for sim in sim_records:
        bl = sim['bl_number']

        # Check leading zeros bug
        for field in ('blitem_yard_item_number', 'blitem_barcode', 'blitem_chassis_number'):
            orig = sim.get(field, '')
            written = sim.get(field + '_as_written', '')
            if orig and written and orig != written and orig.startswith('0'):
                leading_zero_affected += 1
                break  # count record once

        if bl not in xls_by_bl:
            continue

        matched_bl += 1
        xrow = xls_by_bl[bl]

        # Helper: normalise XLS cell to string
        def xls_val(col):
            if col is None:
                return None
            v = xrow.get(col)
            return normalise_xls_value(v)

        # ---- BL Weight ----
        sim_w = sim.get('bl_weight')   # e.g. "1500" or None
        xls_w = xls_val(col_bl_weight)

        if sim_w is None:
            weight_none += 1
            if xls_w is not None:
                mismatches_detail.append({
                    'bl': bl, 'field': 'bl_weight',
                    'sim': sim_w, 'xls': xls_w
                })
        else:
            if xls_w is None:
                weight_no_xls += 1
            elif sim_w == xls_w:
                weight_correct += 1
            else:
                # try numeric comparison
                try:
                    if abs(float(sim_w) - float(xls_w)) < 0.01:
                        weight_correct += 1
                    else:
                        weight_mismatch += 1
                        mismatches_detail.append({
                            'bl': bl, 'field': 'bl_weight',
                            'sim': sim_w, 'xls': xls_w
                        })
                except Exception:
                    weight_mismatch += 1
                    mismatches_detail.append({
                        'bl': bl, 'field': 'bl_weight',
                        'sim': sim_w, 'xls': xls_w
                    })

        # ---- BL Volume ----
        sim_v = sim.get('bl_volume')
        xls_v = xls_val(col_bl_volume)

        if sim_v == '0' or sim_v is None:
            if xls_v is not None and xls_v != '0':
                volume_zero_sim_but_xls_has_value += 1
            else:
                volume_correct += 1
        else:
            if xls_v is None:
                pass
            elif sim_v == xls_v:
                volume_correct += 1
            else:
                try:
                    if abs(float(sim_v) - float(xls_v)) < 0.001:
                        volume_correct += 1
                    else:
                        volume_mismatch += 1
                        mismatches_detail.append({
                            'bl': bl, 'field': 'bl_volume',
                            'sim': sim_v, 'xls': xls_v
                        })
                except Exception:
                    volume_mismatch += 1

        # ---- BLItem Commodity (category) ----
        sim_c = sim.get('blitem_commodity', '')
        xls_c = xls_val(col_commodity)
        if xls_c is not None:
            if sim_c == xls_c:
                commodity_correct += 1
            else:
                commodity_mismatch += 1
                if len(mismatches_detail) < 30:
                    mismatches_detail.append({
                        'bl': bl, 'field': 'blitem_commodity',
                        'sim': sim_c, 'xls': xls_c
                    })

        # ---- BLItem Commodity Weight ----
        sim_iw = sim.get('blitem_commodity_weight')
        xls_iw = xls_val(col_item_weight)
        if xls_iw is not None and sim_iw is not None:
            if sim_iw == xls_iw:
                item_weight_correct += 1
            else:
                try:
                    if abs(float(sim_iw) - float(xls_iw)) < 0.01:
                        item_weight_correct += 1
                    else:
                        item_weight_mismatch += 1
                except Exception:
                    item_weight_mismatch += 1

    # -----------------------------------------------------------------------
    # Print report
    # -----------------------------------------------------------------------
    print("\n" + "="*70)
    print("SIMULATION vs XLS COMPARISON REPORT")
    print("="*70)
    print(f"Total TXT records parsed  : {total}")
    print(f"Records matched to XLS BL : {matched_bl}")
    print(f"XLS rows                  : {len(xls_rows)}")

    print("\n--- BL WEIGHT ---")
    print(f"  Correct (sim == xls)    : {weight_correct}")
    print(f"  sim=None (empty output) : {weight_none}")
    print(f"  sim has value, xls=None : {weight_no_xls}")
    print(f"  Mismatch (both non-null): {weight_mismatch}")
    pct = weight_correct / matched_bl * 100 if matched_bl else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BL VOLUME ---")
    print(f"  Correct (sim == xls)    : {volume_correct}")
    print(f"  sim=0 but XLS has value : {volume_zero_sim_but_xls_has_value}")
    print(f"  Mismatch (both non-zero): {volume_mismatch}")
    pct = volume_correct / matched_bl * 100 if matched_bl else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BL ITEM COMMODITY ---")
    print(f"  Correct                 : {commodity_correct}")
    print(f"  Mismatch                : {commodity_mismatch}")
    pct = commodity_correct / (commodity_correct + commodity_mismatch) * 100 if (commodity_correct + commodity_mismatch) > 0 else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BL ITEM COMMODITY WEIGHT ---")
    print(f"  Correct                 : {item_weight_correct}")
    print(f"  Mismatch                : {item_weight_mismatch}")
    pct = item_weight_correct / (item_weight_correct + item_weight_mismatch) * 100 if (item_weight_correct + item_weight_mismatch) > 0 else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- LEADING ZEROS BUG ---")
    print(f"  Records affected        : {leading_zero_affected}")

    if mismatches_detail:
        print(f"\n--- SAMPLE MISMATCHES (first 20) ---")
        for m in mismatches_detail[:20]:
            print(f"  BL={m['bl']!s:25s}  field={m['field']!s:30s}  sim={str(m['sim'])!s:15s}  xls={str(m['xls'])!s:15s}")

    # Extra: show commodity breakdown for mismatches
    print("\n--- COMMODITY MISMATCH BREAKDOWN ---")
    from collections import Counter
    cmod_pairs = Counter()
    for m in mismatches_detail:
        if m['field'] == 'blitem_commodity':
            cmod_pairs[(m['sim'] or '', m['xls'] or '')] += 1
    for (s, x), cnt in cmod_pairs.most_common(10):
        print(f"  sim={s!r:25s}  xls={x!r:25s}  count={cnt}")

    # Show weight mismatches sample
    print("\n--- WEIGHT MISMATCH DETAIL (first 10) ---")
    wm = [m for m in mismatches_detail if m['field'] == 'bl_weight'][:10]
    for m in wm:
        print(f"  BL={m['bl']!s:25s}  sim={str(m['sim'])!s:15s}  xls={str(m['xls'])!s:15s}")

    # Also compute: how many sim records output bl_weight=None total
    total_weight_none_overall = sum(1 for r in sim_records if r.get('bl_weight') is None)
    total_weight_has_value    = sum(1 for r in sim_records if r.get('bl_weight') is not None)
    print(f"\n--- OVERALL SIM STATS (all {total} records) ---")
    print(f"  bl_weight = None        : {total_weight_none_overall}")
    print(f"  bl_weight has value     : {total_weight_has_value}")
    total_vol_zero = sum(1 for r in sim_records if r.get('bl_volume') in ('0', None, ''))
    total_vol_val  = sum(1 for r in sim_records if r.get('bl_volume') not in ('0', None, ''))
    print(f"  bl_volume = 0 or empty  : {total_vol_zero}")
    print(f"  bl_volume has value     : {total_vol_val}")
    veh = sum(1 for r in sim_records if r.get('blitem_yard_item_type') == 'VEHICULE')
    con = sum(1 for r in sim_records if r.get('blitem_yard_item_type') == 'CONTENEUR')
    print(f"  VEHICULE records        : {veh}")
    print(f"  CONTENEUR records       : {con}")

    # Commodity distribution in sim
    print("\n--- SIM COMMODITY DISTRIBUTION ---")
    from collections import Counter
    cd = Counter(r.get('blitem_commodity', '') for r in sim_records)
    for k, v in cd.most_common():
        print(f"  {k!r:30s}: {v}")

    # XLS commodity distribution
    if col_commodity:
        print("\n--- XLS COMMODITY DISTRIBUTION ---")
        xcd = Counter()
        for row in xls_rows:
            v = row.get(col_commodity)
            xcd[normalise_xls_value(v) or ''] += 1
        for k, v in xcd.most_common():
            print(f"  {k!r:30s}: {v}")

if __name__ == '__main__':
    main()
