@extends('panel.layout')

@section('content')
<div class="pf-grid pf-grid_2">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Site settings</h2></div>
        <form class="pf-form" method="POST" action="{{ route('panel.settings.save') }}">
            @csrf
            <div class="pf-field">
                <label for="min_payment">Minimum deposit</label>
                <input class="pf-input" id="min_payment" name="min_payment" type="number" step="0.01" value="{{ $site->min_payment ?? 0 }}">
            </div>
            <div class="pf-field">
                <label for="min_withdraw">Minimum withdrawal</label>
                <input class="pf-input" id="min_withdraw" name="min_withdraw" type="number" step="0.01" value="{{ $site->min_withdraw ?? 0 }}">
            </div>
            <div class="pf-field" style="flex:1 1 100%">
                <label for="maintenance_message">Maintenance message</label>
                <input class="pf-input" id="maintenance_message" name="maintenance_message" maxlength="190" value="{{ \App\Services\Platform\Settings::maintenanceMessage() }}">
            </div>
            <div class="pf-field" style="flex:1 1 100%">
                <label for="admin_igns">Staff Minecraft accounts (comma separated)</label>
                <input class="pf-input" id="admin_igns" name="admin_igns" maxlength="190" value="{{ implode(', ', \App\Services\Platform\Settings::adminIgns()) }}">
                <span class="pf-sub">Verifying with one of these IGNs grants owner access. Verification is still required — the whisper proves the player owns the account.</span>
            </div>
            <button class="pf-btn" type="submit">Save settings</button>
        </form>
    </div>

    <div class="pf-card">
        <div class="pf-card_head">
            <h2 class="pf-h2">DONUTTRIX event</h2>
            <span class="pf-chip {{ $event['active'] ? 'is-on' : 'is-off' }}"><i></i>{{ $event['active'] ? 'running' : 'ended' }}</span>
        </div>
        <div class="pf-row"><span class="pf-dim">Window</span><span class="pf-mono">{{ $event['starts_at'] }} → {{ $event['ends_at'] }}</span></div>
        <div class="pf-row"><span class="pf-dim">Elapsed</span><span class="pf-mono">{{ $event['progress'] }}%</span></div>
        <p class="pf-sub">Letters drop from games, rain and the daily free letter. Completing DONUTTRIX pays 100,000,000 once.</p>
        <a class="pf-btn pf-btn_sm pf-btn_ghost" style="align-self:flex-start;margin-top:8px" href="{{ route('panel.event') }}">Open event panel</a>
    </div>
</div>

<div class="pf-grid pf-grid_2" style="margin-top:12px">
    <div class="pf-card">
        <div class="pf-card_head"><h2 class="pf-h2">Level rewards</h2></div>
        <div class="pf-table-wrap">
            <table class="pf-table">
                <thead><tr><th>Level</th><th>XP needed</th><th>Reward</th></tr></thead>
                <tbody>
                @foreach($levels as $level => $amount)
                    <tr>
                        <td class="pf-mono">{{ $level }}</td>
                        <td class="pf-mono pf-dim">{{ number_format(\App\Services\Platform\Levels::threshold($level)) }}</td>
                        <td class="pf-mono pf-lime">{{ number_format($amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
