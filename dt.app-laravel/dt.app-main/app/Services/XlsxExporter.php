<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxExporter
{
    // Brand colors
    const COLOR_HEADER_BG    = '1F3864';   // Dark navy
    const COLOR_HEADER_FONT  = 'FFFFFF';   // White
    const COLOR_ROW_ALT      = 'EEF2F7';   // Light blue-grey
    const COLOR_ROW_NORMAL   = 'FFFFFF';   // White
    const COLOR_BORDER       = 'B8C4D0';   // Soft blue-grey

    public function export(Collection $records, array $headers, string $outputPath): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EDI Data');

        // ── Write header row ──────────────────────────────────────────────────
        $col = 1;
        foreach ($headers as $key => $label) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getCell("{$colLetter}1")->setValue($label);
            $col++;
        }

        $this->applyHeaderStyle($sheet, 1, count($headers));

        // ── Write data rows ───────────────────────────────────────────────────
        $row = 2;
        foreach ($records as $record) {
            $col  = 1;
            $data = $record->toArray();

            // ── Corrections XLSX uniquement ───────────────────────────────────
            //
            // POIDS : EdiRecord stocke les poids en tonnes.
            // bl_weight = total du BL (agrégé) → ×1000 → kg
            // blitem_commodity_weight = poids individuel de l'item → ×1000 → kg
            // On préserve 1 décimale pour les valeurs non entières (ex: 1175.2 kg).
            $rawWeight = (float)($data['bl_weight'] ?? 0);
            $data['bl_weight'] = $rawWeight > 0
                ? rtrim(rtrim(number_format($rawWeight * 1000, 1, '.', ''), '0'), '.')
                : '';

            $rawItemWeight = (float)($data['blitem_commodity_weight'] ?? 0);
            $data['blitem_commodity_weight'] = $rawItemWeight > 0
                ? rtrim(rtrim(number_format($rawItemWeight * 1000, 1, '.', ''), '0'), '.')
                : '';

            // VOLUMES : EdiRecord stocke les volumes directement en m³.
            // bl_volume = total du BL (agrégé).
            // blitem_commodity_volume = volume individuel de l'item.
            // On nettoie les erreurs de virgule flottante avec number_format.
            $rawVolume = (float)($data['bl_volume'] ?? 0);
            if ($rawVolume > 0) {
                $data['bl_volume'] = rtrim(rtrim(number_format($rawVolume, 3, '.', ''), '0'), '.');
            } else {
                $data['bl_volume'] = (($data['yard_item_type'] ?? '') === 'CONTENEUR') ? '0' : '';
            }

            $rawItemVol = (float)($data['blitem_commodity_volume'] ?? 0);
            if ($rawItemVol > 0) {
                $data['blitem_commodity_volume'] = rtrim(rtrim(number_format($rawItemVol, 3, '.', ''), '0'), '.');
            } else {
                $data['blitem_commodity_volume'] = (($data['blitem_yard_item_type'] ?? '') === 'CONTENEUR') ? '0' : '';
            }

            // CATÉGORIE VEHICULE : recalcul sur le poids individuel de l'item (blitem_commodity_weight),
            // déjà converti en kg ci-dessus. On ne se base pas sur bl_weight (total multi-items).
            // Les tranches correspondent exactement à celles d'EdiRecord (en kg ici, en tonnes là-bas).
            $itemWeightKg = (float)($data['blitem_commodity_weight'] ?? 0);
            if (str_starts_with(trim($data['blitem_commodity'] ?? ''), 'VEH ') && $itemWeightKg > 0) {
                $data['blitem_commodity'] = match (true) {
                    $itemWeightKg <= 1500  => 'VEH 0-1500Kgs',
                    $itemWeightKg <= 3000  => 'VEH 1501-3000Kgs',
                    $itemWeightKg <= 6000  => 'VEH 3001-6000Kgs',
                    $itemWeightKg <= 9000  => 'VEH 6001-9000Kgs',
                    $itemWeightKg <= 30000 => 'VEH 9001-30000Kgs',
                    default                => 'VEH +30000Kgs',
                };
            }

            // BUG-08 : 'consignee' doit être vide dans le XLSX.
            $data['consignee'] = '';

            // BUG-09 : 'shipper_name' idem — vide dans le XLSX.
            $data['shipper_name'] = '';

            // BUG-11 : 'final_destination_country' doit être vide dans le XLSX.
            // Le fichier de référence n'exporte jamais ce champ.
            $data['final_destination_country'] = '';

            // BUG-07 : Les ports de transbordement ne doivent pas figurer dans le XLSX.
            $data['transshipment_port_1'] = '';
            $data['transshipment_port_2'] = '';

            // BUG-B (ex BUG-06) : Le swap port_of_loading ↔ reception_location était incorrect.
            // EdiRecord::fromLine() fournit déjà les bonnes valeurs dans les bons champs —
            // le swap précédent créait une double inversion. On le supprime.
            // (Aucune modification nécessaire : $data['port_of_loading'] et $data['reception_location']
            //  sont utilisés tels quels.)
            // ─────────────────────────────────────────────────────────────────

            foreach (array_keys($headers) as $key) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->getCell("{$colLetter}{$row}")->setValue($data[$key] ?? '');
                $col++;
            }
            $this->applyRowStyle($sheet, $row, count($headers), $row % 2 === 0);
            $row++;
        }

        // ── Auto-size columns ─────────────────────────────────────────────────
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // ── Freeze header row ─────────────────────────────────────────────────
        $sheet->freezePane('A2');

        // ── Auto-filter ───────────────────────────────────────────────────────
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->setAutoFilter("A1:{$lastCol}1");

        // ── Write file ────────────────────────────────────────────────────────
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }

    private function applyHeaderStyle($sheet, int $row, int $colCount): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $range = "A{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FF' . self::COLOR_HEADER_FONT],
                'size'  => 10,
                'name'  => 'Arial',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF' . self::COLOR_HEADER_BG],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => false,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF' . self::COLOR_BORDER],
                ],
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function applyRowStyle($sheet, int $row, int $colCount, bool $alternate): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $range = "A{$row}:{$lastCol}{$row}";

        $bgColor = $alternate ? self::COLOR_ROW_ALT : self::COLOR_ROW_NORMAL;

        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'size' => 9,
                'name' => 'Arial',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF' . $bgColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF' . self::COLOR_BORDER],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }
}
