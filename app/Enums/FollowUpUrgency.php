<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

/**
 * How pressing a scheduled follow-up is.
 *
 * Derived from a date rather than stored, so it can never go stale — a row
 * written yesterday as "Upcoming" reads as "Overdue" today without anything
 * having to rewrite it.
 */
enum FollowUpUrgency: string
{
    case Overdue = 'overdue';
    case DueToday = 'due_today';
    case Upcoming = 'upcoming';

    public function label(): string
    {
        return match ($this) {
            self::Overdue => 'Overdue',
            self::DueToday => 'Due Today',
            self::Upcoming => 'Upcoming',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Overdue => 'badge-lead-lost',
            self::DueToday => 'badge-due',
            self::Upcoming => 'badge-lead-new',
        };
    }

    /**
     * Classify a follow-up date. Null in, null out — no date means nothing
     * is pending, which is not the same as "not urgent".
     */
    public static function forDate(?Carbon $date): ?self
    {
        if ($date === null) {
            return null;
        }

        return match (true) {
            $date->isBefore(today()) => self::Overdue,
            $date->isSameDay(today()) => self::DueToday,
            default => self::Upcoming,
        };
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
