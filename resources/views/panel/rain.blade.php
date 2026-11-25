@extends('panel.layout')

@section('content')
<div class="pf-grid pf-grid_2">
    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">Rain status</h2>
            <span class="pf-chip {{ $active ? 'is-on' : 'is-off' }}"><i></i>{{ $active ? 'running' : 'idle' }}</span>
        </div>

        @if($active)
            <div class="pf-row"><span class="pf-dim">Pot</span><span class="pf-mono pf-lime">{{ number_format($active->amount) }}</span></div>
            <div class="pf-row"><span class="pf-dim">Joined</span><span class="pf-mono">{{ $active->participants }}</span></div>
            <div class="pf-row"><span class="pf-dim">Ends</span><span class="pf-mono">{{ $active->ends_at }}</span></div>
            <form method="POST" action="{{ route('panel.rain.stop') }}" style="margin-top:10px">
                @csrf
                <button class="pf-btn pf-btn_danger" type="submit">Stop now and pay out</button>
            </form>
        @else
            <p class="pf-sub">No rain running. {{ $next ? 'Next automatic rain '.$next->diffForHumans().'.' : 'Automatic rains are paused.' }}</p>
            <form class="pf-form" method="POST" action="{{ route('panel.rain.start') }}" style="margin-top:10px">
                @csrf
                <div class="pf-field">
                    <label for="amount">Pot</label>
                    <input class="pf-input" id="amount" name="amount" type="number" min="1" value="{{ (int) $settings['amount'] }}">
                </div>
                <div class="pf-field">
                    <label for="duration">Seconds</label>
                    <input class="pf-input" id="duration" name="duration" type="number" min="15" max="3600" value="{{ $settings['duration'] }}">
                </div>
                <button class="pf-btn" type="submit">Start rain</button>
            </form>
        @endif
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Configuration</h2></div>
        <form class="pf-form" method="POST" action="{{ route('panel.rain.settings') }}">
            @csrf
            <div class="pf-field">
                <label for="interval">Interval (minutes)</label>
                <input class="pf-input" id="interval" name="interval" type="number" min="1" max="1440" value="{{ $settings['interval'] }}">
            </div>
            <div class="pf-field">
                <label for="amount">Points per rain</label>
                <input class="pf-input" id="amount" name="amount" type="number" min="1" value="{{ (int) $settings['amount'] }}">
            </div>
            <div class="pf-field">
                <label for="duration">Length (seconds)</label>
                <input class="pf-input" id="duration" name="duration" type="number" min="15" max="3600" value="{{ $settings['duration'] }}">
            </div>
            <label class="pf-switch" style="flex:1 1 100%">
                <input type="checkbox" name="enabled" value="1" @checked($settings['enabled'])>
                Automatic rains enabled
            </label>
            <button class="pf-btn" type="submit">Save</button>
        </form>
    </div>
</div>

<div class="pf-card" style="margin-top:12px">
    <div class="pf-card_head"><h2 class="pf-h2">Recent rains</h2></div>
    <div class="pf-table-wrap">
        <table class="pf-table">
            <thead><tr><th>#</th><th>Pot</th><th>Status</th><th>Players</th><th>Paid out</th><th>Started</th></tr></thead>
            <tbody>
            @forelse($history as $row)
                <tr>
                    <td class="pf-dim pf-mono">{{ $row->id }}</td>
                    <td class="pf-mono">{{ number_format($row->amount) }}</td>
                    <td><span class="pf-chip {{ $row->status === 'running' ? 'is-on' : '' }}" style="padding:3px 8px;font-size:10px">{{ $row->status }}</span></td>
                    <td class="pf-mono">{{ $row->participants }}</td>
                    <td class="pf-mono">{{ number_format($row->paid_out) }}</td>
                    <td class="pf-dim">{{ $row->started_at }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="pf-empty">No rains yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
