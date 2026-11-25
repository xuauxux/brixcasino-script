@extends('panel.layout')

@section('content')
<div class="pf-grid">
    <div class="pf-card">
        <span class="pf-label">Status</span>
        <div class="pf-big {{ $status['active'] ? 'pf-lime' : 'pf-dim' }}">{{ $status['active'] ? 'RUNNING' : 'ENDED' }}</div>
        <span class="pf-sub">{{ $status['ends_at'] ? 'ends '.$status['ends_at']->diffForHumans() : 'no window set' }}</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Time elapsed</span>
        <div class="pf-value">{{ $status['progress'] }}%</div>
        <div class="pf-bar" style="margin-top:6px"><span style="width: {{ $status['progress'] }}%"></span></div>
        <span class="pf-sub">{{ $status['starts_at'] }} → {{ $status['ends_at'] }}</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Players with letters</span>
        <div class="pf-value">{{ number_format($collected) }}</div>
        <span class="pf-sub">of {{ number_format(\App\Models\User::count()) }} accounts</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Completed</span>
        <div class="pf-value pf-lime">{{ number_format($completed) }}</div>
        <span class="pf-sub">each paid {{ number_format(\App\Services\Platform\DonutEvent::REWARD) }}</span>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Restart the 14-day window</h2></div>
        <p class="pf-sub">This sets the start to now and the end to now + N days. Players keep the letters they already own.</p>
        <form class="pf-form" method="POST" action="{{ route('panel.event') }}" style="margin-top:10px">
            @csrf
            <div class="pf-field">
                <label for="days">Days</label>
                <input class="pf-input" id="days" name="days" type="number" min="1" max="60" value="14">
            </div>
            <button class="pf-btn" type="submit">Start event</button>
        </form>
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Event leaderboard</h2></div>
        <div class="pf-list">
            @forelse($board as $row)
                <div class="pf-row">
                    <span><span class="pf-rank pf-rank_{{ $row['rank'] }}">{{ $row['rank'] }}</span> {{ $row['username'] }}</span>
                    <span class="pf-mono">{{ $row['letters'] }}/9</span>
                </div>
            @empty
                <p class="pf-sub">Nobody has collected a letter yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
