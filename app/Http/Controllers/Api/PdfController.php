<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Pdf\VoucherPdfService;
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
}
