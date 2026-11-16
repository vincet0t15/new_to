<?php

namespace App\Enums;

enum TravelOrderDeliveryStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Acted = 'acted';
    case Closed = 'closed';

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Received;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Acted => 'Acted',
            self::Closed => 'Closed',
        };
    }
}
