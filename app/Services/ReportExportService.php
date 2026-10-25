<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportExportService
{
    /**
     * @param  array{
     *     from: string,
     *     to: string,
     *     totalIncome: float,
     *     totalExpenses: float,
     *     netBalance: float,
     *     harvestCount: int,
     *     harvestSummary: Collection<int, object>
     * }  $report
     */
    public function excel(array $report): string
    {
        $rows = [
            [['Ringkasan Laporan TaniKu', 'String', 'Title']],
            [['Periode', 'String', 'Header'], [$this->formatDate($report['from']), 'String', ''], ['sampai', 'String', ''], [$this->formatDate($report['to']), 'String', '']],
            [],
            [['Ringkasan keuangan', 'String', 'Header']],
            [['Pemasukan penjualan', 'String', ''], [$report['totalIncome'], 'Number', 'Currency']],
            [['Total pengeluaran', 'String', ''], [$report['totalExpenses'], 'Number', 'Currency']],
            [['Saldo bersih', 'String', 'Header'], [$report['netBalance'], 'Number', 'Currency']],
            [],
            [['Ringkasan panen', 'String', 'Header']],
            [['Jumlah catatan panen', 'String', ''], [$report['harvestCount'], 'Number', '']],
            [['Satuan', 'String', 'Header'], ['Jumlah panen', 'String', 'Header']],
        ];

        if ($report['harvestSummary']->isEmpty()) {
            $rows[] = [['Belum ada catatan panen pada periode ini.', 'String', '']];
        } else {
            foreach ($report['harvestSummary'] as $harvest) {
                $rows[] = [
                    [$harvest->unit, 'String', ''],
                    [(float) $harvest->quantity, 'Number', 'Quantity'],
                ];
            }
        }

        $xmlRows = collect($rows)->map(function (array $cells): string {
            $xmlCells = collect($cells)->map(function (array $cell): string {
                [$value, $type, $style] = $cell;
                $styleAttribute = $style === '' ? '' : ' ss:StyleID="'.$style.'"';
                $escapedValue = $type === 'Number'
                    ? (string) $value
                    : htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

                return '<ss:Cell'.$styleAttribute.'><ss:Data ss:Type="'.$type.'">'.$escapedValue.'</ss:Data></ss:Cell>';
            })->implode('');

            return '<ss:Row>'.$xmlCells.'</ss:Row>';
        })->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:o="urn:schemas-microsoft-com:office:office" '
            .'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            .'xmlns:html="http://www.w3.org/TR/REC-html40">'
            .'<ss:Styles>'
            .'<ss:Style ss:ID="Default" ss:Name="Normal"><ss:Alignment ss:Vertical="Center"/>'
            .'<ss:Font ss:FontName="Arial" ss:Size="10"/></ss:Style>'
            .'<ss:Style ss:ID="Title"><ss:Font ss:FontName="Arial" ss:Size="16" ss:Bold="1"/>'
            .'<ss:Alignment ss:Vertical="Center"/></ss:Style>'
            .'<ss:Style ss:ID="Header"><ss:Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>'
            .'<ss:Interior ss:Color="#E7F1E2" ss:Pattern="Solid"/></ss:Style>'
            .'<ss:Style ss:ID="Currency"><ss:NumberFormat ss:Format="&quot;Rp&quot; #,##0.00"/></ss:Style>'
            .'<ss:Style ss:ID="Quantity"><ss:NumberFormat ss:Format="#,##0.00"/></ss:Style>'
            .'</ss:Styles><ss:Worksheet ss:Name="Laporan"><ss:Table ss:DefaultColumnWidth="180">'
            .$xmlRows
            .'</ss:Table><x:WorksheetOptions><x:PageSetup><x:Layout x:Orientation="Portrait"/></x:PageSetup>'
            .'<x:Print><x:ValidPrinterInfo/><x:PaperSizeIndex>9</x:PaperSizeIndex><x:FitWidth>1</x:FitWidth>'
            .'<x:FitHeight>0</x:FitHeight></x:Print><x:Selected/><x:DoNotDisplayGridlines/></x:WorksheetOptions>'
            .'</ss:Worksheet></ss:Workbook>';
    }

    /**
     * @param  array{
     *     from: string,
     *     to: string,
     *     totalIncome: float,
     *     totalExpenses: float,
     *     netBalance: float,
     *     harvestCount: int,
     *     harvestSummary: Collection<int, object>
     * }  $report
     */
    public function pdf(array $report): string
    {
        $rows = [
            ['Laporan Terpadu TaniKu', 20, true],
            ['Periode: '.$this->formatDate($report['from']).' - '.$this->formatDate($report['to']), 10, false],
            ['', 8, false],
            ['Ringkasan Keuangan', 13, true],
            ['Pemasukan penjualan', 10, false, $this->formatCurrency($report['totalIncome'])],
            ['Total pengeluaran', 10, false, $this->formatCurrency($report['totalExpenses'])],
            ['Saldo bersih', 11, true, $this->formatCurrency($report['netBalance'])],
            ['', 8, false],
            ['Ringkasan Panen ('.$report['harvestCount'].' catatan)', 13, true],
            ['Satuan', 10, true, 'Jumlah panen'],
        ];

        if ($report['harvestSummary']->isEmpty()) {
            $rows[] = ['Belum ada catatan panen pada periode ini.', 10, false];
        } else {
            foreach ($report['harvestSummary'] as $harvest) {
                $rows[] = [$harvest->unit, 10, false, number_format((float) $harvest->quantity, 2, ',', '.')];
            }
        }

        $pages = [];
        $content = '';
        $y = 794;

        foreach ($rows as $row) {
            if ($row === []) {
                $y -= 12;

                continue;
            }

            [$label, $fontSize, $bold] = $row;
            $value = $row[3] ?? null;

            if ($y < 58) {
                $pages[] = $content;
                $content = '';
                $y = 794;
            }

            if ($label === 'Ringkasan Keuangan' || str_starts_with($label, 'Ringkasan Panen')) {
                $content .= '0.18 0.42 0.31 rg 48 '.($y - 4).' 499 1 re f ';
                $y -= 4;
            }

            $font = $bold ? 'F2' : 'F1';
            $content .= 'BT /'.$font.' '.$fontSize.' Tf 48 '.$y.' Td ('.$this->pdfText($label).') Tj ET ';

            if (isset($value)) {
                $content .= 'BT /'.$font.' '.$fontSize.' Tf 365 '.$y.' Td ('.$this->pdfText($value).') Tj ET ';
            }

            $y -= $fontSize + 13;
        }

        if ($content !== '') {
            $pages[] = $content;
        }

        return $this->buildPdf($pages);
    }

    /**
     * @param  array<int, string>  $pageContents
     */
    private function buildPdf(array $pageContents): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pageReferences = [];
        $nextObjectId = 5;

        foreach ($pageContents as $pageContent) {
            $pageId = $nextObjectId++;
            $contentId = $nextObjectId++;
            $pageReferences[] = $pageId.' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = '<< /Length '.strlen($pageContent)." >>\nstream\n".$pageContent."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.count($pageReferences).' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $objectId => $object) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($objectId = 1; $objectId <= count($objects); $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xrefOffset."\n%%EOF";
    }

    private function pdfText(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        if ($encoded === false) {
            $encoded = '';
        }

        $encoded = preg_replace('/[^\x20-\xFF]/', '', $encoded) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    private function formatCurrency(float $amount): string
    {
        return 'Rp '.number_format($amount, 2, ',', '.');
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }
}
