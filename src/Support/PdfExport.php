<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal server-side PDF export built on the vendored FPDF 1.86
 * (no composer needed - cPanel compatible). Produces a branded,
 * paginated A4 report. Falls back gracefully: callers can keep using
 * the print view if FPDF is unavailable.
 */
final class PdfExport
{
    /** @param array<int,string> $headers @param array<int,array<int,string>> $rows */
    public static function download(string $filename, string $reportTitle, array $headers, array $rows, string $subtitle = ''): void
    {
        require_once BASE_PATH . '/vendor/fpdf/fpdf.php';

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetTitle($reportTitle . ' - KUTT SUKA MAKMUR', true);
        $pdf->SetAuthor('KUTT SUKA MAKMUR', true);
        $pdf->AddPage();

        // Header band
        $pdf->SetFillColor(11, 122, 62);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->Cell(0, 12, 'KUTT SUKA MAKMUR', 0, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(0, 6, 'Koperasi Usaha Tani Ternak - Grati, Pasuruan', 0, 1, 'L', true);
        $pdf->Ln(4);

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 7, self::ascii($reportTitle), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 5, self::ascii('Dicetak ' . date('d/m/Y H:i') . '  |  ' . $subtitle), 0, 1, 'L');
        $pdf->Ln(2);

        // Table
        $widths = self::columnWidths(count($headers));
        $pdf->SetFillColor(11, 122, 62);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Helvetica', 'B', 8);
        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, self::ascii((string) $h), 1, 0, $i === 0 ? 'L' : 'L', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', '', 8);
        $fill = false;
        if ($rows === []) {
            $pdf->Cell(array_sum($widths), 7, self::ascii('Tidak ada data.'), 1, 1, 'C');
        }
        foreach ($rows as $row) {
            if ($pdf->GetY() > 270) {
                $pdf->AddPage();
            }
            foreach ($row as $i => $cell) {
                $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
                $pdf->Cell($widths[$i], 6.5, self::ascii((string) $cell), 1, 0, 'L', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }

        // Footer via callback
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetFont('Helvetica', 'I', 6.5);
        $pdf->SetY(-12);
        $pdf->Cell(0, 5, self::ascii('KUTT SUKA MAKMUR - dokumen dicetak dari sistem informasi koperasi'), 0, 0, 'L');
        $pdf->Cell(0, 5, self::ascii('Halaman ' . $pdf->PageNo() . '/{nb}'), 0, 0, 'R');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf->Output('S');
        exit;
    }

    /** Evenly distributed column widths across the 190mm printable area. */
    private static function columnWidths(int $count): array
    {
        if ($count <= 0) {
            return [];
        }
        $w = 190 / $count;

        return array_fill(0, $count, $w);
    }

    /** FPDF core fonts are latin-1 only; degrade gracefully. */
    private static function ascii(string $text): string
    {
        $t = str_replace(['Rp ', "\u{2014}"], ['Rp ', '-'], $text);
        $t = iconv('UTF-8', 'ASCII//TRANSLIT', $t);

        return $t === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '' : $t;
    }
}
