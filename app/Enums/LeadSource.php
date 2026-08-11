<?php

namespace App\Enums;

enum LeadSource: string
{
    case Call = 'call';
    case SocialMedia = 'social_media';
    case WhatsApp = 'WhatsApp';
    

    public function label(): string
    {
        return match ($this) {
            self::SocialMedia => 'Social Media',
            self::Call =>'Call',
            self::WhatsApp =>'WhatsApp',
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
