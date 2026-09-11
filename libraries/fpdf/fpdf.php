<?php
class FPDF
{
    private float $pageWidth = 210.0;
    private float $pageHeight = 297.0;
    private float $leftMargin = 10.0;
    private float $rightMargin = 10.0;
    private float $topMargin = 10.0;
    private float $bottomMargin = 15.0;
    private float $x = 10.0;
    private float $y = 10.0;
    private float $lastCellHeight = 0.0;
    private float $fontSize = 10.0;
    private string $fontStyle = '';
    private array $textColor = [17, 24, 39];
    private array $drawColor = [229, 231, 235];
    private array $fillColor = [255, 255, 255];
    private array $pages = [];
    private int $page = -1;
    private bool $autoPageBreak = true;
    private float $autoPageBreakMargin = 15.0;

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        if (strtoupper($orientation) === 'L') {
            [$this->pageWidth, $this->pageHeight] = [$this->pageHeight, $this->pageWidth];
        }
    }

    public function SetMargins(float $left, float $top, ?float $right = null): void
    {
        $this->leftMargin = $left;
        $this->topMargin = $top;
        $this->rightMargin = $right ?? $left;
        $this->x = $left;
        $this->y = $top;
    }

    public function SetAutoPageBreak(bool $auto, float $margin = 15): void
    {
        $this->autoPageBreak = $auto;
        $this->autoPageBreakMargin = $margin;
        $this->bottomMargin = $margin;
    }

    public function AddPage(string $orientation = ''): void
    {
        $this->pages[] = '';
        $this->page = count($this->pages) - 1;
        $this->x = $this->leftMargin;
        $this->y = $this->topMargin;
    }

    public function SetFont(string $family, string $style = '', float $size = 0): void
    {
        $this->fontStyle = strtoupper($style);
        if ($size > 0) {
            $this->fontSize = $size;
        }
    }

    public function SetTextColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $this->textColor = [$r, $g ?? $r, $b ?? $r];
    }

    public function SetDrawColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $this->drawColor = [$r, $g ?? $r, $b ?? $r];
    }

    public function SetFillColor(int $r, ?int $g = null, ?int $b = null): void
    {
        $this->fillColor = [$r, $g ?? $r, $b ?? $r];
    }

    public function GetX(): float
    {
        return $this->x;
    }

    public function GetY(): float
    {
        return $this->y;
    }

    public function SetX(float $x): void
    {
        $this->x = $x >= 0 ? $x : $this->pageWidth + $x;
    }

    public function SetY(float $y, bool $resetX = true): void
    {
        $this->y = $y >= 0 ? $y : $this->pageHeight + $y;
        if ($resetX) {
            $this->x = $this->leftMargin;
        }
    }

    public function SetXY(float $x, float $y): void
    {
        $this->SetX($x);
        $this->SetY($y, false);
    }

    public function Ln(?float $h = null): void
    {
        $this->y += $h ?? ($this->lastCellHeight > 0 ? $this->lastCellHeight : 5);
        $this->x = $this->leftMargin;
    }

    public function Cell(
        float $w,
        float $h = 0,
        string $txt = '',
        int|string $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false,
        string $link = ''
    ): void {
        $this->ensurePage();
        $h = $h > 0 ? $h : 5.0;
        $this->checkPageBreak($h);

        if ($w <= 0) {
            $w = $this->pageWidth - $this->rightMargin - $this->x;
        }

        if ($fill) {
            $this->Rect($this->x, $this->y, $w, $h, 'F');
        }

        if ($border) {
            $this->Rect($this->x, $this->y, $w, $h, 'D');
        }

        if ($txt !== '') {
            $padding = 1.8;
            $textWidth = $this->GetStringWidth($txt);
            $tx = $this->x + $padding;

            if ($align === 'R') {
                $tx = $this->x + $w - $textWidth - $padding;
            } elseif ($align === 'C') {
                $tx = $this->x + max(0, ($w - $textWidth) / 2);
            }

            $baseline = $this->y + ($h / 2) + ($this->fontSize * 0.13);
            $this->text($tx, $baseline, $txt);
        }

        $this->lastCellHeight = $h;
        $oldX = $this->x;
        $this->x += $w;

        if ($ln > 0) {
            $this->y += $h;
            $this->x = $ln === 1 ? $this->leftMargin : $oldX;
        }
    }

    public function MultiCell(
        float $w,
        float $h,
        string $txt,
        int|string $border = 0,
        string $align = 'J',
        bool $fill = false
    ): void {
        $this->ensurePage();
        $lines = [];
        foreach (preg_split('/\R/u', $txt) ?: [''] as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                $lines[] = '';
                continue;
            }
            $words = preg_split('/\s+/u', $paragraph) ?: [];
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if ($this->GetStringWidth($candidate) <= max(1, $w - 3.6)) {
                    $line = $candidate;
                } else {
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    $line = $word;
                }
            }
            $lines[] = $line;
        }

        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, $border, 1, $align === 'J' ? 'L' : $align, $fill);
        }
    }

    public function Rect(float $x, float $y, float $w, float $h, string $style = ''): void
    {
        $this->ensurePage();
        $stroke = $this->rgb($this->drawColor, 'RG');
        $fill = $this->rgb($this->fillColor, 'rg');
        $op = strtoupper($style) === 'F' ? 'f' : (strtoupper($style) === 'FD' || strtoupper($style) === 'DF' ? 'B' : 'S');
        $this->append($stroke . "\n" . $fill . "\n" . $this->n($this->mm($x)) . ' ' . $this->n($this->pdfY($y + $h)) . ' ' . $this->n($this->mm($w)) . ' ' . $this->n($this->mm($h)) . " re {$op}\n");
    }

    public function Line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->ensurePage();
        $stroke = $this->rgb($this->drawColor, 'RG');
        $this->append($stroke . "\n" . $this->n($this->mm($x1)) . ' ' . $this->n($this->pdfY($y1)) . ' m ' . $this->n($this->mm($x2)) . ' ' . $this->n($this->pdfY($y2)) . " l S\n");
    }

    public function GetStringWidth(string $s): float
    {
        $plain = preg_replace('/[^\x20-\x7E]/', '?', $s) ?? $s;
        $factor = str_contains($this->fontStyle, 'B') ? 0.54 : 0.50;
        return strlen($plain) * $this->fontSize * 0.352778 * $factor;
    }

    public function Output(string $dest = '', string $name = '', bool $isUTF8 = false): string
    {
        if ($this->page < 0) {
            $this->AddPage();
        }

        $pdf = $this->buildPdf();
        $dest = strtoupper($dest ?: 'I');
        $name = $name ?: 'doc.pdf';

        if ($dest === 'S') {
            return $pdf;
        }

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            if ($dest === 'D') {
                header('Content-Disposition: attachment; filename="' . basename($name) . '"');
            } else {
                header('Content-Disposition: inline; filename="' . basename($name) . '"');
            }
            header('Content-Length: ' . strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');
        }

        echo $pdf;
        return '';
    }

    private function ensurePage(): void
    {
        if ($this->page < 0) {
            $this->AddPage();
        }
    }

    private function checkPageBreak(float $height): void
    {
        if ($this->autoPageBreak && ($this->y + $height) > ($this->pageHeight - $this->autoPageBreakMargin)) {
            $this->AddPage();
        }
    }

    private function text(float $x, float $y, string $txt): void
    {
        $font = str_contains($this->fontStyle, 'B') ? '/F2' : '/F1';
        $safe = $this->escapeText($txt);
        $color = $this->rgb($this->textColor, 'rg');
        $this->append(
            $color . "\nBT {$font} " . $this->n($this->fontSize) . ' Tf ' .
            $this->n($this->mm($x)) . ' ' . $this->n($this->pdfY($y)) .
            " Td ({$safe}) Tj ET\n"
        );
    }

    private function append(string $content): void
    {
        $this->pages[$this->page] .= $content;
    }

    private function mm(float $value): float
    {
        return $value * 72 / 25.4;
    }

    private function pdfY(float $yMm): float
    {
        return $this->mm($this->pageHeight - $yMm);
    }

    private function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function rgb(array $rgb, string $operator): string
    {
        return $this->n($rgb[0] / 255) . ' ' . $this->n($rgb[1] / 255) . ' ' . $this->n($rgb[2] / 255) . ' ' . $operator;
    }

    private function escapeText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function buildPdf(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageCount = count($this->pages);
        $pageObjectIds = [];
        $contentObjectIds = [];
        $nextId = 5;

        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectIds[$i] = $nextId++;
            $contentObjectIds[$i] = $nextId++;
        }

        $kids = implode(' ', array_map(fn($id) => $id . ' 0 R', $pageObjectIds));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . $pageCount . ' /MediaBox [0 0 ' . $this->n($this->mm($this->pageWidth)) . ' ' . $this->n($this->mm($this->pageHeight)) . '] >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        foreach ($this->pages as $i => $content) {
            $objects[$pageObjectIds[$i]] = '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObjectIds[$i] . ' 0 R >>';
            $objects[$contentObjectIds[$i]] = '<< /Length ' . strlen($content) . ">>\nstream\n" . $content . "endstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        $maxId = max(array_keys($objects));

        for ($id = 1; $id <= $maxId; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . ($objects[$id] ?? '<<>>') . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }
}
