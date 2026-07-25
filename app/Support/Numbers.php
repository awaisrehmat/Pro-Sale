<?php
namespace App\Support;
use Illuminate\Database\Eloquent\Model;
final class Numbers {
    public static function next(string $model, string $column, string $prefix): string {
        /** @var Model $model */
        $last = $model::query()->lockForUpdate()->max('id') ?? 0;
        return $prefix.'-'.str_pad((string)($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
