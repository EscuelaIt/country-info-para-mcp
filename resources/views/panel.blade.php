@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="card bg-base-200 shadow-xl">
        <div class="card-body gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="card-title text-2xl">Panel</h1>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Log out</button>
                </form>
            </div>

            <p>Welcome, {{ auth()->user()->name }}.</p>

            @if (session('status'))
                <div role="alert" class="alert alert-info">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <section class="flex flex-col gap-4">
                <h2 class="text-xl font-semibold">MCP bearer token</h2>

                @if ($mcpToken)
                    @if ($plainTextToken)
                        <div role="alert" class="alert alert-warning">
                            <span>Copy your token now. For security reasons it won't be shown again.</span>
                        </div>

                        <div class="join w-full">
                            <input
                                id="plain-text-token"
                                type="text"
                                value="{{ $plainTextToken }}"
                                readonly
                                class="input join-item w-full font-mono"
                                onclick="this.select()"
                            >
                            <button
                                type="button"
                                class="btn btn-primary join-item"
                                onclick="navigator.clipboard.writeText(document.getElementById('plain-text-token').value); this.textContent = 'Copied!'"
                            >
                                Copy
                            </button>
                        </div>
                    @endif

                    <div class="stats stats-vertical bg-base-100 shadow sm:stats-horizontal">
                        <div class="stat">
                            <div class="stat-title">Created</div>
                            <div class="stat-value text-lg">{{ $mcpToken->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="stat">
                            <div class="stat-title">Valid until</div>
                            <div class="stat-value text-lg">{{ $mcpToken->expires_at?->format('d/m/Y H:i') ?? 'Never expires' }}</div>
                            @if ($mcpToken->expires_at?->isPast())
                                <div class="stat-desc text-error">Expired</div>
                            @endif
                        </div>
                        <div class="stat">
                            <div class="stat-title">Last used</div>
                            <div class="stat-value text-lg">{{ $mcpToken->last_used_at?->format('d/m/Y H:i') ?? 'Never' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('panel.token.destroy') }}" onsubmit="return confirm('Delete this token? MCP clients using it will stop working.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error">Delete token</button>
                    </form>
                @else
                    <p>You don't have a token yet. Generate one to authenticate your MCP clients.</p>

                    <form method="POST" action="{{ route('panel.token.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Generate token</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
