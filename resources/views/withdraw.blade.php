@extends('layout')

@section('content')
<div class="withdraw__container">
    <div class="modal__wallet">
        <div class="modal__wallet_header">
            <div class="modal__wallet_header_nav">
                <a href="/wallet/pay" class="modal__wallet_header_item">
                    <i class='bx bx-plus-circle'></i>
                    <span>Deposit</span>
                </a>
                <a href="/wallet/withdraw" class="modal__wallet_header_item active">
                    <i class='bx bx-minus-circle'></i>
                    <span>Withdrawal</span>
                </a>
                <a href="/wallet/history" class="modal__wallet_header_item">
                    <i class='bx bx-history'></i>
                    <span>History</span>
                </a>
            </div>
        </div>
        <div class="modal__wallet_body mc__wallet">
            <div class="mc__side">
                <label class="modal__wallet_body-label">Balance</label>
                <div class="modal__wallet_balance">
                    {{ Auth::user()->balance }} <span class="ruble">$</span>
                </div>

                <div class="mc__pill {{ $online ? 'mc__pill_online' : 'mc__pill_offline' }}">
                    <span class="mc__dot"></span>
                    {{ $online ? 'Bridge online' : 'Bridge offline' }}
                </div>

                <div class="mc__row">
                    <span>Your IGN</span>
                    <strong class="{{ $verified ? '' : 'mc__muted' }}">{{ $verified ? $ign : 'not verified' }}</strong>
                </div>
                <div class="mc__row">
                    <span>Paid by</span>
                    <strong class="mc__mono">{{ $bot ?? '—' }}</strong>
                </div>
                <div class="mc__row">
                    <span>Rate</span>
                    <strong>{{ $coins_per_million }} $ = 1,000,000</strong>
                </div>
                <div class="mc__row">
                    <span>Bot can pay</span>
                    <strong class="mc__mono">{{ number_format($available) }}</strong>
                </div>

                <ol class="mc__steps">
                    <li>Request a payout below.</li>
                    <li>Coins leave your balance straight away.</li>
                    <li>The bot pays you in game and marks it paid.</li>
                </ol>

                @if(!$verified)
                <div class="mc__verify" id="mc_verify">
                    <label class="modal__wallet_body-label">Link your Minecraft name</label>
                    <p class="mc__hint_small">Payouts are only sent to a verified IGN.</p>
                    <div class="modal__wallet_input_wrapper">
                        <input type="text" class="modal__wallet_input" id="mc_ign" placeholder="Your IGN" maxlength="16" autocomplete="off">
                    </div>
                    <button type="button" class="sidebar__play mc__submit" id="mc_ign_start">Verify my Minecraft name</button>
                    <div class="mc__code_box" id="mc_code_box" hidden>
                        <p class="mc__hint_small">Whisper anything to this account on DonutSMP:</p>
                        <div class="mc__code" id="mc_code"></div>
                        <p class="mc__state" id="mc_link_state">Waiting for your message…</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="mc__main{{ $verified ? '' : ' mc__main_locked' }}">
                <div class="mc__lock">
                    <i class='bx bx-lock-alt'></i>
                    <h3>Verify your Minecraft name</h3>
                    <p>Whisper any message to the account in the panel on the left in DonutSMP, then reload this page. Payouts are only available to a verified IGN.</p>
                    <a href="/wallet/withdraw" class="sidebar__play mc__submit">Reload</a>
                </div>
                <label class="modal__wallet_body-label">Payout amount (in game)</label>

                <div class="modal__wallet_input_wrapper">
                    <input type="number" class="modal__wallet_input" id="mc_amount" placeholder="Amount in game, e.g. {{ $min }}" min="{{ $min }}" max="{{ $max }}">
                </div>

                <div class="mc__hint_small">
                    Minimum {{ number_format($min) }} · tax {{ number_format($tax_bps / 100, 2) }}% · you receive it as a /pay from the bot.
                </div>

                <button type="button" class="sidebar__play mc__submit" id="mc_withdraw">Request payout</button>
                <div class="mc__hint" id="mc_hint"></div>

                <label class="modal__wallet_body-label">Payouts in progress</label>
                <div class="mc__list" id="mc_pending">
                    <div class="mc__empty" id="mc_pending_empty">No payouts in the queue.</div>
                </div>

                <label class="modal__wallet_body-label">Recent payouts</label>
                <div class="mc__list" id="mc_recent">
                    <div class="mc__empty" id="mc_recent_empty">Nothing here yet.</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/assets/js/minecraft.js"></script>
@endsection
