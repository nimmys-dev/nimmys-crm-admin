<?php

namespace App\Enums;

/**
 * Outcome of a single phone call.
 *
 * Distinct from LeadStatus: this records what happened on one call, while
 * LeadStatus records where the lead sits in the pipeline. A call can be
 * "Not Connected" without the lead moving at all.
 */
enum CallStatus: string
{
    case Connected = 'connected';
    case NotConnected = 'not_connected';
    case Busy = 'busy';
    case CallBack = 'call_back';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case WrongNumber = 'wrong_number';
    case SwitchedOff = 'switched_off';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::NotConnected => 'Not Connected',
            self::Busy => 'Busy',
            self::CallBack => 'Call Back',
            self::Interested => 'Interested',
            self::NotInterested => 'Not Interested',
            self::WrongNumber => 'Wrong Number',
            self::SwitchedOff => 'Switched Off',
            self::Converted => 'Converted',
            self::Lost => 'Lost',
        };
    }

    /**
     * Whether the call actually reached the person.
     *
     * Drives the "contact rate" figure and decides whether a call updates
     * the lead's last_contacted_at.
     */
    public function reachedContact(): bool
    {
        return in_array($this, [
            self::Connected,
            self::Interested,
            self::NotInterested,
            self::Converted,
            self::Lost,
            self::CallBack,
        ], true);
    }

    /**
     * Outcomes that close the conversation — no further follow-up expected.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Converted,
            self::Lost,
            self::NotInterested,
            self::WrongNumber,
        ], true);
    }

    /**
     * Counted towards "interested leads" on the dashboard.
     */
    public function isPositive(): bool
    {
        return in_array($this, [self::Interested, self::Converted], true);
    }

    /**
     * Semantic badge colour, reusing the palette the Lead module defines.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Connected => 'badge-lead-progress',
            self::Interested => 'badge-lead-active',
            self::Converted => 'badge-lead-won',
            self::CallBack => 'badge-lead-new',
            self::Busy, self::NotConnected, self::SwitchedOff => 'badge-off',
            self::NotInterested, self::WrongNumber, self::Lost => 'badge-lead-lost',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Connected, self::Interested => 'ti ti-phone-call',
            self::Converted => 'ti ti-circle-check',
            self::CallBack => 'ti ti-phone-plus',
            self::Busy => 'ti ti-phone-pause',
            self::NotConnected, self::SwitchedOff => 'ti ti-phone-off',
            self::NotInterested, self::Lost => 'ti ti-phone-x',
            self::WrongNumber => 'ti ti-alert-triangle',
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
