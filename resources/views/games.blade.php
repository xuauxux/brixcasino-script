@extends('layout')

@section('content')
<div class="pf-dash">
    <section class="pf-card pf-hero">
        <div class="pf-hero_who">
            <div>
                <h1 class="pf-hero_name">All games</h1>
                <p class="pf-sub" style="margin-top:4px">Every game on DonutTrix. Unavailable ones are greyed out until staff re-enable them.</p>
            </div>
        </div>
        <div class="pf-hero_balance">
            <span class="pf-label">Available now</span>
            <div class="pf-big">{{ collect($games)->where('enabled', true)->count() }}/{{ count($games) }}</div>
        </div>
    </section>

    <div class="pf-catalogue">
        @foreach($games as $slug => $game)
            <a class="pf-card pf-game {{ $game['enabled'] ? '' : 'is-off' }}"
               href="{{ $game['enabled'] ? '/'.$slug : '#' }}"
               @if(! $game['enabled']) onclick="return false" @endif>
                <div class="pf-game_art" style="background-image:url('/assets/images/games/{{ $game['image'] }}')"></div>
                <div class="pf-game_body">
                    <div class="pf-row">
                        <strong>{{ $game['name'] }}</strong>
                        <span class="pf-chip {{ $game['enabled'] ? 'is-on' : 'is-off' }}"><i></i>{{ $game['enabled'] ? 'available' : 'disabled' }}</span>
                    </div>
                    <span class="pf-sub">{{ $game['description'] }}</span>
                    <span class="pf-sub" style="color:var(--pf-lime);font-weight:700">{{ $game['tag'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
