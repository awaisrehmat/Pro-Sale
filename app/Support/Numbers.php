<?php
namespace App\Support;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use App\Tenancy\CompanyContext;
final class Numbers {
    public static function next(string $documentCode, CarbonInterface|string $date): string {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $period = $date->format('ym');
        $companyId = app(CompanyContext::class)->requireId();
        DB::table('document_number_sequences')->insertOrIgnore([
            'company_id' => $companyId,
            'document_code' => $documentCode,
            'period' => $period,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sequence = DB::table('document_number_sequences')
            ->where('company_id', $companyId)
            ->where('document_code', $documentCode)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();
        $next = $sequence->last_number + 1;
        DB::table('document_number_sequences')->where('company_id',$companyId)->where('id', $sequence->id)->update(['last_number' => $next, 'updated_at' => now()]);
        $monthLetter = chr(64 + (int) $date->format('n'));

        return $documentCode.'_'.$monthLetter.$date->format('y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
