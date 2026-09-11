<?php

require_once __DIR__ . '/../../libraries/fpdf/fpdf.php';

class InvoiceService
{
    public function generate(array $order): void
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        $this->drawHeader($pdf, $order);
        $this->drawCustomerBlocks($pdf, $order);
        $this->drawItemsTable($pdf, $order['items'] ?? []);
        $this->drawTotals($pdf, $order);
        $this->drawPayment($pdf, $order);
        $this->drawFooter($pdf);

        $filename = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '-',
            (string) ($order['invoice_number'] ?? 'invoice')
        ) . '.pdf';

        $pdf->Output('D', $filename);
        exit;
    }

    private function drawHeader(FPDF $pdf, array $order): void
    {
        $pdf->SetFillColor(20, 24, 32);
        $pdf->Rect(14, 14, 13, 13, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(14, 14);
        $pdf->Cell(13, 13, 'F', 0, 0, 'C');

        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY(31, 14);
        $pdf->Cell(70, 8, 'FINOVO', 0, 1);

        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetX(31);
        $pdf->Cell(85, 5, $this->clean($order['store_name'] ?? 'OMS / WMS'), 0, 1);

        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetFont('Arial', 'B', 23);
        $pdf->SetXY(135, 14);
        $pdf->Cell(61, 9, 'INVOICE', 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetXY(135, 25);
        $pdf->Cell(25, 5, 'INVOICE NO.', 0, 0, 'R');
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(36, 5, $this->clean($order['invoice_number'] ?? '-'), 0, 1, 'R');

        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetX(135);
        $pdf->Cell(25, 5, 'ORDER NO.', 0, 0, 'R');
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(36, 5, $this->clean($order['order_number'] ?? '-'), 0, 1, 'R');

        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetX(135);
        $pdf->Cell(25, 5, 'DATE', 0, 0, 'R');
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(36, 5, $this->formatDate($order['order_date'] ?? null), 0, 1, 'R');

        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Line(14, 43, 196, 43);
        $pdf->SetY(49);
    }

    private function drawCustomerBlocks(FPDF $pdf, array $order): void
    {
        $customer = $order['customer'] ?? [];

        $this->sectionLabel($pdf, 'BILL TO', 14, 49);
        $this->sectionLabel($pdf, 'SHIP TO', 108, 49);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetXY(14, 56);
        $pdf->Cell(82, 6, $this->clean($customer['name'] ?? 'Customer'), 0, 1);

        $pdf->SetFont('Arial', '', 8.8);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetX(14);
        $pdf->Cell(82, 5, $this->clean($customer['email'] ?? '-'), 0, 1);
        $pdf->SetX(14);
        $pdf->Cell(82, 5, $this->clean($customer['phone'] ?? '-'), 0, 1);
        $pdf->SetX(14);
        $pdf->MultiCell(82, 4.7, $this->clean($order['billing_address'] ?? '-'));

        $pdf->SetXY(108, 56);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell(88, 6, $this->clean($customer['name'] ?? 'Customer'), 0, 1);

        $pdf->SetFont('Arial', '', 8.8);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetX(108);
        $pdf->MultiCell(88, 4.7, $this->clean($order['shipping_address'] ?? '-'));

        $pdf->SetY(max($pdf->GetY(), 82));
        $pdf->Ln(4);
    }

    private function drawItemsTable(FPDF $pdf, array $items): void
    {
        $widths = [45, 24, 31, 14, 32, 36];
        $headers = ['PRODUCT', 'SKU', 'VARIANT', 'QTY', 'UNIT PRICE', 'LINE TOTAL'];
        $aligns = ['L', 'L', 'L', 'C', 'R', 'R'];

        $pdf->SetFillColor(245, 246, 248);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetFont('Arial', 'B', 8);

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, $i === count($headers) - 1 ? 1 : 0, $aligns[$i], true);
        }

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(31, 41, 55);

        if (empty($items)) {
            $pdf->Cell(array_sum($widths), 11, 'No order items found.', 1, 1, 'C');
            return;
        }

        foreach ($items as $item) {
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $pdf->SetFillColor(245, 246, 248);
                $pdf->SetTextColor(75, 85, 99);
                $pdf->SetDrawColor(229, 231, 235);
                $pdf->SetFont('Arial', 'B', 8);
                foreach ($headers as $i => $header) {
                    $pdf->Cell($widths[$i], 8, $header, 1, $i === count($headers) - 1 ? 1 : 0, $aligns[$i], true);
                }
                $pdf->SetFont('Arial', '', 8.5);
                $pdf->SetTextColor(31, 41, 55);
            }

            $pdf->Cell($widths[0], 9, $this->truncate($item['product_name'] ?? '-', 28), 1, 0, 'L');
            $pdf->Cell($widths[1], 9, $this->truncate($item['sku'] ?? '-', 14), 1, 0, 'L');
            $pdf->Cell($widths[2], 9, $this->truncate($item['variant'] ?? '-', 18), 1, 0, 'L');
            $pdf->Cell($widths[3], 9, (string) ((int) ($item['quantity'] ?? 1)), 1, 0, 'C');
            $pdf->Cell($widths[4], 9, $this->money((float) ($item['unit_price'] ?? 0)), 1, 0, 'R');
            $pdf->Cell($widths[5], 9, $this->money((float) ($item['line_total'] ?? 0)), 1, 1, 'R');
        }

        $pdf->Ln(8);
    }

    private function drawTotals(FPDF $pdf, array $order): void
    {
        $labelX = 122;
        $valueX = 158;
        $labelW = 34;
        $valueW = 38;

        $rows = [
            ['Subtotal', (float) ($order['subtotal'] ?? 0)],
            ['Discount', -1 * (float) ($order['discount'] ?? 0)],
            ['Shipping', (float) ($order['shipping'] ?? 0)],
            ['Tax', (float) ($order['tax'] ?? 0)],
        ];

        $pdf->SetFont('Arial', '', 9);
        foreach ($rows as [$label, $amount]) {
            $pdf->SetX($labelX);
            $pdf->SetTextColor(107, 114, 128);
            $pdf->Cell($labelW, 6.5, $label, 0, 0, 'R');
            $pdf->SetTextColor(31, 41, 55);
            $pdf->Cell($valueW, 6.5, $this->money($amount), 0, 1, 'R');
        }

        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Line($labelX + 4, $pdf->GetY() + 1, 196, $pdf->GetY() + 1);
        $pdf->Ln(4);

        $pdf->SetX($labelX);
        $pdf->SetFont('Arial', 'B', 11.5);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->Cell($labelW, 8, 'Grand Total', 0, 0, 'R');
        $pdf->Cell($valueW, 8, $this->money((float) ($order['grand_total'] ?? 0)), 0, 1, 'R');

        $pdf->Ln(7);
    }

    private function drawPayment(FPDF $pdf, array $order): void
    {
        if ($pdf->GetY() > 248) {
            $pdf->AddPage();
        }

        $y = $pdf->GetY();
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Rect(14, $y, 182, 26, 'FD');

        $pdf->SetXY(20, $y + 5);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->Cell(44, 5, 'PAYMENT METHOD', 0, 0);
        $pdf->Cell(44, 5, 'PAYMENT STATUS', 0, 0);
        $pdf->Cell(44, 5, 'ORDER SOURCE', 0, 1);

        $pdf->SetX(20);
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(31, 41, 55);
        $pdf->Cell(44, 7, $this->truncate($order['payment_method'] ?? 'Not specified', 20), 0, 0);
        $pdf->Cell(44, 7, $this->truncate($order['payment_status'] ?? 'Pending', 20), 0, 0);
        $pdf->Cell(70, 7, $this->sourceLabel($order['source'] ?? 'manual'), 0, 1);

        $pdf->SetY($y + 32);
    }

    private function drawFooter(FPDF $pdf): void
    {
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
        }

        $pdf->SetDrawColor(229, 231, 235);
        $pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(182, 5, 'Thank you for your order. This invoice was generated by Finovo OMS/WMS.', 0, 1, 'C');
    }

    private function sectionLabel(FPDF $pdf, string $label, float $x, float $y): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(80, 5, $label, 0, 1);
    }

    private function money(float $value): string
    {
        $prefix = $value < 0 ? '-PKR ' : 'PKR ';
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
