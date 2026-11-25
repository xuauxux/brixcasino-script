@extends('panel.layout')

@section('content')
<div class="pf-grid" style="margin-bottom:12px">
    <div class="pf-card">
        <span class="pf-label">Points in</span>
        <div class="pf-value pf-lime">{{ number_format($totals['in']) }}</div>
    </div>
    <div class="pf-card">
        <span class="pf-label">Points out</span>
        <div class="pf-value pf-red">{{ number_format($totals['out']) }}</div>
    </div>
    <div class="pf-card">
        <span class="pf-label">Net</span>
        <div class="pf-value">{{ number_format($totals['in'] - $totals['out']) }}</div>
    </div>
</div>

<div class="pf-card" style="margin-bottom:12px">
    <form class="pf-form" method="GET" action="{{ route('panel.transactions') }}">
        <div class="pf-field">
            <label for="q">Player</label>
            <input class="pf-input" id="q" name="q" value="{{ $filters['q'] }}" placeholder="username or id">
        </div>
        <div class="pf-field">
            <label for="type">Type</label>
            <select class="pf-select" id="type" name="type">
                <option value="">All types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="pf-field">
            <label for="from">From</label>
            <input class="pf-input" id="from" name="from" type="date" value="{{ $filters['from'] }}">
        </div>
        <div class="pf-field">
            <label for="to">To</label>
            <input class="pf-input" id="to" name="to" type="date" value="{{ $filters['to'] }}">
        </div>
        <button class="pf-btn" type="submit">Filter</button>
        <a class="pf-btn pf-btn_ghost" href="{{ route('panel.transactions') }}">Reset</a>
    </form>
</div>

<div class="pf-card">
    <div class="pf-table-wrap">
        <table class="pf-table">
            <thead>
            <tr><th>ID</th><th>Player</th><th>Type</th><th>Amount</th><th>Balance after</th><th>By</th><th>Reason</th><th>Date</th></tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="pf-dim pf-mono">{{ $row->id }}</td>
                    <td><a href="{{ url('/panel/users/'.$row->user_id) }}">{{ $row->username ?? '#'.$row->user_id }}</a></td>
                    <td class="pf-dim">{{ $row->type }}</td>
                    <td class="{{ $row->amount >= 0 ? 'pf-lime' : 'pf-red' }} pf-mono">{{ $row->amount >= 0 ? '+' : '−' }}{{ number_format(abs($row->amount)) }}</td>
                    <td class="pf-mono">{{ number_format($row->balance_after) }}</td>
                    <td class="pf-dim">{{ $row->admin_name ?? 'system' }}</td>
                    <td class="pf-dim">{{ $row->reason }}</td>
                    <td class="pf-dim pf-mono">{{ $row->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="pf-empty">No transactions matched those filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div class="pf-actions" style="margin-top:12px">
            @if(! $rows->onFirstPage())
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ $rows->previousPageUrl() }}">Previous</a>
            @endif
            <span class="pf-sub" style="align-self:center">Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>
            @if($rows->hasMorePages())
                <a class="pf-btn pf-btn_sm pf-btn_ghost" href="{{ $rows->nextPageUrl() }}">Next</a>
            @endif
        </div>
    @endif
</div>
@endsection
