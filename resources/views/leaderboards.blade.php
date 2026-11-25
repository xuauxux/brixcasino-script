@extends('layout')

@section('content')
@php
    $me = auth()->id();
    $labels = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];

    $lead = null;
    $leadPeriod = null;

    foreach ($labels as $periodKey => $periodLabel) {
        if (! empty($boards[$periodKey])) {
            $lead = $boards[$periodKey][0];
            $leadPeriod = $periodLabel;
            break;
        }
    }
@endphp

<div class="lb">
    <section class="lb-hero">
        <div class="lb-hero_body">
            <span class="lb-eyebrow"><i class='bx bxs-trophy'></i> Activity races</span>
            <h1 class="lb-title">Leaderboards</h1>
            <p class="lb-text">Play any game to earn activity points and climb the daily, weekly or monthly board.</p>

            <div class="lb-ranks">
                @foreach($labels as $period => $label)
                    <span class="lb-rank_chip {{ ($positions[$period] ?? null) ? 'is-ranked' : '' }}">
                        <span class="lb-rank_chip_label">{{ $label }}</span>
                        <strong>{{ ($positions[$period] ?? null) ? '#'.$positions[$period]['rank'] : '—' }}</strong>
                    </span>
                @endforeach
            </div>

            @guest
            <button type="button" class="lb-btn lb-btn_lime lb-hero_cta" data-auth>
                <i class='bx bx-log-in'></i> Sign in to compete
            </button>
            @endguest
        </div>

        @if($lead)
        <div class="lb-hero_lead">
            <span class="lb-hero_lead_label"><i class='bx bxs-crown'></i> Leading now</span>
            <div class="lb-hero_lead_who">
                <span class="lb-avatar">{{ strtoupper(substr($lead['username'], 0, 1)) }}</span>
                <div>
                    <strong>{{ $lead['username'] }}</strong>
                    <span>{{ number_format($lead['score']) }} pts on the {{ $leadPeriod }} board</span>
                </div>
            </div>
        </div>
        @endif
    </section>

    <div class="lb-tabs">
        @foreach($labels as $period => $label)
            <button class="lb-tab {{ $loop->first ? 'is-active' : '' }}" data-tab="{{ $period }}">{{ $label }}</button>
        @endforeach
        <button class="lb-tab" data-tab="referral">Referrals</button>
    </div>

    @foreach($labels as $period => $label)
        <section class="lb-panel" data-panel="{{ $period }}" @if(! $loop->first) style="display:none" @endif>
            <div class="lb-panel_head">
                <div>
                    <h2 class="lb-panel_title">{{ $label }} activity</h2>
                    <p class="lb-panel_sub">{{ count($boards[$period]) }} {{ \Illuminate\Support\Str::plural('player', count($boards[$period])) }} on the board</p>
                </div>
                <span class="lb-time"><i class='bx bx-time-five'></i> ends {{ $endsAt[$period]->diffForHumans() }}</span>
            </div>

            @if(count($boards[$period]))
                <div class="lb-table lb-table_3">
                    <div class="lb-table_head">
                        <span>Rank</span>
                        <span>Player</span>
                        <span>Points</span>
                    </div>

                    @foreach($boards[$period] as $row)
                        <div class="lb-row {{ $row['rank'] <= 3 ? 'is-top' : '' }} {{ (int) ($row['user_id'] ?? 0) === (int) $me ? 'is-me' : '' }}">
                            <span class="lb-rank lb-rank_{{ $row['rank'] }}">{{ $row['rank'] }}</span>
                            <span class="lb-player">
                                <span class="lb-avatar">{{ strtoupper(substr($row['username'], 0, 1)) }}</span>
                                <span class="lb-player_name">
                                    {{ $row['username'] }}
                                    @if((int) ($row['user_id'] ?? 0) === (int) $me)<span class="lb-you">you</span>@endif
                                </span>
                            </span>
                            <span class="lb-score">{{ number_format($row['score']) }} <small>pts</small></span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="lb-empty">
                    <span class="lb-empty_icon"><i class='bx bxs-trophy'></i></span>
                    <strong>No activity yet</strong>
                    <p>Play a round and your name lands at the top of this board.</p>
                    @auth
                    <a href="/games" class="lb-btn lb-btn_lime">Play games</a>
                    @else
                    <button type="button" class="lb-btn lb-btn_lime" data-auth>Sign in to play</button>
                    @endauth
                </div>
            @endif
        </section>
    @endforeach

    <section class="lb-panel" data-panel="referral" style="display:none">
        <div class="lb-panel_head">
            <div>
                <h2 class="lb-panel_title">Referral leaderboard</h2>
                <p class="lb-panel_sub">{{ count($referralBoard) }} {{ \Illuminate\Support\Str::plural('player', count($referralBoard)) }} · verified invites only</p>
            </div>
            <span class="lb-time"><i class='bx bx-link-external'></i> invites only</span>
        </div>

        @if(count($referralBoard))
            <div class="lb-table lb-table_3">
                <div class="lb-table_head">
                    <span>Rank</span>
                    <span>Player</span>
                    <span>Invites</span>
                </div>
                @foreach($referralBoard as $row)
                    <div class="lb-row {{ (int) $row['user_id'] === (int) $me ? 'is-me' : '' }}">
                        <span class="lb-rank lb-rank_{{ $row['rank'] }}">{{ $row['rank'] }}</span>
                        <span class="lb-player">
                            <span class="lb-avatar">{{ strtoupper(substr($row['username'], 0, 1)) }}</span>
                            <span class="lb-player_name">
                                {{ $row['username'] }}
                                @if((int) $row['user_id'] === (int) $me)<span class="lb-you">you</span>@endif
                            </span>
                        </span>
                        <span class="lb-score">{{ $row['referrals'] }} <small>invites</small></span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="lb-empty">
                <span class="lb-empty_icon"><i class='bx bx-user-plus'></i></span>
                <strong>No referrals yet</strong>
                <p>Share your affiliate link and every verified friend you bring can put you on this board.</p>
                @auth
                <a href="/referrals" class="lb-btn lb-btn_lime">Open affiliates</a>
                @else
                <button type="button" class="lb-btn lb-btn_lime" data-auth>Sign in to invite</button>
                @endauth
            </div>
        @endif
    </section>

    <section class="lb-how">
        <div class="lb-how_item">
            <span class="lb-how_num">1</span>
            <strong>Play any game</strong>
            <p>Every bet on dice, mines, plinko or any other round adds activity points.</p>
        </div>
        <div class="lb-how_item">
            <span class="lb-how_num">2</span>
            <strong>Climb the board</strong>
            <p>The most active players rise to the top of each board.</p>
        </div>
        <div class="lb-how_item">
            <span class="lb-how_num">3</span>
            <strong>Come back daily</strong>
            <p>Daily, weekly and monthly boards reset on their own and start fresh.</p>
        </div>
    </section>
</div>

<script>
    (() => {
        const tabs = document.querySelectorAll('[data-tab]');
        const panels = document.querySelectorAll('[data-panel]');

        tabs.forEach((tab) => tab.addEventListener('click', () => {
            tabs.forEach((t) => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            panels.forEach((panel) => {
                panel.style.display = panel.dataset.panel === tab.dataset.tab ? '' : 'none';
            });
        }));

        document.querySelectorAll('[data-auth]').forEach((el) => el.addEventListener('click', (e) => {
            e.preventDefault();
            const trigger = document.querySelector('.auth__btn');
            if (trigger) trigger.click();
        }));
    })();
</script>
@endsection
