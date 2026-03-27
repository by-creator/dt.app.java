<?php

namespace App\Services;

use App\Models\TiersUnify;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TiersUnifyService
{
    public function saveTiers(TiersUnify|array $data): TiersUnify
    {
        $payload = $data instanceof TiersUnify ? $data->toArray() : $data;

        return TiersUnify::query()->create([
            'raison_sociale' => strtoupper(trim((string) ($payload['raison_sociale'] ?? $payload['raisonSociale'] ?? ''))),
            'compte_ipaki' => trim((string) ($payload['compte_ipaki'] ?? $payload['compteIpaki'] ?? '')),
            'compte_neptune' => $this->nullableString($payload['compte_neptune'] ?? $payload['compteNeptune'] ?? null),
            'created_at' => now(),
        ]);
    }

    public function listTiers(?string $search, int $page, int $size): LengthAwarePaginator
    {
        return TiersUnify::query()
            ->when($search, function ($query, $search) {
                $term = '%'.$search.'%';

                $query->where(function ($builder) use ($term) {
                    $builder->where('raison_sociale', 'like', $term)
                        ->orWhere('compte_ipaki', 'like', $term)
                        ->orWhere('compte_neptune', 'like', $term);
                });
            })
            ->orderBy('raison_sociale')
            ->paginate(
                perPage: max(1, $size),
                columns: ['*'],
                pageName: 'page',
                page: max(1, $page + 1),
            );
    }

    public function findAll(): \Illuminate\Support\Collection
    {
        return TiersUnify::query()->orderBy('raison_sociale')->get();
    }

    public function saveAll(iterable $items): void
    {
        foreach ($items as $item) {
            $this->saveTiers($item instanceof TiersUnify ? $item : (array) $item);
        }
    }

    public function importCsv(UploadedFile $file): int
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'csv') {
            throw new \InvalidArgumentException('Format non supporte. Utilisez un fichier .csv pour l\'import massif.');
        }

        $path = $file->getRealPath();
        if (! $path) {
            throw new \RuntimeException('Impossible de lire le fichier importe.');
        }

        $delimiter = $this->detectCsvDelimiter($path);
        $handle = fopen($path, 'rb');

        if (! is_resource($handle)) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier CSV.');
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header) || $header === []) {
            fclose($handle);
            throw new \InvalidArgumentException('Le fichier CSV est vide ou invalide.');
        }

        $columns = collect($header)
            ->map(fn ($value) => $this->normalizeHeader((string) $value))
            ->values()
            ->all();

        $requiredColumns = ['raisonsociale', 'compteipaki'];
        foreach ($requiredColumns as $requiredColumn) {
            if (! in_array($requiredColumn, $columns, true)) {
                fclose($handle);
                throw new \InvalidArgumentException('Colonnes attendues : raisonSociale, compteIpaki et compteNeptune.');
            }
        }

        $now = now();
        $rows = [];
        $count = 0;

        try {
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->rowIsEmpty($data)) {
                    continue;
                }

                $row = array_pad($data, count($columns), null);
                $mapped = array_combine($columns, array_slice($row, 0, count($columns)));

                $raisonSociale = strtoupper(trim((string) ($mapped['raisonsociale'] ?? '')));
                $compteIpaki = trim((string) ($mapped['compteipaki'] ?? ''));

                if ($raisonSociale === '' || $compteIpaki === '') {
                    continue;
                }

                $rows[] = [
                    'raison_sociale' => $raisonSociale,
                    'compte_ipaki' => $compteIpaki,
                    'compte_neptune' => $this->nullableString($mapped['compteneptune'] ?? null),
                    'created_at' => $now,
                ];

                if (count($rows) >= 500) {
                    TiersUnify::query()->insert($rows);
                    $count += count($rows);
                    $rows = [];
                }
            }

            if ($rows !== []) {
                TiersUnify::query()->insert($rows);
                $count += count($rows);
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');
        $header = $handle ? (string) fgets($handle) : '';
        if (is_resource($handle)) {
            fclose($handle);
        }

        return substr_count($header, ';') >= substr_count($header, ',') ? ';' : ',';
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = trim($value);

        return strtolower(str_replace([' ', '_', '-'], '', $value));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
