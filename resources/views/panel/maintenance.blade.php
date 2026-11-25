@extends('panel.layout')

@section('content')
<div class="pf-grid pf-grid_2">
    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">Maintenance mode</h2>
            <span class="pf-chip {{ $on ? 'is-warn' : 'is-on' }}"><i></i>{{ $on ? 'ON' : 'off' }}</span>
        </div>

        <p class="pf-sub">
            While it is on, players see a maintenance screen instead of the site and cannot open games.
            Staff and the owner keep full access, and the in-game bridge keeps sending deposits.
            The switch is stored in the database, so it applies to every visitor on every server.
        </p>

        <form method="POST" action="{{ route('panel.maintenance.save') }}" style="margin-top:14px">
            @csrf
            <div class="pf-field">
                <label for="message">Message shown to players</label>
                <input class="pf-input" id="message" name="message" maxlength="190" value="{{ $message }}">
            </div>
            <div class="pf-actions" style="margin-top:12px">
                <button class="pf-btn" name="state" value="on" type="submit">Turn maintenance ON</button>
                <button class="pf-btn pf-btn_ghost" name="state" value="off" type="submit">Turn OFF</button>
            </div>
        </form>
    </div>

    <div class="pf-card">
        <h2 class="pf-h2">What players see</h2>
        <div class="pf-card" style="background:#0d1219;margin-top:10px;align-items:center;text-align:center;padding:28px 18px">
            <span class="pf-label">DONUTTRIX</span>
            <div class="pf-h2" style="font-size:20px">We'll be right back</div>
            <p class="pf-sub" style="max-width:340px">{{ $message }}</p>
            <a class="pf-btn pf-btn_sm" href="{{ url('/') }}" style="margin-top:8px">Players: retry</a>
        </div>
        <p class="pf-sub" style="margin-top:10px">Owner-only feature. Only accounts with the owner role can change this.</p>
    </div>
</div>
@endsection
