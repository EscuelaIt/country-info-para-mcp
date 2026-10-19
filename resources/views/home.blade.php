@extends('layouts.app')

@section('title', 'Countries by Continent')

@section('content')
    {{-- Hero --}}
    <section class="hero rounded-box bg-linear-to-br from-primary to-secondary text-primary-content shadow-xl">
        <div class="hero-content flex-col gap-8 py-16 text-center">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-extrabold tracking-tight md:text-5xl">Countries Around the World</h1>
                <p class="mt-4 text-lg opacity-90">
                    Explore countries organized by continent. Pick one to discover its capital, languages, currencies and more.
                </p>
            </div>

            <div class="stats stats-vertical bg-base-100 text-base-content shadow-lg sm:stats-horizontal">
                <div class="stat place-items-center">
                    <div class="stat-title">Countries</div>
                    <div class="stat-value text-primary">{{ $stats['countries'] }}</div>
                </div>
                <div class="stat place-items-center">
                    <div class="stat-title">Continents</div>
                    <div class="stat-value text-secondary">{{ $stats['continents'] }}</div>
                </div>
                <div class="stat place-items-center">
                    <div class="stat-title">Population</div>
                    <div class="stat-value">{{ Number::abbreviate($stats['population'], precision: 1) }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Continent quick navigation --}}
    <nav class="sticky top-0 z-10 -mx-4 mt-8 flex flex-wrap justify-center gap-2 bg-base-100/80 px-4 py-3 backdrop-blur">
        @foreach($continents as $continent)
            <a href="#continent-{{ $continent->code }}" class="btn btn-sm btn-outline btn-primary rounded-full">
                {{ $continent->getContinentEmoji() }} {{ $continent->name }}
                <span class="badge badge-sm">{{ $continent->countries->count() }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Continents --}}
    <div class="mt-8 flex flex-col gap-14">
        @forelse($continents as $continent)
            <section id="continent-{{ $continent->code }}" class="scroll-mt-20">
                <div class="mb-6 flex items-center gap-3">
                    <span class="text-4xl">{{ $continent->getContinentEmoji() }}</span>
                    <h2 class="text-3xl font-bold">{{ $continent->name }}</h2>
                    <span class="badge badge-secondary badge-outline">{{ $continent->countries->count() }} countries</span>
                    <div class="divider grow"></div>
                </div>

                @if($continent->countries->isNotEmpty())
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach($continent->countries as $country)
                            <a href="{{ route('countries.show', $country->id) }}"
                               class="card border border-base-300 bg-base-100 shadow-sm transition hover:-translate-y-1 hover:border-primary hover:shadow-lg">
                                <div class="card-body items-center gap-1 p-5 text-center">
                                    <x-flag :code="$country->cca2" class="text-5xl" />
                                    <h3 class="mt-2 font-semibold">{{ $country->name }}</h3>
                                    @if($country->capital)
                                        <p class="text-sm text-base-content/60">{{ $country->capital }}</p>
                                    @endif
                                    <span class="badge badge-ghost badge-sm mt-1 font-mono">{{ $country->cca2 }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div role="alert" class="alert alert-soft alert-info">
                        <span>No countries found for this continent.</span>
                    </div>
                @endif
            </section>
        @empty
            <div role="alert" class="alert alert-warning">
                <span>No continents or countries data available.</span>
            </div>
        @endforelse
    </div>
@endsection
