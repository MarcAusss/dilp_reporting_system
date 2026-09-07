<?php

namespace App\Services\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelXmlExporter
{
    public function download(array $report): StreamedResponse
    {
        $filename = sprintf(
            'DILP_%s_FY%s_%s.xls',
            $report['type']->shortCode(),
            $report['filters']['fiscal_year'],
            now()->format('Ymd_His')
        );

        return response()->streamDownload(
            function () use ($report): void {
                echo $this->xml($report);
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    private function xml(array $report): string
    {
        $columns = $report['columns'];
        $rows = $report['rows'];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#DCE6F1" ss:Pattern="Solid"/><Borders>'
            . '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>'
            . '</Borders></Style>';
        $xml .= '<Style ss:ID="Money"><NumberFormat ss:Format="#,##0.00"/></Style>';
        $xml .= '<Style ss:ID="Percent"><NumberFormat ss:Format="0.00\%"/></Style>';
        $xml .= '</Styles>';
        $xml .= '<Worksheet ss:Name="' . $this->escape($report['type']->label()) . '"><Table>';

        $xml .= '<Row><Cell ss:MergeAcross="' . max(0, count($columns) - 1) . '"><Data ss:Type="String">'
            . $this->escape('DILP ' . $report['title'] . ' Report')
            . '</Data></Cell></Row>';

        $filterText = collect($report['filterLabels'])
            ->map(fn ($value, $label) => $label . ': ' . $value)
            ->implode(' | ');
        $xml .= '<Row><Cell ss:MergeAcross="' . max(0, count($columns) - 1) . '"><Data ss:Type="String">'
            . $this->escape($filterText)
            . '</Data></Cell></Row>';

        $xml .= '<Row>';
        foreach ($columns as $column) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'
                . $this->escape($column['label'])
                . '</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($columns as $column) {
                $value = $row[$column['key']] ?? '';
                $type = $column['type'] ?? 'text';

                if (in_array($type, ['money', 'integer'], true)) {
                    $xml .= '<Cell' . ($type === 'money' ? ' ss:StyleID="Money"' : '') . '><Data ss:Type="Number">'
                        . (is_numeric($value) ? $value : 0)
                        . '</Data></Cell>';
                } elseif ($type === 'percent') {
                    $numeric = is_numeric($value) ? ((float) $value / 100) : 0;
                    $xml .= '<Cell ss:StyleID="Percent"><Data ss:Type="Number">'
                        . $numeric
                        . '</Data></Cell>';
                } else {
                    $xml .= '<Cell><Data ss:Type="String">'
                        . $this->escape((string) $value)
                        . '</Data></Cell>';
                }
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
