<?php

namespace App\Mcp\Tools;

use App\Models\Trip;
use App\Models\User;
use App\TripStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List the trips to ACME LTDA branches registered by the authenticated user. Only the trips owned by the current user are returned, never the trips of other users. Results can be filtered by status (planned, completed or cancelled) and are sorted by start date.')]
class MyTripsListTool extends Tool
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
            'status' => ['nullable', Rule::enum(TripStatus::class)],
        ]);

        $trips = $user->trips()
            ->with('country')
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status']),
            )
            ->orderBy('starts_on')
            ->get()
            ->map(fn (Trip $trip): array => [
                'id' => $trip->id,
                'country' => $trip->country->name,
                'country_code' => $trip->country->cca2,
                'starts_on' => $trip->starts_on->toDateString(),
                'ends_on' => $trip->ends_on->toDateString(),
                'purpose' => $trip->purpose,
                'status' => $trip->status->value,
            ]);

        return Response::structured([
            'count' => $trips->count(),
            'trips' => $trips->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(array_column(TripStatus::cases(), 'value'))
                ->description('Optional trip status to filter by: "planned", "completed" or "cancelled". Omit it to retrieve all the trips of the user.'),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'count' => $schema->integer()->description('Total number of trips returned.')->required(),
            'trips' => $schema->array()
                ->description('Trips of the authenticated user, sorted by start date.')
                ->items($schema->object([
                    'id' => $schema->integer()->description('Trip identifier.')->required(),
                    'country' => $schema->string()->description('Name of the visited country (e.g. "Spain").')->required(),
                    'country_code' => $schema->string()->description('ISO 3166-1 alpha-2 code of the country (e.g. "ES").')->required(),
                    'starts_on' => $schema->string()->description('Start date of the trip (YYYY-MM-DD).')->required(),
                    'ends_on' => $schema->string()->description('End date of the trip (YYYY-MM-DD).')->required(),
                    'purpose' => $schema->string()->description('Reason for the trip.')->required(),
                    'status' => $schema->string()->enum(array_column(TripStatus::cases(), 'value'))->description('Trip status.')->required(),
                ]))
                ->required(),
        ];
    }
}
