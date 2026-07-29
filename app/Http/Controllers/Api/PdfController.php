<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Payment,Purchase,Sale};
use App\Services\Pdf\{TransactionPdfService,VoucherPdfService};
use Illuminate\Http\Response;

class PdfController extends Controller
{
    public function voucher(Payment $payment, VoucherPdfService $service): Response
    {
        $content = $service->generate($payment);
        $type = $payment->payment_type === 'customer_payment' ? 'receipt-voucher' : 'payment-voucher';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$type.'-'.$payment->payment_number.'.pdf"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function purchase(Purchase $purchase, TransactionPdfService $service): Response
    {
        return $this->pdfResponse($service->purchase($purchase), 'purchase-voucher-'.$purchase->purchase_number.'.pdf');
    }

    public function sale(Sale $sale, TransactionPdfService $service): Response
    {
        return $this->pdfResponse($service->sale($sale), 'sales-invoice-'.$sale->sale_number.'.pdf');
    }

    private function pdfResponse(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
