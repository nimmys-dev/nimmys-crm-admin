<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case WhatsApp = 'whatsapp';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::WhatsApp => 'WhatsApp',
            self::Note => 'Note',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'ti ti-phone',
            self::Email => 'ti ti-mail',
            self::Meeting => 'ti ti-users',
            self::WhatsApp => 'ti ti-brand-whatsapp',
            self::Note => 'ti ti-note',
        };
    }

    /**
     * A note records something that already happened, so it is never
     * scheduled for a future date.
     */
    public function isSchedulable(): bool
    {
        return $this !== self::Note;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
