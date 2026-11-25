@extends('layout')

@section('content')
@php
    $live = $rain !== null;
    $pot = $live ? (float) $rain->amount : (float) $settings['amount'];
    $people = $live ? (int) $rain->participants : 0;
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;
@endphp

<div class="pf-dash">
    <section class="pf-card pf-event">
        <span class="pf-tag {{ $live ? 'pf-tag_ok' : '' }}">{{ $live ? 'Rain live' : 'Next drop soon' }}</span>
        <h1 class="pf-hero_name" style="font-size:30px">{{ $live ? 'Money rain is falling' : 'Money rain' }}</h1>
        <p class="pf-sub" style="max-width:540px">
            @if($live)
                {{ number_format($pot) }} points split between everyone who joins before the countdown ends. Every player takes an equal share.
            @else
                A rain drops every {{ $settings['interval'] }} minutes. Join while it is live and your share is paid out automatically when the countdown ends.
            @endif
        </p>

        <span class="pf-hero_name" style="font-size:46px;font-weight:900;letter-spacing:-.03em;font-variant-numeric:tabular-nums;{{ $live ? 'color:var(--pf-lime)' : '' }}" data-rain-pot>
            {{ number_format($pot) }}
        </span>

        @if($live)
        <div class="pf-countdown" data-countdown data-seconds="{{ $seconds }}">
            <span><b data-d>{{ $days }}</b><small>days</small></span>
            <span><b data-h>{{ $hours }}</b><small>hours</small></span>
            <span><b data-m>{{ $minutes }}</b><small>mins</small></span>
            <span><b data-s>{{ $secs }}</b><small>secs</small></span>
        </div>
        @else
        <span class="pf-sub">The next drop starts on its own, so keep this page open or come back shortly.</span>
        @endif

        @if(session('error'))
            <span class="pf-sub" style="color:#ff9b9e">{{ session('error') }}</span>
        @endif
        @if(session('success'))
            <span class="pf-sub pf-lime">{{ session('success') }}</span>
        @endif

        @auth
            @if(! $live)
                <button class="pf-btn" type="button" disabled>Waiting for the drop</button>
            @elseif($joined)
                <button class="pf-btn pf-btn_ghost" type="button" disabled>You are in this rain</button>
            @else
                <form method="POST" action="{{ route('rain.join') }}">
                    @csrf
                    <button class="pf-btn" type="submit">Join the rain</button>
                </form>
            @endif
        @else
            <button class="pf-btn js-auth" type="button">Sign in to join</button>
        @endauth

        <span class="pf-sub" data-rain-people>{{ number_format($people) }} joined</span>
    </section>

    <div class="pf-dash_grid">
        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">How the rain works</h2></div>
            <div class="pf-list">
                <div class="pf-row"><span>Runs automatically</span><span class="pf-sub">every {{ $settings['interval'] }} minutes</span></div>
                <div class="pf-row"><span>Pot</span><span class="pf-mono pf-lime">{{ number_format($pot) }}</span></div>
                <div class="pf-row"><span>Split</span><span class="pf-sub">equally between everyone who joined</span></div>
                <div class="pf-row"><span>Joining</span><span class="pf-sub">once per rain, free</span></div>
                <div class="pf-row"><span>Join bonus</span><span class="pf-sub">25 activity points</span></div>
            </div>
        </section>

        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Recent rains</h2><a class="pf-sub" href="{{ route('leaderboards') }}">All boards</a></div>
            <div class="pf-board">
                @forelse($history as $row)
                    <div class="pf-row">
                        <span class="pf-mono">{{ number_format((float) $row->amount) }} pts</span>
                        <span class="pf-sub">
                            {{ $row->participants }} {{ \Illuminate\Support\Str::plural('player', $row->participants) }},
                            {{ strtolower($row->status) }}
                            {{ \Illuminate\Support\Carbon::parse($row->started_at ?? $row->created_at)->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <p class="pf-sub">No rain has dropped yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>

<script>
    (() => {
        const box = document.querySelector('[data-countdown]');
        let left = box ? Number(box.dataset.seconds || 0) : 0;

        if (box) {
            const d = box.querySelector('[data-d]');
            const h = box.querySelector('[data-h]');
            const m = box.querySelector('[data-m]');
            const s = box.querySelector('[data-s]');

            setInterval(() => {
                if (left <= 0) return;
                left--;
                d.textContent = Math.floor(left / 86400);
                h.textContent = Math.floor((left % 86400) / 3600);
                m.textContent = Math.floor((left % 3600) / 60);
                s.textContent = left % 60;
            }, 1000);
        }

        const pot = document.querySelector('[data-rain-pot]');
        const people = document.querySelector('[data-rain-people]');
        let wasLive = {{ $live ? 'true' : 'false' }};
        let endsAt = @json($live ? $rain->ends_at : null);

        const paint = (json) => {
            if (json.active !== wasLive || (json.ends_at || null) !== endsAt) {
                location.reload();
                return;
            }

            if (json.active) {
                if (pot) pot.textContent = Number(json.amount).toLocaleString('en-US');
                if (people) people.textContent = Number(json.participants).toLocaleString('en-US') + ' joined';
            }
        };

        setInterval(() => {
            fetch('/rain/status', { headers: { accept: 'application/json' } })
                .then((response) => response.ok ? response.json() : null)
                .then((json) => json && paint(json))
                .catch(() => {});
        }, 10000);
    })();
</script>
@endsection
