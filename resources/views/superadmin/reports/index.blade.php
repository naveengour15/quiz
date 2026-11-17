@extends('superadmin.layouts.app')
@section('title', 'Reports & Analytics')
@section('content')

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Reports &amp; Analytics</h1>
                <p>Revenue and engagement trends over a chosen date range.</p>
            </div>
        </div>

        <form method="GET" class="toolbar">
            <input class="tb-input" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
            <input class="tb-input" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
            <button class="btn btn-ghost" type="submit">Apply Range</button>
        </form>

        <div class="section-label">Subscription Revenue by Week</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenueByWeek as $row)
                        <tr>
                            <td class="mono">{{ $row->yw }}</td>
                            <td class="mono">₹{{ number_format($row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align:center;color:var(--ink-faint);">No revenue recorded for
                                this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p style="color:var(--ink-faint);font-size:12px;margin-top:14px;">Creator earnings and cohort retention reports can
            be added here later using the same query pattern in <code>ReportController</code>.</p>
    </div>

@endsection
