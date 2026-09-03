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
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal Sent',
            self::Negotiation => 'Negotiation',
            self::Closed => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return ! $this->isClosed();
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'badge-lead-new',
            self::Contacted, self::Qualified => 'badge-lead-progress',
            self::Proposal, self::Negotiation => 'badge-lead-active',
            self::Closed => 'badge-lead-closed',
        };
    }

    public static function open(): array
    {
        return array_values(
            array_filter(
                self::cases(),
                fn (self $s) => $s->isOpen()
            )
        );
    }

    public static function closed(): array
    {
        return array_values(
            array_filter(
                self::cases(),
                fn (self $s) => $s->isClosed()
            )
        );
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}