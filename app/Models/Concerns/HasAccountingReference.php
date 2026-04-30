<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasAccountingReference
{
    protected static function bootHasAccountingReference(): void
    {
        static::created(function (Model $model): void {
            if (filled($model->reference)) {
                return;
            }

            $model->forceFill([
                'reference' => static::referenceFromId((int) $model->id),
            ])->saveQuietly();
        });
    }

    public static function referenceFromId(int $id): string
    {
        return static::REFERENCE_PREFIX.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
