<?php

namespace App\Enums;

enum LeadSource: string
{
    case Website = 'website';
    case Call = 'call';
    case SocialMedia = 'social_media';
    case WhatsApp = 'WhatsApp';
    case Ref = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::SocialMedia => 'Social Media',
            self::Call =>'Call',
            self::WhatsApp =>'WhatsApp',
            self::Ref =>'Reff'
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect([
            self::SocialMedia,
            self::Call,
            self::WhatsApp,
        ])
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
