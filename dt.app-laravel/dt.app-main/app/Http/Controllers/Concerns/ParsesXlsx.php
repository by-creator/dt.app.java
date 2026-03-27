<?php

namespace App\Http\Controllers\Concerns;

/**
 * Streaming XLSX parser using XMLReader.
 *
 * Replaces DOMDocument::loadXML() on the sheet XML — the sheet is streamed
 * node-by-node from a temp file so it never lives entirely in PHP memory,
 * regardless of file size.
 *
 * Usage:
 *   $this->parseXlsx($path, function (array $rowValues) { ... });
 *
 * $rowValues is a zero-indexed array of raw string values for each row.
 * The first call will be the header row; subsequent calls are data rows.
 */
trait ParsesXlsx
{
    /**
     * Stream-parse an XLSX file, calling $onRow once per non-empty row
     * (header row included as the first call).
     */
    protected function parseXlsx(string $path, callable $onRow): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier XLSX (format invalide).');
        }

        // --- Shared strings (separate file, usually small — DOM is fine) ---
        $sharedStrings = [];
        $ssRaw = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssRaw !== false) {
            $ssDom = new \DOMDocument();
            $ssDom->loadXML($ssRaw, LIBXML_COMPACT | LIBXML_NOWARNING);
            $ssXpath = new \DOMXPath($ssDom);
            $ssXpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($ssXpath->query('//s:si') as $si) {
                $tNodes = $ssXpath->query('s:t|s:r/s:t', $si);
                $text = '';
                foreach ($tNodes as $t) {
                    $text .= $t->nodeValue;
                }
                $sharedStrings[] = $text;
            }
            unset($ssDom, $ssXpath, $ssRaw);
        }

        // --- Sheet XML → streamed to temp file to avoid loading in memory ---
        $sheetTmp = tempnam(sys_get_temp_dir(), 'dt_xl_');

        $stream = $zip->getStream('xl/worksheets/sheet1.xml')
               ?: $zip->getStream('xl/worksheets/Sheet1.xml');

        if ($stream === false) {
            $zip->close();
            unlink($sheetTmp);
            throw new \RuntimeException('Feuille de calcul introuvable dans le fichier XLSX.');
        }

        $out = fopen($sheetTmp, 'w');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
        $zip->close();

        // --- XMLReader: one node at a time, O(1) memory ---
        $reader = new \XMLReader();
        if (! $reader->open($sheetTmp)) {
            unlink($sheetTmp);
            throw new \RuntimeException('Impossible d\'analyser le fichier de feuille.');
        }

        $rowData  = [];
        $inRow    = false;
        $inCell   = false;
        $inV      = false;
        $inT      = false;
        $cellRef  = '';
        $cellType = '';
        $cellVal  = '';
        $colSeq   = 0; // fallback sequential index when "r" attribute is absent

        while ($reader->read()) {
            $type = $reader->nodeType;
            $name = $reader->localName;

            if ($type === \XMLReader::ELEMENT) {
                if ($name === 'row') {
                    $inRow  = true;
                    $rowData = [];
                    $colSeq  = 0;
                } elseif ($name === 'c' && $inRow) {
                    $inCell   = true;
                    $cellRef  = $reader->getAttribute('r') ?? '';
                    $cellType = $reader->getAttribute('t') ?? '';
                    $cellVal  = '';
                    $inV      = false;
                    $inT      = false;
                } elseif ($name === 'v' && $inCell) {
                    $inV = true;
                } elseif ($name === 't' && $inCell) {
                    $inT = true;
                }
            } elseif ($type === \XMLReader::TEXT || $type === \XMLReader::CDATA) {
                if ($inV) {
                    $cellVal .= $reader->value;
                } elseif ($inT) {
                    $cellVal .= $reader->value;
                }
            } elseif ($type === \XMLReader::END_ELEMENT) {
                if ($name === 'v') {
                    $inV = false;
                } elseif ($name === 't') {
                    $inT = false;
                } elseif ($name === 'c' && $inCell) {
                    $inCell = false;

                    // Resolve final value
                    if ($cellType === 's') {
                        $val = $sharedStrings[(int) $cellVal] ?? '';
                    } else {
                        $val = $cellVal; // inlineStr or numeric
                    }

                    // Column index from ref attribute ("A1" → 0) or sequential
                    if ($cellRef !== '' && preg_match('/^([A-Z]+)/', $cellRef, $m)) {
                        $colIdx = $this->xlsxColLetterToIndex($m[1]);
                    } else {
                        $colIdx = $colSeq;
                    }

                    $rowData[$colIdx] = $val;
                    $colSeq = $colIdx + 1;
                } elseif ($name === 'row' && $inRow) {
                    $inRow = false;

                    if (empty($rowData)) {
                        continue;
                    }

                    // Fill sparse gaps (missing cells in the middle)
                    $maxCol = max(array_keys($rowData));
                    for ($i = 0; $i <= $maxCol; $i++) {
                        if (! isset($rowData[$i])) {
                            $rowData[$i] = '';
                        }
                    }
                    ksort($rowData);

                    $onRow(array_values($rowData));
                }
            }
        }

        $reader->close();
        unlink($sheetTmp);
    }

    private function xlsxColLetterToIndex(string $col): int
    {
        $index = 0;
        for ($i = 0, $len = strlen($col); $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }

        return $index - 1;
    }
}
