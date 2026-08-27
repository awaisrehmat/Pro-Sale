<?php
namespace App\Support;
use App\Tenancy\CompanyContext;
use Illuminate\Validation\Rule;
final class TenantRule { public static function exists(string $table,string $column='id'){return Rule::exists($table,$column)->where('company_id',app(CompanyContext::class)->requireId());} public static function unique(string $table,string $column){return Rule::unique($table,$column)->where('company_id',app(CompanyContext::class)->requireId());} }
