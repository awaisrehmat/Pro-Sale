<?php
namespace App\Services;
use App\Models\{Customer,Payment,Purchase,Sale,Supplier};
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Tenancy\FinancialYearContext;

class PaymentService
{
    public function record(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            app(FinancialYearService::class)->lockActive();
            $amount = (float) $data['amount'];
            $document = null;
            $year=app(FinancialYearContext::class)->financialYear();
            $financialYears=app(FinancialYearService::class);

            if ($data['payment_type'] === 'supplier_payment') {
                $party = Supplier::query()->lockForUpdate()->findOrFail($data['supplier_id']);
                $partyOutstanding = $financialYears->partyOpening($year,'supplier',$party->id,(float)$party->opening_balance)
                    + (float) $party->purchases()->where('financial_year_id',$year->id)->where('status', 'completed')->sum('grand_total')
                    - (float) $party->payments()->where('financial_year_id',$year->id)->where('payment_type','supplier_payment')->where('is_reversed', false)->sum('amount');
                if (! empty($data['purchase_id'])) {
                    $document = Purchase::query()->lockForUpdate()->where('financial_year_id',$year->id)->where('supplier_id', $party->id)->where('status', 'completed')->findOrFail($data['purchase_id']);
                    $outstanding = min((float) $document->due_amount, $partyOutstanding);
                } else {
                    $outstanding = $partyOutstanding;
                }
            } else {
                $party = Customer::query()->lockForUpdate()->findOrFail($data['customer_id']);
                $partyOutstanding = $financialYears->partyOpening($year,'customer',$party->id,(float)$party->opening_balance)
                    + (float) $party->sales()->where('financial_year_id',$year->id)->where('status', 'completed')->sum('grand_total')
                    - (float) $party->payments()->where('financial_year_id',$year->id)->where('payment_type','customer_payment')->where('is_reversed', false)->sum('amount');
                if (! empty($data['sale_id'])) {
                    $document = Sale::query()->lockForUpdate()->where('financial_year_id',$year->id)->where('customer_id', $party->id)->where('status', 'completed')->findOrFail($data['sale_id']);
                    $outstanding = min((float) $document->due_amount, $partyOutstanding);
                } else {
                    $outstanding = $partyOutstanding;
                }
            }

            if ($amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => "Payment cannot exceed outstanding amount of {$outstanding}."]);
            }

            $payment = Payment::create([
                ...$data,
                'payment_number' => Numbers::next($data['payment_type'] === 'supplier_payment' ? 'PV' : 'RV', $data['payment_date']),
                'created_by' => $userId,
            ]);

            if ($document) {
                $paid = round((float) $document->paid_amount + $amount, 2);
                $due = max(0, round((float) $document->grand_total - $paid, 2));
                $document->update([
                    'paid_amount' => $paid,
                    'due_amount' => $due,
                    'payment_status' => $due <= 0 ? 'paid' : 'partial',
                ]);
            }

            return $payment->load('supplier', 'customer', 'purchase', 'sale');
        });
    }
}
