<?php

namespace App\Services;

use App\Models\EdiRecord;
use Illuminate\Support\Collection;

/**
 * Convertit une collection d'EdiRecord (TXT fixe) en fichier EDI IFTMIN D04 96B UN.
 * Structure calquée sur le fichier de référence 1065706643-IFTMIN-DAKARDTT_244.edi
 */
class EdiExporter
{
    // Table de lookup UNLOCODE -> nom du port
    const PORT_NAMES = [
        'USNYC' => 'NEW YORK',        'USBWI' => 'BALTIMORE',
        'USBA1' => 'BALTIMORE',        'USPVD' => 'PROVIDENCE',
        'USFPO' => 'FREEPORT',         'USVAB' => 'Virginia Beach',
        'USPNJ' => 'NEWARK OCEAN PORT','USHOU' => 'HOUSTON',
        'USLAX' => 'LOS ANGELES',      'USSAV' => 'SAVANNAH',
        'SNDKR' => 'DAKAR',            'CIABJ' => 'ABIDJAN',
        'TGLFW' => 'LOME',             'CMDLA' => 'DOUALA',
        'NGAPP' => 'APAPA LAGOS',      'GHTEM' => 'TEMA',
        'GNCKY' => 'CONAKRY',          'SLFNA' => 'FREETOWN',
        'MRMTL' => 'NOUAKCHOTT',       'ANLAD' => 'LUANDA',
        'AOLAD' => 'LUANDA',           'GCGRAD'=> 'GRANDE ABIDJAN',
        'LRMLW' => 'MONROVIA',         'BJOOO' => 'COTONOU',
    ];

    // Table transport mode TXT -> libellé EDI
    const TRANSPORT_MODES = [
        'R' => 'Roro',
        'C' => 'Container',
        'B' => 'Bulk',
        'M' => 'Mafi',
    ];

    private string $sender     = 'GRIMALDI';
    private string $recipient  = 'DAKARDTT';
    private string $documentRef = '';

    public function export(Collection $records, string $outputPath, array $options = []): string
    {
        $this->sender      = $options['sender']      ?? 'GRIMALDI';
        $this->recipient   = $options['recipient']   ?? 'DAKARDTT';
        $this->documentRef = $options['document_ref'] ?? $this->generateDocRef($records);

        $now        = now();
        $dateStr    = $now->format('ymd');   // 251006
        $timeStr    = $now->format('Hi');    // 1038
        $interchangeRef = $options['interchange_ref'] ?? rand(100, 999);

        $lines = [];

        // ── UNA + UNB (interchange envelope) ────────────────────────────────
        $lines[] = "UNA:+.? '";
        $lines[] = "UNB+UNOA:2+{$this->sender}+{$this->recipient}+{$dateStr}:{$timeStr}+{$interchangeRef}'";

        // ── One UNH block per record ─────────────────────────────────────────
        $msgNum    = 11394;
        $blockCount = 0;

        foreach ($records as $record) {
            $data       = $record->toArray();
            $blockSegs  = $this->buildBlock($data, $msgNum, $now);
            $segCount   = count($blockSegs) + 1; // +1 for UNT itself

            foreach ($blockSegs as $seg) {
                $lines[] = $seg;
            }
            $lines[] = "UNT+{$segCount}+{$msgNum}'";

            $msgNum++;
            $blockCount++;
        }

        // ── UNZ (interchange trailer) ────────────────────────────────────────
        $lines[] = "UNZ+{$blockCount}+{$interchangeRef}'";

        file_put_contents($outputPath, implode("\r\n", $lines) . "\r\n");

        return $outputPath;
    }

    private function buildBlock(array $d, int $msgNum, \Carbon\CarbonInterface $now): array
    {
        $segs = [];

        $bl         = $d['bl_number']      ?? '';
        $callNum    = trim($d['call_number'] ?? '');

        // BUG-B : Le swap précédent était incorrect. EdiRecord::fromLine() fournit
        // port_of_loading et reception_location dans le bon ordre — on les utilise directement.
        $loadPort   = trim($d['port_of_loading']    ?? '');
        $discharge  = trim($d['reception_location'] ?? '');

        $transship1 = trim($d['transshipment_port_1'] ?? '');
        $transship2 = trim($d['transshipment_port_2'] ?? '');
        $transpMode = trim($d['transport_mode'] ?? '');
        $container  = trim($d['blitem_yard_item_number'] ?? '');
        $itemCode   = trim($d['blitem_yard_item_code'] ?? '');
        $itemDesc   = trim($d['blitem_comment'] ?? '');

        // POIDS : EdiRecord stocke en tonnes.
        // bl_weight = total BL → ×1000 pour KGM dans segment MEA.
        $rawWeight  = (float)($d['bl_weight'] ?? 0);
        $weight     = $rawWeight > 0 ? (string)(int)round($rawWeight * 1000) : '';
        // VOLUMES : déjà en m³. bl_volume = total BL.
        $rawVolume  = (float)($d['bl_volume'] ?? 0);
        $volume     = $rawVolume > 0 ? rtrim(rtrim(number_format($rawVolume, 3, '.', ''), '0'), '.') : '';

        $seal1      = $this->cleanSeal($d['blitem_seal_number_1'] ?? '');
        $seal2      = $this->cleanSeal($d['blitem_seal_number_2'] ?? '');
        $shipperName = trim($d['shipper_name'] ?? '');
        $consignee  = trim($d['manifest']   ?? '');
        $addr2      = trim($d['adresse_2']  ?? '');
        $addr3      = trim($d['adresse_3']  ?? '');
        $addr4      = trim($d['adresse_4']  ?? '');
        $addr5      = trim($d['adresse_5']  ?? '');
        $notify1    = trim($d['notify1']    ?? '');
        $notify2    = trim($d['notify2']    ?? '');
        $notify3    = trim($d['notify3']    ?? '');
        $notify4    = trim($d['notify4']    ?? '');
        $nyi        = $d['number_of_yard_items'] ?? '1';
        $goods      = trim($d['goods_nature'] ?? $itemDesc);

        $createdAt  = $now->format('YmdHi');   // 202510061614
        $blDate     = '';

        $vesselCode = '9680712';

        // ── UNH ──────────────────────────────────────────────────────────────
        $segs[] = "UNH+{$msgNum}+IFTMIN:D04:96B:UN'";

        // ── BGM ──────────────────────────────────────────────────────────────
        $segs[] = "BGM+705+{$this->documentRef}+9'";

        // ── DTM ──────────────────────────────────────────────────────────────
        $segs[] = "DTM+137:{$createdAt}:203'";

        // ── LOC ──────────────────────────────────────────────────────────────
        if ($loadPort) {
            $name = self::PORT_NAMES[$loadPort] ?? $loadPort;
            $segs[] = "LOC+9+{$loadPort}::6:{$name}'";
        }

        if ($discharge) {
            $name = self::PORT_NAMES[$discharge] ?? $discharge;
            $segs[] = "LOC+12+{$discharge}::6:{$name}'";
        }

        if ($transship1 && $transship1 !== $loadPort) {
            $name = self::PORT_NAMES[$transship1] ?? $transship1;
            $segs[] = "LOC+88+{$transship1}::6:{$name}'";
        }

        if ($transship2 && $transship2 !== $loadPort && $transship2 !== $transship1) {
            $name = self::PORT_NAMES[$transship2] ?? $transship2;
            $segs[] = "LOC+91+{$transship2}::6:{$name}'";
        }

        if ($discharge) {
            $name = self::PORT_NAMES[$discharge] ?? $discharge;
            $segs[] = "LOC+13+{$discharge}::6:{$name}'";
        }

        // ── RFF (B/L references) ─────────────────────────────────────────────
        $segs[] = "RFF+BM:{$bl}'";
        if ($blDate) {
            $segs[] = "DTM+95:{$blDate}:102'";
        }
        $segs[] = "RFF+BN:{$bl}'";

        // ── TDT (transport/voyage) ────────────────────────────────────────────
        if ($callNum) {
            $portName = self::PORT_NAMES[$discharge] ?? 'PORT';
            $segs[] = "TDT+20+{$callNum}+1+13++++{$vesselCode}:103::GRANDE ABIDJAN'";
        }

        // ── NAD (parties) ─────────────────────────────────────────────────────
        // Shipper (FW)
        if ($shipperName) {
            $shipAddr = trim($d['shipper_address1'] ?? '');
            $nad = $this->buildNad('FW', 'NEW', $shipperName, $shipAddr, '');
            $segs[] = $nad;
            $segs[] = "DOC+705:::{$this->documentRef}+++1+3'";
        }

        // Carrier agent (CZ) = same as FW if no specific data
        if ($shipperName) {
            $shipAddr = trim($d['shipper_address1'] ?? '');
            $nad = $this->buildNad('CZ', 'NEW', $shipperName, $shipAddr, '');
            $segs[] = $nad;
            $segs[] = "DOC+705:::{$this->documentRef}+++1+3'";
        }

        // Consignee (CN)
        if ($consignee) {
            $consAddr  = implode(':', array_filter([$addr2, $addr3, $addr4 ?: $addr5]));
            $nad = $this->buildNad('CN', 'BROEKMAN', $consignee, $addr2, $addr5 ?: $addr4);
            $segs[] = $nad;
            $segs[] = "DOC+705:::{$this->documentRef}+++1+3'";
        }

        // Notify party (N1)
        if ($notify1) {
            $notifyAddr = implode(':', array_filter([$notify2, $notify3]));
            $nad = $this->buildNad('N1', 'BROEKMAN', $notify1, $notify2, $notify3);
            $segs[] = $nad;
            $segs[] = "DOC+705:::{$this->documentRef}+++1+3'";
        }

        // ── GID (goods) ───────────────────────────────────────────────────────
        $unit = ($transpMode === 'C') ? 'PCS:::PIECE(S)' : 'UNT:::UNIT(S)';
        $segs[] = "GID+1+{$nyi}:{$unit}'";
        $segs[] = "TMD+3'";

        // ── FTX (free text description) ───────────────────────────────────────
        if ($goods) {
            $ftx = $this->escapeFtx($goods);
            $segs[] = "FTX+AAA+++{$ftx}'";
        }

        // ── MEA (measurements) ────────────────────────────────────────────────
        if ($weight !== '') {
            $segs[] = "MEA+WT+AAE+KGM:{$weight}'";
        }
        if ($volume !== '') {
            $segs[] = "MEA+VOL+ABJ+MTQ:{$volume}'";
        }

        // ── SGP + EQD (container/equipment) ──────────────────────────────────
        if ($container) {
            $segs[] = "SGP+{$container}+{$nyi}'";

            if ($weight !== '') {
                $segs[] = "MEA+WT+AAE+KGM:{$weight}'";
            }
            if ($volume !== '') {
                $segs[] = "MEA+VOL+ABJ+MTQ:.000'";
            }

            $eqType  = $this->equipmentType($itemCode, $transpMode);
            $eqSize  = $this->equipmentSize($itemCode);
            $loadSt  = ($transpMode === 'C') ? 'C3' : 'C1';
            $segs[]  = "EQD+HV+{$container}+{$eqSize}+2+{$loadSt}+5'";
            $segs[]  = "TMD+3'";

            if ($weight !== '') {
                $segs[] = "MEA+WT+AAL+KGM:{$weight}'";
            }
            $segs[] = "MEA+WT+T+KGM:.000'";
            $segs[] = "MEA+VOL+ABJ+MTW:.000'";

            // Seal
            if ($seal1) {
                $segs[] = "SEL+{$seal1}'";
            }
            if ($seal2 && $seal2 !== $seal1) {
                $segs[] = "SEL+{$seal2}'";
            }
        }

        return $segs;
    }

    private function buildNad(string $role, string $qualifier, string $name, string $addr1, string $city): string
    {
        $name  = $this->escapeEdi($name);
        $addr1 = $this->escapeEdi($addr1);
        $city  = $this->escapeEdi($city);
        $parts = array_filter([$name, $addr1, $city]);
        $nad   = "NAD+{$role}+{$qualifier}+" . implode(':', $parts);
        return $nad . "'";
    }

    private function cleanSeal(string $seal): string
    {
        return trim(str_replace(['|', ' '], '', $seal));
    }

    private function escapeFtx(string $text): string
    {
        $text = preg_replace('/[+:\'?]/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function escapeEdi(string $text): string
    {
        return preg_replace('/[+:\']/', ' ', trim($text));
    }

    private function equipmentType(string $itemCode, string $mode): string
    {
        if ($mode === 'C') return '4500';
        return 'RO';
    }

    private function equipmentSize(string $itemCode): string
    {
        $code = strtoupper(trim($itemCode));
        if (str_contains($code, '40HC') || str_contains($code, '40HQ')) return '4500';
        if (str_contains($code, '40'))   return '4200';
        if (str_contains($code, '20'))   return '2200';
        return '4500';
    }

    private function generateDocRef(Collection $records): string
    {
        return (string) rand(100000000000, 999999999999);
    }
}
