<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Excel export without external dependencies.
 * Produces SpreadsheetML 2003 XML which Microsoft Excel (and LibreOffice,
 * Google Sheets) opens natively as a formatted worksheet.
 */
final class ExcelExport
{
    /**
     * @param array<int,string>  $headers
     * @param array<int,array<int,mixed>> $rows
     */
    public static function download(string $filename, string $sheetTitle, array $headers, array $rows): never
    {
        if (!str_ends_with(strtolower($filename), '.xls')) {
            $filename .= '.xls';
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Styles>
  <Style ss:ID="hdr">
   <Font ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#0B7A3E" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="title">
   <Font ss:Bold="1" ss:Size="13"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="<?= self::escapeXml($sheetTitle) ?>">
  <Table>
   <Row><Cell ss:StyleID="title"><Data ss:Type="String"><?= self::escapeXml($sheetTitle) ?> — KUTT SUKA MAKMUR (dicetak <?= date('d/m/Y H:i') ?>)</Data></Cell></Row>
   <Row></Row>
   <Row>
<?php foreach ($headers as $header): ?>
    <Cell ss:StyleID="hdr"><Data ss:Type="String"><?= self::escapeXml($header) ?></Data></Cell>
<?php endforeach; ?>
   </Row>
<?php foreach ($rows as $row): ?>
   <Row>
<?php foreach ($row as $cell): ?>
<?php // Strings only for long digit codes (NIK/phone/member_no): 15+ digits
      // would lose precision when Excel casts them to float.
      $asNumber = is_numeric($cell) && !str_starts_with((string) $cell, '0') && strlen((string) $cell) <= 14; ?>
<?php if ($asNumber): ?>
    <Cell><Data ss:Type="Number"><?= self::escapeXml((string) $cell) ?></Data></Cell>
<?php else: ?>
    <Cell><Data ss:Type="String"><?= self::escapeXml((string) $cell) ?></Data></Cell>
<?php endif; ?>
<?php endforeach; ?>
   </Row>
<?php endforeach; ?>
  </Table>
 </Worksheet>
</Workbook>
<?php
        exit;
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
