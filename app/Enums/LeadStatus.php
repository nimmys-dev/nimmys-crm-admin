<?php

namespace App\Enums;

/**
 * Pipeline stage of a lead.
 *
 * Ordered as the pipeline runs, so ::cases() doubles as the funnel order for
 * dashboard reporting.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal Sent',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /**
     * Still in play. Open leads are what follow-up reminders and the
     * dashboard pipeline count.
     */
    public function isOpen(): bool
    {
        return ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }

    /**
     * Losing a lead requires a reason; winning does not.
     */
    public function requiresReason(): bool
    {
        return $this === self::Lost;
    }

    /**
     * Theme badge classes. Semantic colour, deliberately not the brand
     * accent, so stage reads at a glance in a dense listing.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'badge-lead-new',
            self::Contacted, self::Qualified => 'badge-lead-progress',
            self::Proposal, self::Negotiation => 'badge-lead-active',
            self::Won => 'badge-lead-won',
            self::Lost => 'badge-lead-lost',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isOpen()));
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
