<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Generates the next sequential employee code, e.g. EMP-0001.
 *
 * Two admins can submit the create form at the same moment, so reading the
 * highest existing code and adding one is not safe on its own. Two guards:
 *
 *   1. The read happens inside a transaction with a row-level lock
 *      (lockForUpdate), so concurrent writers queue rather than both
 *      reading the same maximum.
 *   2. employee_code carries a UNIQUE index, so even if a duplicate slipped
 *      through the database refuses it and the caller retries.
 *
 * Soft-deleted staff are included in the scan — reusing a departed
 * employee's code would corrupt historical records.
 */
class EmployeeCode
{
    public const PREFIX = 'EMP-';

    public const PAD = 4;

    /**
     * Next available code.
     *
     * Call inside the same transaction as the insert so the lock is held
     * until the new row exists.
     */
    public static function next(): string
    {
        $latest = User::withTrashed()
            ->where('employee_code', 'like', self::PREFIX.'%')
            ->orderByRaw('LENGTH(employee_code) DESC')
            ->orderBy('employee_code', 'desc')
            ->lockForUpdate()
            ->value('employee_code');

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
     * Generate a code and hand it to the callback, retrying on the unique
     * constraint. Protects against the narrow window the lock cannot cover
     * (for example a code inserted by a seeder outside the transaction).
     *
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

        // Unreachable: the loop either returns or rethrows.
        throw new \RuntimeException('Could not allocate an employee code.');
    }
}
