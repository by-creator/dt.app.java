#!/usr/bin/env python3
"""
Deep analysis of simulation vs XLS.
Key observations from v1:
  1. BL weight/volume mismatch - XLS values are SUMS of multiple items per BL
     (the Java code writes the per-item weight to the BL-level weight)
  2. BLItem Commodity column is at XLS col 'BLItem Commodity', not 'Commodity'
  3. Multi-item BLs: XLS has one row per item, TXT also has one record per item
     but we matched by BL number which collides
"""
import math
import sys
import xlrd
from collections import defaultdict, Counter

TXT_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"
XLS_PATH = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls"
CHARSET = "windows-1252"

# ---------------------------------------------------------------------------
# EdiRecord.fromLine() simulation
# ---------------------------------------------------------------------------
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
    def rf(offset, length):
        return read_field(raw_bytes, offset, length)

    bl_number   = rf(62, 20)
    bl_volume   = rf(293, 12)
    bl_vol_roro = rf(1308, 12)
    bl_weight   = rf(281, 12)
    bl_wt_alt   = rf(1296, 12)
    yard_item_number = rf(126, 20)
    transport_mode   = rf(61, 1)

    # Weight
    raw_w = strip_leading_zeros(bl_weight)
    raw_wa = strip_leading_zeros(bl_wt_alt)
    weight = 0.0
    if is_positive_numeric(raw_w):
        weight = round(float(raw_w) / 1_000_000.0, 6)
    elif is_positive_numeric(raw_wa):
        weight = round(float(raw_wa) / 1_000_000.0, 6)

    # Volume
    raw_v = strip_leading_zeros(bl_volume)
    raw_vr = strip_leading_zeros(bl_vol_roro)
    vol = 0.0
    if is_positive_numeric(raw_v):
        vol = round(float(raw_v) / 1000.0, 3)
    elif is_positive_numeric(raw_vr):
        vol = round(float(raw_vr) / 1000.0, 3)

    # Transport mode
    mode = transport_mode.strip()
    yard_type = 'VEHICULE' if mode in ('R', 'M') else 'CONTENEUR'

    # XlsExporter transform
    # Weight: tonnes -> kg
    bl_weight_kg = round(weight * 1000, 2) if weight > 0 else None
    item_weight_kg = bl_weight_kg  # same source in EdiRecord

    bl_vol_out = round(vol, 3) if vol > 0 else 0.0
    item_vol_out = bl_vol_out

    # Commodity
    if yard_type == 'VEHICULE':
        ikw = item_weight_kg if item_weight_kg else 0.0
        if   ikw <= 0:     commodity = 'VEH 0-1500Kgs'
        elif ikw <= 1500:  commodity = 'VEH 0-1500Kgs'
        elif ikw <= 3000:  commodity = 'VEH 1501-3000Kgs'
        elif ikw <= 6000:  commodity = 'VEH 3001-6000Kgs'
        elif ikw <= 9000:  commodity = 'VEH 6001-9000Kgs'
        elif ikw <= 30000: commodity = 'VEH 9001-30000Kgs'
        else:              commodity = 'VEH +30000Kgs'
    else:
        commodity = ''

    # Format output
    def fnum(d):
        if d is None: return None
        if d == math.floor(d) and not math.isinf(d):
            return str(int(d))
        return str(d)

    return {
        'bl_number': bl_number,
        'yard_item_number': yard_item_number,
        'yard_item_type': yard_type,
        'bl_weight_kg': bl_weight_kg,
        'bl_volume': bl_vol_out,
        'item_weight_kg': item_weight_kg,
        'item_volume': item_vol_out,
        'commodity': commodity,
        'transport_mode': mode,
        # raw for leading zero check
        '_raw_yard_item_number': yard_item_number,
    }

def load_txt_records(path):
    records = []
    with open(path, 'rb') as f:
        for raw_line in f:
            raw = raw_line.rstrip(b'\r\n')
            if not raw:
                continue
            records.append(parse_line(raw))
    return records

# ---------------------------------------------------------------------------
# XLS loading
# ---------------------------------------------------------------------------
def xls_num(v):
    """Normalise XLS cell: float cells -> strip .0 or keep decimal"""
    if v is None:
        return None
    if isinstance(v, float):
        if v == math.floor(v):
            return int(v)
        return v
    return v

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
            if ct == xlrd.XL_CELL_EMPTY:
                row[h] = None
            elif ct == xlrd.XL_CELL_NUMBER:
                row[h] = cv
            else:
                sv = str(cv).strip()
                row[h] = sv if sv else None
        rows.append(row)
    return headers, rows

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main():
    print("Loading records...")
    sim_records = load_txt_records(TXT_PATH)
    headers, xls_rows = load_xls(XLS_PATH)
    print(f"  TXT: {len(sim_records)} records, XLS: {len(xls_rows)} rows")

    # -----------------------------------------------------------------------
    # Understanding the structure: BL numbers with multiple rows
    # -----------------------------------------------------------------------
    bl_xls_counts = Counter(r['BL Number'] for r in xls_rows if r.get('BL Number'))
    bl_txt_counts = Counter(r['bl_number'] for r in sim_records if r.get('bl_number'))
    multi_bl_xls = {bl: c for bl, c in bl_xls_counts.items() if c > 1}
    print(f"\nBLs with multiple XLS rows: {len(multi_bl_xls)}")
    print(f"Sample multi-item BLs: {list(multi_bl_xls.items())[:5]}")

    # -----------------------------------------------------------------------
    # Pair up TXT and XLS records in order (they should be aligned by position)
    # The XLS was generated from the same TXT so row N of XLS = record N of TXT
    # -----------------------------------------------------------------------
    assert len(sim_records) == len(xls_rows), \
        f"Count mismatch: TXT={len(sim_records)}, XLS={len(xls_rows)}"

    # -----------------------------------------------------------------------
    # Run comparison record-by-record (positional alignment)
    # -----------------------------------------------------------------------
    stats = {
        'weight_correct': 0,
        'weight_none_sim': 0,
        'weight_mismatch': 0,
        'vol_correct': 0,
        'vol_zero_sim': 0,   # sim=0 but XLS has value
        'vol_mismatch': 0,
        'item_commodity_correct': 0,
        'item_commodity_mismatch': 0,
        'item_weight_correct': 0,
        'item_weight_mismatch': 0,
        'item_vol_correct': 0,
        'item_vol_mismatch': 0,
        'leading_zero': 0,
        'bl_number_match': 0,
        'bl_number_mismatch': 0,
    }

    weight_mismatch_detail = []
    vol_mismatch_detail    = []
    commodity_mismatch_detail = []
    lz_detail = []

    for i, (sim, xrow) in enumerate(zip(sim_records, xls_rows)):
        xbl = xrow.get('BL Number') or ''
        sbl = sim['bl_number']
        if xbl == sbl:
            stats['bl_number_match'] += 1
        else:
            stats['bl_number_mismatch'] += 1

        # --- BL Weight ---
        sim_w = sim['bl_weight_kg']  # float or None
        xls_w = xrow.get('BLWeight')  # float from XLS

        if sim_w is None:
            stats['weight_none_sim'] += 1
            if xls_w is not None:
                weight_mismatch_detail.append({
                    'row': i, 'bl': sbl,
                    'sim': None, 'xls': xls_w,
                    'note': 'sim=None, xls has value'
                })
        else:
            if xls_w is None:
                # sim has value but XLS empty - count as correct if XLS is genuinely empty
                stats['weight_correct'] += 1
            else:
                diff = abs(sim_w - xls_w)
                if diff < 0.5:  # within 0.5 kg
                    stats['weight_correct'] += 1
                else:
                    stats['weight_mismatch'] += 1
                    weight_mismatch_detail.append({
                        'row': i, 'bl': sbl,
                        'sim': sim_w, 'xls': xls_w,
                        'ratio': xls_w / sim_w if sim_w else None
                    })

        # --- BL Volume ---
        sim_v = sim['bl_volume']    # float
        xls_v = xrow.get('BLVolume')  # float

        if sim_v == 0.0 or sim_v is None:
            if xls_v is not None and xls_v > 0:
                stats['vol_zero_sim'] += 1
                vol_mismatch_detail.append({
                    'row': i, 'bl': sbl,
                    'sim': 0, 'xls': xls_v,
                    'note': 'sim=0, xls has value'
                })
            else:
                stats['vol_correct'] += 1
        else:
            if xls_v is None:
                stats['vol_correct'] += 1
            else:
                diff = abs(sim_v - xls_v)
                if diff < 0.01:
                    stats['vol_correct'] += 1
                else:
                    stats['vol_mismatch'] += 1
                    vol_mismatch_detail.append({
                        'row': i, 'bl': sbl,
                        'sim': sim_v, 'xls': xls_v,
                        'ratio': xls_v / sim_v if sim_v else None
                    })

        # --- BLItem Commodity ---
        sim_c = sim['commodity']
        xls_c = xrow.get('BLItem Commodity')  # string or None

        if xls_c is not None and xls_c != '':
            if sim_c == xls_c:
                stats['item_commodity_correct'] += 1
            else:
                stats['item_commodity_mismatch'] += 1
                commodity_mismatch_detail.append({
                    'row': i, 'bl': sbl,
                    'sim': sim_c, 'xls': xls_c,
                    'sim_weight_kg': sim['item_weight_kg'],
                    'xls_weight': xrow.get('BLItem Commodity Weight'),
                    'yard_type': sim['yard_item_type'],
                })
        elif sim_c:
            # sim has commodity but XLS is empty
            pass  # VEHICULEs might still match

        # --- BLItem Commodity Weight ---
        sim_iw = sim['item_weight_kg']
        xls_iw = xrow.get('BLItem Commodity Weight')
        if xls_iw is not None and sim_iw is not None:
            if abs(sim_iw - xls_iw) < 0.5:
                stats['item_weight_correct'] += 1
            else:
                stats['item_weight_mismatch'] += 1
        elif xls_iw is None and sim_iw is None:
            stats['item_weight_correct'] += 1

        # --- BLItem Commodity Volume ---
        sim_iv = sim['item_volume']
        xls_iv = xrow.get('BLItem Commodity Volume')
        if xls_iv is not None and sim_iv is not None:
            if abs(sim_iv - xls_iv) < 0.01:
                stats['item_vol_correct'] += 1
            else:
                stats['item_vol_mismatch'] += 1
        elif xls_iv is None and (sim_iv is None or sim_iv == 0):
            stats['item_vol_correct'] += 1

        # --- Leading zeros ---
        yn = sim['_raw_yard_item_number']
        if yn and yn.startswith('0'):
            try:
                as_num = str(int(float(yn)))
                if as_num != yn:
                    stats['leading_zero'] += 1
                    lz_detail.append({'row': i, 'bl': sbl, 'original': yn, 'written': as_num})
            except Exception:
                pass

    total = len(sim_records)

    print("\n" + "="*70)
    print("SIMULATION vs XLS — POSITIONAL COMPARISON REPORT")
    print("="*70)
    print(f"Total records          : {total}")
    print(f"BL Number match        : {stats['bl_number_match']} / {total}")
    print(f"BL Number mismatch     : {stats['bl_number_mismatch']}")

    print("\n--- BL WEIGHT (BLWeight column) ---")
    print(f"  Correct (<0.5 kg diff)  : {stats['weight_correct']}")
    print(f"  sim=None (empty output) : {stats['weight_none_sim']}")
    print(f"  Mismatch (>0.5 kg diff) : {stats['weight_mismatch']}")
    pct = stats['weight_correct'] / total * 100
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BL VOLUME (BLVolume column) ---")
    print(f"  Correct (<0.01 diff)    : {stats['vol_correct']}")
    print(f"  sim=0 but XLS has value : {stats['vol_zero_sim']}")
    print(f"  Mismatch (non-zero both): {stats['vol_mismatch']}")
    pct = stats['vol_correct'] / total * 100
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BLItem COMMODITY (BLItem Commodity column) ---")
    tot_c = stats['item_commodity_correct'] + stats['item_commodity_mismatch']
    print(f"  Correct                 : {stats['item_commodity_correct']}")
    print(f"  Mismatch                : {stats['item_commodity_mismatch']}")
    print(f"  XLS had commodity value : {tot_c}")
    pct = stats['item_commodity_correct'] / tot_c * 100 if tot_c else 0
    print(f"  Accuracy (where XLS set): {pct:.1f}%")

    print("\n--- BLItem COMMODITY WEIGHT ---")
    tot_iw = stats['item_weight_correct'] + stats['item_weight_mismatch']
    print(f"  Correct                 : {stats['item_weight_correct']}")
    print(f"  Mismatch                : {stats['item_weight_mismatch']}")
    pct = stats['item_weight_correct'] / tot_iw * 100 if tot_iw else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- BLItem COMMODITY VOLUME ---")
    tot_iv = stats['item_vol_correct'] + stats['item_vol_mismatch']
    print(f"  Correct                 : {stats['item_vol_correct']}")
    print(f"  Mismatch                : {stats['item_vol_mismatch']}")
    pct = stats['item_vol_correct'] / tot_iv * 100 if tot_iv else 0
    print(f"  Accuracy                : {pct:.1f}%")

    print("\n--- LEADING ZEROS BUG ---")
    print(f"  Records with leading-zero yard item number: {stats['leading_zero']}")
    if lz_detail:
        print(f"  Examples:")
        for d in lz_detail[:5]:
            print(f"    row={d['row']:4d} BL={d['bl']:15s} original={d['original']:22s} -> would_write={d['written']}")

    # -----------------------------------------------------------------------
    # Analyse the weight mismatch pattern
    # -----------------------------------------------------------------------
    print(f"\n--- WEIGHT MISMATCH ANALYSIS ({len(weight_mismatch_detail)} mismatches) ---")
    # Check ratio patterns
    ratios = [d['ratio'] for d in weight_mismatch_detail if d.get('ratio')]
    if ratios:
        # Are some close to integer multiples? (multi-item BLs)
        near_int = sum(1 for r in ratios if abs(r - round(r)) < 0.15)
        print(f"  Mismatches where XLS/sim ratio is near an integer: {near_int} / {len(ratios)}")
        ratio_counter = Counter(round(r) for r in ratios)
        print(f"  Rounded ratio distribution: {ratio_counter.most_common(10)}")

    # Sample of None mismatches
    none_miss = [d for d in weight_mismatch_detail if d['sim'] is None]
    print(f"\n  sim=None but XLS has value ({len(none_miss)} records):")
    for d in none_miss[:10]:
        print(f"    row={d['row']:4d} BL={d['bl']:15s} xls_weight={d['xls']}")

    # Sample of numeric mismatches
    num_miss = [d for d in weight_mismatch_detail if d['sim'] is not None][:15]
    print(f"\n  Numeric weight mismatches (first 15):")
    for d in num_miss:
        print(f"    row={d['row']:4d} BL={d['bl']:15s} sim={d['sim']:10.2f}  xls={d['xls']:10.2f}  ratio={d.get('ratio', 'N/A')}")

    # -----------------------------------------------------------------------
    # Analyse volume mismatches
    # -----------------------------------------------------------------------
    print(f"\n--- VOLUME MISMATCH ANALYSIS ({len(vol_mismatch_detail)} mismatches) ---")
    zero_vol = [d for d in vol_mismatch_detail if d.get('note')]
    print(f"  sim=0 but XLS has value: {len(zero_vol)}")
    for d in zero_vol[:5]:
        print(f"    row={d['row']:4d} BL={d['bl']:15s} xls_vol={d['xls']}")

    num_vol_mm = [d for d in vol_mismatch_detail if not d.get('note')]
    vrat = [d['ratio'] for d in num_vol_mm if d.get('ratio')]
    if vrat:
        near_int_v = sum(1 for r in vrat if abs(r - round(r)) < 0.1)
        print(f"  Numeric vol mismatches where ratio near integer: {near_int_v} / {len(vrat)}")
        vratio_counter = Counter(round(r) for r in vrat)
        print(f"  Rounded ratio distribution: {vratio_counter.most_common(10)}")

    print(f"\n  Sample numeric vol mismatches (first 10):")
    for d in num_vol_mm[:10]:
        print(f"    row={d['row']:4d} BL={d['bl']:15s} sim={d['sim']:10.3f}  xls={d['xls']:10.3f}  ratio={d.get('ratio', 'N/A')}")

    # -----------------------------------------------------------------------
    # Commodity mismatches
    # -----------------------------------------------------------------------
    print(f"\n--- COMMODITY MISMATCH ANALYSIS ({len(commodity_mismatch_detail)} mismatches) ---")
    cd_pairs = Counter((d['sim'], d['xls']) for d in commodity_mismatch_detail)
    print(f"  Sim vs XLS commodity pairs (most common):")
    for (s, x), cnt in cd_pairs.most_common(10):
        print(f"    sim={s!r:25s} xls={x!r:25s} count={cnt}")

    print(f"\n  Weight comparison for commodity mismatches (first 15):")
    for d in commodity_mismatch_detail[:15]:
        print(f"    row={d['row']:4d} BL={d['bl']:15s} sim_kg={str(d['sim_weight_kg']):10s} xls_kg={str(d['xls_weight']):10s}  sim_cat={d['sim']!r}  xls_cat={d['xls']!r}")

    # -----------------------------------------------------------------------
    # Deep dive: multi-item BL problem
    # -----------------------------------------------------------------------
    print("\n--- MULTI-ITEM BL ANALYSIS ---")
    # Group XLS rows by BL number and sum their weights/volumes
    bl_groups = defaultdict(list)
    for i, xrow in enumerate(xls_rows):
        bl = xrow.get('BL Number') or ''
        bl_groups[bl].append({'idx': i, 'row': xrow})

    multi_item = {bl: rows for bl, rows in bl_groups.items() if len(rows) > 1}
    print(f"  BLs with multiple items in XLS: {len(multi_item)}")
    print(f"  Total extra rows due to multi-item: {sum(len(v)-1 for v in multi_item.values())}")

    # For multi-item BLs, the XLS BLWeight = sum of all item weights
    # but the TXT (and sim) has EACH item listed separately with its own weight
    # Let's check: does the XLS BLWeight for a multi-item BL equal the sum of item weights?
    print(f"\n  Checking XLS BLWeight == sum(BLItem Commodity Weight) for multi-item BLs:")
    matches = 0
    misses  = 0
    for bl, rows in list(multi_item.items())[:20]:
        bl_wt = rows[0]['row'].get('BLWeight')  # all rows have same BLWeight
        sum_item_wt = sum(r['row'].get('BLItem Commodity Weight') or 0 for r in rows)
        if bl_wt is not None and abs(bl_wt - sum_item_wt) < 1:
            matches += 1
        else:
            misses += 1
            print(f"    BL={bl}: BLWeight={bl_wt}, sum_item={sum_item_wt:.2f}")
    print(f"  BLWeight == sum(item weights): {matches} match, {misses} mismatch (of first 20)")

    # Check if sim BL weight for first item = one item's xls item weight
    print(f"\n  Sim per-item weight vs XLS per-item weight (positional):")
    for bl, rows in list(multi_item.items())[:5]:
        print(f"\n  BL={bl} ({len(rows)} items):")
        for seq, entry in enumerate(rows):
            idx  = entry['idx']
            xrow = entry['row']
            sim  = sim_records[idx]
            print(f"    item {seq+1}: sim_weight={sim['bl_weight_kg']} | xls_BLWeight={xrow.get('BLWeight')} | xls_ItemWeight={xrow.get('BLItem Commodity Weight')}")

    # -----------------------------------------------------------------------
    # Root cause: for multi-item BLs, XLS BLWeight = total BL weight (sum),
    # but sim reads PER-ITEM weight from offset 281 and puts it as BLWeight.
    # This means for multi-item BLs the BL-level weight is wrong in simulation.
    # The ITEM-level weight (BLItem Commodity Weight) might be correct.
    # -----------------------------------------------------------------------
    print("\n--- ITEM WEIGHT ACCURACY (all records) ---")
    item_exact = 0
    item_wrong = 0
    for sim, xrow in zip(sim_records, xls_rows):
        sim_iw = sim['item_weight_kg']
        xls_iw = xrow.get('BLItem Commodity Weight')
        if xls_iw is None and sim_iw is None:
            item_exact += 1
        elif xls_iw is not None and sim_iw is not None:
            if abs(sim_iw - xls_iw) < 0.5:
                item_exact += 1
            else:
                item_wrong += 1
        else:
            item_wrong += 1
    print(f"  BLItem Commodity Weight exact: {item_exact} / {total}")
    print(f"  BLItem Commodity Weight wrong: {item_wrong} / {total}")

    # -----------------------------------------------------------------------
    # Summary: which bugs remain?
    # -----------------------------------------------------------------------
    print("\n" + "="*70)
    print("SUMMARY OF REMAINING BUGS")
    print("="*70)

    print(f"""
BUG 1 - BLWeight shows per-item weight instead of total BL weight:
  - {stats['weight_mismatch']} records have wrong BLWeight
  - Pattern: for multi-item BLs, XLS BLWeight = sum of all item weights,
    but sim outputs the individual item weight read from offset 281.
  - This is {stats['weight_mismatch']}/{total} = {stats['weight_mismatch']/total*100:.1f}% of records wrong.

BUG 2 - BLWeight = None for {stats['weight_none_sim']} records:
  - These records have no weight data at offsets 281 or 1296 in the TXT.
  - XLS has a value for these (likely because the total was computed differently).

BUG 3 - BLVolume wrong for {stats['vol_mismatch']} records:
  - Same root cause as BUG 1: per-item volume vs total BL volume.

BUG 4 - BLVolume = 0 for {stats['vol_zero_sim']} records where XLS has value:
  - Volume not found at offsets 293 or 1308.

BUG 5 - BLItem Commodity wrong for {stats['item_commodity_mismatch']} records:
  - See mismatch detail above.

BUG 6 (Leading zeros):
  - {stats['leading_zero']} yard item numbers start with '0' and would be corrupted.
""")

if __name__ == '__main__':
    main()
