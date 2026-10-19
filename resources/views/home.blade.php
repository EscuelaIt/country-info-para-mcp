@extends('layouts.app')

@section('title', 'Countries by Continent')

@section('content')
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-center mb-4">Countries Around the World</h1>
        <p class="text-center text-lg text-base-content/70">
            Explore countries organized by continent
        </p>
    </div>

    @forelse($continents as $continent)
        <section class="mb-12">
            <h2 class="text-3xl font-bold mb-6 flex items-center gap-2">
                <span class="text-2xl">{{ $continent->getContinentEmoji() ?? '🌍' }}</span>
                {{ $continent->name }}
            </h2>

            @if($continent->countries->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($continent->countries as $country)
                        <div class="card bg-base-100 shadow-md hover:shadow-lg transition-shadow">
                            <div class="card-body">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="card-title text-xl">
                                            <span class="text-2xl">{{ $country->flag_emoji ?? '🏳️' }}</span>
                                            {{ $country->name }}
                                        </h3>
                                        <p class="text-sm text-base-content/60">
                                            {{ $country->official_name }}
                                        </p>
                                    </div>
                                    <div class="badge badge-primary">{{ $country->cca2 }}</div>
                                </div>

                                <div class="divider my-2"></div>

                                <div class="space-y-2 text-sm">
                                    @if($country->capital)
                                        <p>
                                            <span class="font-semibold">Capital:</span>
                                            {{ $country->capital }}
                                        </p>
                                    @endif

                                    @if($country->population)
                                        <p>
                                            <span class="font-semibold">Population:</span>
                                            {{ number_format($country->population) }}
                                        </p>
                                    @endif

                                    @if($country->area_km2)
                                        <p>
                                            <span class="font-semibold">Area:</span>
                                            {{ number_format($country->area_km2, 0) }} km²
                                        </p>
                                    @endif

                                    @if($country->languages)
                                        <p>
                                            <span class="font-semibold">Languages:</span>
                                            <span class="text-xs">
                                                {{ implode(', ', array_values($country->languages)) }}
                                            </span>
                                        </p>
                                    @endif
                                </div>

                                @if($country->summary)
                                    <div class="mt-4 p-3 bg-base-200 rounded">
                                        <p class="text-xs text-base-content/70 line-clamp-3">
                                            {{ $country->summary }}
                                        </p>
                                    </div>
                                @endif

                                <div class="card-actions justify-end mt-4">
                                    <a href="#" class="btn btn-sm btn-primary">
                                        Learn More
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <p>No countries found for this continent.</p>
                </div>
            @endif
        </section>
    @empty
        <div class="alert alert-warning">
            <p>No continents or countries data available.</p>
        </div>
    @endforelse
@endsection
