@extends('superadmin.layouts.app')

@section('title', 'Quiz Notification History')

@section('content')

    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    @push('styles')
        <style>
            /*   Notification Message*/
            .notification-message {
                line-height: 1.5;
                word-break: break-word;
                white-space: normal;
            }

            /* Status*/

            .notification-status {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 5px 11px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: capitalize;
                white-space: nowrap;
            }

            .notification-status.sent {
                background: #dcfce7;
                color: #166534;
            }

            .notification-status.pending {
                background: #fef3c7;
                color: #92400e;
            }

            .notification-status.failed {
                background: #fee2e2;
                color: #991b1b;
            }

            .notification-status.default {
                background: #e5e7eb;
                color: #374151;
            }

            /*Empty */
            .notification-empty {
                text-align: center;
                color: var(--ink-faint);
                padding: 35px !important;
            }

            /*  Date Time*/
            .notification-date {
                white-space: nowrap;
            }

            /*   Responsive*/

            @media(max-width: 991px) {
                .notification-message {
                    max-width: 300px;
                    min-width: 180px;
                }
            }

            @media(max-width: 575px) {
                .notification-message {
                    max-width: 100%;
                    min-width: auto;
                }

                .notification-date {
                    white-space: normal;
                }
            }


            /* ================= Status Change Modal ================= */

            .status-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                padding: 20px;
            }

            .status-modal-overlay.active {
                display: flex;
            }

            .status-modal {
                width: 100%;
                max-width: 500px;
                background: var(--paper);
                border-radius: 14px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.20);
                overflow: hidden;
            }

            .status-modal-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 15px;
                padding: 20px;
                border-bottom: 1px solid var(--line);
            }

            .status-modal-header h2 {
                margin: 0;
                font-size: 18px;
            }

            .status-modal-header p {
                margin: 5px 0 0;
                color: var(--ink-faint);
                font-size: 12px;
            }

            .status-modal-close {
                border: none;
                background: transparent;
                font-size: 26px;
                line-height: 1;
                cursor: pointer;
                color: var(--ink);
            }

            .status-modal-body {
                padding: 20px;
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .status-modal-body .input-field {
                width: 100%;
            }

            .status-modal-body .tb-select {
                width: 100%;
            }

            .status-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 16px 20px;
                border-top: 1px solid var(--line);
            }

            @media(max-width: 575px) {

                .status-modal {
                    max-width: 100%;
                }

                .status-modal-footer {
                    flex-direction: column-reverse;
                }

                .status-modal-footer .btn {
                    width: 100%;
                }
            }
        </style>
    @endpush

    <div class="panel active">
        {{-- ========================= Header ========================= --}}
        <div class="page-head"
            style=" display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">
            <div>
                <h1>Quiz Notification History</h1>
                <p>View quiz notification history. </p>
            </div>
            <div style="    gap: 10px;
    display: flex;">
                <button type="button" class="btn btn-primary" onclick="openCreateLeadsModal()"> Create Leads </button>
                <button type="button" class="btn btn-primary" onclick="openStatusModal()"> Change Notification
                    Status</button>
            </div>

        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">

                    <div class="users-search-filter-wrapper">

                        {{-- Search --}}
                        <div class="input-field">
                            <label class="lbl">Search</label>
                            <input type="text" name="search" class="tb-input search-field"
                                value="{{ request('search') }}" placeholder="Quiz / User / Message">
                        </div>

                        {{-- Status --}}
                        <div class="input-field">
                            <label class="lbl">Status</label>
                            <select name="status" class="tb-select status-field">
                                <option value="">All Status</option>
                                <option value="sent" @selected(request('status') == 'sent')>Sent</option>
                                <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                                <option value="failed" @selected(request('status') == 'failed')>Failed</option>
                            </select>
                        </div>



                        {{-- From Date --}}
                        <div class="input-field">
                            <label class="lbl">From Date</label>
                            <input type="date" name="from_date" class="tb-input" value="{{ request('from_date') }}">
                        </div>

                        {{-- To Date --}}
                        <div class="input-field">
                            <label class="lbl">To Date</label>
                            <input type="date" name="to_date" class="tb-input" value="{{ request('to_date') }}">
                        </div>

                    </div>

                    <div class="users-filter-btn">
                        <button type="submit" class="btn btn-primary"> Filter </button>

                        <a href="{{ route('superadmin.notifications.quizHistory') }}" class="btn btn-ghost">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>
        {{-- ========================= Notification History Table ========================= --}}
        <div class="table-wrap table-wrap-res-01">
            <div style="overflow-x:auto;">
                <table class="retailer-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Quiz Title</th>
                            <th>User Name</th>
                            <th>Quiz Schedule Time</th>
                            <th>Activation Date/Time</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Sent Date/Time</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($notifications as $notification)
                            <tr>

                                <td data-label="#">{{ $notifications->firstItem() + $loop->index }}</td>

                                {{-- <td data-label="Quiz Title">{{ $notification->quiz_name ?? '-' }} </td> --}}

                                <td data-label="Quiz Title">{{ $notification->quiz->title ?? '-' }} </td>

                                <td data-label="User Name">{{ $notification->user_name ?? '-' }}</td>

                                <td data-label="Quiz Schedule Time">
                                    <span class="notification-date">
                                        @if ($notification->quiz_schedule_time)
                                            {{ \Carbon\Carbon::parse($notification->quiz_schedule_time)->format('d M, Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>

                                <td data-label="Activation Date/Time">
                                    <span class="notification-date">
                                        @if ($notification->notificationactiv_date_time)
                                            {{ \Carbon\Carbon::parse($notification->notificationactiv_date_time)->format('d M, Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>

                                {{--  Status --}}

                                <td data-label="Status">
                                    @php
                                        $status = strtolower($notification->status ?? '');
                                    @endphp
                                    @if ($status === 'sent')
                                        <span class="notification-status sent">
                                            Sent
                                        </span>
                                    @elseif($status === 'pending')
                                        <span class="notification-status pending">
                                            Pending
                                        </span>
                                    @elseif($status === 'failed')
                                        <span class="notification-status failed">Failed</span>
                                    @else
                                        <span class="notification-status default">{{ $notification->status ?? '-' }}</span>
                                    @endif
                                </td>

                                {{--  Message --}}

                                <td data-label="Message">
                                    <div class="notification-message"> {{ $notification->msg ?? '-' }} </div>
                                </td>
                                {{--   Sent Date/Time --}}

                                <td data-label="Sent Date/Time">
                                    <span class="notification-date">
                                        @if ($notification->sent_date_time)
                                            {{ \Carbon\Carbon::parse($notification->sent_date_time)->format('d M, Y h:i A') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="notification-empty">
                                    No notification history found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ========================= Pagination ========================= --}}
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $notifications])
            </div>
        </div>
    </div>

    <!-- ================= Status Change Modal ================= -->

    <div id="notificationStatusModal" class="status-modal-overlay">
        <div class="status-modal">
            <div class="status-modal-header">
                <div>
                    <h2>Change Notification Status</h2>
                    <p>Update notification status in bulk.</p>
                </div>
                <button type="button" class="status-modal-close" onclick="closeStatusModal()"> &times;</button>
            </div>
            <form method="POST" action="{{ route('superadmin.notifications.quizHistory.updateStatus') }}">
                @csrf
                <div class="status-modal-body">
                    {{-- Quiz --}}
                    <div class="input-field">
                        <label>Quiz<span class="req">*</span></label>
                        <select name="quiz_id" class="tb-select" required>
                            <option value="all"> All Quiz</option>
                            @foreach ($quizzes as $quiz)
                                <option value="{{ $quiz->quiz_id }}">
                                    {{ $quiz->quiz_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Current Status --}}
                    <div class="input-field">
                        <label> Current Status <span class="req">*</span> </label>
                        <select name="current_status" class="tb-select" required>
                            <option value=""> Select Current Status </option>
                            <option value="sent"> Sent </option>
                            <option value="pending"> Pending </option>
                            <option value="failed"> Failed </option>
                        </select>
                    </div>

                    {{-- New Status --}}
                    <div class="input-field">
                        <label> Change To Status <span class="req">*</span> </label>
                        <select name="new_status" class="tb-select" required>
                            <option value=""> Select New Status </option>
                            <option value="sent"> Sent </option>
                            <option value="pending"> Pending </option>
                            <option value="failed"> Failed </option>
                        </select>
                    </div>
                </div>
                <div class="status-modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeStatusModal()"> Cancel </button>
                    <button type="submit" class="btn btn-primary"> Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= Create Leads Modal ================= -->

    <div id="createLeadsModal" class="status-modal-overlay">
        <div class="status-modal">
            {{-- ================= Modal Header ================= --}}
            <div class="status-modal-header">
                <div>
                    <h2>Create Leads</h2>
                    <p> Create leads from quiz notification users. </p>
                </div>
                <button type="button" class="status-modal-close" onclick="closeCreateLeadsModal()"> &times; </button>
            </div>

            {{-- ================= Form ================= --}}
            <form method="POST" action="{{ route('superadmin.notifications.quizHistory.createLeads') }}">
                @csrf
                <div class="status-modal-body">
                    {{-- ================= Quiz ================= --}}
                    <div class="input-field">
                        <label> Quiz <span class="req">*</span> </label>
                        <select name="quiz_id" id="leadQuizSelect" class="tb-select" required>
                            <option value=""> Select Quiz </option>
                            @foreach ($quizzes as $quiz)
                                <option value="{{ $quiz->quiz_id }}">
                                    {{ $quiz->quiz_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- ================= Category ================= --}}
                    <div class="input-field">
                        <label> Category <span class="req">*</span> </label>
                        <input type="text" name="category" class="tb-input" maxlength="100"
                            placeholder="Enter category" required>

                    </div>


                    {{-- ================= Lead Type ================= --}}
                    <div class="input-field">
                        <label> Lead Type <span class="req">*</span> </label>
                        <select name="leadtype" class="tb-select" required>
                            <option value=""> Select Lead Type </option>
                            <option value="customer"> Customer </option>
                            <option value="retailer"> Retailer </option>
                            <option value="other"> Other </option>
                        </select>
                    </div>
                </div>

                {{-- ================= Footer ================= --}}
                <div class="status-modal-footer">

                       <button type="submit"
                                class="btn btn-primary"
                                formaction="{{ route('superadmin.notifications.quizHistory.downloadLeadsExcel') }}"
                                formmethod="POST">
                            Download Excel
                        </button>
                    <button type="button" class="btn btn-ghost" onclick="closeCreateLeadsModal()"> Cancel </button>
                    <button type="submit" class="btn btn-primary"> Create Leads </button>
                </div>
            </form>
        </div>
    </div>

@endsection
<script>
    function openStatusModal() {
        document.getElementById('notificationStatusModal').classList.add('active');
    }

    function closeStatusModal() {
        document.getElementById('notificationStatusModal').classList.remove('active');
    }

    // Modal ke bahar click karne par close
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('notificationStatusModal');
        if (
            event.target === modal
        ) {
            closeStatusModal();
        }
    });

    function openCreateLeadsModal() {
        document.getElementById('createLeadsModal').classList.add('active');
    }

    function closeCreateLeadsModal() {
        document.getElementById('createLeadsModal').classList.remove('active');
    }


    // Modal ke bahar click karne par close
    document.addEventListener('click', function(event) {
        const statusModal = document.getElementById('notificationStatusModal');
        const createLeadsModal = document.getElementById('createLeadsModal');

        if (
            statusModal &&
            event.target === statusModal
        ) {
            closeStatusModal();
        }

        if (
            createLeadsModal &&
            event.target === createLeadsModal
        ) {
            closeCreateLeadsModal();
        }
    });
</script>
