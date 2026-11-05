<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the generate button when the user has no token', function () {
    $this->actingAs(User::factory()->create())
        ->get('/panel')
        ->assertOk()
        ->assertSee('Generate token')
        ->assertDontSee('Delete token');
});

it('issues a token valid for one year and shows it once', function () {
    $this->freezeSecond();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->followingRedirects()
        ->post('/panel/token')
        ->assertOk();

    $mcpToken = $user->mcpToken();

    expect($mcpToken)->not->toBeNull()
        ->and($mcpToken->expires_at->equalTo(now()->plus(years: 1)))->toBeTrue();

    $response->assertSee($mcpToken->id.'|')
        ->assertSee(now()->plus(years: 1)->format('d/m/Y H:i'))
        ->assertSee('Delete token');
});

it('issues a token that authenticates bearer requests', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/panel/token')->assertRedirect('/panel');
    $plainTextToken = session('plainTextToken');

    auth()->forgetGuards();

    $this->withToken($plainTextToken)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('shows an existing token without revealing its plain text value', function () {
    $user = User::factory()->create();
    $newAccessToken = $user->issueMcpToken();

    $this->actingAs($user)
        ->get('/panel')
        ->assertOk()
        ->assertSee($newAccessToken->accessToken->expires_at->format('d/m/Y H:i'))
        ->assertSee('Delete token')
        ->assertDontSee($newAccessToken->plainTextToken)
        ->assertDontSee('Generate token');
});

it('replaces the previous token when a new one is issued', function () {
    $user = User::factory()->create();
    $user->issueMcpToken();

    $this->actingAs($user)->post('/panel/token');

    expect($user->tokens()->count())->toBe(1);
});

it('deletes the token', function () {
    $user = User::factory()->create();
    $user->issueMcpToken();

    $this->actingAs($user)
        ->delete('/panel/token')
        ->assertRedirect('/panel');

    expect($user->tokens()->count())->toBe(0);
});

it('only deletes the authenticated user token', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUser->issueMcpToken();

    $this->actingAs($user)->delete('/panel/token');

    expect($otherUser->tokens()->count())->toBe(1);
});

it('requires authentication to manage tokens', function () {
    $this->post('/panel/token')->assertRedirect('/user_login');
    $this->delete('/panel/token')->assertRedirect('/user_login');
});
