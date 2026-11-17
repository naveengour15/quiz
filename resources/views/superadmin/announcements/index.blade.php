@extends('superadmin.layouts.app')

@section('title', 'Announcement')

<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')

<style>
    .broadcast-card {
        width: 720px;
        max-width: 100%;
        margin-bottom: 18px;
        background: var(--surface);
        border-radius: 16px;
        border: 1px solid var(--line);
        box-shadow: 0 4px 12px rgba(0,0,0,.02);
    }

    .broadcast-form {
        padding: 18px;
    }

    .form-grid-box {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .field textarea {
        min-height: 150px;
    }

    .send-btn-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 5px;
    }

    .announcement-search-filter-box {
        width: 100%;
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .announcement-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    .announcement-filter-btn {
        display: flex;
        gap: 10px;
    }

    .announcement-table-box {
        width: 100%;
        overflow-x: auto;
        background: #fff;
        border-radius: 10px;
    }

    .announcement-table-wrapper {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
    }

    .announcement-table-wrapper th {
        padding: 10px 12px;
        background: #050505;
        color: #fff;
        text-align: left;
        white-space: nowrap;
        font-size: 11px;
    }

    .announcement-table-wrapper td {
        padding: 10px 12px;
        border-bottom: 1px solid #e6e3df;
        font-size: 11px;
        vertical-align: middle;
    }

    .announcement-table-wrapper tbody tr:hover {
        background: #faf9f6;
    }

    .subject-text {
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .announcement-action-buttons {
        display: flex;
        gap: 5px;
        white-space: nowrap;
    }

    /* Recipient Search */

    .recipient-search-wrap {
        position: relative;
    }

    .recipient-results {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        max-height: 230px;
        overflow-y: auto;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(0,0,0,.10);
    }

    .recipient-result {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .recipient-result:last-child {
        border-bottom: 0;
    }

    .recipient-result:hover {
        background: #f7f7f7;
    }

    .recipient-result-name {
        font-size: 13px;
        font-weight: 600;
        color: #222;
    }

    .recipient-result-contact {
        margin-top: 3px;
        font-size: 11px;
        color: #777;
    }

    /* Selected Recipients*/

    .selected-recipient-box {
        display: none;
        margin-top: 10px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 9px;
        background: #fafafa;
    }

    .selected-recipient-title {
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #555;
    }

    .selected-recipient-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .selected-recipient-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        background: #fff;
        border: 1px solid #e7e7e7;
        border-radius: 7px;
    }

    .selected-recipient-info {
        min-width: 0;
    }

    .selected-recipient-name {
        font-size: 12px;
        font-weight: 600;
        color: #222;
    }

    .selected-recipient-contact {
        margin-top: 2px;
        font-size: 10px;
        color: #777;
    }

    .remove-recipient {
        flex-shrink: 0;
        width: 25px;
        height: 25px;
        border: 0;
        border-radius: 50%;
        background: #f1f1f1;
        color: #c00;
        cursor: pointer;
        font-size: 15px;
        line-height: 25px;
    }

    .remove-recipient:hover {
        background: #ffe8e8;
    }

    /* Modal */

    .announcement-popup-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0,0,0,.55);
    }

    .announcement-popup-overlay.show {
        display: flex;
    }

    .announcement-popup {
        width: 650px;
        max-width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }

    .announcement-popup-header {
        display: flex;
        justify-content: space-between;
        padding: 18px 20px;
        border-bottom: 1px solid #e7e4df;
    }

    .announcement-popup-header h3 {
        margin: 0 0 4px;
        font-size: 18px;
    }

    .announcement-popup-header p {
        margin: 0;
        color: #777;
        font-size: 12px;
    }

    .announcement-popup-close {
        border: 0;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
        color: #555;
    }

    .announcement-popup-body {
        padding: 20px;
    }

    .announcement-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .announcement-detail-item {
        padding: 10px;
        background: var(--paper);
        border-radius: 8px;
    }

    .announcement-detail-item.full {
        grid-column: 1 / -1;
    }

    .announcement-detail-label {
        display: block;
        margin-bottom: 6px;
        color: #777;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .announcement-detail-value {
        color: #222;
        font-size: 12px;
    }

    .announcement-message {
        padding: 12px;
        background: #fff;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1.6;
        border: 1px solid #eee;
    }

    .announcement-popup-footer {
        display: flex;
        justify-content: flex-end;
        padding: 14px 20px;
        border-top: 1px solid #e7e4df;
    }

    .popup-close-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 7px;
        background: #fff;
        cursor: pointer;
    }

    .announcement-read-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .announcement-read-item {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 8px;
        background: var(--paper);
    }

    .read-user {
        font-size: 13px;
        font-weight: 600;
    }

    .read-time {
        margin-top: 3px;
        color: #777;
        font-size: 11px;
    }

    .read-status {
        color: #16803c;
        font-size: 11px;
        font-weight: 700;
    }

    @media (max-width: 1199px) {
        .announcement-search-filter-box {
            flex-direction: column;
        }

        .announcement-search-filter-wrapper {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 991px) {
        .announcement-table-box {
            overflow: visible;
            background: transparent;
        }

        .announcement-table-wrapper,
        .announcement-table-wrapper tbody,
        .announcement-table-wrapper tr,
        .announcement-table-wrapper td {
            display: block;
            width: 100%;
        }

        .announcement-table-wrapper {
            min-width: 0;
        }

        .announcement-table-wrapper thead {
            display: none;
        }

        .announcement-table-wrapper tbody {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .announcement-table-wrapper tbody tr {
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #e8e5e0;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        .announcement-table-wrapper tbody td {
            position: relative;
            display: flex;
            justify-content: flex-end;
            min-height: 38px;
            padding: 8px 0 8px 42%;
            border-bottom: 1px solid #eeeae5;
            text-align: right;
        }

        .announcement-table-wrapper tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 38%;
            color: #777;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
        }

        .announcement-table-wrapper tbody td:last-child {
            border-bottom: 0;
        }

        .announcement-action-buttons {
            justify-content: flex-end;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 767px) {
        .announcement-search-filter-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }

        .announcement-detail-grid {
            grid-template-columns: 1fr;
        }

        .announcement-detail-item.full {
            grid-column: auto;
        }
    }

    @media (max-width: 575px) {
        .announcement-search-filter-wrapper {
            grid-template-columns: 1fr;
        }

        .announcement-read-list {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="hr-communication-page">

    <div class="page-head">
        <div> <h1>Announcement</h1>  </div>
    </div>

    {{--   SEND ANNOUNCEMENT--}}
    <div class="hr-card broadcast-card">

        <div class="fh" style="padding:15px 18px;border-bottom:1px solid var(--line);">
            <h3>Send Announcement Message</h3>
        </div>

        <form class="broadcast-form" id="announcementForm"  method="POST"action="{{ route('superadmin.announcements.store') }}" >
            @csrf
            <div class="form-grid-box">
                {{-- Subject --}}
                <div class="field">
                    <label>Subject</label>
                    <input type="text"name="subject"class="form-control" placeholder="Message subject" required>
                </div>

                {{-- Recipient Type --}}
                <div class="field">
                    <label>Recipient Type</label>
                    <select name="recipient_type"  id="recipientType" class="form-control" required>
                        <option value="">  Select Recipient Type </option>
                        <option value="all_user"> All Users </option>
                        {{-- <option value="all_retailer"> All Retailers</option> --}}
                        <option value="individual_user"> Individual User </option>
                        {{-- <option value="individual_retailer"> Individual Retailer </option> --}}
                    </select>
                </div>

                {{-- INDIVIDUAL RECIPIENT --}}

                <div class="field" id="individualRecipientBox"style="display:none;">

                    <label id="recipientLabel">  Select Recipient </label>
                    <div class="recipient-search-wrap">
                        <input type="text" id="recipientSearch"class="form-control"placeholder="Search..."autocomplete="off">
                        <div id="recipientResults"class="recipient-results"></div>
                    </div>

                    {{-- Selected List --}}
                    <div id="selectedRecipientBox" class="selected-recipient-box">
                        <div class="selected-recipient-title">Selected Recipients</div>
                        <div id="selectedRecipientList" class="selected-recipient-list"></div>
                    </div>
                </div>

                {{-- Message --}}
                <div class="field">
                    <label>Message</label>
                    <textarea name="message" class="form-control" placeholder="Type your message here..." required></textarea>
                </div>


                {{-- Button --}}
                <div class="send-btn-wrap">
                    <button type="submit"class="btn btn-primary"id="sendAnnouncementBtn">
                        <span>📧</span> Send Message
                    </button>
                </div>
            </div>
        </form>
    </div>


    {{-- FILTER--}}

    <div class="search-filter-box">
        <form method="GET" class="toolbar">
            <div class="announcement-search-filter-box">
                <div class="announcement-search-filter-wrapper">
                    <div class="input-field">
                        <label class="lbl"> Search </label>
                        <input class="tb-input"  name="search" value="{{ request('search') }}" placeholder="Search subject">
                    </div>

                    <div class="input-field">
                        <label class="lbl"> Recipient Type </label>

                        <select class="tb-select" name="recipient">

                            <option value=""> All </option>
                            <option value="all_user" @selected(request('recipient') === 'all_user')> All Users </option>
                            {{-- <option value="all_retailer" @selected(request('recipient') === 'all_retailer')> All Retailers </option> --}}
                            <option value="individual_user" @selected(request('recipient') === 'individual_user')> Individual User </option>
                            {{-- <option value="individual_retailer" @selected(request('recipient') === 'individual_retailer')> Individual Retailer </option> --}}
                        </select>
                    </div>

                    <div class="input-field">
                        <label class="lbl">  Status </label>
                        <select class="tb-select" name="status">
                            <option value=""> All Statuses </option>
                            <option value="send" @selected(request('status') === 'send')> Send </option>
                        </select>
                    </div>

                    <div class="input-field">
                        <label class="lbl"> From Date </label>
                        <input type="date" class="tb-input" name="from_date" value="{{ request('from_date') }}">
                    </div>

                    <div class="input-field">
                        <label class="lbl"> To Date </label>
                        <input type="date" class="tb-input" name="to_date" value="{{ request('to_date') }}">
                    </div>
                </div>


                <div class="announcement-filter-btn">
                    <button class="btn btn-primary" type="submit"> Filter</button>
                    <a href="{{ route('superadmin.announcements.index') }}" class="btn btn-ghost"> Reset </a>
                </div>
            </div>
        </form>
    </div>


    {{--  TABLE --}}

    <div class="table-wrap table-wrap-res-01">
        <div class="announcement-table-box">
            <table class="announcement-table-wrapper">
                <thead>
                    <tr>
                        <th>Date Sent</th>
                        <th>Subject</th>
                        <th>Recipient Type</th>
                        <th>Sent By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                @forelse($announcements as $announcement)

                    @php

                        $userCount = is_array($announcement->user_id)
                            ? count($announcement->user_id)
                            : 0;

                        $retailerCount = is_array($announcement->tenant_id)
                            ? count($announcement->tenant_id)
                            : 0;

                        $recipientText = match ($announcement->recipient_type) {

                            'all_user' =>
                                'All Users',

                            // 'all_retailer' =>
                            //     'All Retailers',

                            'individual_user' =>
                                'Individual Users (' . $userCount . ')',

                            // 'individual_retailer' =>
                            //     'Individual Retailers (' . $retailerCount . ')',

                            default =>
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $announcement->recipient_type
                                    )
                                ),
                        };

                    @endphp

                    <tr>
                        <td data-label="Date Sent"> {{ $announcement->created_at?->format('d M Y, h:i A') }}</td>
                        <td data-label="Subject">
                            <div class="subject-text"> {{ $announcement->subject ?? '-' }}</div>
                        </td>
                        <td data-label="Recipient Type">
                            <span class="badge warn">{{ $recipientText }}</span>
                        </td>

                        <td data-label="Sent By">
                            {{ $announcement->sender_name ?? 'Super Admin' }}
                        </td>

                        <td data-label="Status">
                            <span class="badge success">{{ ucfirst($announcement->status ?? '-') }}</span>
                        </td>

                        <td data-label="Actions">
                            <div class="announcement-action-buttons">
                                <button type="button" class="btn btn-ghost" onclick="openDetailsModal({{ $announcement->id }})">
                                    View Details
                                </button>

                                <button type="button" class="btn btn-ghost" onclick="openReadsModal({{ $announcement->id }})">
                                    View Reads
                                </button>
                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="6" style="text-align:center;padding:30px;color:#777;"> No announcements found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($announcements->hasPages())
            <div style="margin-top:15px;">{{ $announcements->links() }}</div>
        @endif
    </div>
</div>

{{-- DETAILS MODAL--}}

<div class="announcement-popup-overlay" id="detailsModal" >
    <div class="announcement-popup">
        <div class="announcement-popup-header">
            <div>
                <h3>Announcement Details </h3>
                <p>  Complete announcement information </p>
            </div>
            <button type="button" class="announcement-popup-close" onclick="closeDetailsModal()"> &times; </button>
        </div>


        <div class="announcement-popup-body" id="detailsModalBody">
            <div style="text-align:center;padding:30px;"> Loading...</div>
        </div>

        <div class="announcement-popup-footer">
            <button type="button" class="popup-close-btn" onclick="closeDetailsModal()" > Close </button>
        </div>
    </div>
</div>


{{-- READS MODAL--}}

<div class="announcement-popup-overlay" id="readsModal">

    <div class="announcement-popup">
        <div class="announcement-popup-header">
            <div>
                <h3> Message Reads</h3>
                <p> Users who viewed this message </p>
            </div>
            <button type="button" class="announcement-popup-close"onclick="closeReadsModal()"> &times;</button>
        </div>

        <div class="announcement-popup-body" id="readsModalBody">
            <div style="text-align:center;padding:30px;">  Loading...</div>
        </div>

        <div class="announcement-popup-footer">

            <button type="button" class="popup-close-btn" onclick="closeReadsModal()" >
                Close
            </button>
        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const recipientType =
        document.getElementById('recipientType');

    const recipientBox =
        document.getElementById('individualRecipientBox');

    const recipientLabel =
        document.getElementById('recipientLabel');

    const recipientSearch =
        document.getElementById('recipientSearch');

    const recipientResults =
        document.getElementById('recipientResults');

    const selectedRecipientBox =
        document.getElementById('selectedRecipientBox');

    const selectedRecipientList =
        document.getElementById('selectedRecipientList');

    const form =
        document.getElementById('announcementForm');

    const sendButton =
        document.getElementById('sendAnnouncementBtn');


    let searchTimer = null;

    let selectedRecipients = [];


    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value ?? '';

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    function resetRecipients() {

        selectedRecipients = [];

        recipientSearch.value = '';

        recipientResults.innerHTML = '';

        recipientResults.style.display = 'none';

        renderSelectedRecipients();
    }


    /*
    |--------------------------------------------------------------------------
    | Render Selected
    |--------------------------------------------------------------------------
    */

    function renderSelectedRecipients() {

        selectedRecipientList.innerHTML = '';

        if (!selectedRecipients.length) {

            selectedRecipientBox.style.display = 'none';

            return;
        }

        selectedRecipientBox.style.display = 'block';


        selectedRecipients.forEach(function (item) {

            const row =
                document.createElement('div');

            row.className =
                'selected-recipient-item';


            row.innerHTML = `

                <div class="selected-recipient-info">

                    <div class="selected-recipient-name">
                        ${escapeHtml(item.name)}
                    </div>

                    <div class="selected-recipient-contact">
                        ${escapeHtml(item.contact)}
                    </div>

                </div>

                <button
                    type="button"
                    class="remove-recipient"
                    data-id="${item.id}"
                >
                    ×
                </button>

            `;


            row.querySelector(
                '.remove-recipient'
            ).addEventListener(
                'click',
                function () {

                    selectedRecipients =
                        selectedRecipients.filter(
                            function (selected) {
                                return String(selected.id) !==
                                    String(item.id);
                            }
                        );

                    renderSelectedRecipients();
                }
            );


            selectedRecipientList.appendChild(row);

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Recipient Type
    |--------------------------------------------------------------------------
    */

    recipientType.addEventListener(
        'change',
        function () {

            const type = this.value;

            resetRecipients();


            if (
                type === 'individual_user' ||
                type === 'individual_retailer'
            ) {

                recipientBox.style.display =
                    'block';


                if (type === 'individual_user') {

                    recipientLabel.innerText =
                        'Select Users';

                    recipientSearch.placeholder =
                        'Search user by name, email or mobile...';

                } else {

                    recipientLabel.innerText =
                        'Select Retailers';

                    recipientSearch.placeholder =
                        'Search retailer by name, email or phone...';
                }

            } else {

                recipientBox.style.display =
                    'none';
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    recipientSearch.addEventListener(
        'input',
        function () {

            const search =
                this.value.trim();

            const type =
                recipientType.value;


            if (
                type !== 'individual_user' &&
                type !== 'individual_retailer'
            ) {
                return;
            }


            clearTimeout(searchTimer);


            if (!search) {

                recipientResults.innerHTML = '';

                recipientResults.style.display =
                    'none';

                return;
            }


            searchTimer = setTimeout(
                function () {

                    const url =
                        type === 'individual_user'
                            ? "{{ route('superadmin.announcements.search-users') }}"
                            : "{{ route('superadmin.announcements.search-retailers') }}";


                    fetch(
                        url +
                        '?search=' +
                        encodeURIComponent(search),
                        {
                            method: 'GET',

                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    )
                    .then(function (response) {

                        if (!response.ok) {
                            throw new Error(
                                'Unable to search recipient.'
                            );
                        }

                        return response.json();
                    })
                    .then(function (result) {

                        recipientResults.innerHTML = '';


                        if (
                            !result.success ||
                            !Array.isArray(result.data) ||
                            !result.data.length
                        ) {

                            recipientResults.innerHTML = `
                                <div style="
                                    padding:12px;
                                    color:#777;
                                    font-size:12px;
                                ">
                                    No result found
                                </div>
                            `;

                            recipientResults.style.display =
                                'block';

                            return;
                        }


                        result.data.forEach(
                            function (item) {

                                const alreadySelected =
                                    selectedRecipients.some(
                                        function (selected) {
                                            return String(selected.id) ===
                                                String(item.id);
                                        }
                                    );


                                if (alreadySelected) {
                                    return;
                                }


                                const div =
                                    document.createElement('div');

                                div.className =
                                    'recipient-result';


                                const contact =
                                    item.email ||
                                    item.mobile ||
                                    item.phone ||
                                    '';


                                div.innerHTML = `

                                    <div class="recipient-result-name">
                                        ${escapeHtml(item.name || 'Unnamed')}
                                    </div>

                                    <div class="recipient-result-contact">
                                        ${escapeHtml(contact)}
                                    </div>

                                `;


                                div.addEventListener(
                                    'click',
                                    function () {

                                        selectedRecipients.push({

                                            id: item.id,

                                            name:
                                                item.name ||
                                                'Unnamed',

                                            contact:
                                                contact

                                        });


                                        renderSelectedRecipients();


                                        recipientSearch.value =
                                            '';

                                        recipientResults.innerHTML =
                                            '';

                                        recipientResults.style.display =
                                            'none';

                                    }
                                );


                                recipientResults.appendChild(div);

                            }
                        );


                        if (!recipientResults.children.length) {

                            recipientResults.innerHTML = `
                                <div style="
                                    padding:12px;
                                    color:#777;
                                    font-size:12px;
                                ">
                                    All searched recipients are already selected.
                                </div>
                            `;
                        }


                        recipientResults.style.display =
                            'block';

                    })
                    .catch(function (error) {

                        console.error(
                            'Recipient search error:',
                            error
                        );

                        recipientResults.innerHTML = `
                            <div style="
                                padding:12px;
                                color:#c00;
                                font-size:12px;
                            ">
                                Unable to search recipient.
                            </div>
                        `;

                        recipientResults.style.display =
                            'block';
                    });

                },
                300
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Submit
    |--------------------------------------------------------------------------
    */

    form.addEventListener(
        'submit',
        function (e) {

            e.preventDefault();


            const type =
                recipientType.value;


            if (!type) {

                alert(
                    'Please select recipient type.'
                );

                return;
            }


            if (
                type === 'individual_user' ||
                type === 'individual_retailer'
            ) {

                if (!selectedRecipients.length) {

                    alert(
                        type === 'individual_user'
                            ? 'Please select at least one user.'
                            : 'Please select at least one retailer.'
                    );

                    return;
                }
            }


            const formData =
                new FormData(form);


            /*
            |--------------------------------------------------------------------------
            | Remove old recipient_ids
            |--------------------------------------------------------------------------
            */

            formData.delete('recipient_ids[]');


            /*
            |--------------------------------------------------------------------------
            | Add Multiple IDs
            |--------------------------------------------------------------------------
            */

            selectedRecipients.forEach(
                function (item) {

                    formData.append(
                        'recipient_ids[]',
                        item.id
                    );

                }
            );


            sendButton.disabled =
                true;

            sendButton.innerHTML =
                '<span>⏳</span> Sending...';


            fetch(
                form.action,
                {
                    method: 'POST',

                    body: formData,

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            )
            .then(async function (response) {

                const result =
                    await response.json();

                if (!response.ok) {

                    throw new Error(
                        result.message ||
                        'Unable to send announcement.'
                    );
                }

                return result;

            })
            .then(function (result) {

                if (!result.success) {

                    throw new Error(
                        result.message ||
                        'Unable to send announcement.'
                    );
                }


                alert(
                    result.message ||
                    'Announcement sent successfully.'
                );


                window.location.reload();

            })
            .catch(function (error) {

                console.error(
                    'Announcement submit error:',
                    error
                );

                alert(
                    error.message ||
                    'Something went wrong.'
                );

            })
            .finally(function () {

                sendButton.disabled =
                    false;

                sendButton.innerHTML =
                    '<span>📧</span> Send Message';
            });

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Close Search Dropdown
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function (e) {

            if (
                !recipientBox.contains(e.target)
            ) {

                recipientResults.style.display =
                    'none';
            }
        }
    );

});


/*
|--------------------------------------------------------------------------
| Details Modal
|--------------------------------------------------------------------------
*/

function openDetailsModal(id)
{
    document
        .getElementById('readsModal')
        .classList.remove('show');

    document
        .getElementById('detailsModal')
        .classList.add('show');

    document.body.style.overflow = 'hidden';

    const body =
        document.getElementById('detailsModalBody');

    body.innerHTML = `
        <div style="text-align:center;padding:30px;">
            Loading...
        </div>
    `;


    fetch(
        "{{ url('superadmin/announcements') }}/" + id,
        {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }
    )
    .then(function (response) {
        return response.json();
    })
    .then(function (result) {

        if (!result.success) {
            throw new Error('Unable to load details.');
        }


        const data =
            result.data;

        let recipients = '';


        if (
            data.recipient_type === 'individual_user'
        ) {

            recipients =
                result.users.map(
                    function (user) {

                        return `
                            <div style="margin-bottom:5px;">
                                <strong>
                                    ${escapeHtmlGlobal(user.name)}
                                </strong>
                                ${
                                    user.mobile
                                        ? ' - ' + escapeHtmlGlobal(user.mobile)
                                        : ''
                                }
                            </div>
                        `;
                    }
                ).join('');

        } else if (
            data.recipient_type === 'individual_retailer'
        ) {

            recipients =
                result.retailers.map(
                    function (retailer) {

                        return `
                            <div style="margin-bottom:5px;">
                                <strong>
                                    ${escapeHtmlGlobal(retailer.name)}
                                </strong>
                                ${
                                    retailer.phone
                                        ? ' - ' + escapeHtmlGlobal(retailer.phone)
                                        : ''
                                }
                            </div>
                        `;
                    }
                ).join('');

        } else {

            recipients =
                data.recipient_type === 'all_user'
                    ? 'All Users'
                    : 'All Retailers';
        }


        body.innerHTML = `

            <div class="announcement-detail-grid">

                <div class="announcement-detail-item">

                    <span class="announcement-detail-label">
                        Date Sent
                    </span>

                    <div class="announcement-detail-value">
                        ${
                            data.created_at
                                ? new Date(data.created_at)
                                    .toLocaleString()
                                : '-'
                        }
                    </div>

                </div>


                <div class="announcement-detail-item">

                    <span class="announcement-detail-label">
                        Sent By
                    </span>

                    <div class="announcement-detail-value">
                        ${escapeHtmlGlobal(
                            data.sender_name || 'Super Admin'
                        )}
                    </div>

                </div>


                <div class="announcement-detail-item full">

                    <span class="announcement-detail-label">
                        Subject
                    </span>

                    <div class="announcement-detail-value">
                        ${escapeHtmlGlobal(data.subject)}
                    </div>

                </div>


                <div class="announcement-detail-item full">

                    <span class="announcement-detail-label">
                        Recipients
                    </span>

                    <div class="announcement-detail-value">
                        ${recipients || '-'}
                    </div>

                </div>


                <div class="announcement-detail-item">

                    <span class="announcement-detail-label">
                        Status
                    </span>

                    <div class="announcement-detail-value">

                        <span class="badge success">
                            ${escapeHtmlGlobal(data.status)}
                        </span>

                    </div>

                </div>


                <div class="announcement-detail-item full">

                    <span class="announcement-detail-label">
                        Message
                    </span>

                    <div class="announcement-message">
                        ${escapeHtmlGlobal(data.message)}
                    </div>

                </div>

            </div>
        `;

    })
    .catch(function (error) {

        body.innerHTML = `
            <div style="
                text-align:center;
                padding:30px;
                color:#c00;
            ">
                ${escapeHtmlGlobal(error.message)}
            </div>
        `;
    });
}


function closeDetailsModal()
{
    document
        .getElementById('detailsModal')
        .classList.remove('show');

    document.body.style.overflow = '';
}


/*
|--------------------------------------------------------------------------
| Reads Modal
|--------------------------------------------------------------------------
*/

function openReadsModal(id)
{
    document
        .getElementById('detailsModal')
        .classList.remove('show');

    document
        .getElementById('readsModal')
        .classList.add('show');

    document.body.style.overflow = 'hidden';

    const body =
        document.getElementById('readsModalBody');

    body.innerHTML = `
        <div style="text-align:center;padding:30px;">
            Loading...
        </div>
    `;


    fetch(
        "{{ url('superadmin/announcements') }}/" +
        id +
        "/reads",
        {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }
    )
    .then(function (response) {
        return response.json();
    })
    .then(function (result) {

        if (
            !result.success ||
            !result.data.length
        ) {

            body.innerHTML = `
                <div style="
                    text-align:center;
                    padding:30px;
                    color:#777;
                ">
                    No reads yet.
                </div>
            `;

            return;
        }


        body.innerHTML =
            '<div class="announcement-read-list">' +
            result.data.map(
                function (read) {

                    return `

                        <div class="announcement-read-item">

                            <div>

                                <div class="read-user">
                                    ${
                                        escapeHtmlGlobal(
                                            read.user?.name ||
                                            'User'
                                        )
                                    }
                                </div>

                                <div class="read-time">
                                    ${
                                        read.read_at
                                            ? new Date(
                                                read.read_at
                                            ).toLocaleString()
                                            : '-'
                                    }
                                </div>

                            </div>

                            <span class="read-status">
                                Read
                            </span>

                        </div>
                    `;
                }
            ).join('') +
            '</div>';

    })
    .catch(function () {

        body.innerHTML = `
            <div style="
                text-align:center;
                padding:30px;
                color:#c00;
            ">
                Unable to load reads.
            </div>
        `;
    });
}


function closeReadsModal()
{
    document
        .getElementById('readsModal')
        .classList.remove('show');

    document.body.style.overflow = '';
}


/*
|--------------------------------------------------------------------------
| Global Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtmlGlobal(value)
{
    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| Outside Click
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'click',
    function (e) {

        const details =
            document.getElementById('detailsModal');

        const reads =
            document.getElementById('readsModal');


        if (e.target === details) {
            closeDetailsModal();
        }

        if (e.target === reads) {
            closeReadsModal();
        }
    }
);


/*
|--------------------------------------------------------------------------
| ESC
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (e) {

        if (e.key === 'Escape') {

            closeDetailsModal();

            closeReadsModal();
        }
    }
);
</script>

@endsection