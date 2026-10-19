<?php

namespace App\Models;

use Database\Factories\ContinentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Continent extends Model
{
    /** @use HasFactory<ContinentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function getContinentEmoji(): ?string
    {
        return match ($this->code ?? $this->name) {
            'AF', 'Africa' => '🌍',
            'AN', 'Antarctica' => '❄️',
            'AS', 'Asia' => '🌏',
            'EU', 'Europe' => '🏛️',
            'NA', 'North America' => '🗽',
            'AM', 'América' => '🌎',
            'OC', 'Oceania' => '🏝️',
            'SA', 'South America' => '🦜',
            default => '🌍',
        };
    }
}
