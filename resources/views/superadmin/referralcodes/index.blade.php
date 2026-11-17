@extends('superadmin.layouts.app')

@section('title', 'Referral Codes')

<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')

    @push('styles')
        <style>
            /*
                |--------------------------------------------------------------------------
                | Referral Code
                |--------------------------------------------------------------------------
             */

            .referral-code {
                display: inline-flex;
                align-items: center;
                padding: 6px 10px;
                border-radius: 7px;
                background: #f1f5f9;
                color: #0f172a;
                font-family: monospace;
                font-weight: 600;
                font-size: 13px;
                letter-spacing: .5px;
                white-space: nowrap;
            }


            /*
                |--------------------------------------------------------------------------
                | User Information
                |--------------------------------------------------------------------------
             */

            .user-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 130px;
            }

            .user-name {
                font-weight: 600;
                color: var(--ink, #0f172a);
                white-space: nowrap;
            }

            .user-id {
                font-size: 12px;
                color: var(--ink-faint, #94a3b8);
                white-space: nowrap;
            }

             /*
                |--------------------------------------------------------------------------
                | points
                |--------------------------------------------------------------------------
             */


            .points-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 6px;
                background: #ecfdf5;
                color: #047857;
                font-weight: 600;
                font-size: 13px;
                white-space: nowrap;
            }

            /*
                |--------------------------------------------------------------------------
                | Used badge
                |--------------------------------------------------------------------------
             */


            .used-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }

            .used-badge.yes {
                background: #ecfdf5;
                color: #047857;
            }

            .used-badge.no {
                background: #fff7ed;
                color: #c2410c;
            }

            /*
                |--------------------------------------------------------------------------
                | status-badge
                |--------------------------------------------------------------------------
             */

            
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }

            .status-badge.active {
                background: #ecfdf5;
                color: #047857;
            }

            .status-badge.inactive {
                background: #fef2f2;
                color: #b91c1c;
            }

            .date-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
                white-space: nowrap;
            }

            .date-main {
                font-size: 13px;
                font-weight: 500;
            }

            .date-time {
                font-size: 11px;
                color: var(--ink-faint, #94a3b8);
            }


            .referral-table-box {
                overflow-x: auto;
            }

            .referral-table-box table {
                /* min-width: 1400px; */
            }

            .referral-search-filter-box{
                width:100%;
                display: flex;
                gap: 10px;
                align-items: flex-end;
            }

            .referral-search-filter-wrapper{
                width:100%;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .referral-filter-btn{
                display: flex;
                gap: 10px;
            }

            @media(max-width:991px){
                .referral-search-filter-box {
                   flex-direction: column;
                }
            }

             @media(max-width:767px){
             .referral-table-box{
                    background: transparent;
                    border: none;
                    border-radius: none;
                }
            }

             @media(max-width:575px){
               .referral-search-filter-wrapper {
                    grid-template-columns: repeat(1, 1fr);
                }
                .referral-filter-btn{
                   width: 100%;
                }
                .referral-filter-btn .btn{
                   width: 100%;
                   justify-content: center;
                   display: flex;
                }

        
            }
    
        </style>
    @endpush


    <div class="panel active">


        {{-- =========================================================
             PAGE HEADER
        ========================================================== --}}

        <div class="page-head">

            <div>

                <h1>
                    Referral Codes
                </h1>

                <p>
                    Manage and view all referral codes and their usage details.
                </p>

            </div>

        </div>


        {{-- =========================================================
             SEARCH / FILTER
        ========================================================== --}}

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="referral-search-filter-box">
                    <div class="referral-search-filter-wrapper">
                        {{-- Search --}}

                        <div class="input-field">
                            <label class="lbl" for="search">
                                Search
                            </label>

                            <input class="tb-input" type="text" id="search" name="search"
                                value="{{ request('search') }}" placeholder="Search code, user or tenant">
                        </div>


                        {{-- Used --}}

                        <div class="input-field">
                            <label class="lbl" for="used">
                                Usage
                            </label>

                            <select class="tb-select" id="used" name="used"
                                onchange="this.form.submit()">
                                <option value="">
                                    All
                                </option>

                                <option value="used" @selected(request('used') === 'used')>
                                    Used
                                </option>

                                <option value="pending" @selected(request('used') === 'pending')>
                                    Pending
                                </option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label class="lbl" for="referral_point_provide">
                                Point Provide
                            </label>

                            <select class="tb-select" id="referral_point_provide" name="referral_point_provide"
                                onchange="this.form.submit()">

                                <option value="">
                                    All
                                </option>

                                <option value="yes" @selected(request('referral_point_provide') === 'yes')>
                                    Yes
                                </option>

                                <option value="no" @selected(request('referral_point_provide') === 'no')>
                                    No
                                </option>

                            </select>

                        </div>


                        {{-- Status --}}

                        {{-- <div class="input-field">

                            <label class="lbl" for="status">
                                Status
                            </label>

                            <select class="tb-select status-field" id="status" name="status"
                                onchange="this.form.submit()">

                                <option value="">
                                    All Statuses
                                </option>

                                <option value="active" @selected(request('status') === 'active')>
                                    Active
                                </option>

                                <option value="inactive" @selected(request('status') === 'inactive')>
                                    Inactive
                                </option>

                            </select>

                        </div> --}}


                    </div>


                    <div class="referral-filter-btn">

                        <button class="btn btn-primary" type="submit">
                            Filter
                        </button>

                        <a href="{{ request()->url() }}" class="btn btn-ghost">
                            Reset
                        </a>

                    </div>


                </div>

            </form>

        </div>


        {{-- =========================================================
             TABLE
        ========================================================== --}}

        <div class="table-wrap referral-table-box">
            <div class="referral-table-box">
                <table class="level-table-wrapper">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Retailer</th>
                            <th>User</th>
                            <th>Referral Code</th>
                            <th>Point Provide</th>
                            <th>Points</th>
                            <th>Referral User</th>
                            <th>Used</th>
                            <th>Used Date</th>
                            {{-- <th>Status</th> --}}
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referralCodes as $referral)
                            <tr>
                                <td data-label="#" class="mono">
                                    {{ $referralCodes->firstItem() + $loop->index }}
                                </td>

                                <td data-label="Tenant">
                                    <div class="user-info">
                                        <span class="user-name">
                                            {{ $referral->tenant_name ?? '—' }}
                                        </span>

                                        <span class="user-id">
                                            Tenant ID:
                                            {{ $referral->tenant_id }}
                                        </span>
                                    </div>
                                </td>


                                {{-- =================================================
                                    CREATED BY
                                ================================================== --}}

                                <td data-label="Created By">

                                    <div class="user-info">


                                        <span class="user-name">

                                            {{ $referral->creator_name ?? '—' }}

                                        </span>


                                        @if ($referral->creator_mobile)
                                            <span class="user-id">

                                                {{ $referral->creator_mobile }}

                                            </span>
                                        @endif


                                        <span class="user-id">

                                            User ID:
                                            {{ $referral->user_id }}

                                        </span>


                                    </div>

                                </td>


                                {{-- =================================================
                                    REFERRAL CODE
                                ================================================== --}}

                                <td data-label="Referral Code">

                                    <span class="referral-code">

                                        {{ $referral->code }}

                                    </span>

                                </td>


                                {{-- =================================================
                                    REFERRAL POINT PROVIDE
                                ================================================== --}}

                                <td data-label="Point Provide">

                                    @if ($referral->referral_point_provide === 'yes')
                                        <span class="used-badge yes">
                                            ✓ Yes
                                        </span>
                                    @elseif ($referral->referral_point_provide === 'no')
                                        <span class="used-badge no">
                                            No
                                        </span>
                                    @else
                                        <span class="used-badge no">
                                            —
                                        </span>
                                    @endif

                                </td>


                                {{-- =================================================
                                    POINTS
                                ================================================== --}}

                                <td data-label="Points">

                                    <span class="points-badge">

                                        {{ number_format((float) $referral->points) }}

                                    </span>

                                </td>


                                {{-- =================================================
                                    REFERRAL USER
                                ================================================== --}}

                                <td data-label="Referral User">


                                    @if ($referral->referral_user_id)
                                        <div class="user-info">


                                            <span class="user-name">

                                                {{ $referral->referred_user_name ?? '—' }}

                                            </span>


                                            @if ($referral->referred_user_mobile)
                                                <span class="user-id">

                                                    {{ $referral->referred_user_mobile }}

                                                </span>
                                            @endif


                                            <span class="user-id">

                                                User ID:
                                                {{ $referral->referral_user_id }}

                                            </span>


                                        </div>
                                    @else
                                        <span class="user-id">

                                            Not Used

                                        </span>
                                    @endif


                                </td>


                                {{-- =================================================
                                    USED
                                ================================================== --}}
                                <td data-label="Used">

                                    @if ($referral->used === 'used')
                                        <span class="used-badge yes">
                                            ✓ Used
                                        </span>
                                    @elseif ($referral->used === 'pending')
                                        <span class="used-badge no">
                                            Pending
                                        </span>
                                    @else
                                        <span class="used-badge no">
                                            —
                                        </span>
                                    @endif

                                </td>


                                {{-- =================================================
                                    USED DATE
                                ================================================== --}}

                                <td data-label="Used Date">


                                    @if ($referral->used_date)
                                        <div class="date-info">


                                            <span class="date-main">

                                                {{ \Carbon\Carbon::parse($referral->used_date)->format('d M Y') }}

                                            </span>


                                            <span class="date-time">

                                                {{ \Carbon\Carbon::parse($referral->used_date)->format('h:i A') }}

                                            </span>


                                        </div>
                                    @else
                                        <span class="user-id">

                                            —

                                        </span>
                                    @endif


                                </td>


                                {{-- =================================================
                                    STATUS
                                ================================================== --}}

                                {{-- <td data-label="Status">


                                    @if ($referral->status === 'active')
                                        <span class="status-badge active">

                                            Active

                                        </span>
                                    @else
                                        <span class="status-badge inactive">

                                            Inactive

                                        </span>
                                    @endif


                                </td> --}}


                                {{-- =================================================
                                    CREATED AT
                                ================================================== --}}

                                <td data-label="Created At">

                                    <div class="date-info">


                                        <span class="date-main">

                                            {{ \Carbon\Carbon::parse($referral->created_at)->format('d M Y') }}

                                        </span>


                                        <span class="date-time">

                                            {{ \Carbon\Carbon::parse($referral->created_at)->format('h:i A') }}

                                        </span>


                                    </div>

                                </td>





                            </tr>


                        @empty


                            <tr>

                                <td colspan="13"
                                    style="
                                        text-align:center;
                                        color:var(--ink-faint);
                                        padding:40px;
                                    ">

                                    No referral codes found.

                                </td>

                            </tr>


                        @endforelse


                    </tbody>


                </table>
            </div>


            {{-- =========================================================
                 PAGINATION
            ========================================================== --}}

            <div class="pagination">

                @include('common.pagination.superadmin_pagination', ['pageData' => $referralCodes])

            </div>


        </div>


    </div>

@endsection
