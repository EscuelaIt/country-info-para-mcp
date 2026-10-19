@extends('layouts.app')

@section('title', $country->name . ' - Countries Info')

@section('content')
    <div class="mb-8">
        <a href="{{ route('home') }}" class="btn btn-ghost btn-sm">
            ← Back to Countries
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Info --}}
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h1 class="text-4xl font-bold">
                                <x-flag :code="$country->cca2" class="text-5xl" />
                                {{ $country->name }}
                            </h1>
                            <p class="text-xl text-base-content/70 mt-2">
                                {{ $country->official_name }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div class="badge badge-lg badge-primary">{{ $country->cca2 }}</div>
                            <div class="badge badge-lg badge-secondary">{{ $country->cca3 }}</div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    {{-- Basic Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        @if($country->continent)
                            <div>
                                <h3 class="text-sm font-semibold text-base-content/70 mb-2">CONTINENT</h3>
                                <p class="text-lg">
                                    <span>{{ $country->continent->getContinentEmoji() ?? '🌍' }}</span>
                                    {{ $country->continent->name }}
                                </p>
                            </div>
                        @endif

                        @if($country->capital)
                            <div>
                                <h3 class="text-sm font-semibold text-base-content/70 mb-2">CAPITAL</h3>
                                <p class="text-lg">{{ $country->capital }}</p>
                            </div>
                        @endif

                        @if($country->population)
                            <div>
                                <h3 class="text-sm font-semibold text-base-content/70 mb-2">POPULATION</h3>
                                <p class="text-lg">{{ number_format($country->population) }}</p>
                            </div>
                        @endif

                        @if($country->area_km2)
                            <div>
                                <h3 class="text-sm font-semibold text-base-content/70 mb-2">AREA</h3>
                                <p class="text-lg">{{ number_format($country->area_km2, 0) }} km²</p>
                            </div>
                        @endif
                    </div>

                    {{-- Languages --}}
                    @if($country->languages)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-base-content/70 mb-3">LANGUAGES</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($country->languages as $language)
                                    <div class="badge badge-outline">{{ $language }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Currencies --}}
                    @if($country->currencies->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-base-content/70 mb-3">CURRENCIES</h3>
                            <div class="space-y-2">
                                @foreach($country->currencies as $currency)
                                    <div class="flex justify-between items-center p-2 bg-base-200 rounded">
                                        <span>{{ $currency->name }}</span>
                                        <span class="font-mono font-semibold">{{ $currency->code }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Summary --}}
                    @if($country->summary)
                        <div>
                            <h3 class="text-sm font-semibold text-base-content/70 mb-3">SUMMARY</h3>
                            <p class="text-base-content/80 leading-relaxed">
                                {{ $country->summary }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="lg:col-span-1">
            <div class="space-y-4">
                {{-- Coordinates --}}
                @if($country->latitude && $country->longitude)
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h3 class="card-title text-lg">Coordinates</h3>
                            <div class="space-y-2 text-sm">
                                <p>
                                    <span class="font-semibold">Latitude:</span>
                                    <span class="font-mono">{{ $country->latitude }}</span>
                                </p>
                                <p>
                                    <span class="font-semibold">Longitude:</span>
                                    <span class="font-mono">{{ $country->longitude }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Gini Index --}}
                @if($country->gini)
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h3 class="card-title text-lg">Gini Index</h3>
                            <p class="text-2xl font-bold text-primary">{{ $country->gini }}</p>
                            <p class="text-xs text-base-content/60">
                                Income inequality measure (0-100)
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Neighbors --}}
                @if($country->neighbors->count() > 0)
                    <div class="card bg-base-100 shadow">
                        <div class="card-body">
                            <h3 class="card-title text-lg">Neighboring Countries</h3>
                            <div class="space-y-2">
                                @foreach($country->neighbors as $neighbor)
                                    <a href="{{ route('countries.show', $neighbor->id) }}" class="block p-2 bg-base-200 hover:bg-base-300 rounded transition">
                                        <x-flag :code="$neighbor->cca2" />
                                        <span>{{ $neighbor->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
