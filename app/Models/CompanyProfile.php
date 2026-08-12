<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The letterhead used on generated documents (currently: quotations).
 *
 * A singleton — always row id 1, reached through current() rather than a
 * query, so callers never have to think about "what if there are two" or
 * "what if there are none".
 */
class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address_line',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'logo_path',
    ];

    /**
     * The one company profile row, creating it with sensible defaults the
     * first time anything asks for it.
     *
     * Deliberately not firstOrCreate(['id' => 1], ...): 'id' is not
     * fillable (mass-assigning a primary key is asking for trouble), so
     * Eloquent would silently drop it from the create — every call would
     * find no row with id 1, insert a new autoincrement row that still
     * is not 1, and repeat forever. Taking the first row ever created
     * needs no such pinning.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? static::query()->create(['name' => config('app.name', 'Company')]);
    }

    /**
     * Address lines joined for a single-line display, or null when nothing
     * has been recorded — printing "—" beats printing a dangling comma.
     */
    public function fullAddress(): ?string
    {
        $line = collect([$this->address_line, $this->city, $this->state, $this->postal_code, $this->country])
            ->filter()
            ->implode(', ');

        return $line !== '' ? $line : null;
    }
}
