<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class XlsExporter
{
    private string $templatePath;

    public function __construct()
    {
        $this->templatePath = dirname(__DIR__, 2) . '/others/tests/template.xls';
    }

    public function export(Collection $records, array $headers, string $outputPath): string
    {
        $spreadsheet = IOFactory::load($this->templatePath);

        // Supprimer tous les onglets sauf le premier ("Bl importer")
        while ($spreadsheet->getSheetCount() > 1) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getSheetCount() - 1);
        }
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // ── Write data rows (starting at row 2, row 1 = template header) ──
        $row = 2;
        foreach ($records as $record) {
            $data = $record->toArray();

            // ── Weight conversion: tonnes → kg (stored as float for XLS compatibility) ──
            $rawWeight = (float)($data['bl_weight'] ?? 0);
            $data['bl_weight'] = $rawWeight > 0 ? round($rawWeight * 1000, 2) : null;

            $rawItemWeight = (float)($data['blitem_commodity_weight'] ?? 0);
            $isVehicle = ($data['blitem_yard_item_type'] ?? '') === 'VEHICULE';
            if ($rawItemWeight > 0) {
                $itemWeightKg = round($rawItemWeight * 1000, 2);
                $data['blitem_commodity_weight'] = $itemWeightKg;
            } else {
                $itemWeightKg = 0;
                $data['blitem_commodity_weight'] = $isVehicle ? 0 : null;
            }

            // ── Volume conversion: internal m³ → float ────────────────────
            $rawVolume = (float)($data['bl_volume'] ?? 0);
            $data['bl_volume'] = $rawVolume > 0 ? round($rawVolume, 3) : 0;

            $rawItemVol = (float)($data['blitem_commodity_volume'] ?? 0);
            if ($rawItemVol > 0) {
                $data['blitem_commodity_volume'] = round($rawItemVol, 3);
            } else {
                $itemType = $data['blitem_yard_item_type'] ?? '';
                $data['blitem_commodity_volume'] = in_array($itemType, ['CONTENEUR', 'VEHICULE']) ? 0 : null;
            }

            // ── Vehicle commodity category (recalculate from individual item weight) ─
            if ($isVehicle) {
                $data['blitem_commodity'] = match (true) {
                    $itemWeightKg <= 0     => 'VEH 0-1500Kgs',
                    $itemWeightKg <= 1500  => 'VEH 0-1500Kgs',
                    $itemWeightKg <= 3000  => 'VEH 1501-3000Kgs',
                    $itemWeightKg <= 6000  => 'VEH 3001-6000Kgs',
                    $itemWeightKg <= 9000  => 'VEH 6001-9000Kgs',
                    $itemWeightKg <= 30000 => 'VEH 9001-30000Kgs',
                    default                => 'VEH +30000Kgs',
                };
            }

            // ── Fields empty in XLS output ────────────────────────────────
            $data['consignee']                  = '';
            $data['shipper_name']               = '';
            $data['final_destination_country']  = '';
            $data['transshipment_port_1']       = '';
            $data['transshipment_port_2']       = '';

            // ── Write each column ─────────────────────────────────────────
            $col = 1;
            foreach (array_keys($headers) as $key) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $value = $data[$key] ?? null;
                // Write null as truly empty cell (no value), string '' as empty cell too
                if ($value === null || $value === '') {
                    $sheet->getCell("{$colLetter}{$row}")->setValue(null);
                } else {
                    $sheet->getCell("{$colLetter}{$row}")->setValue($value);
                }
                $col++;
            }

            $row++;
        }

        $writer = new Xls($spreadsheet);
        $writer->save($outputPath);

        return $outputPath;
    }
}
