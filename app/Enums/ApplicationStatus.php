<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Wishlist = 'wishlist';
    case Applied = 'applied';
    case Interview = 'interview';
    case Offer = 'offer';
    case Rejected = 'rejected';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
