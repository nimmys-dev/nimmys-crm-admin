<?php

namespace App\Support;

use App\Models\Quotation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Sequential quotation reference, e.g. QTN-0001.
 *
 * Same shape as LeadReference: the read is taken under a row lock inside a
 * transaction, and the UNIQUE index is the backstop if a writer slips past
 * it. Unlike LeadReference, there is no withTrashed() — Quotation is not
 * soft-deleted (see the model), so every row is a real one and a deleted
 * quotation's reference is free to be reused by a later one.
 */
class QuotationReference
{
    public const PREFIX = 'QTN-';

    public const PAD = 4;

    public static function next(): string
    {
        $latest = Quotation::query()
            ->where('reference', 'like', self::PREFIX.'%')
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->lockForUpdate()
            ->value('reference');

        $number = $latest
            ? (int) substr($latest, strlen(self::PREFIX)) + 1
            : 1;

        return self::format($number);
    }

    public static function format(int $number): string
    {
        return self::PREFIX.str_pad((string) $number, self::PAD, '0', STR_PAD_LEFT);
    }

    /**
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function withNext(callable $callback, int $attempts = 3): mixed
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction(fn () => $callback(self::next()));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === $attempts) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a quotation reference.');
    }
}
