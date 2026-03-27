<?php

namespace App\Services;

use App\Models\EdiRecord;
use Illuminate\Support\Collection;

class EdiParser
{
    public function parse(string $filePath): Collection
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("Fichier introuvable : {$filePath}");
        }

        $content = file_get_contents($filePath);

        // Detect encoding ONCE but do NOT convert the whole content.
        // The file uses fixed-byte offsets (Windows-1252 = single-byte encoding).
        // Converting the entire content to UTF-8 shifts all offsets after the first
        // multi-byte character — causing wrong values at high offsets (e.g., offset 1308).
        // Instead, pass the raw bytes to EdiRecord and convert each field value individually.
        $srcEncoding = mb_detect_encoding($content, 'UTF-8', true) ? 'UTF-8' : 'Windows-1252';

        $records = collect();

        foreach (explode("\n", $content) as $line) {
            $line = rtrim($line, "\r");
            if (trim($line) === '' || strlen($line) < 400) {
                continue;
            }
            $type = trim(substr($line, 0, 5));
            if (empty($type) || ! ctype_alnum($type)) {
                continue;
            }
            $records->push(EdiRecord::fromLine($line, $srcEncoding));
        }

        // ── Agrégation des BLs multi-lignes ──────────────────────────────────
        // Note BUG-02 : EdiRecord::fromLine() stocke les poids en tonnes (÷1000) et les volumes
        // en m³ (÷1000). Les agrégats ci-dessous restent dans ces unités internes.
        // La conversion vers kg / m³ entiers est faite par chaque exporteur (XlsxExporter, EdiExporter).
        $blCounts  = $records->groupBy(fn($r) => $r->data['bl_number'])->map->count();
        $blVolumes = $records->groupBy(fn($r) => $r->data['bl_number'])
            ->map(fn($group) => $group->sum(fn($r) => (float)($r->data['bl_volume'] ?? 0)));
        $blWeights = $records->groupBy(fn($r) => $r->data['bl_number'])
            ->map(fn($group) => $group->sum(fn($r) => (float)($r->data['bl_weight'] ?? 0)));

        foreach ($records as $record) {
            $bl    = $record->data['bl_number'];
            $count = $blCounts[$bl] ?? 1;

            if ($count > 1) {
                $record->data['number_of_yard_items'] = (string)$count;
                $record->data['number_of_packages']   = (string)$count;

                // Agrégation : bl_volume/bl_weight = TOTAL du BL (somme de tous les items).
                // blitem_commodity_volume/blitem_commodity_weight = valeur INDIVIDUELLE de cet item
                // (déjà stockée par EdiRecord::fromLine()). On ne les touche PAS lors de l'agrégation.
                $totalVol = round($blVolumes[$bl] ?? 0, 6);
                if ($totalVol > 0) {
                    $record->data['bl_volume'] = (string)$totalVol;
                    // blitem_commodity_volume conserve sa valeur individuelle d'origine — ne pas écraser
                }

                $totalWgt = round($blWeights[$bl] ?? 0, 6);
                if ($totalWgt > 0) {
                    $record->data['bl_weight'] = (string)$totalWgt;
                    // blitem_commodity_weight conserve sa valeur individuelle d'origine — ne pas écraser
                }
            }

            // BUG-03 : Pour les véhicules (transport_mode = 'R'), le champ seal_number_1
            // contient à tort la marque du véhicule (ex: 'PEUGEOT|') lue depuis la même
            // position que vehicle_model dans le fichier TXT fixe. On le vide si sa valeur
            // (nettoyée du séparateur |) est un préfixe du vehicle_model.
            if (($record->data['transport_mode'] ?? '') === 'R') {
                $seal1 = trim($record->data['blitem_seal_number_1'] ?? '');
                if ($seal1 !== '') {
                    $sealClean = rtrim($seal1, '| ');
                    $model     = trim($record->data['blitem_vehicle_model'] ?? '');
                    // Si le seal est la marque (1er mot du modèle) → ce n'est pas un vrai numéro de scellé
                    $firstWord = strtok($model, ' ');
                    if ($firstWord && (
                        strcasecmp($sealClean, $firstWord) === 0 ||
                        (strlen($sealClean) >= 4 && stripos($firstWord, $sealClean) === 0)
                    )) {
                        $record->data['blitem_seal_number_1'] = '';
                    }
                }

                // BUG-D : seal_number_2 peut contenir un code parasite de grade/destination
                // lu depuis un mauvais offset TXT. Deux cas :
                // 1. Valeur pure comme 'NGCGRAD|', 'NFGRADE|' → vider entièrement.
                // 2. Valeur mixte comme '4660   NGCGRAD|' → le vrai scellé est la partie
                //    avant les espaces ('4660'), le reste est débordement de lecture.
                $seal2c = $record->data['blitem_seal_number_2'] ?? '';
                if ($seal2c !== '') {
                    // Si la valeur contient des espaces internes, le vrai scellé est avant
                    if (preg_match('/^(\S+)\s{2,}.*[A-Z]{3,}\|$/', trim($seal2c), $m)) {
                        $record->data['blitem_seal_number_2'] = $m[1];
                    } else {
                        // Valeur pure : vider si c'est un code de grade (lettres + GRAD/GRADE)
                        $seal2Clean = rtrim(trim($seal2c), '| ');
                        if (preg_match('/^[YN]?[A-Z]*GR(?:AD|ADE|ADES?)$/', $seal2Clean) ||
                            preg_match('/^[A-Z]*GRADE?$/', $seal2Clean)) {
                            $record->data['blitem_seal_number_2'] = '';
                        }
                    }
                }
            }

            // BUG-E : Pour les conteneurs (transport_mode != 'R'), seal1 = seal2.
            // Le champ TXT 70-chars contient "sceau1|sceau2_opt   CODE_GRADE   ".
            // On extrait sceau1 et le 1er mot après "|" si ce n'est pas un code de grade.
            if (($record->data['transport_mode'] ?? '') !== 'R') {
                $raw = $record->data['blitem_seal_number_1'] ?? '';
                $s1  = $this->parseContainerSeal($raw);
                $record->data['blitem_seal_number_1'] = $s1;
                $record->data['blitem_seal_number_2'] = $s1;
            }

            // BUG-10 : Le champ final_destination_country contient parfois 'TRANSIT:...'
            // alors qu'il doit être vide dans le XLSX.
            $fdc = trim($record->data['final_destination_country'] ?? '');
            if (str_starts_with($fdc, 'TRANSIT:')) {
                $record->data['final_destination_country'] = '';
            }

            // BUG-F : Les numéros de téléphone internationaux perdent leur '+' initial.
            // On le réinjecte sur les champs d'adresse/notify qui contiennent un numéro pur.
            $phoneFields = ['adresse_2', 'adresse_3', 'adresse_4', 'adresse_5',
                            'notify2', 'notify3', 'notify4', 'notify5'];
            foreach ($phoneFields as $f) {
                $val = trim($record->data[$f] ?? '');
                if (preg_match('/^[1-9][0-9]{7,14}$/', $val)) {
                    $record->data[$f] = '+' . $val;
                }
                // Cas 'h Mr …' : préfixe parasite de 1-2 lettres minuscules suivi d'un espace.
                // On utilise [a-z] uniquement (pas [0-9]) pour éviter de supprimer les numéros
                // de rue légitimes comme '35 Rue Des Brasseurs' ou '01 Bp 1304'.
                elseif (preg_match('/^([a-z]{1,2}) ([A-Z].{5,})$/', $val, $m)) {
                    $record->data[$f] = $m[2];
                }
            }

            // BUG-H : number_of_yard_items peut contenir une valeur aberrante pour des BLs
            // qui n'ont qu'une seule ligne dans le fichier (offset 1757 lit une date ou un autre
            // entier). Pour les BLs mono-ligne, l'agrégation ci-dessus n'a pas tourné, donc
            // la valeur brute du TXT est conservée. On la force à '1' si le BL n'a qu'une seule
            // ligne (la valeur légitime est toujours 1 dans ce cas).
            $bl     = $record->data['bl_number'];
            $blCount = $blCounts[$bl] ?? 1;
            if ($blCount === 1) {
                $record->data['number_of_yard_items'] = '1';
                $record->data['number_of_packages']   = '1';
            } elseif ((int)($record->data['number_of_yard_items'] ?? 1) > 999) {
                // Sécurité supplémentaire pour les BLs multi-lignes avec valeur très aberrante
                $record->data['number_of_yard_items'] = '1';
                $record->data['number_of_packages']   = '1';
            }

        }

        // ── BUG-01 : Tri stable par bl_number uniquement ─────────────────────
        // sortBy() attend un sélecteur de clé fn($item) → on retourne simplement bl_number.
        // PHP 8 garantit un tri stable : l'ordre d'apparition dans le TXT est préservé
        // pour les items d'un même BL (ce que confirme le fichier de référence).
        $records = $records->sortBy(fn($r) => $r->data['bl_number'] ?? '')->values();

        return $records;
    }

    /**
     * Parse a container seal field of the form "SEAL|SECOND_SEAL   GRADE_CODE   ".
     * Returns "SEAL|SECOND_SEAL" if a real second seal exists, "SEAL|" if not,
     * or "" if the whole value is a grade code.
     */
    private function parseContainerSeal(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $pipePos = strpos($raw, '|');

        if ($pipePos === false) {
            // No pipe — clean the whole value
            return $this->isGradeCode($raw) ? '' : $raw;
        }

        $mainSeal = trim(substr($raw, 0, $pipePos));

        if ($this->isGradeCode($mainSeal)) {
            return '';
        }

        // Extract first non-space token after the pipe
        $afterPipe = ltrim(substr($raw, $pipePos + 1));
        $firstWord = strtok($afterPipe, " \t");

        if ($firstWord === false || $firstWord === '' || $this->isGradeCode($firstWord)) {
            return $mainSeal . '|';
        }

        return $mainSeal . '|' . $firstWord;
    }

    private function isGradeCode(string $val): bool
    {
        $v = rtrim(trim($val), '| ');
        return preg_match('/^[YN]?[A-Z]*GR(?:AD|ADE|ADES?)$/', $v) ||
               preg_match('/^[A-Z]*GRADE?$/', $v);
    }

    public function getHeaders(): array
    {
        return [
            'bl_number'                         => 'BL Number',
            'import_export'                     => 'ImportExport',
            'stevedore'                         => 'Stevedore',
            'shipping_agent'                    => 'Shipping Agent',
            'estimated_departure_date'          => 'Estimated Departure Date',
            'call_number'                       => 'Call Number',
            'shipper'                           => 'Shipper',
            'forwarder'                         => 'Forwarder',
            'related_customer'                  => 'Related Customer',
            'forwarding_agent'                  => 'Clearing and Forwarding Agent',
            'final_destination_country'         => 'Final Destination Country',
            'manifest'                          => 'Manifest',
            'number_of_yard_items'              => 'Number of Yard Items',
            'number_of_packages'                => 'Number of Packages',
            'slot_file'                         => 'SlotFile',
            'transport_mode'                    => 'TransportMode',
            'consignee'                         => 'Consignee',
            'custom_release_order'              => 'CustomReleaseOrder',
            'custom_release_order_date'         => 'CustomReleaseOrderDate',
            'delivery_order'                    => 'DeliveryOrder',
            'delivery_order_date'               => 'DeliveryOrderDate',
            'master_bl'                         => 'MasterBL',
            'bl_volume'                         => 'BLVolume',
            'bl_weight'                         => 'BLWeight',
            'incoterm'                          => 'Incoterm',
            'port_of_loading'                   => 'Port Of Loading City UNLOCODE',
            'reception_location'                => 'Reception Location UNLOCODE',
            'transshipment_port_1'              => 'Transshipment port 1 UNLOCODE',
            'transshipment_port_2'              => 'Transshipment port 2 UNLOCODE',
            'commodity'                         => 'Commodity',
            'yard_item_type'                    => 'YardItemType',
            'unit_of_measure'                   => 'UnitOfMeasure',
            'comment'                           => 'Comment',
            'direction_code'                    => 'DirectionCode',
            'agent_name'                        => 'Agent Name',
            'blitem_yard_item_type'             => 'BLItem YardItemType',
            'blitem_comment'                    => 'BLItem Comment',
            'blitem_yard_item_number'           => 'BLItem YardItemNumber',
            'blitem_allow_invalid'              => 'BLItem AllowInvalidYardItemNumber',
            'blitem_yard_item_code'             => 'BLItem YardItemCode',
            'blitem_out_of_gauge'               => 'BLItem OutOfGauge',
            'blitem_commodity'                  => 'BLItem Commodity',
            'blitem_hs_code'                    => 'BLItem HS Code',
            'blitem_unloading_date'             => 'BLItem YardItemUnloadingDate',
            'blitem_commodity_volume'           => 'BLItem Commodity Volume',
            'blitem_commodity_weight'           => 'BLItem Commodity Weight',
            'blitem_commodity_packages'         => 'BLItem Commodity Packages',
            'blitem_import_export'              => 'BLItem ImportExport',
            'blitem_custom_number'              => 'BLItem CustomNumber',
            'blitem_seal_number_1'              => 'BLItem SealNumber1',
            'blitem_seal_number_2'              => 'BLItem SealNumber2',
            'blitem_commodity_hazardous_class'  => 'BLItem Commodity HazardousClass',
            'blitem_barcode'                    => 'BLItem BarCode',
            'blitem_vehicle_model'              => 'BLItem VehicleModel',
            'blitem_chassis_number'             => 'BLItem ChassisNumber',
            'blitem_gross_weight'               => 'BLItem GrossWeight',
            'outgoing_call_number'              => 'OutGoingCallNumber',
            'outgoing_slot_file'                => 'OutGoingSlotFile',
            'is_lifter'                         => 'Is Lifter',
            'stacked_chassis'                   => 'Stacked Vehicle Chassis Number',
            'stacked_model'                     => 'Stacked Vehicle Model',
            'stacked_weight'                    => 'Stacked Vehicle Weight',
            'stacked_volume'                    => 'Stacked Vehicle Volume',
            'new_transshipment_bl'              => 'New Transshipment BL',
            'shipper_name'                      => 'Shipper Name',
            'freight_prepaid_collect'           => 'Freight Prepaid / Collect',
            'shipping_line_export_bl'           => 'Shipping Line Export BL Number',
            'is_transfer'                       => 'Is Transfer',
            'blitem_hazardous_class'            => 'BLItem HazardousClass',
            'attach_to_bl'                      => 'Attach to BL',
        ];
    }
}