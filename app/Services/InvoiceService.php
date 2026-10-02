<?php
require_once __DIR__ . '/../../libraries/fpdf/fpdf.php';

/**
 * FPDF ka chhota sa extension — sirf rounded boxes draw karne ke liye.
 */
if (!class_exists('InvoicePdf')) {
    class InvoicePdf extends FPDF
    {
        public function RoundedRect(float $x, float $y, float $w, float $h, float $r, string $style = ''): void
        {
            $k = $this->k;
            $hp = $this->h;
            $op = $style === 'F' ? 'f' : (($style === 'FD' || $style === 'DF') ? 'B' : 'S');
            $arc = 4 / 3 * (M_SQRT2 - 1);

            $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
            $xc = $x + $w - $r; $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
            $this->arc($xc + $r * $arc, $yc - $r, $xc + $r, $yc - $r * $arc, $xc + $r, $yc);
            $xc = $x + $w - $r; $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
            $this->arc($xc + $r, $yc + $r * $arc, $xc + $r * $arc, $yc + $r, $xc, $yc + $r);
            $xc = $x + $r; $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
            $this->arc($xc - $r * $arc, $yc + $r, $xc - $r, $yc + $r * $arc, $xc - $r, $yc);
            $xc = $x + $r; $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
            $this->arc($xc - $r, $yc - $r * $arc, $xc - $r * $arc, $yc - $r, $xc, $yc - $r);
            $this->_out($op);
        }

        private function arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
        {
            $h = $this->h;
            $this->_out(sprintf(
                '%.2F %.2F %.2F %.2F %.2F %.2F c',
                $x1 * $this->k, ($h - $y1) * $this->k,
                $x2 * $this->k, ($h - $y2) * $this->k,
                $x3 * $this->k, ($h - $y3) * $this->k
            ));
        }
    }
}

class InvoiceService
{
    private string $currency = 'Rs.';

    /** Store ka brand color [r, g, b] — store ke naam se automatically chuna jata hai */
    private array $accent = [29, 78, 216];

    /** Har store ko in mein se ek color milta hai (naam ke hisaab se, hamesha same) */
    private array $palette = [
        [29, 78, 216],   // blue
        [4, 120, 87],    // green
        [124, 58, 237],  // purple
        [190, 24, 93],   // pink
        [194, 65, 12],   // orange
        [14, 116, 144],  // teal
        [67, 56, 202],   // indigo
        [15, 23, 42],    // slate
    ];

    private const LEFT = 14;
    private const RIGHT = 196;
    private const WIDTH = 182;

    public function generate(array $order): void
    {
        $this->currency = $order['currency'] ?? 'Rs.';
        $this->accent = $this->accentFor((string) ($order['store_name'] ?? 'Finovo'));

        $pdf = new InvoicePdf('P', 'mm', 'A4');
        $pdf->SetMargins(self::LEFT, 14, self::LEFT);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        $this->drawHeader($pdf, $order);
        $this->drawMetaRow($pdf, $order);
        $this->drawCustomerBlocks($pdf, $order);
        $this->drawItemsTable($pdf, $order['items'] ?? []);
        $this->drawTotals($pdf, $order);
        $this->drawPayment($pdf, $order);
        $this->drawFooter($pdf, $order);

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($order['invoice_number'] ?? 'invoice')) . '.pdf';

        $pdf->Output('D', $filename);
        exit;
    }

    // ------------------------------------------------------------------
    // Header: store ke brand color ki patti, store ka naam aur initials
    // ------------------------------------------------------------------
    private function drawHeader(InvoicePdf $pdf, array $order): void
    {
        [$r, $g, $b] = $this->accent;
        $storeName = $this->clean((string) ($order['store_name'] ?? 'Finovo'));

        // Colored band across the top
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 0, 210, 44, 'F');

        // Store initials "logo"
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect(self::LEFT, 12, 18, 18, 3.5, 'F');
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetXY(self::LEFT, 12);
        $pdf->Cell(18, 18, $this->initials($storeName), 0, 0, 'C');

        // Store name
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 17);
        $pdf->SetXY(self::LEFT + 23, 12.5);
        $pdf->Cell(100, 9, $this->truncate($storeName, 32), 0, 1);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(226, 232, 240);
        $pdf->SetX(self::LEFT + 23);
        $pdf->Cell(100, 5, 'Sales Invoice', 0, 1);

        // INVOICE title + number on the right
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetXY(120, 11);
        $pdf->Cell(76, 11, 'INVOICE', 0, 1, 'R');

        $pdf->SetFont('Arial', '', 9.5);
        $pdf->SetTextColor(226, 232, 240);
        $pdf->SetXY(120, 22);
        $pdf->Cell(76, 6, $this->clean((string) ($order['invoice_number'] ?? '-')), 0, 1, 'R');

        $pdf->SetY(52);
    }

    // ------------------------------------------------------------------
    // 4 chhote boxes: Invoice No, Order No, Date, Payment status
    // ------------------------------------------------------------------
    private function drawMetaRow(InvoicePdf $pdf, array $order): void
    {
        $y = 52;
        $gap = 4;
        $w = (self::WIDTH - 3 * $gap) / 4;

        $status = strtolower((string) ($order['payment_status'] ?? 'pending'));
        $isPaid = $status === 'paid';

        $boxes = [
            ['INVOICE NO.', $this->clean((string) ($order['invoice_number'] ?? '-')), null],
            ['ORDER NO.', $this->clean((string) ($order['order_number'] ?? '-')), null],
            ['INVOICE DATE', $this->formatDate($order['order_date'] ?? null), null],
            ['PAYMENT', $isPaid ? 'PAID' : strtoupper($this->clean((string) ($order['payment_status'] ?? 'Pending'))), $isPaid ? [22, 163, 74] : [220, 38, 38]],
        ];

        foreach ($boxes as $i => [$label, $value, $color]) {
            $x = self::LEFT + $i * ($w + $gap);

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->RoundedRect($x, $y, $w, 17, 2.5, 'FD');

            $pdf->SetXY($x + 4, $y + 3);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell($w - 8, 4, $label, 0, 2);

            $pdf->SetX($x + 4);
            $pdf->SetFont('Arial', 'B', 10);
            if ($color) {
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
            } else {
                $pdf->SetTextColor(15, 23, 42);
            }
            $pdf->Cell($w - 8, 7, $this->truncate($value, 20), 0, 0);
        }

        $pdf->SetY($y + 25);
    }

    // ------------------------------------------------------------------
    // Bill To / Ship To
    // ------------------------------------------------------------------
    private function drawCustomerBlocks(InvoicePdf $pdf, array $order): void
    {
        $customer = $order['customer'] ?? [];
        $y = $pdf->GetY();
        $w = (self::WIDTH - 6) / 2;

        foreach ([['BILL TO', self::LEFT], ['SHIP TO', self::LEFT + $w + 6]] as [$label, $x]) {
            $this->sectionLabel($pdf, $label, $x, $y);
        }

        // Bill to
        $pdf->SetXY(self::LEFT, $y + 6);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($w, 6, $this->clean((string) ($customer['name'] ?? 'Customer')), 0, 1);

        $pdf->SetFont('Arial', '', 8.8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX(self::LEFT);
        $pdf->Cell($w, 5, $this->clean((string) ($customer['email'] ?? '-')), 0, 1);
        $pdf->SetX(self::LEFT);
        $pdf->Cell($w, 5, $this->clean((string) ($customer['phone'] ?? '-')), 0, 1);
        $pdf->SetX(self::LEFT);
        $pdf->MultiCell($w, 4.7, $this->clean((string) ($order['billing_address'] ?? '-')));
        $billBottom = $pdf->GetY();

        // Ship to
        $shipX = self::LEFT + $w + 6;
        $pdf->SetXY($shipX, $y + 6);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($w, 6, $this->clean((string) ($customer['name'] ?? 'Customer')), 0, 1);

        $pdf->SetFont('Arial', '', 8.8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX($shipX);
        $pdf->MultiCell($w, 4.7, $this->clean((string) ($order['shipping_address'] ?? '-')));
        $shipBottom = $pdf->GetY();

        $pdf->SetY(max($billBottom, $shipBottom, $y + 30) + 6);
    }

    // ------------------------------------------------------------------
    // Items table — header store ke color mein, halki zebra rows
    // ------------------------------------------------------------------
    private function drawItemsTable(InvoicePdf $pdf, array $items): void
    {
        $widths = [52, 26, 30, 14, 28, 32];
        $headers = ['PRODUCT', 'SKU', 'VARIANT', 'QTY', 'UNIT PRICE', 'LINE TOTAL'];
        $aligns = ['L', 'L', 'L', 'C', 'R', 'R'];

        $this->tableHeader($pdf, $widths, $headers, $aligns);

        $pdf->SetFont('Arial', '', 8.8);

        if (empty($items)) {
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(array_sum($widths), 12, 'No order items found.', 'B', 1, 'C');
            $pdf->Ln(6);
            return;
        }

        $zebra = false;
        foreach ($items as $item) {
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $this->tableHeader($pdf, $widths, $headers, $aligns);
                $pdf->SetFont('Arial', '', 8.8);
            }

            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetTextColor(15, 23, 42);

            $pdf->SetFont('Arial', 'B', 8.8);
            $pdf->Cell($widths[0], 10, '  ' . $this->truncate((string) ($item['product_name'] ?? '-'), 30), 'B', 0, 'L', $zebra);
            $pdf->SetFont('Arial', '', 8.8);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell($widths[1], 10, $this->truncate((string) ($item['sku'] ?? '-'), 14), 'B', 0, 'L', $zebra);
            $pdf->Cell($widths[2], 10, $this->truncate((string) ($item['variant'] ?? '-'), 17), 'B', 0, 'L', $zebra);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell($widths[3], 10, (string) ((int) ($item['quantity'] ?? 1)), 'B', 0, 'C', $zebra);
            $pdf->Cell($widths[4], 10, $this->money((float) ($item['unit_price'] ?? 0)), 'B', 0, 'R', $zebra);
            $pdf->SetFont('Arial', 'B', 8.8);
            $pdf->Cell($widths[5], 10, $this->money((float) ($item['line_total'] ?? 0)) . '  ', 'B', 1, 'R', $zebra);

            $zebra = !$zebra;
        }

        $pdf->Ln(6);
    }

    private function tableHeader(InvoicePdf $pdf, array $widths, array $headers, array $aligns): void
    {
        [$r, $g, $b] = $this->accent;
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 7.8);

        foreach ($headers as $i => $header) {
            $text = $header;
            if ($i === 0) { $text = '  ' . $header; }
            if ($i === count($headers) - 1) { $text = $header . '  '; }
            $pdf->Cell($widths[$i], 9, $text, 0, $i === count($headers) - 1 ? 1 : 0, $aligns[$i], true);
        }
    }

    // ------------------------------------------------------------------
    // Totals — Grand Total store ke color wale box mein
    // ------------------------------------------------------------------
    private function drawTotals(InvoicePdf $pdf, array $order): void
    {
        if ($pdf->GetY() > 225) {
            $pdf->AddPage();
        }

        $boxX = 112;
        $boxW = self::RIGHT - $boxX;
        $labelW = 40;
        $valueW = $boxW - $labelW - 6;

        $rows = [
            ['Subtotal', (float) ($order['subtotal'] ?? 0)],
            ['Discount', -1 * (float) ($order['discount'] ?? 0)],
            ['Shipping', (float) ($order['shipping'] ?? 0)],
            ['Tax', (float) ($order['tax'] ?? 0)],
        ];

        $pdf->SetFont('Arial', '', 9);
        foreach ($rows as [$label, $amount]) {
            $pdf->SetX($boxX);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell($labelW, 6.5, $label, 0, 0, 'L');
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell($valueW, 6.5, $this->money($amount), 0, 1, 'R');
        }

        $pdf->Ln(2);
        $y = $pdf->GetY();
        [$r, $g, $b] = $this->accent;
        $pdf->SetFillColor($r, $g, $b);
        $pdf->RoundedRect($boxX, $y, $boxW, 13, 2.5, 'F');

        $pdf->SetXY($boxX + 4, $y);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($labelW - 4, 13, 'Grand Total', 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell($valueW, 13, $this->money((float) ($order['grand_total'] ?? 0)), 0, 1, 'R');

        $pdf->SetY($y + 21);
    }

    // ------------------------------------------------------------------
    // Payment details
    // ------------------------------------------------------------------
    private function drawPayment(InvoicePdf $pdf, array $order): void
    {
        if ($pdf->GetY() > 245) {
            $pdf->AddPage();
        }

        $y = $pdf->GetY();
        [$r, $g, $b] = $this->accent;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(self::LEFT, $y, self::WIDTH, 24, 3, 'FD');

        // Accent strip on the left of the box
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(self::LEFT, $y + 4, 1.4, 16, 'F');

        $colW = (self::WIDTH - 12) / 3;

        $pdf->SetXY(self::LEFT + 7, $y + 5);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell($colW, 5, 'PAYMENT METHOD', 0, 0);
        $pdf->Cell($colW, 5, 'PAYMENT STATUS', 0, 0);
        $pdf->Cell($colW, 5, 'ORDER SOURCE', 0, 1);

        $pdf->SetX(self::LEFT + 7);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($colW, 8, $this->truncate((string) ($order['payment_method'] ?? 'Not specified'), 22), 0, 0);

        $status = strtolower((string) ($order['payment_status'] ?? 'pending'));
        if ($status === 'paid') {
            $pdf->SetTextColor(22, 163, 74);
        } else {
            $pdf->SetTextColor(220, 38, 38);
        }
        $pdf->Cell($colW, 8, ucfirst($this->truncate((string) ($order['payment_status'] ?? 'Pending'), 22)), 0, 0);

        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($colW, 8, $this->sourceLabel((string) ($order['source'] ?? 'manual')), 0, 1);

        $pdf->SetY($y + 32);
    }

    // ------------------------------------------------------------------
    // Footer — store ka shukriya, neeche chhota sa "Powered by Finovo"
    // ------------------------------------------------------------------
    private function drawFooter(InvoicePdf $pdf, array $order): void
    {
        if ($pdf->GetY() > 262) {
            $pdf->AddPage();
        }

        $storeName = $this->clean((string) ($order['store_name'] ?? 'Finovo'));
        [$r, $g, $b] = $this->accent;

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(self::LEFT, $pdf->GetY(), self::RIGHT, $pdf->GetY());
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->Cell(self::WIDTH, 6, 'Thank you for shopping with ' . $this->truncate($storeName, 40) . '!', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(self::WIDTH, 5, 'This invoice was generated by Finovo OMS/WMS.', 0, 1, 'C');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function sectionLabel(InvoicePdf $pdf, string $label, float $x, float $y): void
    {
        [$r, $g, $b] = $this->accent;
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect($x, $y + 0.8, 1.2, 3.6, 'F');

        $pdf->SetXY($x + 3, $y);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Arial', 'B', 7.8);
        $pdf->Cell(80, 5, $label, 0, 1);
    }

    /** Store ke naam se ek fixed brand color — har store ka apna, har baar same */
    private function accentFor(string $storeName): array
    {
        $index = abs(crc32(strtolower(trim($storeName)))) % count($this->palette);
        return $this->palette[$index];
    }

    /** "My Store" => "MS", "Finovo" => "F" */
    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $out .= strtoupper(substr($part, 0, 1));
            }
        }
        return $out !== '' ? $out : 'F';
    }

    private function money(float $value): string
    {
        $prefix = $value < 0 ? ('-' . $this->currency . ' ') : ($this->currency . ' ');
        return $prefix . number_format(abs($value), 2);
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return date('d M Y');
        }
        $timestamp = strtotime($date);
        return $timestamp ? date('d M Y', $timestamp) : $this->clean($date);
    }

    private function sourceLabel(string $source): string
    {
        return ucwords(str_replace('_', ' ', $source));
    }

    private function truncate(string $value, int $max): string
    {
        $value = $this->clean($value);
        if (strlen($value) <= $max) {
            return $value;
        }
        return substr($value, 0, max(1, $max - 3)) . '...';
    }

    private function clean(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        return $value === '' ? '-' : $value;
    }
}