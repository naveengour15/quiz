@extends('superadmin.layouts.app')

@section('title','Banner Details')

@section('content')

<style>
    .banner-detail-card{
        background:#fff;
        border-radius:12px;
        padding:20px;
        margin-bottom:20px;
        box-shadow:0 2px 10px rgba(0,0,0,.06);
    }

    .banner-info-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
        gap:15px;
    }

    .info-box{
        background:#f8fafc;
        padding:12px 15px;
        border-radius:10px;
        border:1px solid #e5e7eb;
    }

    .info-box label{
        display:block;
        font-size:12px;
        color:#6b7280;
        margin-bottom:4px;
        font-weight:600;
    }

    .info-box span{
        font-size:14px;
        color:#111827;
        font-weight:600;
        word-break:break-word;
    }

    .summary-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
        gap:15px;
        margin-bottom:25px;
    }

    .summary-card{
        background:#fff;
        border-radius:12px;
        padding:20px;
        text-align:center;
        box-shadow:0 2px 10px rgba(0,0,0,.06);
    }

    .summary-card h6{
        color:#6b7280;
        font-size:13px;
        margin-bottom:8px;
    }

    .summary-card h2{
        color:#2563eb;
        margin:0;
        font-weight:700;
    }

    .history-tabs{
        display:flex;
        border-bottom:2px solid #e5e7eb;
        margin-bottom:15px;
        gap:10px;
    }

    .history-tab{
        padding:10px 18px;
        border:none;
        background:none;
        font-weight:600;
        color:#6b7280;
        border-bottom:3px solid transparent;
        cursor:pointer;
    }

    .history-tab.active{
        color:#2563eb;
        border-color:#2563eb;
    }

    .history-content{
        display:none;
    }

    .history-content.active{
        display:block;
    }

    .table thead th{
        background:#f3f4f6;
        font-size:13px;
    }

    .table td{
        font-size:13px;
        vertical-align:middle;
    }


    /* 992px AND ABOVE - TABLE SCROLL */
@media (min-width: 992px) {
    .banner-detail-view-table-box {
        overflow-x: auto !important;
        width: 100%;
    }

    .banner-detail-view-table-wrapper {
        min-width: 1200px;
    }

     .banner-detail-click-history-table-box {
        overflow-x: auto !important;
        width: 100%;
    }

    .banner-detail-click-history-table-wrapper {
        min-width: 1200px;
    }
}


/* 991px AND BELOW - CARD FORMAT */
@media (max-width: 991px) {

    .banner-detail-view-table-box {
        overflow: visible !important;
    }

    .banner-detail-view-table-wrapper thead {
        display: none;
    }

    .banner-detail-view-table-wrapper,
    .banner-detail-view-table-wrapper tbody,
    .banner-detail-view-table-wrapper tr,
    .banner-detail-view-table-wrapper td {
        display: block;
        width: 100%;
    }

    .banner-detail-view-table-wrapper tbody tr {
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #e5e7eb !important;
        border-radius: 10px;
        background: #fff;
    }

    .banner-detail-view-table-wrapper tbody td {
        padding: 8px 0;
        border: 0 !important;
        text-align: left !important;
        white-space: normal;
        word-break: break-word;
    }

    .banner-detail-view-table-wrapper tbody td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-align: left;
    }

    .banner-detail-click-history-table-box {
        overflow: visible !important;
    }

    .banner-detail-click-history-table-wrapper thead {
        display: none;
    }

    .banner-detail-click-history-table-wrapper,
    .banner-detail-click-history-table-wrapper tbody,
    .banner-detail-click-history-table-wrapper tr,
    .banner-detail-click-history-table-wrapper td {
        display: block;
        width: 100%;
    }

    .banner-detail-click-history-table-wrapper tbody tr {
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #e5e7eb !important;
        border-radius: 10px;
        background: #fff;
    }

    .banner-detail-click-history-table-wrapper tbody td {
        padding: 8px 0;
        border: 0 !important;
        text-align: left !important;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .banner-detail-click-history-table-wrapper tbody td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-align: left;
    }

    .banner-detail-click-history-table-wrapper tbody td:last-child {
        padding-bottom: 0;
    }
}

@media (max-width: 400px) {
    .history-tab {
        padding: 10px 10px;
    }
}
</style>

<div class="container-fluid">

    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:20px; ">
        <div>
            <h3 class="mb-1">Banner Details</h3>
            <p class="text-muted mb-0">
                Banner information with view & click history.
            </p>
        </div>

        <a href="{{ url()->previous() }}" class="btn btn-ghost">
            ← Back
        </a>
    </div>

    {{-- Banner Information --}}
    <div class="banner-detail-card">

        <h5 class="mb-3" style="margin-bottom: 10px;">Banner Information</h5>

        <div class="banner-info-grid">

            <div class="info-box">
                <label>Banner Name</label>
                <span>{{ $banner->name ?? '-' }}</span>
            </div>

            <div class="info-box">
                <label>Type</label>
                <span>
                    @if(is_array($banner->type))
                        {{ implode(', ', $banner->type) }}
                    @else
                        {{ $banner->type ?? '-' }}
                    @endif
                </span>
            </div>

            <div class="info-box">
                <label>Status</label>
                <span>{{ ucfirst($banner->status ?? '-') }}</span>
            </div>

            <div class="info-box">
                <label>Retailer</label>
                <span>{{ $banner->tenant->name ?? '-' }}</span>
            </div>

            <div class="info-box">
                <label>URL</label>
                <span>
                    @if($banner->url)
                        <a href="{{ $banner->url }}" target="_blank">
                            Visit Banner
                        </a>
                    @else
                        -
                    @endif
                </span>
            </div>

            <div class="info-box">
                <label>Created At</label>
                <span>{{ optional($banner->created_at)->format('d M Y, h:i A') }}</span>
            </div>

        </div>

    </div>

  

    @if(!empty($typeStats))
        <div class="banner-detail-card">

            <h5 class="mb-3">Banner Type Analytics</h5>

            <div class="banner-info-grid">

                @foreach($typeStats as $type => $stats)

                    <div class="info-box">
                        <label>{{ ucwords(str_replace('_',' ', $type)) }}</label>

                        <div style="display:flex;justify-content:space-between;margin-top:8px;">
                            <span>👁 Views</span>
                            <strong>{{ $stats['views'] }}</strong>
                        </div>

                        <div style="display:flex;justify-content:space-between;margin-top:8px;">
                            <span>🖱 Clicks</span>
                            <strong>{{ $stats['clicks'] }}</strong>
                        </div>
                    </div>

                @endforeach

            </div>

        </div>
    @endif

    {{-- Summary --}}
    <div class="summary-grid">

        <div class="summary-card">
            <h6>Total Views</h6>
            <h2>{{ number_format($totalViews) }}</h2>
        </div>

        <div class="summary-card">
            <h6>Total Clicks</h6>
            <h2>{{ number_format($totalClicks) }}</h2>
        </div>

    </div>

    {{-- History Tabs --}}
    <div class="banner-detail-card">

        <div class="history-tabs">

            <button class="history-tab {{ $activeTab === 'views' ? 'active' : '' }}"
                onclick="changeHistoryTab('views',this)">
                👁 View History
            </button>

            <button class="history-tab {{ $activeTab === 'clicks' ? 'active' : '' }}"
                onclick="changeHistoryTab('clicks',this)">
                🖱 Click History
            </button>

        </div>

        {{-- View History --}}
        <div id="views" class="history-content {{ $activeTab === 'views' ? 'active' : '' }}">
            <div class="table-responsive banner-detail-view-table-box">
                <table class="table table-bordered banner-detail-view-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Page</th>
                            <th>IP</th>
                            <th>Device</th>
                            <th>Browser</th>
                            <th>OS</th>
                            <th>Location</th>
                            <th>Referrer</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($viewHistory as $index=>$view)
                            <tr>
                                {{-- <td data-label="#">{{ $index+1 }}</td> --}}
                                <td data-label="#"> {{ $viewHistory->total() - ($viewHistory->firstItem() + $loop->index) + 1 }}</td>
                                <td data-label="User">{{ $view->viewer->name ?? 'Guest' }}</td>
                                <td data-label="Page">{{ $view->page_type ?? '-' }}</td>
                                <td data-label="IP">{{ $view->ip_address ?? '-' }}</td>
                                <td data-label="Device">{{ $view->device ?? '-' }}</td>
                                <td data-label="Browser">{{ $view->browser ?? '-' }}</td>
                                <td data-label="OS">{{ $view->os ?? '-' }}</td>
                                <td data-label="Location">
                                    {{ collect([$view->city,$view->state,$view->country])->filter()->implode(', ') ?: '-' }}
                                </td>

                                <td data-label="Referrer">{{ $view->referrer ?? '-' }}</td>

                                <td data-label="Date">
                                    {{ optional($view->viewed_at ?? $view->created_at)->format('d M Y h:i A') }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    No View History Found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
                <div class="pagination">
                    @include('common.pagination.superadmin_pagination', [
                        'pageData' => $viewHistory,
                        'paginationTab' => 'views',
                    ])
                </div>
        </div>

        {{-- Click History --}}
        <div id="clicks" class="history-content {{ $activeTab === 'clicks' ? 'active' : '' }}">
            <div class="table-responsive banner-detail-click-history-table-box">
                <table class="table table-bordered banner-detail-click-history-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Page</th>
                            <th>IP</th>
                            <th>Device</th>
                            <th>Browser</th>
                            <th>OS</th>
                            <th>Location</th>
                            <th>Destination URL</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($clickHistory as $index=>$click)
                            <tr>
                                {{-- <td data-label="#">{{ $index+1 }}</td> --}}
                                 <td data-label="#">   {{ $clickHistory->total() - ($clickHistory->firstItem() + $loop->index) + 1 }} </td>
                                <td data-label="User">{{ $click->user->name ?? 'Guest' }}</td>
                                <td data-label="Page">{{ $click->page_type ?? '-' }}</td>
                                <td data-label="IP">{{ $click->ip_address ?? '-' }}</td>
                                <td data-label="Device">{{ $click->device ?? '-' }}</td>
                                <td data-label="Browser">{{ $click->browser ?? '-' }}</td>
                                <td data-label="OS">{{ $click->os ?? '-' }}</td>

                                <td data-label="Location">
                                    {{ collect([$click->city,$click->state,$click->country])->filter()->implode(', ') ?: '-' }}
                                </td>

                                <td data-label="Destination URL">
                                    {{-- @if($click->destination_url)
                                        <a href="{{ $click->destination_url }}" target="_blank">
                                            Open URL
                                        </a>
                                    @else
                                        -
                                    @endif --}}
                                   {{ $click->destination_url ?? '-'}}
                                       
                                </td>

                                <td data-label="Date">
                                    {{ optional($click->clicked_at ?? $click->created_at)->format('d M Y h:i A') }}
                                </td>

                            </tr>

                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    No Click History Found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
             <div class="pagination">
                @include('common.pagination.superadmin_pagination', [
                    'pageData' => $clickHistory,
                    'paginationTab' => 'clicks',
                ])
             </div>
        </div>
    </div>
</div>

<script>
function changeHistoryTab(tab, btn){

    document.querySelectorAll('.history-tab')
        .forEach(item => item.classList.remove('active'));

    document.querySelectorAll('.history-content')
        .forEach(item => item.classList.remove('active'));

    btn.classList.add('active');
    document.getElementById(tab).classList.add('active');

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}
</script>

@endsection