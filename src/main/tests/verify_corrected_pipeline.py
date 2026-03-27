#!/usr/bin/env python3
"""
Full verification of corrected pipeline vs XLS expected output.
Corrected pipeline: divisor=1000 (grams->kg), no x1000 in XlsExporter.
"""

import re
import math
import struct
from pathlib import Path

# ── Helpers ──────────────────────────────────────────────────────────────────

def round_to(val, decimals):
    factor = 10 ** decimals
    return math.floor(val * factor + 0.5) / factor

def strip_leading_zeros(s):
    if not s:
        return "0"
    r = s.lstrip('0')
    return r if r else "0"

def is_positive_numeric(s):
    if not s or s == "0":
        return False
    try:
        return float(s) > 0
    except ValueError:
        return False

def is_numeric(s):
    if not s:
        return False
    try:
        float(s)
        return True
    except ValueError:
        return False

def format_double(d):
    s = f"{d:.6f}"
    s = s.rstrip('0').rstrip('.')
    return s

def parse_double(s):
    if not s:
        return 0.0
    try:
        return float(s)
    except (ValueError, TypeError):
        return 0.0

def format_num(d):
    if d == math.floor(d) and not math.isinf(d):
        return str(int(d))
    return str(d)

GRADE_CODE_1 = re.compile(r'^[YN]?[A-Z]*GR(?:AD|ADE|ADES?)$')
GRADE_CODE_2 = re.compile(r'^[A-Z]*GRADE?$')

def is_grade_code(val):
    v = val.strip().rstrip('| ')
    return bool(GRADE_CODE_1.match(v) or GRADE_CODE_2.match(v))

# ── Field offsets (from EdiRecord.java) ──────────────────────────────────────
FIELDS = {
    "bl_number":                    (62,   20),
    "import_export_raw":            (694,  10),
    "call_number":                  (21,   10),
    "manifest":                     (926,  35),
    "number_of_yard_items":         (1757,  5),
    "number_of_packages":           (1757,  5),
    "transport_mode":               (61,    1),
    "consignee":                    (926,  35),
    "bl_volume":                    (293,  12),
    "bl_volume_roro":               (1308, 12),
    "bl_weight":                    (281,  12),
    "bl_weight_alt":                (1296, 12),
    "port_of_loading":              (31,    5),
    "reception_location":           (46,    5),
    "transshipment_port_1":         (36,    5),
    "transshipment_port_2":         (41,    5),
    "agent_name":                   (1111, 35),
    "blitem_comment":               (221,  35),
    "blitem_yard_item_number":      (126,  20),
    "blitem_yard_item_code":        (146,  35),
    "blitem_commodity_weight":      (281,  12),
    "blitem_seal_number_1":         (351,  70),
    "blitem_barcode":               (126,  20),
    "blitem_vehicle_model":         (221,  35),
    "blitem_chassis_number":        (126,  20),
    "shipper_name":                 (741,  35),
    "adresse_2":                    (961,  35),
    "adresse_3":                    (996,  35),
    "adresse_4":                    (1031, 35),
    "adresse_5":                    (1066, 35),
    "notify1":                      (1111, 35),
    "notify2":                      (1146, 35),
    "notify3":                      (1181, 35),
    "notify4":                      (1216, 35),
    "notify5":                      (1251, 35),
}

def extract_field(raw_bytes, offset, length):
    if offset >= len(raw_bytes):
        return ""
    end = min(offset + length, len(raw_bytes))
    return raw_bytes[offset:end].decode('windows-1252', errors='replace').strip()

def is_valid_utf8(data):
    try:
        data.decode('utf-8')
        return True
    except UnicodeDecodeError:
        return False

# ── EdiRecord.from_line (CORRECTED pipeline) ──────────────────────────────────

def from_line(raw_bytes, src_charset='windows-1252'):
    data = {}

    def get_field(offset, length):
        if offset >= len(raw_bytes):
            return ""
        end = min(offset + length, len(raw_bytes))
        return raw_bytes[offset:end].decode(src_charset, errors='replace').strip()

    # Raw field extraction
    data['bl_number']               = get_field(62,   20)
    data['import_export_raw']       = get_field(694,  10)
    data['call_number']             = get_field(21,   10)
    data['manifest']                = get_field(926,  35)
    data['number_of_yard_items']    = get_field(1757,  5)
    data['number_of_packages']      = get_field(1757,  5)
    data['transport_mode']          = get_field(61,    1)
    data['consignee']               = get_field(926,  35)
    data['bl_volume']               = get_field(293,  12)
    data['bl_volume_roro']          = get_field(1308, 12)
    data['bl_weight']               = get_field(281,  12)
    data['bl_weight_alt']           = get_field(1296, 12)
    data['port_of_loading']         = get_field(31,    5)
    data['reception_location']      = get_field(46,    5)
    data['transshipment_port_1']    = get_field(36,    5)
    data['transshipment_port_2']    = get_field(41,    5)
    data['agent_name']              = get_field(1111, 35)
    data['blitem_comment']          = get_field(221,  35)
    data['blitem_yard_item_number'] = get_field(126,  20)
    data['blitem_yard_item_code']   = get_field(146,  35)
    data['blitem_commodity_weight'] = get_field(281,  12)
    data['blitem_seal_number_1']    = get_field(351,  70)
    data['blitem_barcode']          = get_field(126,  20)
    data['blitem_vehicle_model']    = get_field(221,  35)
    data['blitem_chassis_number']   = get_field(126,  20)
    data['shipper_name']            = get_field(741,  35)
    data['adresse_2']               = get_field(961,  35)
    data['adresse_3']               = get_field(996,  35)
    data['adresse_4']               = get_field(1031, 35)
    data['adresse_5']               = get_field(1066, 35)
    data['notify1']                 = get_field(1111, 35)
    data['notify2']                 = get_field(1146, 35)
    data['notify3']                 = get_field(1181, 35)
    data['notify4']                 = get_field(1216, 35)
    data['notify5']                 = get_field(1251, 35)

    # BL Number
    data['bl_number'] = data['bl_number'].strip()

    # CORRECTED: Weight divisor = 1000 (grams -> kg)
    raw_w281  = strip_leading_zeros(data.get('bl_weight', ''))
    raw_w1296 = strip_leading_zeros(data.get('bl_weight_alt', ''))
    weight = 0.0
    if is_positive_numeric(raw_w281):
        weight = round_to(float(raw_w281) / 1_000.0, 3)
    elif is_positive_numeric(raw_w1296):
        weight = round_to(float(raw_w1296) / 1_000.0, 3)
    weight_str = format_double(weight) if weight > 0 else ""
    data['bl_weight']               = weight_str
    data['blitem_commodity_weight'] = weight_str
    del data['bl_weight_alt']

    # Volume: divisor = 1000 (unchanged)
    raw_v293  = strip_leading_zeros(data.get('bl_volume', ''))
    raw_v1308 = strip_leading_zeros(data.get('bl_volume_roro', ''))
    vol = 0.0
    if is_positive_numeric(raw_v293):
        vol = round_to(float(raw_v293) / 1000.0, 3)
    elif is_positive_numeric(raw_v1308):
        vol = round_to(float(raw_v1308) / 1000.0, 3)
    vol_str = format_double(vol) if vol > 0 else ""
    data['bl_volume']               = vol_str
    data['blitem_commodity_volume'] = vol_str
    del data['bl_volume_roro']

    # Number of yard items
    nyi_raw = data.get('number_of_yard_items', '').strip()
    nyi_val = nyi_raw if is_numeric(nyi_raw) else "1"
    data['number_of_yard_items'] = nyi_val
    data['number_of_packages']   = nyi_val

    # ImportExport
    raw_ie = data.get('import_export_raw', '').strip()
    if raw_ie.startswith('TS'):
        import_export = 'TRANSBO'
    elif raw_ie.startswith('TR'):
        import_export = 'IMPORT TRANSIT'
    else:
        import_export = 'IMPORT'
    data['import_export'] = import_export
    del data['import_export_raw']

    # Final destination country = adresse_5
    data['final_destination_country'] = data.get('adresse_5', '').strip()

    # YardItemType from transport_mode
    mode = data.get('transport_mode', '').strip()
    yard_type = 'VEHICULE' if mode in ('R', 'M') else 'CONTENEUR'
    data['yard_item_type']       = yard_type
    data['blitem_yard_item_type'] = yard_type

    # AllowInvalid
    data['blitem_allow_invalid'] = 'VRAI'

    # Commodity thresholds in kg (CORRECTED: weight already in kg)
    if yard_type == 'VEHICULE' and weight > 0:
        if   weight <= 1500:  commodity = 'VEH 0-1500Kgs'
        elif weight <= 3000:  commodity = 'VEH 1501-3000Kgs'
        elif weight <= 6000:  commodity = 'VEH 3001-6000Kgs'
        elif weight <= 9000:  commodity = 'VEH 6001-9000Kgs'
        elif weight <= 30000: commodity = 'VEH 9001-30000Kgs'
        else:                 commodity = 'VEH +30000Kgs'
        data['blitem_commodity'] = commodity
    else:
        data['blitem_commodity'] = ''

    # Seals
    seal_raw = data.get('blitem_seal_number_1', '').strip()
    if mode in ('R', 'M'):
        parts = [p.strip() for p in seal_raw.split('|') if p.strip()]
        data['blitem_seal_number_1'] = (parts[0] + '|') if len(parts) > 0 else ''
        data['blitem_seal_number_2'] = (parts[1] + '|') if len(parts) > 1 else ''
    else:
        data['blitem_seal_number_1'] = seal_raw
        data['blitem_seal_number_2'] = seal_raw

    # Empty fields
    data['blitem_hs_code']          = ''
    data['blitem_gross_weight']     = ''
    data['freight_prepaid_collect'] = ''
    data['shipping_line_export_bl'] = ''
    data['is_transfer']             = ''
    data['blitem_hazardous_class']  = ''
    data['attach_to_bl']            = ''

    return data, weight  # return weight for commodity recalc in exporter

# ── EdiParser (aggregation) ───────────────────────────────────────────────────

def parse_file(filepath):
    with open(filepath, 'rb') as f:
        content = f.read()

    src_charset = 'utf-8' if is_valid_utf8(content) else 'windows-1252'

    records = []
    weights = []  # track per-line weight for commodity recalc
    start = 0
    for i in range(len(content) + 1):
        if i == len(content) or content[i] == ord('\n'):
            length = i - start
            if length > 0 and content[start + length - 1] == ord('\r'):
                length -= 1
            if length >= 400:
                line_bytes = content[start:start + length]
                type_str = line_bytes[:5].decode('ascii', errors='replace').strip()
                if type_str and all(c.isalnum() for c in type_str):
                    rec, w = from_line(line_bytes, src_charset)
                    records.append(rec)
                    weights.append(w)
            start = i + 1

    # Aggregation
    bl_counts  = {}
    bl_volumes = {}
    bl_weights = {}
    for rec in records:
        bl = rec.get('bl_number', '')
        bl_counts[bl]  = bl_counts.get(bl, 0) + 1
        bl_volumes[bl] = bl_volumes.get(bl, 0.0) + parse_double(rec.get('bl_volume', ''))
        bl_weights[bl] = bl_weights.get(bl, 0.0) + parse_double(rec.get('bl_weight', ''))

    for i, rec in enumerate(records):
        bl    = rec.get('bl_number', '')
        count = bl_counts.get(bl, 1)

        if count > 1:
            rec['number_of_yard_items'] = str(count)
            rec['number_of_packages']   = str(count)

            total_vol = round_to(bl_volumes.get(bl, 0.0), 6)
            if total_vol > 0:
                rec['bl_volume'] = format_double(total_vol)
                # blitem_commodity_volume keeps individual value

            total_wgt = round_to(bl_weights.get(bl, 0.0), 6)
            if total_wgt > 0:
                rec['bl_weight'] = format_double(total_wgt)
                # blitem_commodity_weight keeps individual value

        # BUG-03: vehicles R - seal_number_1 may contain vehicle make
        mode = rec.get('transport_mode', '')
        if mode == 'R':
            seal1 = rec.get('blitem_seal_number_1', '').strip()
            if seal1:
                seal_clean = seal1.rstrip('| ')
                model      = rec.get('blitem_vehicle_model', '').strip()
                words      = model.split()
                first_word = words[0] if words else ''
                if first_word and (
                    seal_clean.lower() == first_word.lower() or
                    (len(seal_clean) >= 4 and first_word.lower().startswith(seal_clean.lower()))
                ):
                    rec['blitem_seal_number_1'] = ''

            # BUG-D: seal_number_2 may contain parasitic grade code
            seal2c = rec.get('blitem_seal_number_2', '')
            if seal2c:
                m = re.match(r'^(\S+)\s{2,}.*[A-Z]{3,}\|$', seal2c.strip())
                if m:
                    rec['blitem_seal_number_2'] = m.group(1)
                else:
                    seal2_clean = seal2c.strip().rstrip('| ')
                    if is_grade_code(seal2_clean):
                        rec['blitem_seal_number_2'] = ''

        # BUG-E: containers - extract real seal from 70-char field
        if mode != 'R':
            raw_seal = rec.get('blitem_seal_number_1', '')
            s1 = parse_container_seal(raw_seal)
            rec['blitem_seal_number_1'] = s1
            rec['blitem_seal_number_2'] = s1

        # BUG-10: final_destination_country must not start with 'TRANSIT:'
        fdc = rec.get('final_destination_country', '').strip()
        if fdc.startswith('TRANSIT:'):
            rec['final_destination_country'] = ''

        # BUG-F: phone numbers
        phone_fields = ['adresse_2', 'adresse_3', 'adresse_4', 'adresse_5',
                        'notify2', 'notify3', 'notify4', 'notify5']
        for f in phone_fields:
            val = rec.get(f, '').strip()
            if re.match(r'^[1-9][0-9]{7,14}$', val):
                rec[f] = '+' + val
            elif re.match(r'^[a-z]{1,2} [A-Z].{5,}$', val):
                rec[f] = re.sub(r'^[a-z]{1,2} ', '', val)

        # BUG-H: number_of_yard_items fix
        bl_count = bl_counts.get(bl, 1)
        if bl_count == 1:
            rec['number_of_yard_items'] = '1'
            rec['number_of_packages']   = '1'
        else:
            try:
                nyi = int(rec.get('number_of_yard_items', '1'))
            except ValueError:
                nyi = 1
            if nyi > 999:
                rec['number_of_yard_items'] = '1'
                rec['number_of_packages']   = '1'

    # Sort by bl_number
    records.sort(key=lambda r: r.get('bl_number', ''))

    return records

def parse_container_seal(raw):
    raw = raw.strip()
    if not raw:
        return ''
    pipe_pos = raw.find('|')
    if pipe_pos < 0:
        return '' if is_grade_code(raw) else raw
    main_seal = raw[:pipe_pos].strip()
    if is_grade_code(main_seal):
        return ''
    after_pipe = raw[pipe_pos + 1:].lstrip()
    first_word = re.split(r'[ \t]', after_pipe)[0]
    if not first_word or is_grade_code(first_word):
        return main_seal + '|'
    return main_seal + '|' + first_word

# ── XlsExporter simulation (CORRECTED: no x1000) ─────────────────────────────

def xls_exporter(records):
    """Simulate XlsExporter and return list of dicts with the 5 key fields."""
    results = []
    for rec in records:
        data = dict(rec)

        # CORRECTED: weight already in kg, no x1000
        raw_weight = parse_double(data.get('bl_weight', ''))
        data['bl_weight'] = format_num(round_to(raw_weight, 2)) if raw_weight > 0 else None

        raw_item_weight = parse_double(data.get('blitem_commodity_weight', ''))
        is_vehicle      = data.get('blitem_yard_item_type', '') == 'VEHICULE'
        item_weight_kg  = 0.0
        if raw_item_weight > 0:
            item_weight_kg = round_to(raw_item_weight, 2)
            data['blitem_commodity_weight'] = format_num(item_weight_kg)
        else:
            data['blitem_commodity_weight'] = '0' if is_vehicle else None

        # Volume: unchanged
        raw_volume = parse_double(data.get('bl_volume', ''))
        data['bl_volume'] = format_num(round_to(raw_volume, 3)) if raw_volume > 0 else '0'

        raw_item_vol = parse_double(data.get('blitem_commodity_volume', ''))
        item_type    = data.get('blitem_yard_item_type', '')
        if raw_item_vol > 0:
            data['blitem_commodity_volume'] = format_num(round_to(raw_item_vol, 3))
        else:
            if item_type in ('CONTENEUR', 'VEHICULE'):
                data['blitem_commodity_volume'] = '0'
            else:
                data['blitem_commodity_volume'] = None

        # Recalc commodity from individual weight
        if is_vehicle:
            if   item_weight_kg <= 0:     commodity = 'VEH 0-1500Kgs'
            elif item_weight_kg <= 1500:  commodity = 'VEH 0-1500Kgs'
            elif item_weight_kg <= 3000:  commodity = 'VEH 1501-3000Kgs'
            elif item_weight_kg <= 6000:  commodity = 'VEH 3001-6000Kgs'
            elif item_weight_kg <= 9000:  commodity = 'VEH 6001-9000Kgs'
            elif item_weight_kg <= 30000: commodity = 'VEH 9001-30000Kgs'
            else:                         commodity = 'VEH +30000Kgs'
            data['blitem_commodity'] = commodity

        results.append(data)
    return results

# ── Read XLS expected ─────────────────────────────────────────────────────────

def read_xls(filepath):
    import xlrd
    wb = xlrd.open_workbook(filepath)
    ws = wb.sheet_by_index(0)

    headers = []
    for c in range(ws.ncols):
        headers.append(ws.cell_value(0, c))

    def col(name):
        try:
            return headers.index(name)
        except ValueError:
            return None

    col_bl_number        = col('BL Number')
    col_blitem_num       = col('BLItem YardItemNumber')
    col_bl_weight        = col('BLWeight')
    col_blitem_weight    = col('BLItem Commodity Weight')
    col_bl_volume        = col('BLVolume')
    col_blitem_vol       = col('BLItem Commodity Volume')
    col_blitem_commodity = col('BLItem Commodity')

    rows = []
    for r in range(1, ws.nrows):
        def cell_val(c_idx):
            if c_idx is None:
                return None
            cell = ws.cell(r, c_idx)
            if cell.ctype == xlrd.XL_CELL_EMPTY:
                return None
            if cell.ctype in (xlrd.XL_CELL_NUMBER,):
                return cell.value
            return str(cell.value).strip()

        rows.append({
            'bl_number':        str(cell_val(col_bl_number) or '').strip(),
            'blitem_num':       str(cell_val(col_blitem_num) or '').strip(),
            'bl_weight':        cell_val(col_bl_weight),
            'blitem_weight':    cell_val(col_blitem_weight),
            'bl_volume':        cell_val(col_bl_volume),
            'blitem_volume':    cell_val(col_blitem_vol),
            'blitem_commodity': str(cell_val(col_blitem_commodity) or '').strip(),
        })

    return rows

# ── Main comparison ───────────────────────────────────────────────────────────

def normalize_num(v):
    """Convert value to float, None if empty/None."""
    if v is None or v == '':
        return None
    try:
        return float(v)
    except (ValueError, TypeError):
        return None

def vals_match(a, b, tol=0.01):
    """Returns True if both are None/empty or differ by < tol."""
    a_f = normalize_num(a)
    b_f = normalize_num(b)
    if a_f is None and b_f is None:
        return True
    if a_f is None or b_f is None:
        # one is empty, other is 0 → treat as match
        if (a_f is None and b_f == 0.0) or (b_f is None and a_f == 0.0):
            return True
        return False
    return abs(a_f - b_f) < tol

def strs_match(a, b):
    """Exact string match (case-sensitive, stripped)."""
    a_s = str(a).strip() if a is not None else ''
    b_s = str(b).strip() if b is not None else ''
    return a_s == b_s

def main():
    txt_path = "C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
    xls_path = "C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"

    print("Parsing TXT file...")
    records = parse_file(txt_path)
    print(f"  Parsed {len(records)} records")

    print("Running XlsExporter simulation...")
    exported = xls_exporter(records)
    print(f"  Exported {len(exported)} rows")

    # Build lookup: (bl_number, blitem_yard_item_number) -> row
    pipeline_map = {}
    for row in exported:
        key = (row.get('bl_number', '').strip(), row.get('blitem_yard_item_number', '').strip())
        pipeline_map[key] = row

    print("\nReading XLS expected...")
    xls_rows = read_xls(xls_path)
    print(f"  Read {len(xls_rows)} XLS rows")

    # ── Comparison ────────────────────────────────────────────────────────────
    columns = [
        ('BLWeight',              'bl_weight',              'blitem_weight',    True),
        ('BLItem Commodity Weight','blitem_commodity_weight','blitem_weight',   True),
        ('BLVolume',              'bl_volume',              'bl_volume',        True),
        ('BLItem Commodity Volume','blitem_commodity_volume','blitem_volume',   True),
        ('BLItem Commodity',      'blitem_commodity',       'blitem_commodity', False),
    ]

    # Map xls column name -> (pipeline_key, xls_key, is_numeric)
    col_defs = {
        'BLWeight':               ('bl_weight',               'bl_weight',        True),
        'BLItem Commodity Weight': ('blitem_commodity_weight', 'blitem_weight',    True),
        'BLVolume':               ('bl_volume',               'bl_volume',        True),
        'BLItem Commodity Volume': ('blitem_commodity_volume', 'blitem_volume',    True),
        'BLItem Commodity':        ('blitem_commodity',        'blitem_commodity', False),
    }

    stats = {col_name: {'match': 0, 'mismatch': 0, 'unmatched': 0, 'examples': []}
             for col_name in col_defs}
    stats_exact = {col_name: {'exact': 0, 'near': 0} for col_name in col_defs}

    unmatched_keys = set()

    for xls_row in xls_rows:
        bl     = xls_row['bl_number']
        bl_item = xls_row['blitem_num']
        key    = (bl, bl_item)

        if key not in pipeline_map:
            unmatched_keys.add(key)
            for col_name in col_defs:
                stats[col_name]['unmatched'] += 1
            continue

        pipe_row = pipeline_map[key]

        for col_name, (p_key, x_key, is_num) in col_defs.items():
            pipe_val = pipe_row.get(p_key)
            xls_val  = xls_row[x_key]

            if is_num:
                matched = vals_match(pipe_val, xls_val)
                # Exact match check
                p_f = normalize_num(pipe_val)
                x_f = normalize_num(xls_val)
                if p_f is not None and x_f is not None and p_f == x_f:
                    stats_exact[col_name]['exact'] += 1
                elif matched:
                    stats_exact[col_name]['near'] += 1
            else:
                matched = strs_match(pipe_val, xls_val)

            if matched:
                stats[col_name]['match'] += 1
            else:
                stats[col_name]['mismatch'] += 1
                if len(stats[col_name]['examples']) < 3:
                    stats[col_name]['examples'].append({
                        'bl': bl, 'blitem': bl_item,
                        'pipeline': pipe_val, 'xls': xls_val
                    })

    # ── Print results ─────────────────────────────────────────────────────────
    print("\n" + "="*70)
    print("CORRECTED PIPELINE vs XLS - COMPARISON RESULTS")
    print("="*70)
    print(f"Pipeline rows: {len(exported)}")
    print(f"XLS rows:      {len(xls_rows)}")
    print(f"Unmatched XLS keys (not found in pipeline): {len(unmatched_keys)}")
    if unmatched_keys:
        for k in sorted(unmatched_keys)[:5]:
            print(f"  {k}")

    for col_name in col_defs:
        s = stats[col_name]
        se = stats_exact[col_name]
        total = s['match'] + s['mismatch'] + s['unmatched']
        _, _, is_num = col_defs[col_name]
        print(f"\n--- {col_name} ---")
        print(f"  Match (tol<0.01): {s['match']}  |  Mismatch: {s['mismatch']}  |  Unmatched key: {s['unmatched']}")
        if is_num:
            print(f"  Exact match (==): {se['exact']}  |  Near match (<0.01): {se['near']}")
        if s['examples']:
            print(f"  Mismatch examples (up to 3):")
            for ex in s['examples']:
                print(f"    BL={ex['bl']!r:30s}  BLItem={ex['blitem']!r:22s}  pipeline={ex['pipeline']!r}  xls={ex['xls']!r}")

    print("\n" + "="*70)

if __name__ == '__main__':
    main()
