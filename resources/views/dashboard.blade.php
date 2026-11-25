@extends('layout')

@section('content')
<div class="pf-dash">
    {{-- hero --}}
    <section class="pf-card pf-hero">
        <div class="pf-hero_who">
            <span class="pf-avatar">{{ strtoupper(substr($user->username, 0, 2)) }}</span>
            <div>
                <h1 class="pf-hero_name">{{ $user->username }}</h1>
                <div class="pf-hero_tags">
                    <span class="pf-tag">Level {{ $progress['level'] }}</span>
                    @if($user->minecraft_ign)
                        <span class="pf-tag pf-tag_ok">{{ $user->minecraft_ign }} verified</span>
                    @endif
                    @if(($user->role ?? 'user') !== 'user')
                        <span class="pf-tag pf-tag_owner">{{ strtoupper($user->role) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="pf-hero_balance">
            <span class="pf-label">Balance</span>
            <div class="pf-big">{{ number_format((float) $user->balance) }} <small>$</small></div>
            <div class="pf-actions">
                <a class="pf-btn pf-btn_sm" href="/wallet/pay">Deposit</a>
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="/wallet/withdraw">Withdraw</a>
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="/profile">Profile</a>
            </div>
        </div>
    </section>

    {{-- rain strip --}}
    <section class="pf-card pf-rain" data-rain>
        <div class="pf-rain_info">
            <span class="pf-label">Point rain</span>
            <strong class="pf-rain_state" data-rain-state>
                {{ $rain['active'] ? 'Live — '.number_format($rain['active']->amount).' split between everyone who joins' : 'Waiting for the next rain' }}
            </strong>
            <span class="pf-sub" data-rain-timer>
                {{ $rain['active'] ? 'Ends in '.max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($rain['active']->ends_at), false)).'s' : 'Rains start automatically every '.$rain['settings']['interval'].' minutes' }}
            </span>
        </div>
        @if($rain['active'])
            <form method="POST" action="{{ route('rain.join') }}">
                @csrf
                <button class="pf-btn" type="submit">Join the rain</button>
            </form>
        @else
            <span class="pf-chip"><i></i>{{ number_format($rain['settings']['amount']) }} pot</span>
        @endif
    </section>

    <div class="pf-dash_grid">
        {{-- level --}}
        <section class="pf-card">
            <div class="pf-card_head">
                <h2 class="pf-h2">Level {{ $progress['level'] }}</h2>
                <span class="pf-sub">{{ number_format($progress['xp']) }} XP</span>
            </div>
            <div class="pf-bar"><span style="width: {{ $progress['percent'] }}%"></span></div>
            <span class="pf-sub">{{ number_format($progress['into']) }} / {{ number_format($progress['needed']) }} XP to level {{ $progress['level'] + 1 }}</span>

            <div class="pf-list" style="margin-top:14px">
                @forelse($upcoming as $level => $reward)
                    <div class="pf-row">
                        <span class="pf-dim">Level {{ $level }}</span>
                        <span class="pf-mono pf-lime">{{ number_format($reward) }}</span>
                    </div>
                @empty
                    <p class="pf-sub">Every level reward is unlocked. Legend.</p>
                @endforelse
            </div>
        </section>

        {{-- referrals --}}
        <section class="pf-card">
            <div class="pf-card_head">
                <h2 class="pf-h2">Referrals</h2>
                <span class="pf-sub">{{ number_format($referrals['earned']) }} earned</span>
            </div>
            <div class="pf-row"><span class="pf-dim">Invited</span><span class="pf-mono">{{ $referrals['total'] }}</span></div>
            <div class="pf-row"><span class="pf-dim">Paid out</span><span class="pf-mono">{{ $referrals['qualified'] }}</span></div>
            <div class="pf-field" style="margin-top:10px">
                <label>Your link</label>
                <input class="pf-input" value="{{ $referrals['link'] }}" readonly onclick="this.select()">
            </div>
            <a class="pf-btn pf-btn_sm pf-btn_ghost" style="align-self:flex-start;margin-top:8px" href="/referrals">Referral page</a>
        </section>

        {{-- positions --}}
        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Your positions</h2><a class="pf-sub" href="{{ route('leaderboards') }}">All leaderboards →</a></div>
            <div class="pf-list">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $period => $label)
                    <div class="pf-row">
                        <span class="pf-dim">{{ $label }}</span>
                        <span class="pf-mono">
                            @if($positions[$period])
                                #{{ $positions[$period]['rank'] }} · {{ number_format($positions[$period]['score']) }}
                            @else
                                unranked
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- competitions --}}
    <section class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">Competitions</h2>
            <span class="pf-sub">paid automatically when each period closes</span>
        </div>
        <div class="pf-dash_grid pf-dash_grid_3">
            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $period => $label)
                <div class="pf-card pf-card_sub">
                    <div class="pf-row">
                        <strong>{{ $label }}</strong>
                        <span class="pf-sub">{{ $endsAt[$period]->diffForHumans() }}</span>
                    </div>
                    <div class="pf-list" style="margin-top:8px">
                        @forelse($boards[$period] as $row)
                            <div class="pf-row">
                                <span><span class="pf-rank pf-rank_{{ $row['rank'] }}">{{ $row['rank'] }}</span> {{ $row['username'] }}</span>
                                <span class="pf-mono pf-lime">{{ number_format($row['score']) }}</span>
                            </div>
                        @empty
                            <p class="pf-sub">No activity yet.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="pf-dash_grid">
        {{-- games --}}
        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Games</h2><a class="pf-sub" href="{{ route('games') }}">All games →</a></div>
            <div class="pf-quickgames">
                @foreach(array_slice($games, 0, 8) as $slug => $game)
                    <a class="pf-quickgame {{ $game['enabled'] ? '' : 'is-off' }}" href="{{ $game['enabled'] ? '/'.$slug : '#' }}" title="{{ $game['name'] }}">
                        <span class="pf-quickgame_art" style="background-image:url('/assets/images/games/{{ $game['image'] }}')"></span>
                        <span>{{ $game['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- announcements + activity --}}
        <section class="pf-card">
            <div class="pf-card_head"><h2 class="pf-h2">Announcements</h2></div>
            <div class="pf-list">
                @foreach($announcements as $note)
                    <div>
                        <div class="pf-h3">{{ $note['title'] }}</div>
                        <p class="pf-sub">{{ $note['body'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="pf-card_head" style="margin-top:16px"><h3 class="pf-h3">Recent activity</h3></div>
            <div class="pf-list">
                @forelse($activity as $row)
                    <div class="pf-row">
                        <span class="pf-dim">{{ $row->source }}</span>
                        <span class="pf-mono">{{ number_format($row->points) }} · {{ $row->xp }} XP</span>
                    </div>
                @empty
                    <p class="pf-sub">Play a round and your activity shows up here.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="pf-card pf-discord">
        <div>
            <h2 class="pf-h2">Join the Discord</h2>
            <p class="pf-sub">Giveaways, rain announcements, support and event updates.</p>
        </div>
        <a class="pf-btn" href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener">Open Discord</a>
    </section>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-rain]');
        if (!root) return;

        const state = root.querySelector('[data-rain-state]');
        const timer = root.querySelector('[data-rain-timer]');

        const tick = async () => {
            try {
                const res = await fetch('/rain/status', { headers: { accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                if (data.active) {
                    state.textContent = 'Live — ' + Number(data.amount).toLocaleString() + ' split between everyone who joins';
                    timer.textContent = 'Ends in ' + Math.max(0, data.seconds_left) + 's · ' + data.participants + ' joined';
                } else {
                    state.textContent = 'Waiting for the next rain';
                }
            } catch (e) { /* ignore */ }
        };

        tick();
        setInterval(tick, 5000);
    })();
</script>
@endsection
