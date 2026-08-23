<?php

namespace App\Support;

use App\Models\CompanyProfile;
use App\Models\Quotation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Sequential quotation reference, e.g. QTN-0001 or configured custom prefix.
 *
 * Same shape as LeadReference: the read is taken under a row lock inside a
 * transaction, and the UNIQUE index is the backstop if a writer slips past
 * it. Unlike LeadReference, there is no withTrashed() — Quotation is not
 * soft-deleted (see the model), so every row is a real one and a deleted
 * quotation's reference is free to be reused by a later one.
 */
class QuotationReference
{
    public const DEFAULT_PREFIX = 'QTN-';

    public const PREFIX = 'QTN-';

    public const PAD = 4;

    public static function prefix(): string
    {
        try {
            $prefix = CompanyProfile::current()->quotation_prefix;
            if (filled($prefix)) {
                $clean = strtoupper(trim((string) $prefix));

                return (str_ends_with($clean, '-') || str_ends_with($clean, '/'))
                    ? $clean
                    : $clean.'-';
            }
        } catch (\Throwable) {
            // Fallback during initial schema setup
        }

        return self::DEFAULT_PREFIX;
    }

    public static function next(?string $customPrefix = null): string
    {
        $prefix = $customPrefix ?? self::prefix();

        $latest = Quotation::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->lockForUpdate()
            ->value('reference');

        $number = $latest
            ? (int) substr($latest, strlen($prefix)) + 1
            : 1;

        return self::format($number, $prefix);
    }

    public static function format(int $number, ?string $prefix = null): string
    {
        $prefix = $prefix ?? self::prefix();

        return $prefix.str_pad((string) $number, self::PAD, '0', STR_PAD_LEFT);
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function withNext(callable $callback, int $attempts = 3, ?string $customPrefix = null): mixed
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction(fn () => $callback(self::next($customPrefix)));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === $attempts) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a quotation reference.');
    }
}
