<?php

namespace App\Http\Middleware;

use App\Services\FinancialYearService;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveFinancialYear
{
    public function __construct(private readonly FinancialYearService $service) {}

    public function handle(Request $request,Closure $next):Response
    {
        $year=$this->service->ensureCurrent(app(CompanyContext::class)->company());
        $request->attributes->set('financial_year',$year);
        return $next($request);
    }
}
