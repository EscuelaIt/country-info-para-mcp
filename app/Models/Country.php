<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'continent_id',
        'name',
        'official_name',
        'cca2',
        'cca3',
        'flag_emoji',
        'capital',
        'latitude',
        'longitude',
        'area_km2',
        'population',
        'gini',
        'languages',
        'summary',
    ];

    protected $casts = [
        'languages' => 'json',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'gini' => 'decimal:1',
    ];

    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    public function neighbors(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'country_borders',
            'country_id',
            'neighbor_id'
        );
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'country_currency');
    }
}
