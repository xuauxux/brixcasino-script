@extends('panel.layout')

@section('content')
<div class="pf-card" style="margin-bottom:12px">
    <form class="pf-form" method="GET" action="{{ route('panel.users') }}">
        <div class="pf-field" style="flex:3 1 260px">
            <label for="q">Search by username, IGN, id or referral code</label>
            <input class="pf-input" id="q" name="q" value="{{ $q }}" placeholder="e.g. donut, 12, AB12CD34" autocomplete="off">
        </div>
        <button class="pf-btn" type="submit">Search</button>
        @if($q !== '')
            <a class="pf-btn pf-btn_ghost" href="{{ route('panel.users') }}">Clear</a>
        @endif
    </form>
</div>

<div class="pf-card">
    <div class="pf-table-wrap">
        <table class="pf-table">
            <thead>
            <tr>
                <th>ID</th><th>Player</th><th>IGN</th><th>Balance</th><th>Level</th><th>Role</th><th>Status</th><th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="pf-dim pf-mono">{{ $user->id }}</td>
                    <td><a href="{{ url('/panel/users/'.$user->id) }}">{{ $user->username }}</a></td>
                    <td class="pf-dim pf-mono">{{ $user->minecraft_ign ?: '—' }}</td>
                    <td class="pf-mono">{{ number_format((float) $user->balance) }}</td>
                    <td class="pf-mono">{{ $user->level ?? 1 }}</td>
                    <td>
                        <span class="pf-chip {{ ($user->role ?? 'user') === 'owner' ? 'is-warn' : '' }}" style="padding:3px 8px;font-size:10px">
                            {{ strtoupper($user->role ?? 'user') }}
                        </span>
                    </td>
                    <td>
                        @if((int) $user->ban === 1)
                            <span class="pf-chip is-off" style="padding:3px 8px;font-size:10px"><i></i>banned</span>
                        @else
                            <span class="pf-chip is-on" style="padding:3px 8px;font-size:10px"><i></i>active</span>
                        @endif
                    </td>
                    <td><a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ url('/panel/users/'.$user->id) }}">Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="pf-empty">No players matched that search.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="pf-actions" style="margin-top:12px">
            @if($users->onFirstPage() === false)
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ $users->previousPageUrl() }}">Previous</a>
            @endif
            <span class="pf-sub" style="align-self:center">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
            @if($users->hasMorePages())
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ $users->nextPageUrl() }}">Next</a>
            @endif
        </div>
    @endif
</div>
@endsection
