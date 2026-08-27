<?php

namespace App\Http\Middleware;

use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        $companyId=(int)($request->header('X-Company-ID') ?: 0);
        $companies=$user->companies()->where('companies.is_active',true)->orderBy('companies.name')->get();
        $company=$companyId?$companies->firstWhere('id',$companyId):$companies->first();
        abort_unless($company,403,'You do not have access to an active company.');
        app(CompanyContext::class)->set($company);
        $request->attributes->set('company',$company);
        return $next($request);
    }
}
