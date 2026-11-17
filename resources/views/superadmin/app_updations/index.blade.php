@extends('superadmin.layouts.app')

@section('title', 'App Updation')

<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    .app-updation-search-filter-box {
        width: 100%;
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .app-updation-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    .app-updation-filter-btn {
        display: flex;
        gap: 10px;
    }

    .app-updation-table-box {
        width: 100%;
        overflow-x: auto;
        background: #fff;
        border-radius: 10px;
    }

    .app-updation-table-wrapper {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
    }

    .app-updation-table-wrapper th {
        padding: 10px 12px;
        background: #050505;
        color: #fff;
        text-align: left;
        white-space: nowrap;
        font-size: 11px;
    }

    .app-updation-table-wrapper td {
        padding: 10px 12px;
        border-bottom: 1px solid #e6e3df;
        font-size: 11px;
        vertical-align: middle;
    }

    .app-updation-table-wrapper tbody tr:hover {
        background: #faf9f6;
    }

    .subject-text {
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .app-updation-action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .recipient-search-wrap {
        position: relative;
    }

    .recipient-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        margin-top: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 100;
        display: none;
        box-shadow: 0 4px 10px rgba(0,0,0,.08);
    }

    .recipient-result {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
        font-size: 12px;
    }

    .recipient-result:hover {
        background: #f8f9fa;
    }

    .recipient-result-name {
        font-weight: 600;
        color: #333;
    }

    .recipient-result-contact {
        font-size: 11px;
        color: #777;
    }

    .selected-recipient-box {
        margin-top: 10px;
        display: none;
    }

    .selected-recipient-title {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
    }

    .selected-recipient-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .selected-recipient-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 11px;
    }

    .selected-recipient-item .remove-recipient {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 13px;
        padding: 0 2px;
        line-height: 1;
    }

    .app-updation-popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .app-updation-popup-overlay.show {
        display: flex;
    }

    .app-updation-popup {
        background: #fff;
        width: 600px;
        max-width: 90%;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .app-updation-popup-header {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
    }

    .app-updation-popup-header h3 {
        margin: 0;
        font-size: 16px;
    }

    .app-updation-popup-header p {
        margin: 2px 0 0 0;
        font-size: 12px;
        color: #666;
    }

    .app-updation-popup-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #999;
    }

    .app-updation-popup-body {
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
    }

    .app-updation-popup-footer {
        padding: 12px 20px;
        text-align: right;
        border-top: 1px solid #eee;
        background: #f9fafb;
    }

    .popup-close-btn {
        padding: 6px 16px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background: #fff;
        cursor: pointer;
        font-size: 12px;
    }

    .app-updation-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .app-updation-detail-item.full {
        grid-column: 1 / -1;
    }

    .app-updation-detail-label {
        font-size: 11px;
        color: #888;
        display: block;
        margin-bottom: 2px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .app-updation-detail-value {
        font-size: 13px;
        color: #222;
        font-weight: 500;
    }

    .app-updation-message {
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        white-space: pre-wrap;
        font-size: 13px;
        line-height: 1.5;
    }

    .app-updation-read-list {
        display: grid;
        gap: 8px;
    }

    .app-updation-read-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
    }

    .read-user {
        font-weight: 600;
        font-size: 13px;
        color: #1e293b;
    }

    .read-time {
        font-size: 11px;
        color: #64748b;
    }

    .read-status {
        font-size: 11px;
        font-weight: 700;
        color: #10b981;
        background: #ecfdf5;
        padding: 2px 8px;
        border-radius: 999px;
    }

    @media (max-width: 991px) {
        .app-updation-search-filter-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }
        .app-updation-search-filter-box {
            flex-direction: column;
            align-items: stretch;
        }
        .app-updation-detail-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .app-updation-search-filter-wrapper {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="hr-communication-page">

    <div class="page-head">
        <div> <h1>App Updation</h1> </div>
    </div>

    {{-- SEND APP UPDATION --}}
    <div class="hr-card broadcast-card">

        <div class="fh" style="padding:15px 18px;border-bottom:1px solid var(--line);">
            <h3>Send App Updation Message</h3>
        </div>

        <form class="broadcast-form" id="appUpdationForm" method="POST" action="{{ route('superadmin.app-updations.store') }}">
            @csrf
            <div class="form-grid-box">
                {{-- Subject --}}
                <div class="field">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Update subject / title" required>
                </div>

                {{-- Recipient Type --}}
                <div class="field">
                    <label>Recipient Type</label>
                    <select name="recipient_type" id="recipientType" class="form-control" required>
                        <option value=""> Select Recipient Type </option>
                        <option value="all_user"> All Users </option>
                        <option value="individual_user"> Individual User </option>
                    </select>
                </div>

                {{-- INDIVIDUAL RECIPIENT --}}
                <div class="field" id="individualRecipientBox" style="display:none;">
                    <label id="recipientLabel"> Select Recipient </label>
                    <div class="recipient-search-wrap">
                        <input type="text" id="recipientSearch" class="form-control" placeholder="Search..." autocomplete="off">
                        <div id="recipientResults" class="recipient-results"></div>
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
                    <textarea name="message" class="form-control" placeholder="Type your update details here..." required></textarea>
                </div>

                {{-- Button --}}
                <div class="send-btn-wrap">
                    <button type="submit" class="btn btn-primary" id="sendAppUpdationBtn">
                        <span>📲</span> Send App Updation
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- FILTER --}}
    <div class="search-filter-box">
        <form method="GET" class="toolbar">
            <div class="app-updation-search-filter-box">
                <div class="app-updation-search-filter-wrapper">
                    <div class="input-field">
                        <label class="lbl"> Search </label>
                        <input class="tb-input" name="search" value="{{ request('search') }}" placeholder="Search subject">
                    </div>

                    <div class="input-field">
                        <label class="lbl"> Recipient Type </label>
                        <select class="tb-select" name="recipient">
                            <option value=""> All </option>
                            <option value="all_user" @selected(request('recipient') === 'all_user')> All Users </option>
                            <option value="individual_user" @selected(request('recipient') === 'individual_user')> Individual User </option>
                        </select>
                    </div>

                    <div class="input-field">
                        <label class="lbl"> Status </label>
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

                <div class="app-updation-filter-btn">
                    <button class="btn btn-primary" type="submit"> Filter</button>
                    <a href="{{ route('superadmin.app-updations.index') }}" class="btn btn-ghost"> Reset </a>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="table-wrap table-wrap-res-01">
        <div class="app-updation-table-box">
            <table class="app-updation-table-wrapper">
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

                @forelse($appUpdations as $appUpdation)
                    @php
                        $userCount = is_array($appUpdation->user_id)
                            ? count($appUpdation->user_id)
                            : 0;

                        $recipientText = match ($appUpdation->recipient_type) {
                            'all_user' => 'All Users',
                            'individual_user' => 'Individual Users (' . $userCount . ')',
                            default => ucfirst(str_replace('_', ' ', $appUpdation->recipient_type)),
                        };
                    @endphp

                    <tr>
                        <td data-label="Date Sent"> {{ $appUpdation->created_at?->format('d M Y, h:i A') }}</td>
                        <td data-label="Subject">
                            <div class="subject-text"> {{ $appUpdation->subject ?? '-' }}</div>
                        </td>
                        <td data-label="Recipient Type">
                            <span class="badge warn">{{ $recipientText }}</span>
                        </td>
                        <td data-label="Sent By">
                            {{ $appUpdation->sender_name ?? 'Super Admin' }}
                        </td>
                        <td data-label="Status">
                            <span class="badge success">{{ ucfirst($appUpdation->status ?? '-') }}</span>
                        </td>
                        <td data-label="Actions">
                            <div class="app-updation-action-buttons">
                                <button type="button" class="btn btn-ghost" onclick="openDetailsModal({{ $appUpdation->id }})">
                                    View Details
                                </button>
                                <button type="button" class="btn btn-ghost" onclick="openReadsModal({{ $appUpdation->id }})">
                                    View Reads
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:30px;color:#777;"> No app updations found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($appUpdations->hasPages())
            <div style="margin-top:15px;">{{ $appUpdations->links() }}</div>
        @endif
    </div>
</div>

{{-- DETAILS MODAL --}}
<div class="app-updation-popup-overlay" id="detailsModal">
    <div class="app-updation-popup">
        <div class="app-updation-popup-header">
            <div>
                <h3>App Updation Details</h3>
                <p>Complete update information</p>
            </div>
            <button type="button" class="app-updation-popup-close" onclick="closeDetailsModal()">&times;</button>
        </div>

        <div class="app-updation-popup-body" id="detailsModalBody">
            <div style="text-align:center;padding:30px;">Loading...</div>
        </div>

        <div class="app-updation-popup-footer">
            <button type="button" class="popup-close-btn" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

{{-- READS MODAL --}}
<div class="app-updation-popup-overlay" id="readsModal">
    <div class="app-updation-popup">
        <div class="app-updation-popup-header">
            <div>
                <h3>Message Reads</h3>
                <p>Users who viewed this update</p>
            </div>
            <button type="button" class="app-updation-popup-close" onclick="closeReadsModal()">&times;</button>
        </div>

        <div class="app-updation-popup-body" id="readsModalBody">
            <div style="text-align:center;padding:30px;">Loading...</div>
        </div>

        <div class="app-updation-popup-footer">
            <button type="button" class="popup-close-btn" onclick="closeReadsModal()">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const recipientType = document.getElementById('recipientType');
    const recipientBox = document.getElementById('individualRecipientBox');
    const recipientLabel = document.getElementById('recipientLabel');
    const recipientSearch = document.getElementById('recipientSearch');
    const recipientResults = document.getElementById('recipientResults');
    const selectedRecipientBox = document.getElementById('selectedRecipientBox');
    const selectedRecipientList = document.getElementById('selectedRecipientList');
    const form = document.getElementById('appUpdationForm');
    const sendButton = document.getElementById('sendAppUpdationBtn');

    let searchTimer = null;
    let selectedRecipients = [];

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function resetRecipients() {
        selectedRecipients = [];
        recipientSearch.value = '';
        recipientResults.innerHTML = '';
        recipientResults.style.display = 'none';
        renderSelectedRecipients();
    }

    function renderSelectedRecipients() {
        selectedRecipientList.innerHTML = '';
        if (!selectedRecipients.length) {
            selectedRecipientBox.style.display = 'none';
            return;
        }

        selectedRecipientBox.style.display = 'block';

        selectedRecipients.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'selected-recipient-item';

            row.innerHTML = `
                <div class="selected-recipient-info">
                    <div class="selected-recipient-name">
                        ${escapeHtml(item.name)}
                    </div>
                    <div class="selected-recipient-contact">
                        ${escapeHtml(item.contact)}
                    </div>
                </div>
                <button type="button" class="remove-recipient" data-id="${item.id}">×</button>
            `;

            row.querySelector('.remove-recipient').addEventListener('click', function () {
                selectedRecipients = selectedRecipients.filter(function (selected) {
                    return String(selected.id) !== String(item.id);
                });
                renderSelectedRecipients();
            });

            selectedRecipientList.appendChild(row);
        });
    }

    recipientType.addEventListener('change', function () {
        const type = this.value;
        resetRecipients();

        if (type === 'individual_user' || type === 'individual_retailer') {
            recipientBox.style.display = 'block';
            if (type === 'individual_user') {
                recipientLabel.innerText = 'Select Users';
                recipientSearch.placeholder = 'Search user by name, email or mobile...';
            } else {
                recipientLabel.innerText = 'Select Retailers';
                recipientSearch.placeholder = 'Search retailer by name, email or phone...';
            }
        } else {
            recipientBox.style.display = 'none';
        }
    });

    recipientSearch.addEventListener('input', function () {
        const search = this.value.trim();
        const type = recipientType.value;

        if (type !== 'individual_user' && type !== 'individual_retailer') {
            return;
        }

        clearTimeout(searchTimer);

        if (!search) {
            recipientResults.innerHTML = '';
            recipientResults.style.display = 'none';
            return;
        }

        searchTimer = setTimeout(function () {
            const url = type === 'individual_user'
                ? "{{ route('superadmin.app-updations.search-users') }}"
                : "{{ route('superadmin.app-updations.search-retailers') }}";

            fetch(url + '?search=' + encodeURIComponent(search), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to search recipient.');
                }
                return response.json();
            })
            .then(function (result) {
                recipientResults.innerHTML = '';

                if (!result.success || !Array.isArray(result.data) || !result.data.length) {
                    recipientResults.innerHTML = `
                        <div style="padding:12px;color:#777;font-size:12px;">
                            No result found
                        </div>
                    `;
                    recipientResults.style.display = 'block';
                    return;
                }

                result.data.forEach(function (item) {
                    const alreadySelected = selectedRecipients.some(function (selected) {
                        return String(selected.id) === String(item.id);
                    });

                    if (alreadySelected) {
                        return;
                    }

                    const div = document.createElement('div');
                    div.className = 'recipient-result';

                    const contact = item.email || item.mobile || item.phone || '';

                    div.innerHTML = `
                        <div class="recipient-result-name">${escapeHtml(item.name || 'Unnamed')}</div>
                        <div class="recipient-result-contact">${escapeHtml(contact)}</div>
                    `;

                    div.addEventListener('click', function () {
                        selectedRecipients.push({
                            id: item.id,
                            name: item.name || 'Unnamed',
                            contact: contact
                        });

                        renderSelectedRecipients();

                        recipientSearch.value = '';
                        recipientResults.innerHTML = '';
                        recipientResults.style.display = 'none';
                    });

                    recipientResults.appendChild(div);
                });

                if (!recipientResults.children.length) {
                    recipientResults.innerHTML = `
                        <div style="padding:12px;color:#777;font-size:12px;">
                            All searched recipients are already selected.
                        </div>
                    `;
                }

                recipientResults.style.display = 'block';
            })
            .catch(function (error) {
                console.error('Recipient search error:', error);
                recipientResults.innerHTML = `
                    <div style="padding:12px;color:#c00;font-size:12px;">
                        Unable to search recipient.
                    </div>
                `;
                recipientResults.style.display = 'block';
            });
        }, 300);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const type = recipientType.value;

        if (!type) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'Please select recipient type.',
                    confirmButtonColor: '#050505',
                });
            } else {
                alert('Please select recipient type.');
            }
            return;
        }

        if (type === 'individual_user' || type === 'individual_retailer') {
            if (!selectedRecipients.length) {
                const msg = type === 'individual_user' ? 'Please select at least one user.' : 'Please select at least one retailer.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: msg,
                        confirmButtonColor: '#050505',
                    });
                } else {
                    alert(msg);
                }
                return;
            }
        }

        const formData = new FormData(form);
        formData.delete('recipient_ids[]');

        selectedRecipients.forEach(function (item) {
            formData.append('recipient_ids[]', item.id);
        });

        sendButton.disabled = true;
        sendButton.innerHTML = '<span>⏳</span> Sending...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async function (response) {
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Unable to send app updation.');
            }
            return result;
        })
        .then(function (result) {
            if (!result.success) {
                throw new Error(result.message || 'Unable to send app updation.');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message || 'App updation sent successfully.',
                    confirmButtonColor: '#050505',
                    confirmButtonText: 'OK',
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(result.message || 'App updation sent successfully.');
                window.location.reload();
            }
        })
        .catch(function (error) {
            console.error('App updation submit error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'Something went wrong.',
                    confirmButtonColor: '#050505',
                });
            } else {
                alert(error.message || 'Something went wrong.');
            }
        })
        .finally(function () {
            sendButton.disabled = false;
            sendButton.innerHTML = '<span>📲</span> Send App Updation';
        });
    });

    document.addEventListener('click', function (e) {
        if (!recipientBox.contains(e.target)) {
            recipientResults.style.display = 'none';
        }
    });
});

/* Details Modal */
function openDetailsModal(id) {
    document.getElementById('readsModal').classList.remove('show');
    document.getElementById('detailsModal').classList.add('show');
    document.body.style.overflow = 'hidden';

    const body = document.getElementById('detailsModalBody');
    body.innerHTML = '<div style="text-align:center;padding:30px;">Loading...</div>';

    fetch("{{ url('superadmin/app-updations') }}/" + id, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (result) {
        if (!result.success) {
            throw new Error('Unable to load details.');
        }

        const data = result.data;
        let recipients = '';

        if (data.recipient_type === 'individual_user') {
            recipients = result.users.map(function (user) {
                return `
                    <div style="margin-bottom:5px;">
                        <strong>${escapeHtmlGlobal(user.name)}</strong>
                        ${user.mobile ? ' - ' + escapeHtmlGlobal(user.mobile) : ''}
                    </div>
                `;
            }).join('');
        } else if (data.recipient_type === 'individual_retailer') {
            recipients = result.retailers.map(function (retailer) {
                return `
                    <div style="margin-bottom:5px;">
                        <strong>${escapeHtmlGlobal(retailer.name)}</strong>
                        ${retailer.phone ? ' - ' + escapeHtmlGlobal(retailer.phone) : ''}
                    </div>
                `;
            }).join('');
        } else {
            recipients = data.recipient_type === 'all_user' ? 'All Users' : 'All Retailers';
        }

        body.innerHTML = `
            <div class="app-updation-detail-grid">
                <div class="app-updation-detail-item">
                    <span class="app-updation-detail-label">Date Sent</span>
                    <div class="app-updation-detail-value">
                        ${data.created_at ? new Date(data.created_at).toLocaleString() : '-'}
                    </div>
                </div>

                <div class="app-updation-detail-item">
                    <span class="app-updation-detail-label">Sent By</span>
                    <div class="app-updation-detail-value">
                        ${escapeHtmlGlobal(data.sender_name || 'Super Admin')}
                    </div>
                </div>

                <div class="app-updation-detail-item full">
                    <span class="app-updation-detail-label">Subject</span>
                    <div class="app-updation-detail-value">
                        ${escapeHtmlGlobal(data.subject)}
                    </div>
                </div>

                <div class="app-updation-detail-item full">
                    <span class="app-updation-detail-label">Recipients</span>
                    <div class="app-updation-detail-value">
                        ${recipients || '-'}
                    </div>
                </div>

                <div class="app-updation-detail-item">
                    <span class="app-updation-detail-label">Status</span>
                    <div class="app-updation-detail-value">
                        <span class="badge success">${escapeHtmlGlobal(data.status)}</span>
                    </div>
                </div>

                <div class="app-updation-detail-item full">
                    <span class="app-updation-detail-label">Message</span>
                    <div class="app-updation-message">
                        ${escapeHtmlGlobal(data.message)}
                    </div>
                </div>
            </div>
        `;
    })
    .catch(function (error) {
        body.innerHTML = `
            <div style="text-align:center;padding:30px;color:#c00;">
                ${escapeHtmlGlobal(error.message)}
            </div>
        `;
    });
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('show');
    document.body.style.overflow = '';
}

/* Reads Modal */
function openReadsModal(id) {
    document.getElementById('detailsModal').classList.remove('show');
    document.getElementById('readsModal').classList.add('show');
    document.body.style.overflow = 'hidden';

    const body = document.getElementById('readsModalBody');
    body.innerHTML = '<div style="text-align:center;padding:30px;">Loading...</div>';

    fetch("{{ url('superadmin/app-updations') }}/" + id + "/reads", {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (result) {
        if (!result.success || !result.data.length) {
            body.innerHTML = '<div style="text-align:center;padding:30px;color:#777;">No reads yet.</div>';
            return;
        }

        body.innerHTML = '<div class="app-updation-read-list">' +
            result.data.map(function (read) {
                return `
                    <div class="app-updation-read-item">
                        <div>
                            <div class="read-user">${escapeHtmlGlobal(read.user?.name || 'User')}</div>
                            <div class="read-time">${read.read_at ? new Date(read.read_at).toLocaleString() : '-'}</div>
                        </div>
                        <span class="read-status">Read</span>
                    </div>
                `;
            }).join('') +
            '</div>';
    })
    .catch(function () {
        body.innerHTML = '<div style="text-align:center;padding:30px;color:#c00;">Unable to load reads.</div>';
    });
}

function closeReadsModal() {
    document.getElementById('readsModal').classList.remove('show');
    document.body.style.overflow = '';
}

function escapeHtmlGlobal(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

document.addEventListener('click', function (e) {
    const details = document.getElementById('detailsModal');
    const reads = document.getElementById('readsModal');
    if (e.target === details) closeDetailsModal();
    if (e.target === reads) closeReadsModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeDetailsModal();
        closeReadsModal();
    }
});
</script>

@endsection
