<?php

namespace App\Http\Controllers;

use App\Models\SuiviStationnement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SuiviStationnementController extends Controller
{
    use \App\Http\Controllers\Concerns\StreamsXlsx;
    use \App\Http\Controllers\Concerns\ParsesXlsx;
    public function index(Request $request): JsonResponse
    {
        $size = $request->integer('size') ?: 5;
        $page = $request->integer('page') + 1;

        $query = SuiviStationnement::query()->orderByDesc('created_at');

        foreach ([
            'terminal'    => 'terminal',
            'billingDate' => 'billing_date',
            'shipowner'   => 'shipowner',
            'blNumber'    => 'bl_number',
            'itemNumber'  => 'item_number',
            'itemType'    => 'item_type',
            'type'        => 'type',
            'entryDate'   => 'entry_date',
            'exitDate'    => 'exit_date',
        ] as $param => $column) {
            $val = $request->string($param)->toString() ?: null;
            if ($val !== null) {
                $query->where($column, 'like', '%'.$val.'%');
            }
        }

        $paginator = $query->paginate($size, ['*'], 'page', $page);

        return response()->json([
            'content' => collect($paginator->items())->map(fn (SuiviStationnement $r) => $this->toArray($r))->values(),
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
                SuiviStationnement::truncate();
                $count = $this->importCsv($file->getRealPath());
            } elseif ($extension === 'xlsx') {
                SuiviStationnement::truncate();
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
            'suivi-stationnements-'.now()->format('YmdHis').'.xlsx',
            ['Terminal', 'BillingDate', 'Shipowner', 'BLNumber', 'Item_Number', 'Item_Type', 'Type', 'EntryDate', 'ExitDate', 'DaysSinceIn'],
            function (callable $write) {
                SuiviStationnement::query()->orderByDesc('created_at')->cursor()->each(function (SuiviStationnement $r) use ($write) {
                    $write([$r->terminal, $r->billing_date, $r->shipowner, $r->bl_number, $r->item_number, $r->item_type, $r->type, $r->entry_date, $r->exit_date, $r->days_since_in]);
                });
            }
        );
    }

    public function destroy(Request $request, SuiviStationnement $suivi): Response
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

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if (! $rawHeader) {
            fclose($handle);
            throw new \RuntimeException('En-tete du fichier introuvable.');
        }

        $normalizedHeaders = array_map(fn ($h) => $this->normalizeHeader($h), $rawHeader);
        $columnInfo = $this->buildColumnInfo($normalizedHeaders);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (array_filter($row) === [] || $this->isMetaRowStationnement($row)) {
                continue;
            }
            $data = $this->buildRow($row, $columnInfo);
            if ($data !== null) {
                $rows[] = $data;
            }

            if (count($rows) >= 200) {
                SuiviStationnement::query()->insert($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (! empty($rows)) {
            SuiviStationnement::query()->insert($rows);
        }

        return SuiviStationnement::query()->count();
    }

    private function importXlsx(string $path): int
    {
        $columnInfo = null;
        $insertRows = [];

        $this->parseXlsx($path, function (array $rowValues) use (&$columnInfo, &$insertRows) {
            if ($this->isMetaRowStationnement($rowValues)) {
                return;
            }

            if ($columnInfo === null) {
                $headers    = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rowValues);
                $columnInfo = $this->buildColumnInfo($headers);

                return;
            }

            if (empty($columnInfo)) {
                return;
            }

            $data = $this->buildRow($rowValues, $columnInfo);
            if ($data === null) {
                return;
            }

            $insertRows[] = $data;

            if (count($insertRows) >= 200) {
                SuiviStationnement::query()->insert($insertRows);
                $insertRows = [];
            }
        });

        if (! empty($insertRows)) {
            SuiviStationnement::query()->insert($insertRows);
        }

        return SuiviStationnement::query()->count();
    }

    /**
     * Build a simple column index map from normalized headers.
     *
     * Handles data(3).xlsx structure:
     *   TerminalName | BilingDateTime - Month | Shipowner | BLNumber |
     *   Item_Number  | Item_Type              | Type      |
     *   EntryDate - Month | ExitDate - Month  | DaysSinceIn
     */
    /**
     * Build a column index map from normalized headers.
     *
     * Direct DB columns are mapped as-is.
     * Date sub-columns (Year / Month / Day) use "_" prefix keys so that
     * buildRow() can reconstruct them into a single dd/mm/yyyy string.
     */
    private function buildColumnInfo(array $normalizedHeaders): array
    {
        $aliases = [
            'terminalname'           => 'terminal',
            'terminal'               => 'terminal',
            'shipowner'              => 'shipowner',
            'blnumber'               => 'bl_number',
            'bl'                     => 'bl_number',
            'itemnumber'             => 'item_number',
            'itemtype'               => 'item_type',
            'type'                   => 'type',
            // Billing date sub-columns
            'bilingdatetimeyear'     => '_billing_year',
            'billingdatetimeyear'    => '_billing_year',
            'bilingyear'             => '_billing_year',
            'billingyear'            => '_billing_year',
            'bilingdatetimemonth'    => '_billing_month',
            'billingdatetimemonth'   => '_billing_month',
            'billingdatemonth'       => '_billing_month',
            'bilingdatemonth'        => '_billing_month',
            'bilingdatetimeday'      => '_billing_day',
            'billingdatetimeday'     => '_billing_day',
            'billingdateday'         => '_billing_day',
            'bilingdateday'          => '_billing_day',
            // Entry date sub-columns
            'entrydateyear'          => '_entry_year',
            'entrydatemonth'         => '_entry_month',
            'entrydate'              => '_entry_month',
            'entrydateday'           => '_entry_day',
            // Exit date sub-columns
            'exitdateyear'           => '_exit_year',
            'exitdatemonth'          => '_exit_month',
            'exitdate'               => '_exit_month',
            'exitdateday'            => '_exit_day',
            // Days
            'dayssincein'            => 'days_since_in',
            'sommedayssincein'       => 'days_since_in',
            'totaldayssincein'       => 'days_since_in',
        ];

        $map = [];
        foreach ($normalizedHeaders as $idx => $h) {
            if (isset($aliases[$h]) && ! isset($map[$aliases[$h]])) {
                $map[$aliases[$h]] = $idx;
            }
        }

        return $map;
    }

    private function buildRow(array $rowValues, array $columnMap): ?array
    {
        $data = [];

        // Direct columns
        foreach (['terminal', 'shipowner', 'bl_number', 'item_number', 'item_type', 'type', 'days_since_in'] as $dbCol) {
            if (! isset($columnMap[$dbCol])) {
                continue;
            }
            $val = (string) ($rowValues[$columnMap[$dbCol]] ?? '');
            $data[$dbCol] = ($dbCol === 'days_since_in')
                ? (is_numeric($val) ? (float) $val : null)
                : ($val !== '' ? $val : null);
        }

        // Compound date columns: Year + Month + Day → dd/mm/yyyy
        foreach ([
            'billing_date' => ['_billing_year', '_billing_month', '_billing_day'],
            'entry_date'   => ['_entry_year',   '_entry_month',   '_entry_day'],
            'exit_date'    => ['_exit_year',    '_exit_month',    '_exit_day'],
        ] as $dbCol => [$yearKey, $monthKey, $dayKey]) {
            $year  = isset($columnMap[$yearKey])  ? trim((string) ($rowValues[$columnMap[$yearKey]]  ?? '')) : '';
            $month = isset($columnMap[$monthKey]) ? trim((string) ($rowValues[$columnMap[$monthKey]] ?? '')) : '';
            $day   = isset($columnMap[$dayKey])   ? trim((string) ($rowValues[$columnMap[$dayKey]]   ?? '')) : '';

            if ($year !== '' && $month !== '' && $day !== '') {
                $monthNum = $this->englishMonthToNumber($month);
                $data[$dbCol] = $monthNum
                    ? sprintf('%02d/%02d/%04d', (int) $day, $monthNum, (int) $year)
                    : null;
            } elseif ($month !== '') {
                // Fallback: month-only column (old format without Year/Day)
                $data[$dbCol] = $this->normalizeToFrenchMonth($month);
            } else {
                $data[$dbCol] = null;
            }
        }

        if (array_filter($data) === []) {
            return null;
        }

        return $data;
    }

    private function englishMonthToNumber(string $month): ?int
    {
        return [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
            'janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12,
        ][strtolower($month)] ?? null;
    }

    private function normalizeToFrenchMonth(string $value): ?string
    {
        if (is_numeric($value)) {
            $excelSerial = (float) $value;
            if ($excelSerial > 0) {
                $seconds = (int) round(($excelSerial - 25569) * 86400);

                return $this->englishMonthToFrench(gmdate('F', $seconds));
            }

            return null;
        }

        $ts = strtotime($value);
        if ($ts !== false) {
            return $this->englishMonthToFrench(date('F', $ts));
        }

        return $this->englishMonthToFrench($value);
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

    private function isMetaRowStationnement(array $rowValues): bool
    {
        $first = strtolower(trim((string) ($rowValues[0] ?? '')));

        return str_starts_with($first, 'aucun filtre')
            || str_starts_with($first, 'filtres')
            || $first === 'total';
    }

    private function normalizeHeader(string $header): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $header);

        return strtolower(preg_replace('/[\s\-_]+/', '', trim($h)));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'ADMIN', 403);
    }

    private function toArray(SuiviStationnement $r): array
    {
        return [
            'id'          => $r->id,
            'terminal'    => $r->terminal,
            'billingDate' => $r->billing_date,
            'shipowner'   => $r->shipowner,
            'blNumber'    => $r->bl_number,
            'itemNumber'  => $r->item_number,
            'itemType'    => $r->item_type,
            'type'        => $r->type,
            'entryDate'   => $r->entry_date,
            'exitDate'    => $r->exit_date,
            'daysSinceIn' => $r->days_since_in,
        ];
    }
}
