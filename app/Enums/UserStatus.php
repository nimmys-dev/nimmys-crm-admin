<?php

namespace App\Enums;

/**
 * Account lifecycle state, kept separate from soft deletes.
 *
 * Deactivating is reversible and preserves the audit trail on leads, tasks
 * and shops; deleting a user would orphan that history.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Only active accounts may authenticate, on web or mobile.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
