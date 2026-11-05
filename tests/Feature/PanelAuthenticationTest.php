<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login form at /user_login', function () {
    $this->get('/user_login')
        ->assertOk()
        ->assertSee('name="email"', escape: false)
        ->assertSee('name="password"', escape: false);
});

it('logs in with valid credentials and redirects to the panel', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post('/user_login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/panel');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->from('/user_login')
        ->post('/user_login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/user_login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('redirects guests from the panel to the login page', function () {
    $this->get('/panel')->assertRedirect('/user_login');
});

it('shows the panel to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/panel')
        ->assertOk()
        ->assertSee($user->name);
});

it('redirects authenticated users away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/user_login')
        ->assertRedirect('/panel');
});

it('logs out the user', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('does not expose user registration', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('does not link the login page from the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('user_login');
});
