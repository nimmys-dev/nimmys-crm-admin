<?php

namespace App\Enums;

enum LeadSource: string
{
    case Website = 'website';
    case Referral = 'referral';
    case WalkIn = 'walk_in';
    case Phone = 'phone';
    case Social = 'social';
    case Campaign = 'campaign';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Referral => 'Referral',
            self::WalkIn => 'Walk-in',
            self::Phone => 'Phone enquiry',
            self::Social => 'Social media',
            self::Campaign => 'Campaign',
            self::Other => 'Other',
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
