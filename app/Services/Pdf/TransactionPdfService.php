<?php

namespace App\Services\Pdf;

use App\Models\{Purchase,Sale,Setting};
use FPDF;

class TransactionPdfService
{
    public function purchase(Purchase $purchase): string
    {
        return $this->generate($purchase->loadMissing('supplier', 'items.product', 'payments'), true);
    }

    public function sale(Sale $sale): string
    {
        return $this->generate($sale->loadMissing('customer', 'items.product', 'payments'), false);
    }

    private function generate(Purchase|Sale $transaction, bool $purchase): string
    {
        $title = $purchase ? 'PURCHASE VOUCHER' : 'SALES INVOICE';
        $number = $purchase ? $transaction->purchase_number : $transaction->sale_number;
        $date = $purchase ? $transaction->purchase_date : $transaction->sale_date;
        $party = $purchase ? $transaction->supplier : $transaction->customer;
        $company = Setting::company();

        $pdf = new class extends FPDF {
            public string $documentTitle = '';
            public string $documentNumber = '';
            public string $companyName = '';
            public string $companyMark = '';
            public string $companySubtitle = '';
            public string $companyFooter = '';
            public string $companyLogo = '';

            public function Header(): void
            {
                if ($this->companyLogo && file_exists($this->companyLogo)) {
                    $this->Image($this->companyLogo, 15, 12, 14, 14);
                } else {
                    $this->SetFillColor(23, 92, 211);
                    $this->Rect(15, 12, 14, 14, 'F');
                    $this->SetXY(15, 16.5);
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetTextColor(255, 255, 255);
                    $this->Cell(14, 5, $this->companyMark, 0, 0, 'C');
                }

                $this->SetXY(34, 12);
                $this->SetTextColor(16, 24, 40);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(80, 7, $this->companyName, 0, 1);
                $this->SetX(34);
                $this->SetTextColor(102, 112, 133);
                $this->SetFont('Arial', '', 7);
                $this->Cell(80, 5, $this->companySubtitle, 0, 0);

                $this->SetXY(120, 12);
                $this->SetTextColor(16, 24, 40);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(75, 7, $this->documentTitle, 0, 1, 'R');
                $this->SetX(120);
                $this->SetTextColor(23, 92, 211);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(75, 5, $this->documentNumber, 0, 1, 'R');

                $this->SetDrawColor(16, 24, 40);
                $this->SetLineWidth(0.5);
                $this->Line(15, 32, 195, 32);
                $this->SetY(38);
            }

            public function Footer(): void
            {
                $this->SetY(-13);
                $this->SetDrawColor(220, 224, 230);
                $this->Line(15, $this->GetY(), 195, $this->GetY());
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(130, 138, 152);
                $this->Cell(0, 5, $this->companyFooter.'  |  Page '.$this->PageNo().' of {nb}', 0, 0, 'C');
            }
        };

        $pdf->documentTitle = $title;
        $pdf->documentNumber = $number;
        $pdf->companyName = $this->text($this->shorten($company['company_name'], 32));
        $pdf->companyMark = $this->text(strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company['company_name']), 0, 2)) ?: 'CO');
        $pdf->companySubtitle = $this->text($this->shorten($company['company_tagline'] ?: $company['company_phone'], 55));
        $pdf->companyFooter = $this->text($this->shorten(implode('  |  ', array_filter([$company['company_address'], $company['company_phone'], $company['company_email'], $company['company_tax_number']])), 110) ?: 'Generated document');
        $pdf->companyLogo = $company['company_logo'] ? storage_path('app/public/'.$company['company_logo']) : '';
        $pdf->AliasNbPages();
        $pdf->SetMargins(15, 38, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetTitle($this->text($title.' '.$number));
        $pdf->SetCreator($this->text($company['company_name']));
        $pdf->AddPage();

        $this->metaBlock($pdf, $purchase ? 'SUPPLIER' : 'BILL TO', $party?->name ?? 'Not specified', [
            $party?->contact_person,
            $party?->phone,
            $party?->email,
            $party?->address,
        ], 15, 39);
        $this->metaBlock($pdf, 'DOCUMENT DETAILS', $date->format('d M Y'), [
            'Payment: '.ucwords(str_replace('_', ' ', $transaction->payment_method)),
            'Status: '.ucfirst($transaction->payment_status),
            $purchase && $transaction->supplier_invoice_number ? 'Supplier invoice: '.$transaction->supplier_invoice_number : null,
        ], 112, 39);

        $pdf->SetY(72);
        $this->tableHeader($pdf);
        foreach ($transaction->items as $index => $item) {
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $this->tableHeader($pdf);
            }
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(52, 64, 84);
            $pdf->Cell(10, 8, (string) ($index + 1), 1, 0, 'C');
            $pdf->Cell(57, 8, $this->text($this->shorten($item->product?->name ?? 'Unknown product', 35)), 1);
            $pdf->Cell(23, 8, $this->text($item->product?->sku ?? '—'), 1);
            $pdf->Cell(22, 8, number_format((float) $item->quantity, 3), 1, 0, 'R');
            $pdf->Cell(24, 8, number_format((float) $item->unit_price, 2), 1, 0, 'R');
            $pdf->Cell(20, 8, number_format((float) $item->discount, 2), 1, 0, 'R');
            $pdf->Cell(24, 8, number_format((float) $item->line_total, 2), 1, 1, 'R');
        }

        $pdf->Ln(6);
        if ($pdf->GetY() > 215) $pdf->AddPage();
        $startY = $pdf->GetY();
        $pdf->SetXY(15, $startY);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(102, 112, 133);
        $pdf->Cell(85, 5, 'NOTES', 0, 1);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(52, 64, 84);
        $pdf->MultiCell(85, 6, $this->text($transaction->notes ?: 'No notes were added to this transaction.'), 0, 'L');

        $labels = [
            ['Subtotal', $transaction->subtotal],
            ['Discount', -$transaction->discount],
            [$purchase ? 'Additional Cost' : 'Tax', $purchase ? $transaction->additional_cost : $transaction->tax],
            ['Grand Total', $transaction->grand_total],
            ['Paid', $transaction->paid_amount],
            ['Balance Due', $transaction->due_amount],
        ];
        $pdf->SetXY(112, $startY);
        foreach ($labels as $position => [$label, $amount]) {
            $grand = $label === 'Grand Total';
            if ($grand) {
                $pdf->SetDrawColor(152, 162, 179);
                $pdf->Line(112, $pdf->GetY(), 195, $pdf->GetY());
            }
            $pdf->SetFont('Arial', $grand ? 'B' : '', $grand ? 10 : 8);
            $pdf->SetTextColor($grand ? 16 : 71, $grand ? 24 : 84, $grand ? 40 : 103);
            $pdf->Cell(39, $grand ? 9 : 7, $label, 0);
            $pdf->Cell(44, $grand ? 9 : 7, 'PKR '.number_format((float) $amount, 2), 0, 1, 'R');
            $pdf->SetX(112);
        }

        $historyY = max($pdf->GetY() + 8, $startY + 48);
        if ($historyY > 235) {
            $pdf->AddPage();
            $historyY = $pdf->GetY();
        }
        $pdf->SetY($historyY);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(71, 84, 103);
        $pdf->Cell(180, 6, 'PAYMENT HISTORY', 0, 1);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetFont('Arial', 'B', 7);
        foreach ([['Voucher',45],['Date',35],['Method',40],['Status',25],['Amount',35]] as [$label,$width]) {
            $pdf->Cell($width, 7, $label, 1, 0, $label === 'Amount' ? 'R' : 'L', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7);
        if ($transaction->payments->isEmpty()) {
            $pdf->Cell(180, 8, 'No payment vouchers recorded.', 1, 1, 'C');
        } else {
            foreach ($transaction->payments as $payment) {
                $pdf->Cell(45, 7, $payment->payment_number, 1);
                $pdf->Cell(35, 7, $payment->payment_date->format('d M Y'), 1);
                $pdf->Cell(40, 7, ucwords(str_replace('_', ' ', $payment->payment_method)), 1);
                $pdf->Cell(25, 7, $payment->is_reversed ? 'Reversed' : 'Posted', 1);
                $pdf->Cell(35, 7, number_format((float) $payment->amount, 2), 1, 1, 'R');
            }
        }

        if ($transaction->status === 'cancelled') {
            $pdf->SetTextColor(180, 35, 24);
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->SetXY(55, min(250, $pdf->GetY() + 10));
            $pdf->Cell(100, 13, 'CANCELLED', 1, 0, 'C');
        }

        return $pdf->Output('S');
    }

    private function metaBlock(FPDF $pdf, string $label, string $primary, array $lines, float $x, float $y): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(102, 112, 133);
        $pdf->Cell(83, 5, $label, 0, 1);
        $pdf->SetX($x);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->Cell(83, 6, $this->text($this->shorten($primary, 45)), 0, 1);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(102, 112, 133);
        foreach (array_filter($lines) as $line) {
            $pdf->SetX($x);
            $pdf->Cell(83, 4, $this->text($this->shorten((string) $line, 55)), 0, 1);
        }
    }

    private function tableHeader(FPDF $pdf): void
    {
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetDrawColor(226, 230, 236);
        $pdf->SetTextColor(71, 84, 103);
        $pdf->SetFont('Arial', 'B', 7);
        foreach ([['#',10,'C'],['Product',57,'L'],['SKU',23,'L'],['Qty',22,'R'],['Unit Price',24,'R'],['Discount',20,'R'],['Total',24,'R']] as [$label,$width,$align]) {
            $pdf->Cell($width, 8, $label, 1, 0, $align, true);
        }
        $pdf->Ln();
    }

    private function shorten(string $value, int $length): string
    {
        return strlen($value) > $length ? substr($value, 0, $length - 3).'...' : $value;
    }

    private function text(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $value) ?: $value;
    }
}
