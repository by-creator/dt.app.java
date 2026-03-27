<?php

namespace App\Http\Controllers;

use App\Models\SuiviVide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RapportController extends Controller
{
    use \App\Http\Controllers\Concerns\StreamsXlsx;
    use \App\Http\Controllers\Concerns\ParsesXlsx;
    public function index(Request $request): JsonResponse
    {
        $size = $request->integer('size') ?: 5;
        $page = $request->integer('page') + 1;

        $query = SuiviVide::query()->orderByDesc('created_at');

        foreach ([
            'terminal'          => 'terminal',
            'equipmentNumber'   => 'equipment_number',
            'equipmentTypeSize' => 'equipment_type_size',
            'eventCode'         => 'event_code',
            'eventName'         => 'event_name',
            'eventFamily'       => 'event_family',
            'eventDate'         => 'event_date',
            'bookingSecNo'      => 'booking_sec_no',
        ] as $param => $column) {
            $val = $request->string($param)->toString() ?: null;
            if ($val !== null) {
                $query->where($column, 'like', '%'.$val.'%');
            }
        }

        $paginator = $query->paginate($size, ['*'], 'page', $page);

        return response()->json([
            'content' => collect($paginator->items())->map(fn (SuiviVide $r) => $this->toArray($r))->values(),
            'page' => $paginator->currentPage() - 1,
            'size' => $paginator->perPage(),
            'totalElements' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'first' => $paginator->onFirstPage(),
            'last' => ! $paginator->hasMorePages(),
        ]);
    }

    public function import(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $validated['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'csv') {
                SuiviVide::truncate();
                $count = $this->importCsv($file->getRealPath());
            } elseif ($extension === 'xlsx') {
                SuiviVide::truncate();
                $count = $this->importXlsx($file->getRealPath());
            } elseif ($extension === 'xls') {
                return response('Le format XLS n\'est pas pris en charge actuellement. Utilisez un fichier CSV ou XLSX.', 422);
            } else {
                return response('Format non supporte. Utilisez CSV ou XLSX.', 422);
            }

            return response('Import effectue avec succes : '.$count.' ligne(s) importee(s).');
        } catch (\Throwable $e) {
            return response('Erreur lors de l\'import : '.$e->getMessage(), 400);
        }
    }

    public function exportExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamXlsx(
            'suivi-vides-'.now()->format('YmdHis').'.xlsx',
            ['Terminal', 'EquipmentNumber', 'EquipmentTypeSize', 'EventCode', 'EventName', 'EventFamily', 'EventDate', 'Booking Sec No'],
            function (callable $write) {
                SuiviVide::query()->orderByDesc('created_at')->cursor()->each(function (SuiviVide $r) use ($write) {
                    $write([$r->terminal, $r->equipment_number, $r->equipment_type_size, $r->event_code, $r->event_name, $r->event_family, $r->event_date, $r->booking_sec_no]);
                });
            }
        );
    }

    public function destroy(Request $request, SuiviVide $suivi): Response
    {
        $this->authorizeAdmin($request);
        $suivi->delete();

        return response()->noContent();
    }

    private function importCsv(string $path): int
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier.');
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        // Read header
        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if (! $rawHeader) {
            fclose($handle);
            throw new \RuntimeException('En-tete du fichier introuvable.');
        }

        $header = array_map(fn ($h) => $this->normalizeHeader($h), $rawHeader);
        $columnMap = $this->buildColumnMap($header);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (array_filter($row) === [] || $this->isMetaRowVides($row)) {
                continue;
            }
            $data = [];
            foreach ($columnMap as $dbCol => $idx) {
                $data[$dbCol] = isset($row[$idx]) && $row[$idx] !== '' ? $row[$idx] : null;
            }
            $rows[] = $data;

            if (count($rows) >= 200) {
                SuiviVide::query()->insert($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (! empty($rows)) {
            SuiviVide::query()->insert($rows);
        }

        return SuiviVide::query()->count();
    }

    private function importXlsx(string $path): int
    {
        $header    = null;
        $columnMap = null;
        $insertRows = [];

        $this->parseXlsx($path, function (array $rowValues) use (&$header, &$columnMap, &$insertRows) {
            if ($this->isMetaRowVides($rowValues)) {
                return;
            }

            if ($header === null) {
                $header    = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rowValues);
                $columnMap = $this->buildColumnMap($header);

                return;
            }

            if (empty($columnMap)) {
                return;
            }

            $data = [];
            foreach ($columnMap as $dbCol => $idx) {
                $val = (string) ($rowValues[$idx] ?? '');
                $data[$dbCol] = ($dbCol === 'event_date')
                    ? $this->normalizeEventDate($val)
                    : ($val !== '' ? $val : null);
            }

            if (array_filter($data) === []) {
                return;
            }

            $insertRows[] = $data;

            if (count($insertRows) >= 200) {
                SuiviVide::query()->insert($insertRows);
                $insertRows = [];
            }
        });

        if (! empty($insertRows)) {
            SuiviVide::query()->insert($insertRows);
        }

        return SuiviVide::query()->count();
    }

    private function isMetaRowVides(array $rowValues): bool
    {
        $first = strtolower(trim((string) ($rowValues[0] ?? '')));

        return str_starts_with($first, 'filtres') || str_starts_with($first, 'aucun filtre');
    }

    private function normalizeHeader(string $header): string
    {
        // Remove BOM, trim, lowercase, remove spaces/underscores/dashes
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $header);

        return strtolower(preg_replace('/[\s\-_]+/', '', trim($h)));
    }

    private function buildColumnMap(array $normalizedHeaders): array
    {
        $aliases = [
            'terminal'          => 'terminal',
            'equipmentnumber'   => 'equipment_number',
            'equipmentno'       => 'equipment_number',
            'equipmenttypesize' => 'equipment_type_size',
            'typesize'          => 'equipment_type_size',
            'eventcode'         => 'event_code',
            'eventname'         => 'event_name',
            'eventfamily'       => 'event_family',
            'eventdate'         => 'event_date',
            'bookingsecno'      => 'booking_sec_no',
            'bookingno'         => 'booking_sec_no',
            'bookingsec'        => 'booking_sec_no',
        ];

        $map = [];
        foreach ($normalizedHeaders as $idx => $h) {
            if (isset($aliases[$h])) {
                $map[$aliases[$h]] = $idx;
            }
        }

        return $map;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'ADMIN', 403);
    }

    private function normalizeEventDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $excelSerial = (float) $value;

            if ($excelSerial > 0) {
                $seconds = (int) round(($excelSerial - 25569) * 86400);

                return $this->englishMonthToFrench(gmdate('F', $seconds)).' '.gmdate('Y', $seconds);
            }

            return null;
        }

        $ts = strtotime($value);
        if ($ts !== false) {
            return $this->englishMonthToFrench(date('F', $ts)).' '.date('Y', $ts);
        }

        return $this->englishMonthToFrench($value) ?? $value;
    }

    private function englishMonthToFrench(string $month): string
    {
        return [
            'January'   => 'Janvier',
            'February'  => 'Fevrier',
            'March'     => 'Mars',
            'April'     => 'Avril',
            'May'       => 'Mai',
            'June'      => 'Juin',
            'July'      => 'Juillet',
            'August'    => 'Aout',
            'September' => 'Septembre',
            'October'   => 'Octobre',
            'November'  => 'Novembre',
            'December'  => 'Decembre',
        ][$month] ?? $month;
    }

    private function toArray(SuiviVide $r): array
    {
        return [
            'id'                => $r->id,
            'terminal'          => $r->terminal,
            'equipmentNumber'   => $r->equipment_number,
            'equipmentTypeSize' => $r->equipment_type_size,
            'eventCode'         => $r->event_code,
            'eventName'         => $r->event_name,
            'eventFamily'       => $r->event_family,
            'eventDate'         => $r->event_date,
            'bookingSecNo'      => $r->booking_sec_no,
        ];
    }
}
