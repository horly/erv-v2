<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasAccountingReference
{
    protected static function bootHasAccountingReference(): void
    {
        static::created(function (Model $model): void {
            if (filled($model->reference)) {
                return;
            }

            $model->forceFill([
                'reference' => static::shouldUseUserYearReference()
                    ? static::userYearReference($model)
                    : static::referenceFromId((int) $model->id),
            ])->saveQuietly();
        });
    }

    public static function referenceFromId(int $id): string
    {
        return static::REFERENCE_PREFIX.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    protected static function shouldUseUserYearReference(): bool
    {
        return defined(static::class.'::USER_YEAR_REFERENCE') && (bool) constant(static::class.'::USER_YEAR_REFERENCE');
    }

    protected static function userYearReference(Model $model): string
    {
        $year = (int) ($model->created_at?->format('Y') ?: now()->format('Y'));
        $initials = static::referenceInitials((int) ($model->created_by ?? 0));
        $sequence = static::nextAnnualReferenceSequence($model, $year);

        return sprintf(
            '%s-%s-%s-%d',
            static::REFERENCE_PREFIX,
            $initials,
            str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            $year,
        );
    }

    protected static function nextAnnualReferenceSequence(Model $model, int $year): int
    {
        $prefix = static::REFERENCE_PREFIX;
        $pattern = '/^'.preg_quote($prefix, '/').'-[A-Z]{1,4}-(\d{6})-'.$year.'$/';
        $count = static::query()
            ->whereYear('created_at', $year)
            ->count();
        $maxSequence = static::query()
            ->where('reference', 'like', $prefix.'-%-'.$year)
            ->pluck('reference')
            ->reduce(function (int $max, ?string $reference) use ($pattern): int {
                if ($reference && preg_match($pattern, $reference, $matches)) {
                    return max($max, (int) $matches[1]);
                }

                return $max;
            }, 0);

        return max((int) $count, (int) $maxSequence + 1, 1);
    }

    protected static function referenceInitials(int $userId): string
    {
        $name = trim((string) User::query()->whereKey($userId)->value('name'));

        if ($name === '') {
            return 'SYS';
        }

        $asciiName = Str::ascii($name);
        $parts = preg_split('/\s+/', trim($asciiName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($parts[0] ?? 'SYS', 0, 2));
    }
}
