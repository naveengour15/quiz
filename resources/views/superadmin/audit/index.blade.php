@extends('superadmin.layouts.app')
@section('title', 'Audit Logs')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

<div class="panel active">
    <div class="page-head">
      <div><h1>Audit Logs</h1>
      <p>Every admin action, permanently recorded.</p>
    </div>
  </div>

   <div class="search-filter-box">
      <form method="GET" class="toolbar">
        <div class="audit-search-filter-box">
            <div class="audit-search-filter-wrapper">
                <div class="input-field">
                  <label class="lbl" for="search">Search</label>
                  <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search admin, action, or record…">
                </div>

                <div class="input-field">
                  <label class="lbl" for="search">Date</label>
                  <input class="tb-input status-field" type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()">
                </div>
            </div>

            <div class="audit-filter-btn">
              <button class="btn btn-primary" type="submit">Filter</button>
              <a href="{{ request()->url() }}" class="btn btn-ghost">Reset</a>
            </div>
        </div>
      </form>
   </div>


  <div class="table-wrap table-wrap-res-01">
    <div class="audit-logs-table-box">
      <table class="audit-logs-table-wrapper">
        <thead><tr><th>Admin</th><th>Action</th><th>Module</th><th>Record</th><th>IP Address</th><th>Time</th><th>Details</th></tr></thead>
        <tbody>
          @forelse($logs as $log)
          <tr>
            <td data-label="Admin">{{ $log->admin_name }}</td>
            <td data-label="Action">{{ $log->action }}</td>
            <td data-label="Module">{{ $log->module }}</td>
            <td data-label="Record"class="mono">
              <div class="audit-records">
                 {{ $log->record }}
              </div>
            </td>
            <td data-label="IP Address" class="mono" style="color: var(--indigo);">{{ $log->ip_address }}</td>
            <td data-label="Time" class="mono">{{ $log->created_at->format('d M Y, h:i A') }}</td>
            <td data-label="Details">
                <button type="button" class="btn btn-sm btn-info"
                        data-bs-toggle="modal" data-bs-target="#auditDetailModal"
                        data-log='@json($log)'>👁️ View</button>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" style="text-align:center;color:var(--ink-faint);">No activity logged yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="pagination">
      @include('common.pagination.superadmin_pagination', ['pageData' => $logs])
    </div>
  </div>
</div>

@endsection
