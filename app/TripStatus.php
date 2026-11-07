<?php

namespace App;

enum TripStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether a trip in this status can no longer change its status.
     */
    public function isFinal(): bool
    {
        return $this !== self::Planned;
    }

    /**
     * DaisyUI badge class used to display the status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Planned => 'badge-info',
            self::Completed => 'badge-success',
            self::Cancelled => 'badge-error',
        };
    }
}
