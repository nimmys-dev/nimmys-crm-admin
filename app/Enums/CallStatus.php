<?php

namespace App\Enums;

/**
 * Outcome of a single phone call.
 *
 * Distinct from LeadStatus: this records whether the call was answered or
 * not answered, initiating the call details decision tree.
 */
enum CallStatus: string
{
    case Answered = 'answered';
    case NotAnswered = 'not_answered';

    public function label(): string
    {
        return match ($this) {
            self::Answered => 'Answered',
            self::NotAnswered => 'Not Answered',
        };
    }

    /**
     * Whether the call actually reached the person.
     */
    public function reachedContact(): bool
    {
        return $this === self::Answered;
    }

    /**
     * Semantic badge colour.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Answered => 'badge-lead-success',
            self::NotAnswered => 'badge-off',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Answered => 'ti ti-phone-call',
            self::NotAnswered => 'ti ti-phone-off',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Answered->value => 'Answered',
            self::NotAnswered->value => 'Not Answered',
        ];
    }
}