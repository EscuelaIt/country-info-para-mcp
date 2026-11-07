<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Auth\Access\Response;

class TripPolicy
{
    /**
     * Determine whether the user can update the trip.
     *
     * Trips of other users are reported as not found so their existence is not disclosed.
     */
    public function update(User $user, Trip $trip): Response
    {
        return $user->id === $trip->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can change the trip to the given status, applying ACME business rules.
     *
     * - Completed and cancelled trips can no longer change their status.
     * - A trip can only be completed once it has started.
     * - A trip can only be cancelled before it starts.
     */
    public function changeStatus(User $user, Trip $trip, TripStatus $newStatus): Response
    {
        $ownershipResponse = $this->update($user, $trip);

        if ($ownershipResponse->denied()) {
            return $ownershipResponse;
        }

        $hasStarted = $trip->starts_on->lte(today());

        return match (true) {
            $trip->status === $newStatus => Response::deny("The trip is already {$newStatus->value}."),
            $trip->status->isFinal() => Response::deny("The trip is {$trip->status->value} and its status can no longer be changed."),
            $newStatus === TripStatus::Completed && ! $hasStarted => Response::deny('The trip cannot be completed before it starts.'),
            $newStatus === TripStatus::Cancelled && $hasStarted => Response::deny('The trip cannot be cancelled once it has started.'),
            default => Response::allow(),
        };
    }
}
