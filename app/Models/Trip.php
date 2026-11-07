<?php

namespace App\Models;

use App\TripStatus;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'starts_on',
        'ends_on',
        'purpose',
        'status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'status' => TripStatus::class,
    ];

    /**
     * The user who owns the trip.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The country (ACME branch) visited on the trip.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
