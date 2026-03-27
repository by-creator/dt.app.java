<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates XLSX files without loading all data into memory.
 * Writes sheet XML row-by-row to a temp file, then assembles
 * the ZIP (XLSX) from disk — O(1) PHP memory regardless of dataset size.
 */
trait StreamsXlsx
{
    protected function streamXlsx(string $filename, array $headers, \Closure $dataWriter): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $dataWriter) {
            // Write sheet XML row-by-row to a temp file
            $sheetTmp = tempnam(sys_get_temp_dir(), 'dt_sheet_');
            $sh = fopen($sheetTmp, 'w');

            fwrite($sh, "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n");
            fwrite($sh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');

            $rowNum = 1;
            fwrite($sh, $this->xlsxRow($rowNum++, $headers));

            $dataWriter(function (array $values) use ($sh, &$rowNum) {
                fwrite($sh, $this->xlsxRow($rowNum++, $values));
            });

            fwrite($sh, '</sheetData></worksheet>');
            fclose($sh);

            // Assemble the XLSX (ZIP) — addFile() reads from disk, no memory spike
            $xlsxTmp = tempnam(sys_get_temp_dir(), 'dt_xlsx_');
            $zip = new \ZipArchive();
            $zip->open($xlsxTmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $zip->addFromString('[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '</Types>'
            );
            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>'
            );
            $zip->addFromString('xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>'
            );
            $zip->addFromString('xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '</Relationships>'
            );
            $zip->addFile($sheetTmp, 'xl/worksheets/sheet1.xml');
            $zip->close();

            readfile($xlsxTmp);

            unlink($sheetTmp);
            unlink($xlsxTmp);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function xlsxRow(int $rowNum, array $values): string
    {
        $xml = "<row r=\"{$rowNum}\">";
        foreach ($values as $i => $value) {
            $col = $this->xlsxColLetter($i + 1);
            $ref = $col.$rowNum;
            $safe = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$safe}</t></is></c>";
        }
        return $xml.'</row>';
    }

    private function xlsxColLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)).$letter;
            $col = intdiv($col, 26);
        }
        return $letter;
    }
}
