@extends('panel.layout')

@section('content')
<div class="pf-grid">
    <div class="pf-card">
        <span class="pf-label">Players</span>
        <div class="pf-big">{{ number_format($stats['users']) }}</div>
        <span class="pf-sub">+{{ number_format($stats['new_today']) }} today · {{ number_format($stats['online']) }} active now</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Held by players</span>
        <div class="pf-value">{{ number_format($stats['held']) }}</div>
        <span class="pf-sub">points across every balance</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Distributed</span>
        <div class="pf-value pf-lime">{{ number_format($stats['distributed']) }}</div>
        <span class="pf-sub">{{ number_format($stats['removed']) }} removed by staff</span>
    </div>
    <div class="pf-card">
        <span class="pf-label">Next rain</span>
        <div class="pf-value">{{ $rain ? 'live now' : ($stats['next_rain'] ? $stats['next_rain']->diffForHumans() : 'paused') }}</div>
        <span class="pf-sub">{{ $rain ? number_format($rain->amount).' pot · '.$rain->participants.' joined' : 'automatic on the configured interval' }}</span>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">Bot</h2>
            <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ route('panel.bot') }}">Manage</a>
        </div>
        <div class="pf-row">
            <span class="pf-dim">State</span>
            <span class="pf-chip {{ $bot['online'] ? 'is-on' : ($bot['desired'] ? 'is-warn' : 'is-off') }}"><i></i>{{ $bot['online'] ? 'online' : ($bot['desired'] ? 'waiting for bridge' : 'switched off') }}</span>
        </div>
        <div class="pf-row"><span class="pf-dim">Account</span><span class="pf-mono">{{ $bot['ign'] ?? '—' }}</span></div>
        <div class="pf-row"><span class="pf-dim">Server</span><span class="pf-mono">{{ $bot['host'] ?? '—' }}</span></div>
        <div class="pf-row"><span class="pf-dim">Players visible</span><span class="pf-mono">{{ $bot['players'] ?? '—' }}</span></div>
    </div>

    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">Daily leaderboard</h2>
            <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ route('panel.transactions') }}">Ledger</a>
        </div>
        <div class="pf-list">
            @forelse($topDaily as $row)
                <div class="pf-row">
                    <span><span class="pf-rank pf-rank_{{ $row['rank'] }}">{{ $row['rank'] }}</span> {{ $row['username'] }}</span>
                    <span class="pf-mono pf-lime">{{ number_format($row['score']) }}</span>
                </div>
            @empty
                <p class="pf-sub">No activity recorded yet today.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Latest balance changes</h2></div>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead><tr><th>Player</th><th>Type</th><th>Amount</th><th>By</th></tr></thead>
                <tbody>
                @forelse($recent as $row)
                    <tr>
                        <td><a href="{{ url('/panel/users/'.$row->user_id) }}">{{ $row->username ?? '#'.$row->user_id }}</a></td>
                        <td class="pf-dim">{{ $row->type }}</td>
                        <td class="{{ $row->amount >= 0 ? 'pf-lime' : 'pf-red' }} pf-mono">{{ $row->amount >= 0 ? '+' : '−' }}{{ number_format(abs($row->amount)) }}</td>
                        <td class="pf-dim">{{ $row->admin_name ?? 'system' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="pf-empty">No balance changes yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Admin actions</h2></div>
        <div class="pf-list">
            @forelse($logs as $log)
                <div class="pf-row">
                    <span class="pf-sub">{{ $log->details ?: $log->action }}</span>
                    <span class="pf-dim" style="font-size:11px">{{ \Illuminate\Support\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                </div>
            @empty
                <p class="pf-sub">Nothing logged yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
