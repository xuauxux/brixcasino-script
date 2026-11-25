<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Owner panel' }} · DonutTrix</title>
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/css/platform.css">
</head>
<body class="pf">
@php
    $me = auth()->user();
    $isOwner = ($me->role ?? 'user') === 'owner';
    $nav = [
        ['panel', 'Overview', 'overview', false],
        ['panel.users', 'Users', 'users', false],
        ['panel.transactions', 'Balance history', 'transactions', false],
        ['panel.games', 'Games', 'games', false],
        ['panel.rain', 'Rain', 'rain', true],
        ['panel.bot', 'Bot balance', 'bot', true],
        ['panel.maintenance', 'Maintenance', 'maintenance', true],
        ['panel.event', 'DONUTTRIX event', 'event', true],
        ['panel.settings', 'Settings', 'settings', true],
    ];
@endphp
<div class="pf-shell">
    <aside class="pf-side">
        <div class="pf-brand">
            <div>DONUT<span>TRIX</span><small>{{ $isOwner ? 'Owner panel' : 'Admin panel' }}</small></div>
        </div>

        <nav class="pf-nav">
            @foreach($nav as [$route, $label, $key, $ownerOnly])
                @continue($ownerOnly && ! $isOwner)
                <a href="{{ route($route) }}" class="{{ ($tab ?? '') === $key ? 'is-active' : '' }}">
                    <span>{{ $label }}</span>
                    @if($ownerOnly)<span class="pf-owner-tag">owner</span>@endif
                </a>
            @endforeach
            <a href="/admin" class="">Legacy admin</a>
            <a href="/">Back to site</a>
        </nav>

        <div class="pf-side_foot">
            <div>{{ $me->username ?? '' }}</div>
            <div>{{ strtoupper($me->role ?? 'user') }}</div>
            <a href="/logout" class="pf-btn pf-btn_ghost pf-btn_sm">Sign out</a>
        </div>
    </aside>

    <main class="pf-main">
        <div class="pf-topbar">
            <div>
                <h1 class="pf-title">{{ $title ?? 'Owner panel' }}</h1>
                @isset($lead)<p class="pf-subtitle">{{ $lead }}</p>@endisset
            </div>
            <div class="pf-chips" id="pf-chips">
                <span class="pf-chip" id="chip-bot"><i></i> bot</span>
                <span class="pf-chip" id="chip-rain"><i></i> rain</span>
                <span class="pf-chip" id="chip-maintenance"><i></i> maintenance</span>
            </div>
        </div>

        @if(session('success'))
            <div class="pf-flash pf-flash_ok">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="pf-flash pf-flash_err">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    (() => {
        const chips = {
            bot: document.getElementById('chip-bot'),
            rain: document.getElementById('chip-rain'),
            maintenance: document.getElementById('chip-maintenance'),
        };

        const paint = (state) => {
            if (!chips.bot) return;

            chips.bot.className = 'pf-chip ' + (state.bot.online ? 'is-on' : (state.bot.desired ? 'is-warn' : 'is-off'));
            chips.bot.innerHTML = '<i></i> bot ' + (state.bot.online ? 'online' : (state.bot.desired ? 'starting' : 'off'));

            chips.rain.className = 'pf-chip ' + (state.rain ? 'is-on' : '');
            chips.rain.innerHTML = '<i></i> ' + (state.rain ? 'rain live' : 'no rain');

            chips.maintenance.className = 'pf-chip ' + (state.maintenance ? 'is-warn' : '');
            chips.maintenance.innerHTML = '<i></i> ' + (state.maintenance ? 'maintenance on' : 'site live');
        };

        const poll = async () => {
            try {
                const res = await fetch('/panel/status', { headers: { accept: 'application/json' } });
                if (res.ok) paint(await res.json());
            } catch (e) { /* ignore */ }
        };

        poll();
        setInterval(poll, 10000);
    })();
</script>
</body>
</html>
