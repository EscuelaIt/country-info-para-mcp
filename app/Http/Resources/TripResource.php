<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, country_id: int, starts_on: string, ends_on: string, purpose: string, status: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'purpose' => $this->purpose,
            'status' => $this->status->value,
        ];
    }
}
