#!/usr/bin/env python3
"""
Deep dive on:
1. TXT row order vs XLS row order (why only 15/460 BL numbers match positionally)
2. Why XLS BLItem Commodity Weight is 0.0 or 0.001 for many rows in multi-item BLs
3. What the correct per-item weight offset should be
4. Volume=0 analysis (93 records)
"""
import math, xlrd
from collections import defaultdict, Counter

TXT_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"
CHARSET = "windows-1252"

def read_field(raw, offset, length):
    if offset >= len(raw): return ''
    end = min(offset + length, len(raw))
    return raw[offset:end].decode(CHARSET, errors='replace').strip()

def strip_leading_zeros(s):
    if not s: return '0'
    r = s.lstrip('0')
    return r if r else '0'

def is_positive_numeric(s):
    if s in (None, '', '0'): return False
    try: return float(s) > 0
    except: return False

def load_raw_records(path):
    records = []
    with open(path, 'rb') as f:
        for i, raw_line in enumerate(f):
            raw = raw_line.rstrip(b'\r\n')
            if not raw: continue
            bl = read_field(raw, 62, 20)
            yard_item = read_field(raw, 126, 20)
            mode = read_field(raw, 61, 1)

            w281  = read_field(raw, 281, 12)
            w1296 = read_field(raw, 1296, 12)
            v293  = read_field(raw, 293, 12)
            v1308 = read_field(raw, 1308, 12)

            # Compute weight
            rw = strip_leading_zeros(w281)
            rwa = strip_leading_zeros(w1296)
            weight = 0.0
            if is_positive_numeric(rw):
                weight = round(float(rw) / 1_000_000.0 * 1000, 3)  # kg
            elif is_positive_numeric(rwa):
                weight = round(float(rwa) / 1_000_000.0 * 1000, 3)

            rv = strip_leading_zeros(v293)
            rva = strip_leading_zeros(v1308)
            vol = 0.0
            if is_positive_numeric(rv):
                vol = round(float(rv) / 1000.0, 3)
            elif is_positive_numeric(rva):
                vol = round(float(rva) / 1000.0, 3)

            records.append({
                'line': i,
                'bl': bl,
                'yard_item': yard_item,
                'mode': mode,
                'weight_kg': weight,
                'volume': vol,
                'raw_w281': w281,
                'raw_w1296': w1296,
                'raw_v293': v293,
                'raw_v1308': v1308,
                'raw': raw,
            })
    return records

def load_xls(path):
    wb = xlrd.open_workbook(path)
    ws = wb.sheets()[0]
    headers = [str(ws.cell_value(0, c)).strip() for c in range(ws.ncols)]
    rows = []
    for r in range(1, ws.nrows):
        row = {}
        for c, h in enumerate(headers):
            ct = ws.cell_type(r, c)
            cv = ws.cell_value(r, c)
            if ct == xlrd.XL_CELL_EMPTY: row[h] = None
            elif ct == xlrd.XL_CELL_NUMBER: row[h] = cv
            else:
                sv = str(cv).strip()
                row[h] = sv if sv else None
        rows.append(row)
    return headers, rows

def main():
    print("Loading data...")
    txt_records = load_raw_records(TXT_PATH)
    headers, xls_rows = load_xls(XLS_PATH)
    print(f"  TXT: {len(txt_records)}, XLS: {len(xls_rows)}")

    # -----------------------------------------------------------------------
    # Build lookups
    # -----------------------------------------------------------------------
    # TXT: bl -> list of records (in TXT order)
    txt_by_bl = defaultdict(list)
    for r in txt_records:
        txt_by_bl[r['bl']].append(r)

    # XLS: bl -> list of rows (in XLS order), with positional index
    xls_by_bl = defaultdict(list)
    for i, r in enumerate(xls_rows):
        bl = r.get('BL Number') or ''
        xls_by_bl[bl].append({'xls_idx': i, 'row': r})

    # -----------------------------------------------------------------------
    # Q1: Why does positional alignment fail?
    # The TXT file is sorted differently from the XLS.
    # -----------------------------------------------------------------------
    print("\n--- ROW ORDER ANALYSIS ---")
    print("First 10 TXT BL numbers (in order):")
    for r in txt_records[:10]:
        print(f"  line={r['line']:4d} bl={r['bl']}")
    print("First 10 XLS BL numbers (in order):")
    for i, r in enumerate(xls_rows[:10]):
        print(f"  row={i:4d}  bl={r.get('BL Number','')}")

    # Find where TXT[0] (S328582473) appears in XLS
    tgt = txt_records[0]['bl']
    xls_pos = [i for i, r in enumerate(xls_rows) if r.get('BL Number') == tgt]
    print(f"\nFirst TXT BL ({tgt}) appears at XLS rows: {xls_pos}")

    # -----------------------------------------------------------------------
    # Q2: For multi-item BLs, what is the correct per-item weight?
    # Check if TXT items appear in same order as XLS items within each BL.
    # -----------------------------------------------------------------------
    print("\n--- PER-ITEM ALIGNMENT FOR MULTI-ITEM BLs ---")
    multi_bls = {bl: rows for bl, rows in xls_by_bl.items() if len(rows) > 1}
    print(f"Multi-item BLs: {len(multi_bls)}")

    # Check alignment within each BL: match by yard_item_number
    aligned_count  = 0
    misaligned_bls = []
    for bl, xls_items in list(multi_bls.items())[:30]:
        txt_items = txt_by_bl.get(bl, [])
        if len(txt_items) != len(xls_items):
            misaligned_bls.append((bl, len(txt_items), len(xls_items)))
            continue
        # Match by yard item number
        xls_yard = [e['row'].get('BLItem YardItemNumber') or '' for e in xls_items]
        txt_yard = [r['yard_item'] for r in txt_items]
        if xls_yard == txt_yard:
            aligned_count += 1
        else:
            misaligned_bls.append((bl, txt_yard, xls_yard))

    print(f"BLs where TXT and XLS items are in same order: {aligned_count}")
    print(f"BLs with misalignment or count mismatch: {len(misaligned_bls)}")
    for info in misaligned_bls[:5]:
        print(f"  {info}")

    # -----------------------------------------------------------------------
    # Q3: Match TXT record to XLS row by (BL, yard_item_number)
    # This gives true per-item comparison
    # -----------------------------------------------------------------------
    print("\n--- ITEM-LEVEL MATCHING (by BL + yard_item_number) ---")
    matched_items = 0
    unmatched_items = 0
    weight_correct = 0
    weight_wrong   = 0
    vol_correct    = 0
    vol_wrong      = 0
    commodity_correct = 0
    commodity_wrong   = 0
    weight_wrong_detail = []
    vol_zero_detail = []
    commodity_wrong_detail = []

    # Build XLS lookup: (bl, yard_item_number) -> xls_row
    xls_by_bl_item = {}
    for i, xrow in enumerate(xls_rows):
        bl   = xrow.get('BL Number') or ''
        ynum = xrow.get('BLItem YardItemNumber') or ''
        key  = (bl, ynum)
        if key not in xls_by_bl_item:
            xls_by_bl_item[key] = xrow
        # if duplicate, keep first occurrence

    for txt in txt_records:
        bl   = txt['bl']
        ynum = txt['yard_item']
        key  = (bl, ynum)

        if key not in xls_by_bl_item:
            unmatched_items += 1
            continue

        matched_items += 1
        xrow = xls_by_bl_item[key]

        # Weight
        sim_w = txt['weight_kg']
        xls_iw = xrow.get('BLItem Commodity Weight')

        if xls_iw is None:
            if sim_w == 0:
                weight_correct += 1
            else:
                weight_wrong += 1
        else:
            if abs(sim_w - xls_iw) < 0.5:
                weight_correct += 1
            else:
                weight_wrong += 1
                weight_wrong_detail.append({
                    'bl': bl, 'yard': ynum,
                    'sim_w': sim_w, 'xls_iw': xls_iw,
                    'xls_bl_w': xrow.get('BLWeight'),
                    'ratio': xls_iw / sim_w if sim_w else None
                })

        # Volume
        sim_v = txt['volume']
        xls_iv = xrow.get('BLItem Commodity Volume')

        if xls_iv is None or xls_iv == 0:
            if sim_v == 0:
                vol_correct += 1
            else:
                vol_wrong += 1
        else:
            if abs(sim_v - xls_iv) < 0.01:
                vol_correct += 1
            else:
                vol_wrong += 1
                vol_zero_detail.append({
                    'bl': bl, 'yard': ynum,
                    'sim_v': sim_v, 'xls_iv': xls_iv
                })

        # Commodity
        sim_c = ''
        mode = txt['mode']
        wkg = sim_w
        if mode in ('R', 'M'):
            if   wkg <= 0:     sim_c = 'VEH 0-1500Kgs'
            elif wkg <= 1500:  sim_c = 'VEH 0-1500Kgs'
            elif wkg <= 3000:  sim_c = 'VEH 1501-3000Kgs'
            elif wkg <= 6000:  sim_c = 'VEH 3001-6000Kgs'
            elif wkg <= 9000:  sim_c = 'VEH 6001-9000Kgs'
            elif wkg <= 30000: sim_c = 'VEH 9001-30000Kgs'
            else:              sim_c = 'VEH +30000Kgs'

        xls_c = xrow.get('BLItem Commodity') or ''
        if xls_c:
            if sim_c == xls_c:
                commodity_correct += 1
            else:
                commodity_wrong += 1
                commodity_wrong_detail.append({
                    'bl': bl, 'yard': ynum,
                    'sim_c': sim_c, 'xls_c': xls_c,
                    'sim_w': sim_w, 'xls_iw': xls_iw
                })

    print(f"Matched (BL + yard_item): {matched_items} / {len(txt_records)}")
    print(f"Unmatched: {unmatched_items}")

    print(f"\n--- PER-ITEM WEIGHT ACCURACY ---")
    print(f"  Correct: {weight_correct}  Wrong: {weight_wrong}")
    pct = weight_correct / (weight_correct + weight_wrong) * 100 if (weight_correct + weight_wrong) else 0
    print(f"  Accuracy: {pct:.1f}%")

    print(f"\nSample weight mismatches (first 15):")
    for d in weight_wrong_detail[:15]:
        xls_iw_str = str(d['xls_iw']) if d['xls_iw'] is not None else 'None'
        ratio_str  = f"{d['ratio']:.2f}" if d['ratio'] else 'N/A'
        print(f"  BL={d['bl']:15s} sim_w={d['sim_w']:8.2f} xls_iw={xls_iw_str:10s} xls_bl_w={d['xls_bl_w']}  ratio={ratio_str}")

    # Count zero-item-weight in XLS for VEHICULE records
    xls_veh_zero_iw = sum(
        1 for xrow in xls_rows
        if xrow.get('BLItem YardItemType') == 'VEHICULE'
        and (xrow.get('BLItem Commodity Weight') or 0) < 0.01
    )
    xls_veh_total = sum(1 for xrow in xls_rows if xrow.get('BLItem YardItemType') == 'VEHICULE')
    print(f"\nXLS VEHICULE rows with item weight < 0.01: {xls_veh_zero_iw} / {xls_veh_total}")

    # Distribution of XLS item weight values for VEHICULE
    xls_veh_weights = [xrow.get('BLItem Commodity Weight') or 0
                       for xrow in xls_rows if xrow.get('BLItem YardItemType') == 'VEHICULE']
    zero_or_trivial = sum(1 for w in xls_veh_weights if w < 1)
    print(f"XLS VEHICULE item weight < 1 kg: {zero_or_trivial} / {xls_veh_total}")

    print(f"\n--- PER-ITEM VOLUME ACCURACY ---")
    print(f"  Correct: {vol_correct}  Wrong: {vol_wrong}")
    pct = vol_correct / (vol_correct + vol_wrong) * 100 if (vol_correct + vol_wrong) else 0
    print(f"  Accuracy: {pct:.1f}%")

    if vol_zero_detail:
        print(f"\nSample volume mismatches (first 10):")
        for d in vol_zero_detail[:10]:
            print(f"  BL={d['bl']:15s} sim_v={d['sim_v']:8.3f} xls_iv={d['xls_iv']}")

    print(f"\n--- PER-ITEM COMMODITY ACCURACY ---")
    print(f"  Correct: {commodity_correct}  Wrong: {commodity_wrong}")
    pct = commodity_correct / (commodity_correct + commodity_wrong) * 100 if (commodity_correct + commodity_wrong) else 0
    print(f"  Accuracy: {pct:.1f}%")
    cd_pairs = Counter((d['sim_c'], d['xls_c']) for d in commodity_wrong_detail)
    print(f"  Mismatch pairs (most common):")
    for (s, x), cnt in cd_pairs.most_common(10):
        print(f"    sim={s!r:25s} xls={x!r:25s} count={cnt}")

    print(f"\nSample commodity mismatches (first 15):")
    for d in commodity_wrong_detail[:15]:
        print(f"  BL={d['bl']:15s} sim_w={d['sim_w']:8.2f} xls_iw={str(d['xls_iw']):10s}  sim_c={d['sim_c']!r}  xls_c={d['xls_c']!r}")

    # -----------------------------------------------------------------------
    # Q4: Weight=None analysis - what do those TXT lines look like?
    # -----------------------------------------------------------------------
    print("\n--- RECORDS WITH weight_kg=0 (no weight found) ---")
    no_weight = [r for r in txt_records if r['weight_kg'] == 0]
    print(f"  Count: {len(no_weight)}")
    print(f"  First 10:")
    for r in no_weight[:10]:
        bl = r['bl']
        xls_key = (bl, r['yard_item'])
        xls_iw = xls_by_bl_item.get(xls_key, {}).get('BLItem Commodity Weight', 'N/A')
        print(f"    BL={bl:15s} mode={r['mode']} raw_w281={r['raw_w281']!r:15s} raw_w1296={r['raw_w1296']!r:15s}  xls_iw={xls_iw}")

    # -----------------------------------------------------------------------
    # Q5: Volume=0 analysis
    # -----------------------------------------------------------------------
    print("\n--- RECORDS WITH volume=0 (no volume found) ---")
    no_vol = [r for r in txt_records if r['volume'] == 0]
    print(f"  Count: {len(no_vol)}")
    print(f"  First 10:")
    for r in no_vol[:10]:
        bl = r['bl']
        xls_key = (bl, r['yard_item'])
        xls_iv = xls_by_bl_item.get(xls_key, {}).get('BLItem Commodity Volume', 'N/A')
        print(f"    BL={bl:15s} mode={r['mode']} raw_v293={r['raw_v293']!r:15s} raw_v1308={r['raw_v1308']!r:15s}  xls_iv={xls_iv}")

    # -----------------------------------------------------------------------
    # Q6: XLS item weight = 0 for multi-item BLs - is this intentional?
    # In the XLS, for a 3-item BL, only ONE item has the full weight,
    # the others have 0 (or 0.001 as placeholder)?
    # -----------------------------------------------------------------------
    print("\n--- XLS ITEM WEIGHT DISTRIBUTION FOR MULTI-ITEM BLs ---")
    for bl, xls_items in list(multi_bls.items())[:8]:
        print(f"\n  BL={bl} ({len(xls_items)} items):")
        for e in xls_items:
            xrow = e['row']
            print(f"    yard={xrow.get('BLItem YardItemNumber',''):22s}  iw={xrow.get('BLItem Commodity Weight','N/A'):10}  iv={xrow.get('BLItem Commodity Volume','N/A'):10}  commodity={xrow.get('BLItem Commodity','')}")
        txt_items_for_bl = txt_by_bl.get(bl, [])
        print(f"    TXT items:")
        for tr in txt_items_for_bl:
            print(f"      yard={tr['yard_item']:22s}  sim_wkg={tr['weight_kg']:8.2f}  sim_vol={tr['volume']:.3f}")

    # -----------------------------------------------------------------------
    # FINAL SUMMARY
    # -----------------------------------------------------------------------
    print("\n" + "="*70)
    print("FINAL ANALYSIS CONCLUSIONS")
    print("="*70)
    total = len(txt_records)
    print(f"""
OBSERVATION 1: The TXT and XLS rows are NOT in the same order.
  - Only 15/460 rows share the same BL number at the same position.
  - Correct matching is by (BL number + yard item number).

OBSERVATION 2: XLS BLWeight = total weight for the whole BL (sum of all items).
  - The current Java code writes per-item weight (from offset 281) as BLWeight.
  - This is WRONG for all multi-item BLs (80 BLs, 295 rows affected).
  - For single-item BLs, it should be correct (165 BLs).

OBSERVATION 3: XLS BLItem Commodity Weight is NOT simply the per-item weight.
  - For multi-item BLs in the XLS, only SOME items have a weight > 0.
  - Many items show 0.0 or 0.001 (placeholder).
  - The sim produces non-zero weight from offset 281 for ALL items.
  => The XLS appears to use a different data source for per-item weight.

OBSERVATION 4: For records with weight_kg=0 ({len(no_weight)} records):
  - These TXT lines have no data at offsets 281 and 1296.
  - XLS has real weights for these (e.g., 1940 kg, 7525 kg).
  => The weight offset for these records may be different.

OBSERVATION 5: For records with volume=0 ({len(no_vol)} records):
  - {len(no_vol)} TXT lines have no data at offsets 293 and 1308.
  - XLS has real volumes for many of these.

OBSERVATION 6 (Leading zeros): Only 1 record is affected (yard item number '001938').
""")

if __name__ == '__main__':
    main()
