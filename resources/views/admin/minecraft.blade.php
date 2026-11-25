@extends('admin')

@section('content')
<div class="mcadmin">
    <div class="mcadmin__head">
        <div>
            <h1 class="mcadmin__title">DonutSMP wallet</h1>
            <p class="mcadmin__lead">Every in-game deposit and payout, plus the money the bot holds for payouts.</p>
        </div>
        <div class="mcadmin__live">
            <span class="mcadmin__dot"></span>
            Live · <span id="js-updated">—</span>
        </div>
    </div>

    <div class="mcadmin__grid">
        <div class="mcadmin__card mcadmin__card_wide">
            <span class="mcadmin__label">Bot float — available for payouts</span>
            <div class="mcadmin__big" id="js-available">{{ number_format($summary['available']) }}</div>
            <span class="mcadmin__sub">
                ≈ <span id="js-coins">{{ number_format($summary['coins_available'], 2) }}</span> $
                at {{ number_format($summary['coins_per_million']) }} $ per 1,000,000 in game
            </span>
        </div>

        <div class="mcadmin__card">
            <span class="mcadmin__label">Deposited in game</span>
            <div class="mcadmin__value" id="js-deposited">{{ number_format($summary['deposited']) }}</div>
            <span class="mcadmin__sub"><span id="js-deposits-count">{{ number_format($summary['completed_deposits']) }}</span> credited deposits</span>
        </div>

        <div class="mcadmin__card">
            <span class="mcadmin__label">Paid out</span>
            <div class="mcadmin__value" id="js-paid">{{ number_format($summary['paid']) }}</div>
            <span class="mcadmin__sub"><span id="js-payouts-count">{{ number_format($summary['payouts_paid']) }}</span> payouts</span>
        </div>

        <div class="mcadmin__card">
            <span class="mcadmin__label">In the payout queue</span>
            <div class="mcadmin__value" id="js-queued">{{ number_format($summary['queued']) }}</div>
            <span class="mcadmin__sub">
                <span id="js-pending-w">{{ $summary['pending_withdrawals'] }}</span> pending ·
                <span id="js-sending">{{ $summary['sending'] }}</span> sending ·
                <span id="js-unconfirmed">{{ $summary['unconfirmed'] }}</span> unconfirmed
            </span>
        </div>

        <div class="mcadmin__card">
            <span class="mcadmin__label">Bot</span>
            <div class="mcadmin__value mcadmin__value_small" id="js-bot">{{ $bridge['bot'] ?? '—' }}</div>
            <span class="mcadmin__sub">
                <span id="js-bridge" class="mcadmin__chip mcadmin__chip_{{ $bridge['online'] ? 'online' : 'offline' }}">{{ $bridge['online'] ? 'online' : 'offline' }}</span>
                <span id="js-host">{{ $bridge['host'] ?? '' }}</span>
            </span>
        </div>

        <div class="mcadmin__card">
            <span class="mcadmin__label">Bot reported balance</span>
            <div class="mcadmin__value" id="js-bot-balance">{{ $bridge['balance'] !== null ? number_format($bridge['balance']) : '—' }}</div>
            <span class="mcadmin__sub">read in game by the bot, when the balance command is configured</span>
        </div>
    </div>

    <div class="mcadmin__panel">
        <div class="mcadmin__panel_head">
            <h2 class="mcadmin__h2">Bot accounts</h2>
            <span class="mcadmin__meta">Minecraft accounts players whisper to and pay — used for verification, deposits and payouts</span>
        </div>

        <form class="mcadmin__account_form" method="POST" action="/admin/minecraft/accounts">
            @csrf
            <div class="mcadmin__field">
                <label for="acc_ign">Minecraft name</label>
                <input class="mcadmin__input" id="acc_ign" type="text" name="ign" placeholder="DonutTrixBot" maxlength="16" autocomplete="off" required>
            </div>

            <div class="mcadmin__field">
                <label for="acc_auth">Login type</label>
                <select class="mcadmin__input mcadmin__input_select" id="acc_auth" name="auth">
                    <option value="microsoft">Microsoft account</option>
                    <option value="offline">Offline (cracked)</option>
                </select>
            </div>

            <div class="mcadmin__field" data-creds>
                <label for="acc_login">Microsoft email</label>
                <input class="mcadmin__input" id="acc_login" type="email" name="login" placeholder="bot@outlook.com" maxlength="190" autocomplete="off">
            </div>

            <div class="mcadmin__field" data-creds>
                <label for="acc_password">Microsoft password</label>
                <input class="mcadmin__input" id="acc_password" type="password" name="password" placeholder="Stored encrypted" maxlength="190" autocomplete="new-password">
            </div>

            <div class="mcadmin__field">
                <label for="acc_purpose">Used for</label>
                <select class="mcadmin__input mcadmin__input_select" id="acc_purpose" name="purpose">
                    <option value="all">All — verify, deposit, withdraw</option>
                    <option value="verify">Verification only</option>
                    <option value="deposit">Deposits only</option>
                    <option value="withdraw">Payouts only</option>
                </select>
            </div>

            <div class="mcadmin__field mcadmin__field_grow">
                <label for="acc_note">Note</label>
                <input class="mcadmin__input" id="acc_note" type="text" name="note" placeholder="Optional" maxlength="120" autocomplete="off">
            </div>

            <div class="mcadmin__field mcadmin__field_btn">
                <button class="mcadmin__btn" type="submit">Add account</button>
            </div>

            <p class="mcadmin__hint">
                The bridge logs in as this account, so a Microsoft account needs the email and password it signs in with —
                the password is encrypted at rest and is never shown again. Offline accounts need no login.
            </p>
        </form>

        <script>
            (() => {
                const form = document.querySelector('.mcadmin__account_form');
                if (!form) return;

                const mode = form.querySelector('[name="auth"]');
                const fields = form.querySelectorAll('[data-creds]');
                const toggle = () => fields.forEach((el) => (el.style.display = mode.value === 'offline' ? 'none' : ''));

                mode.addEventListener('change', toggle);
                toggle();
            })();
        </script>

        @if(count($accounts) === 0)
        <p class="mcadmin__empty">No accounts yet. Add the Microsoft account your bridge runs (Minecraft name + login email + password), plus any extra accounts players should whisper or pay.</p>
        @else
        <div class="mcadmin__scroll">
            <table class="mcadmin__accounts_table">
                <thead>
                    <tr>
                        <th>Minecraft name</th>
                        <th>Login</th>
                        <th>Used for</th>
                        <th>Note</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                    <tr>
                        <td><strong>{{ $account['ign'] }}</strong></td>
                        <td>
                            @if(($account['auth'] ?? 'microsoft') === 'microsoft')
                                <span class="mcadmin__chip mcadmin__chip_online">Microsoft</span>
                                <span class="mcadmin__dim">{{ $account['login'] ?: 'email missing' }}</span>
                                <span class="mcadmin__chip mcadmin__chip_{{ ! empty($account['has_password']) ? 'online' : 'pending' }}">
                                    {{ ! empty($account['has_password']) ? 'password saved' : 'no password' }}
                                </span>
                            @else
                                <span class="mcadmin__chip mcadmin__chip_offline">Offline</span>
                                <span class="mcadmin__dim">no login needed</span>
                            @endif
                        </td>
                        <td>
                            {{ ['all' => 'Verify · deposit · withdraw', 'verify' => 'Verification', 'deposit' => 'Deposits', 'withdraw' => 'Payouts'][$account['purpose']] ?? $account['purpose'] }}
                        </td>
                        <td class="mcadmin__dim">{{ $account['note'] ?: '—' }}</td>
                        <td>
                            <span class="mcadmin__chip mcadmin__chip_{{ $account['enabled'] ? 'online' : 'offline' }}">
                                {{ $account['enabled'] ? 'active' : 'disabled' }}
                            </span>
                        </td>
                        <td class="mcadmin__actions">
                            <form method="POST" action="/admin/minecraft/accounts/{{ $account['id'] }}/toggle">
                                @csrf
                                <button class="mcadmin__mini" type="submit">{{ $account['enabled'] ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="/admin/minecraft/accounts/{{ $account['id'] }}/delete" onsubmit="return confirm('Remove this account?')">
                                @csrf
                                <button class="mcadmin__mini mcadmin__mini_danger" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="mcadmin__panel">
        <div class="mcadmin__panel_head">
            <h2 class="mcadmin__h2">Payouts</h2>
            <span class="mcadmin__meta">
                <span id="js-pending-d">{{ $summary['pending_deposits'] }}</span> deposits waiting ·
                <span id="js-unmatched">{{ $summary['unmatched'] }}</span> unmatched receipts
            </span>
            <a href="/admin/minecraft" class="mcadmin__refresh">Refresh</a>
        </div>

        <div class="mcadmin__scroll">
            <table class="table__user mcadmin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>IGN</th>
                        <th>In game</th>
                        <th>Coins</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>
                            @if($row['username'])
                            <a class="mcadmin__link" href="/admin/user/edit/{{ $row['user_id'] }}" target="_blank">{{ $row['username'] }}</a>
                            @else
                            <span class="mcadmin__muted">#{{ $row['user_id'] }}</span>
                            @endif
                        </td>
                        <td class="mcadmin__mono">{{ $row['player'] }}</td>
                        <td class="mcadmin__num">{{ number_format($row['game_amount']) }}</td>
                        <td class="mcadmin__num">{{ number_format($row['coins'], 2) }} $</td>
                        <td><span class="mcadmin__chip mcadmin__chip_{{ $row['status'] }}">{{ $row['status'] }}</span></td>
                        <td class="mcadmin__muted">{{ $row['created_at'] }}</td>
                        <td class="mcadmin__note">{{ $row['note'] ?? '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="mcadmin__empty">No payouts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mcadmin__panel">
        <div class="mcadmin__panel_head">
            <h2 class="mcadmin__h2">Deposits</h2>
        </div>

        <div class="mcadmin__scroll">
            <table class="table__user mcadmin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Player</th>
                        <th>In game</th>
                        <th>Coins</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>
                            @if($row['username'])
                            <a class="mcadmin__link" href="/admin/user/edit/{{ $row['user_id'] }}" target="_blank">{{ $row['username'] }}</a>
                            @else
                            <span class="mcadmin__muted">#{{ $row['user_id'] }}</span>
                            @endif
                        </td>
                        <td class="mcadmin__mono">{{ $row['player'] }}</td>
                        <td class="mcadmin__num">{{ number_format($row['game_amount']) }}</td>
                        <td class="mcadmin__num">{{ number_format($row['coins'], 2) }} $</td>
                        <td><span class="mcadmin__chip mcadmin__chip_{{ $row['status'] }}">{{ $row['status'] }}</span></td>
                        <td class="mcadmin__muted">{{ $row['created_at'] }}</td>
                        <td class="mcadmin__muted">{{ $row['expires_at'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="mcadmin__empty">No deposits yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mcadmin__panel">
        <div class="mcadmin__panel_head">
            <h2 class="mcadmin__h2">Unmatched receipts</h2>
            <span class="mcadmin__meta">/pay the bot sent with no matching pending deposit</span>
        </div>

        <div class="mcadmin__scroll">
            <table class="table__user mcadmin__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Player</th>
                        <th>In game</th>
                        <th>Raw line</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unmatched as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td class="mcadmin__mono">{{ $row['player'] }}</td>
                        <td class="mcadmin__num">{{ number_format($row['game_amount']) }}</td>
                        <td class="mcadmin__note">{{ $row['raw'] }}</td>
                        <td class="mcadmin__muted">{{ $row['created_at'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="mcadmin__empty">Nothing unmatched.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var url = '{{ url('/admin/minecraft/live') }}';
    var timer = null;

    function nf(value) {
        return Number(value || 0).toLocaleString('en-US');
    }

    function set(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function paint(data) {
        var s = data.summary || {};
        var b = data.bridge || {};

        set('js-available', nf(s.available));
        set('js-coins', Number(s.coins_available || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        set('js-deposited', nf(s.deposited));
        set('js-deposits-count', nf(s.completed_deposits));
        set('js-paid', nf(s.paid));
        set('js-payouts-count', nf(s.payouts_paid));
        set('js-queued', nf(s.queued));
        set('js-pending-w', s.pending_withdrawals);
        set('js-sending', s.sending);
        set('js-unconfirmed', s.unconfirmed);
        set('js-pending-d', s.pending_deposits);
        set('js-unmatched', s.unmatched);
        set('js-bot', b.bot || '—');
        set('js-bot-balance', b.balance === null || b.balance === undefined ? '—' : nf(b.balance));
        set('js-host', b.host || '');

        var chip = document.getElementById('js-bridge');
        if (chip) {
            chip.textContent = b.online ? 'online' : 'offline';
            chip.className = 'mcadmin__chip mcadmin__chip_' + (b.online ? 'online' : 'offline');
        }

        set('js-updated', new Date().toLocaleTimeString());
    }

    function refresh() {
        if (document.hidden) return;

        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(paint)
            .catch(function () {});
    }

    function start() {
        refresh();
        timer = setInterval(refresh, 10000);
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refresh();
    });

    start();
})();
</script>
@endsection
