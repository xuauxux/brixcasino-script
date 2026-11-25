@extends('panel.layout')

@section('content')
<div class="pf-grid">
    @foreach($games as $slug => $game)
        <div class="pf-card">
            <div class="pf-card_head">
                <h2 class="pf-h2">{{ $game['name'] }}</h2>
                <span class="pf-chip {{ $game['enabled'] ? 'is-on' : 'is-off' }}"><i></i>{{ $game['enabled'] ? 'live' : 'disabled' }}</span>
            </div>
            <p class="pf-sub">{{ $game['description'] }}</p>
            <div class="pf-actions" style="margin-top:10px">
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="/{{ $slug }}" target="_blank" rel="noopener">Open game</a>
                @if($game['setting'])
                    <form method="POST" action="{{ route('panel.games.toggle', $slug) }}">
                        @csrf
                        <button class="pf-btn pf-btn_sm {{ $game['enabled'] ? 'pf-btn_danger' : '' }}" type="submit">
                            {{ $game['enabled'] ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                @else
                    <span class="pf-sub" style="align-self:center">always available</span>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
