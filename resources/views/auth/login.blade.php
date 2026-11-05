@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="flex justify-center">
        <div class="card w-full max-w-sm bg-base-200 shadow-xl">
            <form method="POST" action="{{ route('login.store') }}" class="card-body gap-4">
                @csrf

                <h1 class="card-title text-2xl">Login</h1>

                <fieldset class="fieldset">
                    <label for="email" class="fieldset-legend">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        @class(['input w-full', 'input-error' => $errors->has('email')])
                    >
                    @error('email')
                        <p class="label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset">
                    <label for="password" class="fieldset-legend">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        @class(['input w-full', 'input-error' => $errors->has('password')])
                    >
                    @error('password')
                        <p class="label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <label class="label cursor-pointer gap-2">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm">
                    Remember me
                </label>

                <button type="submit" class="btn btn-primary">Log in</button>
            </form>
        </div>
    </div>
@endsection
