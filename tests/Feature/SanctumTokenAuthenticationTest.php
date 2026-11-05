<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticates a user with a valid bearer token', function () {
    $user = User::factory()->create();
    $plainTextToken = $user->createToken('mcp-client')->plainTextToken;

    $this->withToken($plainTextToken)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('rejects requests without a bearer token', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('rejects requests with an invalid bearer token', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/user')
        ->assertUnauthorized();
});

it('rejects a token after it has been revoked', function () {
    $user = User::factory()->create();
    $newAccessToken = $user->createToken('mcp-client');

    $newAccessToken->accessToken->delete();

    $this->withToken($newAccessToken->plainTextToken)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
