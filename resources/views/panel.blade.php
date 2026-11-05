@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="card-title text-2xl">Panel</h1>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Log out</button>
                </form>
            </div>

            <p>Welcome, {{ auth()->user()->name }}.</p>

            <div class="alert">
                <span>Coming soon: here you will be able to create API bearer tokens for your MCP clients.</span>
            </div>
        </div>
    </div>
@endsection
