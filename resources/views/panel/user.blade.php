@extends('panel.layout')

@php
    $isOwner = (auth()->user()->role ?? 'user') === 'owner';
    $banned = (int) $user->ban === 1;
@endphp

@section('content')
<div class="pf-grid pf-grid_2">
    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">{{ $user->username }}</h2>
            <span class="pf-chip {{ $banned ? 'is-off' : 'is-on' }}"><i></i>{{ $banned ? 'banned' : 'active' }}</span>
        </div>
        <div class="pf-row"><span class="pf-dim">Balance</span><span class="pf-mono pf-lime">{{ number_format((float) $user->balance) }}</span></div>
        <div class="pf-row"><span class="pf-dim">Level</span><span class="pf-mono">{{ $progress['level'] }} · {{ number_format($progress['xp']) }} XP</span></div>
        <div class="pf-row"><span class="pf-dim">IGN</span><span class="pf-mono">{{ $user->minecraft_ign ?: 'not linked' }}</span></div>
        <div class="pf-row"><span class="pf-dim">Role</span><span class="pf-mono">{{ strtoupper($user->role ?? 'user') }}</span></div>
        <div class="pf-row"><span class="pf-dim">Joined</span><span class="pf-mono">{{ $user->created_at }}</span></div>
        <div class="pf-row"><span class="pf-dim">Referrals</span><span class="pf-mono">{{ $referrals['total'] }} ({{ $referrals['qualified'] }} paid)</span></div>
        @if($banned && $user->ban_reason)
            <div class="pf-row"><span class="pf-dim">Ban reason</span><span class="pf-dim">{{ $user->ban_reason }}</span></div>
        @endif
        <div class="pf-bar" style="margin-top:10px"><span style="width: {{ $progress['percent'] }}%"></span></div>
        <span class="pf-sub">{{ $progress['into'] }} / {{ $progress['needed'] }} XP to level {{ $progress['level'] + 1 }}</span>
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Balance</h2><span class="pf-sub">every change is logged</span></div>
        <form class="pf-form" method="POST" action="{{ url('/panel/users/'.$user->id.'/balance') }}">
            @csrf
            <div class="pf-field">
                <label for="amount">Amount</label>
                <input class="pf-input" id="amount" name="amount" type="number" step="0.01" min="0.01" placeholder="1000000" required>
            </div>
            <div class="pf-field">
                <label for="mode">Action</label>
                <select class="pf-select" id="mode" name="mode">
                    <option value="add">Add points</option>
                    <option value="remove">Remove points</option>
                </select>
            </div>
            <div class="pf-field" style="flex:2 1 200px">
                <label for="reason">Reason</label>
                <input class="pf-input" id="reason" name="reason" maxlength="190" placeholder="Compensation, event prize…">
            </div>
            <button class="pf-btn" type="submit">Apply</button>
        </form>

        <div class="pf-actions" style="margin-top:14px">
            @if($banned)
                <form method="POST" action="{{ url('/panel/users/'.$user->id.'/unban') }}">
                    @csrf
                    <button class="pf-btn pf-btn_ghost pf-btn_sm" type="submit">Unban player</button>
                </form>
            @else
                <form method="POST" action="{{ url('/panel/users/'.$user->id.'/ban') }}" onsubmit="return confirm('Ban {{ $user->username }}?')">
                    @csrf
                    <input type="hidden" name="reason" value="Banned by staff">
                    <button class="pf-btn pf-btn_danger pf-btn_sm" type="submit">Ban player</button>
                </form>
            @endif

            @if($isOwner)
                <form method="POST" action="{{ url('/panel/users/'.$user->id.'/role') }}">
                    @csrf
                    <select class="pf-select" name="role" style="width:auto;display:inline-block" onchange="this.form.submit()">
                        @foreach(['user', 'admin', 'owner'] as $role)
                            <option value="{{ $role }}" @selected(($user->role ?? 'user') === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">DONUTTRIX letters</h2><span class="pf-sub">{{ $event['count'] }}/9</span></div>
        <div class="pf-letters">
            @foreach(str_split('DONUTTRIX') as $letter)
                <span class="pf-letter {{ in_array($letter, $event['letters'], true) ? 'is-have' : '' }}">{{ $letter }}</span>
            @endforeach
        </div>
        <span class="pf-sub">{{ $event['rewarded'] ? 'Completion reward already paid.' : ($event['complete'] ? 'Word complete — reward pending.' : 'Missing: '.implode(' ', $event['missing'])) }}</span>
    </div>

    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Recent activity</h2></div>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead><tr><th>Source</th><th>Points</th><th>XP</th><th>When</th></tr></thead>
                <tbody>
                @forelse($activity as $row)
                    <tr>
                        <td class="pf-dim">{{ $row->source }}</td>
                        <td class="pf-mono">{{ number_format($row->points) }}</td>
                        <td class="pf-mono">{{ number_format($row->xp) }}</td>
                        <td class="pf-dim">{{ \Illuminate\Support\Carbon::parse($row->created_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="pf-empty">No activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="pf-card" style="margin-top:12px">
    <div class="pf-card_head"><h2 class="pf-h2">Balance history</h2></div>
    <div class="pf-table-wrap">
        <table class="pf-table">
            <thead><tr><th>Type</th><th>Amount</th><th>Before</th><th>After</th><th>By</th><th>Reason</th><th>When</th></tr></thead>
            <tbody>
            @forelse($transactions as $row)
                <tr>
                    <td class="pf-dim">{{ $row->type }}</td>
                    <td class="{{ $row->amount >= 0 ? 'pf-lime' : 'pf-red' }} pf-mono">{{ $row->amount >= 0 ? '+' : '−' }}{{ number_format(abs($row->amount)) }}</td>
                    <td class="pf-mono pf-dim">{{ number_format($row->balance_before) }}</td>
                    <td class="pf-mono">{{ number_format($row->balance_after) }}</td>
                    <td class="pf-dim">{{ $row->admin_name ?? 'system' }}</td>
                    <td class="pf-dim">{{ $row->reason }}</td>
                    <td class="pf-dim">{{ $row->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="pf-empty">No balance changes for this player.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
