@extends('panel.layout')

@section('content')
<div class="pf-grid">
    <div class="pf-card">
        <span class="pf-label">Bot status</span>
        <div class="pf-big {{ $bot['online'] ? 'pf-lime' : 'pf-red' }}">{{ $bot['online'] ? 'ONLINE' : ($bot['desired'] ? 'STARTING' : 'OFFLINE') }}</div>
        <span class="pf-sub">{{ $bot['ign'] ?? 'no account configured' }} · {{ $bot['host'] ?? 'no server' }}</span>
        <form method="POST" action="{{ route('panel.bot.toggle') }}" style="margin-top:10px">
            @csrf
            <button class="pf-btn {{ $bot['desired'] ? 'pf-btn_danger' : '' }}" type="submit">
                {{ $bot['desired'] ? 'Turn bot OFF' : 'Turn bot ON' }}
            </button>
        </form>
        <span class="pf-sub">The bridge applies the switch on its next heartbeat (about 30s) and reconnects by itself when it is turned back on.</span>
    </div>

    <div class="pf-card">
        <span class="pf-label">Held by players</span>
        <div class="pf-value">{{ number_format($totals['held']) }}</div>
        <span class="pf-sub">sum of every player balance</span>
    </div>

    <div class="pf-card">
        <span class="pf-label">Distributed by the platform</span>
        <div class="pf-value pf-lime">{{ number_format($totals['distributed']) }}</div>
        <span class="pf-sub">credits in the ledger</span>
    </div>

    <div class="pf-card">
        <span class="pf-label">Removed</span>
        <div class="pf-value pf-red">{{ number_format($totals['removed']) }}</div>
        <span class="pf-sub">bet losses and staff removals</span>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">In-game float</h2><span class="pf-sub">from the Minecraft bridge</span></div>
        <div class="pf-row"><span class="pf-dim">Available for payouts</span><span class="pf-mono">${{ number_format($float['available'] ?? 0) }}</span></div>
        <div class="pf-row"><span class="pf-dim">Deposited in game</span><span class="pf-mono">${{ number_format($float['deposited'] ?? 0) }}</span></div>
        <div class="pf-row"><span class="pf-dim">Paid out</span><span class="pf-mono">${{ number_format($float['paid'] ?? 0) }}</span></div>
        <div class="pf-row"><span class="pf-dim">Queued</span><span class="pf-mono">${{ number_format($float['queued'] ?? 0) }}</span></div>
        <a class="pf-btn pf-btn_sm pf-btn_ghost" style="margin-top:10px;align-self:flex-start" href="/admin/minecraft">Open wallet panel</a>
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Where the points went</h2></div>
        <div class="pf-list">
            @forelse($rows as $row)
                <div class="pf-row">
                    <span><span class="pf-dim">{{ $row->type }}</span> {{ $row->username ?? 'system' }}</span>
                    <span class="pf-mono {{ $row->amount >= 0 ? 'pf-lime' : 'pf-red' }}">{{ $row->amount >= 0 ? '+' : '−' }}{{ number_format(abs($row->amount)) }}</span>
                </div>
            @empty
                <p class="pf-sub">No payouts recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
