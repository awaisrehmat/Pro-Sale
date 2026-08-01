<?php

namespace App\Services\Pdf;

use App\Models\{Payment,Setting};
use FPDF;

class VoucherPdfService
{
    public function generate(Payment $payment): string
    {
        $payment->loadMissing('supplier', 'customer', 'purchase', 'sale');

        $incoming = $payment->payment_type === 'customer_payment';
        $title = $incoming ? 'RECEIPT VOUCHER' : 'PAYMENT VOUCHER';
        $partyLabel = $incoming ? 'Received From' : 'Paid To';
        $party = $payment->customer ?? $payment->supplier;
        $document = $payment->sale?->sale_number ?? $payment->purchase?->purchase_number ?? 'General / Opening Balance';
        $company = Setting::company();

        $pdf = new class extends FPDF {
            public string $companyFooter = '';

            public function Footer(): void
            {
                $this->SetY(-13);
                $this->SetDrawColor(220, 224, 230);
                $this->Line(15, $this->GetY(), 195, $this->GetY());
                $this->SetY(-10);
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(130, 138, 152);
                $this->Cell(0, 5, $this->companyFooter.'  |  Page '.$this->PageNo(), 0, 0, 'C');
            }
        };
        $pdf->companyFooter = $this->text($this->shorten(implode('  |  ', array_filter([$company['company_address'], $company['company_phone'], $company['company_email'], $company['company_tax_number']])), 110) ?: 'Generated document');

        $pdf->SetMargins(15, 14, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetTitle($this->text($title.' '.$payment->payment_number));
        $pdf->SetCreator($this->text($company['company_name']));

        $logoPath = $company['company_logo'] ? storage_path('app/public/'.$company['company_logo']) : '';
        if ($logoPath && file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 14, 15, 15);
        } else {
            $pdf->SetFillColor(23, 92, 211);
            $pdf->Rect(15, 14, 15, 15, 'F');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(15, 19);
            $mark = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $company['company_name']), 0, 2)) ?: 'CO';
            $pdf->Cell(15, 5, $this->text($mark), 0, 0, 'C');
        }

        $pdf->SetXY(35, 15);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->Cell(90, 7, $this->text($this->shorten($company['company_name'], 32)), 0, 1);
        $pdf->SetX(35);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(102, 112, 133);
        $pdf->Cell(90, 5, $this->text($this->shorten($company['company_tagline'] ?: $company['company_phone'], 55)), 0, 0);

        $pdf->SetXY(125, 15);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->Cell(70, 7, $title, 0, 1, 'R');
        $pdf->SetX(125);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(23, 92, 211);
        $pdf->Cell(70, 6, $payment->payment_number, 0, 1, 'R');

        $pdf->SetY(36);
        $pdf->SetDrawColor(16, 24, 40);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(15, 36, 195, 36);

        $pdf->SetY(44);
        $this->labelValue($pdf, 'Voucher Date', $payment->payment_date->format('d M Y'), 15, 44, 55);
        $this->labelValue($pdf, 'Payment Method', ucwords(str_replace('_', ' ', $payment->payment_method)), 105, 44, 90);

        $this->labelValue($pdf, $partyLabel, $party?->name ?? 'Not specified', 15, 62, 180);
        $this->labelValue($pdf, 'Linked Transaction', $document, 15, 80, 85);
        $this->labelValue($pdf, 'Reference Number', $payment->reference_number ?: '—', 105, 80, 90);

        $pdf->SetXY(15, 100);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 230, 236);
        $pdf->Rect(15, 100, 180, 28, 'DF');
        $pdf->SetXY(21, 106);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(102, 112, 133);
        $pdf->Cell(65, 5, $incoming ? 'AMOUNT RECEIVED' : 'AMOUNT PAID', 0, 1);
        $pdf->SetX(21);
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->Cell(168, 10, 'PKR '.number_format((float) $payment->amount, 2), 0, 1);

        $pdf->SetXY(15, 136);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(71, 84, 103);
        $pdf->Cell(180, 5, 'AMOUNT IN WORDS', 0, 1);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->MultiCell(180, 7, $this->text($this->amountInWords((float) $payment->amount)), 0, 'L');

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(71, 84, 103);
        $pdf->Cell(180, 5, 'NARRATION / NOTES', 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(52, 64, 84);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->MultiCell(180, 7, $this->text($payment->notes ?: ($incoming ? 'Amount received from customer.' : 'Amount paid to supplier.')), 1, 'L', true);

        $signatureY = max(210, $pdf->GetY() + 35);
        $pdf->SetDrawColor(152, 162, 179);
        foreach ([[15, 'Prepared By'], [75, $incoming ? 'Received By' : 'Paid By'], [135, 'Authorized By']] as [$x, $label]) {
            $pdf->Line($x, $signatureY, $x + 45, $signatureY);
            $pdf->SetXY($x, $signatureY + 2);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(102, 112, 133);
            $pdf->Cell(45, 5, $label, 0, 0, 'C');
        }

        if ($payment->is_reversed) {
            $pdf->SetTextColor(180, 35, 24);
            $pdf->SetFont('Arial', 'B', 32);
            $pdf->SetXY(50, 238);
            $pdf->Cell(110, 15, 'REVERSED', 1, 0, 'C');
        }

        return $pdf->Output('S');
    }

    private function labelValue(FPDF $pdf, string $label, string $value, float $x, float $y, float $width): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(102, 112, 133);
        $pdf->Cell($width, 4, strtoupper($this->text($label)), 0, 1);
        $pdf->SetX($x);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(16, 24, 40);
        $pdf->Cell($width, 7, $this->text($value), 0, 1);
        $pdf->SetDrawColor(226, 230, 236);
        $pdf->Line($x, $y + 13, $x + $width, $y + 13);
    }

    private function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);
        $words = 'Pakistani Rupees '.$this->numberWords($rupees);
        if ($paisa > 0) {
            $words .= ' and '.$this->numberWords($paisa).' Paisa';
        }

        return $words.' Only';
    }

    private function numberWords(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        if ($number === 0) return 'Zero';
        if ($number < 20) return $ones[$number];
        if ($number < 100) return trim($tens[intdiv($number, 10)].' '.$ones[$number % 10]);
        if ($number < 1000) return trim($ones[intdiv($number, 100)].' Hundred '.$this->numberWords($number % 100));
        if ($number < 1_000_000) return trim($this->numberWords(intdiv($number, 1000)).' Thousand '.$this->numberWords($number % 1000));
        if ($number < 1_000_000_000) return trim($this->numberWords(intdiv($number, 1_000_000)).' Million '.$this->numberWords($number % 1_000_000));
        return trim($this->numberWords(intdiv($number, 1_000_000_000)).' Billion '.$this->numberWords($number % 1_000_000_000));
    }

    private function text(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $value) ?: $value;
    }

    private function shorten(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'…' : $value;
    }
}
