<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use App\Tenancy\FinancialYearContext;
use Illuminate\Http\Request;

class FinancialYearController extends Controller
{
    public function index(){return $this->ok(['current'=>app(FinancialYearContext::class)->financialYear(),'years'=>FinancialYear::with('closedBy:id,name')->orderByDesc('start_date')->get()]);}
    public function preview(FinancialYear $financialYear,FinancialYearService $service){return $this->ok(['financial_year'=>$financialYear,'balances'=>$service->preview($financialYear)]);}
    public function updateOpenings(Request $request,FinancialYear $financialYear,FinancialYearService $service){$data=$request->validate(['account_balances'=>'required|array|size:4','account_balances.*.account_key'=>'required|distinct|in:cash,bank_transfer,card,other','account_balances.*.opening_balance'=>'required|numeric']);return $this->ok(['financial_year'=>$financialYear,'balances'=>$service->updateAccountOpenings($financialYear,$data['account_balances'])],'Opening account balances updated.');}
    public function close(Request $request,FinancialYear $financialYear,FinancialYearService $service){return $this->ok(['closed'=>$service->close($financialYear,$request->user()->id),'current'=>app(FinancialYearContext::class)->financialYear()],'Financial year closed and balances carried forward.');}
    private function ok($data,string $message='Financial years retrieved.',int $status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
