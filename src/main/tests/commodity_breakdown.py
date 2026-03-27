#!/usr/bin/env python3
"""Show full breakdown of 42 commodity mismatches."""
import sys, os
sys.path.insert(0, os.path.dirname(__file__))

# Patch the main function out so we can use the module's functions
import re, ast

src = open(os.path.join(os.path.dirname(__file__), 'verify_corrected_pipeline.py')).read()
# Remove the main() call at bottom
src = re.sub(r"if __name__ == '__main__':\s*main\(\)", "", src)
exec(compile(src, 'verify_corrected_pipeline.py', 'exec'), globals())

txt_path = r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/ALL_DAKAR_BL_Extract2-GRA0226SB -mis à jour.TXT"

records  = parse_file(txt_path)
exported = xls_exporter(records)
pipe_map = {(r.get('bl_number','').strip(), r.get('blitem_yard_item_number','').strip()): r for r in exported}

import xlrd
from collections import Counter
wb = xlrd.open_workbook(r"C:/Users/marcd/Documents/Code/Spring Boot/application/dt.app.java/src/main/tests/EXTRACTION.xls032524917.xls")
ws = wb.sheet_by_index(0)
headers = [ws.cell_value(0, c) for c in range(ws.ncols)]
col_bl   = headers.index('BL Number')
col_item = headers.index('BLItem YardItemNumber')
col_com  = headers.index('BLItem Commodity')
col_wt   = headers.index('BLItem Commodity Weight')

breakdown   = Counter()
mismatches  = []
for r in range(1, ws.nrows):
    bl   = str(ws.cell_value(r, col_bl)).strip()
    item = str(ws.cell_value(r, col_item)).strip()
    xls_com = str(ws.cell_value(r, col_com)).strip()
    key = (bl, item)
    if key in pipe_map:
        pipe_com = pipe_map[key].get('blitem_commodity', '')
        pipe_wt  = pipe_map[key].get('blitem_commodity_weight', '')
        if pipe_com != xls_com:
            breakdown[(pipe_com, xls_com)] += 1
            mismatches.append((bl, item, pipe_wt, pipe_com, xls_com))

print(f"Total commodity mismatches: {len(mismatches)}")
print("\nBreakdown (pipeline_value  vs  xls_expected):")
for (p, x), cnt in sorted(breakdown.items(), key=lambda kv: -kv[1]):
    print(f"  pipeline={p!r:28s}  xls={x!r:28s}  count={cnt}")

print("\nAll mismatches:")
for m in mismatches:
    print(f"  BL={m[0]:15s}  item={m[1]:22s}  weight={m[2]:8s}  pipe={m[3]!r}  xls={m[4]!r}")
