@extends('admin')

@section('content')

<div class="stats">
    <div class="stats__head">
        <div>
            <h1 class="stats__title">Statistics</h1>
            <p class="stats__lead">Wallet health at a glance — deposits in, payouts out, and the players behind them.</p>
        </div>
        <div class="stats__head_side">
            <span class="stats__pill"><i class="stats__dot"></i>{{ now()->format('H:i') }} local time</span>
            <a href="/admin/minecraft" class="stats__link">DonutSMP wallet →</a>
        </div>
    </div>

    <div class="stats__grid">
        <div class="stats__card stats__card_hero">
            <span class="stats__label">Players' total balance</span>
            <div class="stats__big">${{ number_format($users_balance, 2) }}</div>
            <span class="stats__sub">{{ number_format($users) }} accounts · <em class="stats__up">+{{ number_format($new_users) }} today</em></span>
        </div>

        <div class="stats__card">
            <span class="stats__label">Deposits · today</span>
            <div class="stats__value">${{ number_format($pay_today, 2) }}</div>
            <span class="stats__sub">{{ number_format($pay_week, 2) }} this week · {{ number_format($pay_all, 2) }} all time</span>
        </div>

        <div class="stats__card">
            <span class="stats__label">Payouts · today</span>
            <div class="stats__value">${{ number_format($with_today, 2) }}</div>
            <span class="stats__sub">{{ number_format($with_week, 2) }} this week · {{ number_format($with_all, 2) }} all time</span>
        </div>

        <div class="stats__card">
            <span class="stats__label">Net flow · all time</span>
            <div class="stats__value {{ $net_all < 0 ? 'stats__value_neg' : 'stats__value_pos' }}">
                {{ $net_all < 0 ? '−' : '+' }}${{ number_format(abs($net_all), 2) }}
            </div>
            <span class="stats__sub">Deposits minus payouts</span>
        </div>

        <div class="stats__card">
            <span class="stats__label">Payout queue</span>
            <div class="stats__value">{{ number_format($pending_withdrawals) }}</div>
            <span class="stats__sub">Waiting to be sent in game</span>
        </div>

        <div class="stats__card">
            <span class="stats__label">Unmatched receipts</span>
            <div class="stats__value">{{ number_format($unmatched_receipts) }}</div>
            <span class="stats__sub">Paid in game, no player attached</span>
        </div>
    </div>

    <div class="stats__panel">
        <div class="stats__panel_head">
            <h2 class="stats__panel_title">Last 7 days</h2>
            <div class="stats__legend">
                <span class="stats__key stats__key_in">Deposits</span>
                <span class="stats__key stats__key_out">Payouts</span>
            </div>
        </div>

        @if($chart_total <= 0)
            <p class="stats__chart_empty">No approved deposits or payouts in the last 7 days yet — the chart fills in as players deposit.</p>
        @else
        <div class="stats__chart">
            @foreach($days as $day)
                <div class="stats__col" title="{{ $day['date'] }} · in ${{ number_format($day['deposits'], 2) }} / out ${{ number_format($day['withdrawals'], 2) }}">
                    <div class="stats__bars">
                        <span class="stats__bar stats__bar_in" style="height: {{ max(3, round($day['deposits'] / $chart_max * 100)) }}%"></span>
                        <span class="stats__bar stats__bar_out" style="height: {{ max(3, round($day['withdrawals'] / $chart_max * 100)) }}%"></span>
                    </div>
                    <span class="stats__day">{{ $day['label'] }}</span>
                    <span class="stats__day_num">{{ $day['date'] }}</span>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="stats__tables">
        <div class="stats__panel">
            <div class="stats__panel_head">
                <h2 class="stats__panel_title">Recent deposits</h2>
                <span class="stats__panel_note">Last {{ $last_pay->count() }} approved</span>
            </div>
            <div class="stats__scroll">
                <table class="stats__table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th class="tb-center">Amount</th>
                            <th class="tb-right">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($last_pay as $pay)
                            <tr>
                                @php($who = $recent_users->get($pay->user_id))
                                <td>
                                    <a href="/admin/user/edit/{{ $pay->user_id }}">{{ $who->username ?? '#'.$pay->user_id }}</a>
                                    @if($who && $who->minecraft_ign && strtolower($who->minecraft_ign) !== strtolower($who->username))
                                        <span class="stats__ign">{{ $who->minecraft_ign }}</span>
                                    @endif
                                </td>
                                <td class="tb-center stats__in">+${{ number_format($pay->amount, 2) }}</td>
                                <td class="tb-right">{{ $pay->created_at->format('H:i · d.m.Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="stats__empty">No deposits yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stats__panel">
            <div class="stats__panel_head">
                <h2 class="stats__panel_title">Recent payouts</h2>
                <span class="stats__panel_note">Last {{ $last_withdraws->count() }} approved</span>
            </div>
            <div class="stats__scroll">
                <table class="stats__table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th class="tb-center">Amount</th>
                            <th class="tb-right">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($last_withdraws as $with)
                            <tr>
                                @php($who = $recent_users->get($with->user_id))
                                <td>
                                    <a href="/admin/user/edit/{{ $with->user_id }}">{{ $who->username ?? '#'.$with->user_id }}</a>
                                    @if($who && $who->minecraft_ign && strtolower($who->minecraft_ign) !== strtolower($who->username))
                                        <span class="stats__ign">{{ $who->minecraft_ign }}</span>
                                    @endif
                                </td>
                                <td class="tb-center stats__out">−${{ number_format($with->amount, 2) }}</td>
                                <td class="tb-right">{{ $with->created_at->format('H:i · d.m.Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="stats__empty">No payouts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
