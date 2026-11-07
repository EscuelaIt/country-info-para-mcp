<?php

namespace App\Mcp\Tools;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change the status of a trip owned by the authenticated user. Only planned trips can change: they can be completed once they have started, or cancelled before they start. Completed and cancelled trips can no longer change their status. Use the my-trips-list-tool first to find the trip id.')]
class ChangeTripStatusTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user('sanctum');

        if (! $user instanceof User) {
            return Response::error('No hay un usuario autenticado para esta sesión.');
        }

        $validated = $request->validate([
            'trip_id' => ['required', 'integer'],
            'status' => ['required', Rule::enum(TripStatus::class)],
        ]);

        $trip = $user->trips()->find($validated['trip_id']);

        if (! $trip instanceof Trip) {
            return Response::error("No se ha encontrado el viaje {$validated['trip_id']} entre tus viajes.");
        }

        $newStatus = TripStatus::from($validated['status']);

        $authorization = Gate::forUser($user)->inspect('changeStatus', [$trip, $newStatus]);

        if ($authorization->denied()) {
            return Response::error($authorization->message());
        }

        $trip->update(['status' => $newStatus]);

        return Response::structured(TripResource::make($trip)->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trip_id' => $schema->integer()
                ->description('Identifier of the trip to update.')
                ->required(),
            'status' => $schema->string()->enum(array_column(TripStatus::cases(), 'value'))
                ->description('New status of the trip: "planned", "completed" or "cancelled".')
                ->required(),
        ];
    }
}
