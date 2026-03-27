<?php

namespace App\Models;

class EdiRecord
{
    const FIELDS = [
        'bl_number'                     => [62,  20],
        'import_export'                 => [null, 0],
        'import_export_raw'             => [694, 10],
        'stevedore'                     => [null, 0],
        'shipping_agent'                => [null, 0],
        'estimated_departure_date'      => [null, 0],
        'call_number'                   => [21,  10],
        'shipper'                       => [null, 0],
        'forwarder'                     => [null, 0],
        'related_customer'              => [null, 0],
        'forwarding_agent'              => [null, 0],
        'final_destination_country'     => [null, 0],
        'manifest'                      => [926, 35],
        'number_of_yard_items'          => [1757, 5],
        'number_of_packages'            => [1757, 5],
        'slot_file'                     => [null, 0],
        'transport_mode'                => [61,   1],
        'consignee'                     => [926, 35],
        'custom_release_order'          => [null, 0],
        'custom_release_order_date'     => [null, 0],
        'delivery_order'                => [null, 0],
        'delivery_order_date'           => [null, 0],
        'master_bl'                     => [null, 0],
        'bl_volume'                     => [293, 12],   // conteneurs
        'bl_volume_roro'                => [1308, 12],  // véhicules RORO
        'bl_weight'                     => [281, 12],
        'bl_weight_alt'                 => [1296, 12], // poids alternatif (certains RORO / conteneurs)
        'incoterm'                      => [null, 0],
        'port_of_loading'               => [31,   5],
        'reception_location'            => [46,   5],
        'transshipment_port_1'          => [36,   5],
        'transshipment_port_2'          => [41,   5],
        'commodity'                     => [null, 0],
        'yard_item_type'                => [null, 0],
        'unit_of_measure'               => [null, 0],
        'comment'                       => [null, 0],
        'direction_code'                => [null, 0],
        'agent_name'                    => [1111, 35],
        'blitem_yard_item_type'         => [null, 0],
        'blitem_comment'                => [221, 35],
        'blitem_yard_item_number'       => [126, 20],
        'blitem_allow_invalid'          => [null, 0],
        'blitem_yard_item_code'         => [146, 35],
        'blitem_out_of_gauge'           => [null, 0],
        'blitem_commodity'              => [null, 0],
        'blitem_unloading_date'         => [null, 0],
        'blitem_commodity_volume'       => [null, 0],
        'blitem_commodity_weight'       => [281, 12],
        'blitem_commodity_packages'     => [null, 0],
        'blitem_import_export'          => [null, 0],
        'blitem_custom_number'          => [null, 0],
        'blitem_seal_number_1'          => [351, 70],   // 70 chars pour capturer les deux sceaux séparés par |
        'blitem_seal_number_2'          => [null, 0],   // déduit du champ 1
        'blitem_commodity_hazardous_class' => [null, 0],
        'blitem_barcode'                => [126, 20],
        'blitem_vehicle_model'          => [221, 35],
        'blitem_chassis_number'         => [126, 20],
        'outgoing_call_number'          => [null, 0],
        'outgoing_slot_file'            => [null, 0],
        'is_lifter'                     => [null, 0],
        'stacked_chassis'               => [null, 0],
        'stacked_model'                 => [null, 0],
        'stacked_weight'                => [null, 0],
        'stacked_volume'                => [null, 0],
        'new_transshipment_bl'          => [null, 0],
        'shipper_name'                  => [741, 35],
        'adresse_2'                     => [961, 35],
        'adresse_3'                     => [996, 35],
        'adresse_4'                     => [1031, 35],
        'adresse_5'                     => [1066, 35],
        'notify1'                       => [1111, 35],
        'notify2'                       => [1146, 35],
        'notify3'                       => [1181, 35],
        'notify4'                       => [1216, 35],
        'notify5'                       => [1251, 35],
    ];

    public array $data = [];

    public static function fromLine(string $line, string $srcEncoding = 'UTF-8'): self
    {
        $record = new self();

        foreach (self::FIELDS as $field => [$offset, $length]) {
            if ($offset === null || $length === 0) {
                $record->data[$field] = '';
                continue;
            }
            // substr() works on raw bytes — correct for fixed-width single-byte encodings.
            $value = $offset < strlen($line) ? substr($line, $offset, $length) : '';
            // Convert to UTF-8 per field so byte offsets stay accurate for all fields.
            if ($srcEncoding !== 'UTF-8' && $value !== '') {
                $value = mb_convert_encoding($value, 'UTF-8', $srcEncoding);
            }
            $record->data[$field] = trim($value);
        }

        // ── BL Number : conserver tel quel ───────────────────────────────────
        $record->data['bl_number'] = trim($record->data['bl_number']);

        // ── Poids : offset 281 (principal), fallback offset 1296 ─────────────
        $rawWeight281 = ltrim($record->data['bl_weight']     ?? '', '0') ?: '0';
        $rawWeight296 = ltrim($record->data['bl_weight_alt'] ?? '', '0') ?: '0';
        if (is_numeric($rawWeight281) && (int)$rawWeight281 > 0) {
            $weight = round((float)$rawWeight281 / 1_000_000, 6);
        } elseif (is_numeric($rawWeight296) && (int)$rawWeight296 > 0) {
            $weight = round((float)$rawWeight296 / 1_000_000, 6);
        } else {
            $weight = 0;
        }
        $weightStr = $weight > 0 ? (string)$weight : '';
        $record->data['bl_weight']               = $weightStr;
        $record->data['blitem_commodity_weight'] = $weightStr;
        unset($record->data['bl_weight_alt']);

        // ── Volume : offset 293 (conteneurs) ou 1308 (véhicules RORO) ────────
        $rawVol293  = ltrim($record->data['bl_volume']      ?? '', '0') ?: '0';
        $rawVol1308 = ltrim($record->data['bl_volume_roro'] ?? '', '0') ?: '0';
        $vol = 0;
        if (is_numeric($rawVol293) && (int)$rawVol293 > 0) {
            $vol = round((int)$rawVol293 / 1000, 3);
        } elseif (is_numeric($rawVol1308) && (int)$rawVol1308 > 0) {
            $vol = round((int)$rawVol1308 / 1000, 3);
        }
        $volStr = $vol > 0 ? (string)$vol : '';
        $record->data['bl_volume']               = $volStr;
        $record->data['blitem_commodity_volume'] = $volStr;
        unset($record->data['bl_volume_roro']);

        // ── Nombre d'articles ─────────────────────────────────────────────────
        $nyi = trim($record->data['number_of_yard_items']);
        $record->data['number_of_yard_items'] = is_numeric($nyi) ? $nyi : '1';
        $record->data['number_of_packages']   = $record->data['number_of_yard_items'];

        // ── ImportExport ──────────────────────────────────────────────────────
        $raw = trim($record->data['import_export_raw'] ?? '');
        if (str_starts_with($raw, 'TS')) {
            $record->data['import_export'] = 'TRANSBO';
        } elseif (str_starts_with($raw, 'TR')) {
            $record->data['import_export'] = 'IMPORT TRANSIT';
        } else {
            $record->data['import_export'] = 'IMPORT';
        }
        unset($record->data['import_export_raw']);

        // ── Final Destination Country : reprend adresse_5 ────────────────────
        $record->data['final_destination_country'] = trim($record->data['adresse_5'] ?? '');

        // ── YardItemType : déduit du mode de transport ────────────────────────
        $mode     = trim($record->data['transport_mode'] ?? '');
        $yardType = in_array($mode, ['R', 'M']) ? 'VEHICULE' : 'CONTENEUR';
        $record->data['yard_item_type']        = $yardType;
        $record->data['blitem_yard_item_type'] = $yardType;

        // ── BLItem AllowInvalidYardItemNumber : toujours VRAI ─────────────────
        $record->data['blitem_allow_invalid'] = 'VRAI';

        // ── BLItem Commodity : tranches de poids (véhicules uniquement) ───────
        // $weight est en tonnes (÷1 000 000). Seuils convertis en tonnes.
        if ($yardType === 'VEHICULE' && $weight > 0) {
            $record->data['blitem_commodity'] = match (true) {
                $weight <= 1.5   => 'VEH 0-1500Kgs',
                $weight <= 3.0   => 'VEH 1501-3000Kgs',
                $weight <= 6.0   => 'VEH 3001-6000Kgs',
                $weight <= 9.0   => 'VEH 6001-9000Kgs',
                $weight <= 30.0  => 'VEH 9001-30000Kgs',
                default          => 'VEH +30000Kgs',
            };
        } else {
            $record->data['blitem_commodity'] = '';
        }

        // ── Sceaux : format différent selon le type de transport ─────────────
        // RORO (mode R/M) : le champ 70-chars contient deux sceaux séparés par |
        //   → on split : seal1 = premier sceau + '|', seal2 = second sceau + '|'
        // Conteneurs (autres modes) : le champ contient la valeur complète avec |
        //   → on garde tel quel dans seal1, seal2 = seal1 (BUG-E)
        $sealRaw = trim($record->data['blitem_seal_number_1'] ?? '');
        if (in_array($mode, ['R', 'M'])) {
            // RORO : split at |
            $sealParts = array_values(array_filter(array_map('trim', explode('|', $sealRaw))));
            $record->data['blitem_seal_number_1'] = isset($sealParts[0]) ? $sealParts[0] . '|' : '';
            $record->data['blitem_seal_number_2'] = isset($sealParts[1]) ? $sealParts[1] . '|' : '';
        } else {
            // Conteneurs : garder la valeur brute complète
            $record->data['blitem_seal_number_1'] = $sealRaw;
            $record->data['blitem_seal_number_2'] = $sealRaw;
        }

        // ── New template fields (always empty – no source offset available) ──
        $record->data['blitem_hs_code']           = '';
        $record->data['blitem_gross_weight']      = '';
        $record->data['freight_prepaid_collect']  = '';
        $record->data['shipping_line_export_bl']  = '';
        $record->data['is_transfer']              = '';
        $record->data['blitem_hazardous_class']   = '';
        $record->data['attach_to_bl']             = '';

        return $record;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}