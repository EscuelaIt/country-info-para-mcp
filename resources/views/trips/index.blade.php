@extends('layouts.app')

@section('title', 'Trips of all users - Countries Info')

@section('content')
    <div class="mb-8">
        <h1 class="text-4xl font-bold">✈️ Trips to ACME branches</h1>
        <p class="mt-2 text-base-content/70">
            Trips registered by every user. Each user should only be able to access their own trips through the MCP server.
        </p>
    </div>

    <div class="flex flex-col gap-10">
        @forelse($tripsByUser as $trips)
            @php($user = $trips->first()->user)

            <section class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <h2 class="card-title text-2xl">{{ $user->name }}</h2>
                        <span class="badge badge-outline">{{ $user->email }}</span>
                        <span class="badge badge-secondary">{{ $trips->count() }} trips</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Country</th>
                                    <th>Dates</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trips as $trip)
                                    <tr>
                                        <td class="font-mono">{{ $trip->id }}</td>
                                        <td>
                                            <a href="{{ route('countries.show', $trip->country) }}" class="link link-hover">
                                                <x-flag :code="$trip->country->cca2" />
                                                {{ $trip->country->name }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            {{ $trip->starts_on->toDateString() }} → {{ $trip->ends_on->toDateString() }}
                                        </td>
                                        <td>{{ $trip->purpose }}</td>
                                        <td>
                                            <span class="badge {{ $trip->status->badgeClass() }}">{{ $trip->status->label() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @empty
            <div class="alert">No trips registered yet.</div>
        @endforelse
    </div>
@endsection
