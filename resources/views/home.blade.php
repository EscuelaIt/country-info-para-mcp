@extends('layouts.app')

@section('title', 'Countries by Continent')

@section('content')
    <div class="mb-12">
        <h1 class="text-4xl font-bold text-center mb-4">Countries Around the World</h1>
        <p class="text-center text-lg text-base-content/70">
            Explore countries organized by continent
        </p>
    </div>

    @forelse($continents as $continent)
        <div class="mb-8">
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-4">
                        <span class="text-3xl">{{ $continent->getContinentEmoji() ?? '🌍' }}</span>
                        {{ $continent->name }}
                    </h2>

                    @if($continent->countries->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="table table-compact w-full">
                                <thead>
                                    <tr>
                                        <th>Flag</th>
                                        <th>Country</th>
                                        <th>ISO Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($continent->countries as $country)
                                        <tr class="hover:bg-base-200 cursor-pointer" onclick="window.location.href='{{ route('countries.show', $country->id) }}'">
                                            <td class="text-2xl">{{ $country->flag_emoji ?? '🏳️' }}</td>
                                            <td>
                                                <a href="{{ route('countries.show', $country->id) }}" class="link link-hover">
                                                    {{ $country->name }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="badge badge-primary">{{ $country->cca2 }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <p>No countries found for this continent.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">
            <p>No continents or countries data available.</p>
        </div>
    @endforelse
@endsection
