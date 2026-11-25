@extends('layout')

@section('content')
<div class="pf-dash">
    <section class="pf-card pf-hero">
        <div>
            <h1 class="pf-hero_name">Referral program</h1>
            <p class="pf-sub" style="margin-top:4px">
                Earn {{ number_format(20000000) }} points for every friend who verifies their Minecraft name and reaches level 2.
                Accounts sharing your IP, or alt accounts, never qualify.
            </p>
        </div>
        <div class="pf-hero_balance">
            <span class="pf-label">Earned</span>
            <div class="pf-big">{{ number_format($stats['earned']) }} <small>$</small></div>
        </div>
    </section>

    <div class="pf-grid">
        <div class="pf-card">
            <span class="pf-label">Invited</span>
            <div class="pf-value">{{ number_format($stats['total']) }}</div>
            <span class="pf-sub">signed up with your link</span>
        </div>
        <div class="pf-card">
            <span class="pf-label">Paid referrals</span>
            <div class="pf-value pf-lime">{{ number_format($stats['qualified']) }}</div>
            <span class="pf-sub">verified + level 2+</span>
        </div>
        <div class="pf-card">
            <span class="pf-label">Your code</span>
            <div class="pf-value pf-mono" style="font-size:18px">{{ $stats['code'] }}</div>
            <span class="pf-sub">one per account, permanent</span>
        </div>
    </div>

    <div class="pf-dash_grid">
        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Your link</h2></div>
            <div class="pf-field">
                <input class="pf-input" id="refLink" value="{{ $stats['link'] }}" readonly onclick="this.select()">
            </div>
            <button class="pf-btn pf-btn_sm" style="align-self:flex-start;margin-top:8px" type="button" data-copy>Copy link</button>
            <p class="pf-sub" style="margin-top:10px">
                A referral pays out once, after the new player links their Minecraft name and reaches level 2.
                Self-referrals, same-IP signups and duplicate accounts are rejected automatically.
            </p>
        </section>

        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Referral leaderboard</h2><a class="pf-sub" href="{{ route('leaderboards') }}">All boards →</a></div>
            <div class="pf-board">
                @forelse($board as $row)
                    <div class="pf-board_row {{ $row['username'] === Auth::user()->username ? 'is-me' : '' }}">
                        <span class="pf-rank pf-rank_{{ min(3, $row['rank']) }}">{{ $row['rank'] }}</span>
                        <span>{{ $row['username'] }}</span>
                        <span class="pf-mono">{{ $row['referrals'] }}</span>
                        <span class="pf-sub">invites</span>
                    </div>
                @empty
                    <p class="pf-sub">No referrals yet — be the first.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Referral history</h2></div>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead><tr><th>Player</th><th>Status</th><th>Reward</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($stats['history'] as $row)
                    <tr>
                        <td>{{ $row->username ?? 'deleted account' }}</td>
                        <td><span class="pf-chip is-on"><i></i>paid</span></td>
                        <td class="pf-mono pf-lime">{{ number_format($row->amount) }}</td>
                        <td class="pf-dim">{{ $row->created_at }}</td>
                    </tr>
                @empty
                    @foreach($historyRows as $row)
                        <tr>
                            <td>{{ $row->username }}</td>
                            <td><span class="pf-chip"><i></i>pending</span></td>
                            <td class="pf-mono pf-dim">—</td>
                            <td class="pf-dim">{{ $row->created_at }}</td>
                        </tr>
                    @endforeach
                @endforelse
                @if(empty($stats['history']) && $historyRows->isEmpty())
                    <tr><td colspan="4" class="pf-empty">No referrals yet. Share your link to get started.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    document.querySelector('[data-copy]')?.addEventListener('click', async (e) => {
        const input = document.getElementById('refLink');
        try {
            await navigator.clipboard.writeText(input.value);
            e.target.textContent = 'Copied';
            setTimeout(() => (e.target.textContent = 'Copy link'), 1500);
        } catch {
            input.select();
        }
    });
</script>
@endsection
