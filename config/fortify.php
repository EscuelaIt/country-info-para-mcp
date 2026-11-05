<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard Fortify will use while authenticating users.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | The field the login form sends to identify the user.
    |
    */

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Where users are redirected after a successful login.
    |
    */

    'home' => '/panel',

    /*
    |--------------------------------------------------------------------------
    | Custom Route Paths
    |--------------------------------------------------------------------------
    |
    | The login screen lives at /user_login instead of Fortify's default /login.
    | The route keeps its "login" name, so the "auth" middleware still finds it.
    |
    */

    'paths' => [
        'login' => '/user_login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Prefix / Subdomain / Middleware
    |--------------------------------------------------------------------------
    */

    'prefix' => '',

    'domain' => null,

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | The "login" limiter is defined in App\Providers\FortifyServiceProvider.
    |
    */

    'limiters' => [
        'login' => 'login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | When enabled, Fortify registers the GET routes that render our Blade views.
    |
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Login and logout are always available. Every optional feature, including
    | registration, is disabled on purpose to keep this example minimal.
    |
    */

    'features' => [],

];
