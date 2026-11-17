@extends('superadmin.layouts.app')
@section('title', 'Quiz User Feedback')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

<style>
    tbody tr:last-child td {
       border-bottom: 1px solid #eee !important;
    }
    .user-feedback-table-box {
        width: 100%;
        overflow-x: auto;
    }

    .user-feedback-table-wrapper {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .swal2-popup{
        border-radius: 12px;
    }

    .swal2-textarea:focus{
        box-shadow: none;
    }

    .swal2-title {
       font-size: 17px !important;
    }

    .swal2-input-label{
        font-size: 14px !important;
    }

    .swal2-textarea{
        border: 1px solid var(--line);
    }

    .swal2-cancel{
        background: var(--surface);
        border: 1px solid var(--line);
        color: var(--ink);
        border-radius: 9px;
    }

    .swal2-confirm{
        border-radius: 9px;
    }

    .swal2-cancel:hover{
        background: transparent;
    }

    @media (max-width: 991px) {
        .user-feedback-table-box {
            overflow: visible;
        }

        .user-feedback-table-wrapper {
            min-width: 0;
        }

        .user-feedback-table-wrapper thead {
            display: none;
        }

        .user-feedback-table-wrapper,
        .user-feedback-table-wrapper tbody,
        .user-feedback-table-wrapper tr {
            display: block;
            width: 100%;
        }

        .user-feedback-table-wrapper tr {
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }

        .user-feedback-table-wrapper td {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border: 0;
            border-bottom: 1px solid #eee;
        }

        .user-feedback-table-wrapper td:last-child {
            border-bottom: 0;
        }

        .user-feedback-table-wrapper td::before {
            content: attr(data-label);
            flex: 0 0 110px;
            font-weight: 600;
            color: #555;
        }

        .user-feedback-table-wrapper .note-box {
            min-width: 0;
            overflow-wrap: anywhere;
        }
    }

    @media (max-width: 575px) {
        .user-feedback-table-wrapper td {
            display: block;
        }

        .user-feedback-table-wrapper td::before {
            display: block;
            margin-bottom: 4px;
        }
    }
</style>

    @push('styles')
        <style>
            .feedback-status-change {
                width: 130px;
                padding: 6px 10px;
                border: 1px solid #dcdfe6;
                border-radius: 6px;
                background: #fff;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                cursor: pointer;
                outline: none;
            }
            .feedback-status-change:focus {
                border-color: #4f46e5;
                box-shadow: 0 0 0 2px rgba(79,70,229,.15);
            }
            .note-box {
                display: block;
                font-size: 11px;
                color: #6b7280;
                font-style: italic;
                line-height: 1.4;
                border-left: 2px solid #d1d5db;
                padding: 4px 8px;
                margin-top: 6px;
                max-width: 200px;
                background: #f9fafb;
                border-radius: 0 4px 4px 0;
            }
            .note-box.empty-note { color: #9ca3af; font-style: normal; border-left-color: #e5e7eb; background: transparent; }
            .note-box.reviewed strong { color: #2563eb; }
            .note-box.replied strong { color: #9333ea; }
            .note-box.closed strong { color: #dc2626; }
            
            .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
            .badge.pending { background-color: #fef3c7; color: #92400e; }
            .badge.reviewed { background-color: #dbeafe; color: #1e40af; }
            .badge.replied { background-color: #f3e8ff; color: #6b21a8; }
            .badge.closed { background-color: #fee2e2; color: #991b1b; }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <h1>Quiz User Feedback</h1>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="redeem-request-search-filter-box">
                    <div class="redeem-request-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search by name, mobile, consumer no...">
                        </div>
                        <div class="input-field">
                            <label class="lbl" for="status">Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach (['pending', 'reviewed', 'replied', 'closed'] as $s)
                                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="redeem-request-search-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('superadmin.quiz_user_feedback.index') }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01" style="overflow: hidden">
            <div class="user-feedback-table-box">
                <table class="user-feedback-table-wrapper">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status & Notes</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $fb)
                            <tr>
                                <td data-label="User">
                                    <div>
                                        <strong>{{ $fb->user->name ?? 'Deleted User' }}</strong><br>
                                        <small>{{ $fb->user->mobile ?? '—' }}</small>
                                    </div>
                                </td>
                                <td data-label="Subject">{{ Str::limit($fb->subject, 30) ?? '—' }}</td>
                                <td data-label="Message">
                                    <small class="note-box user-note" title="{{ $fb->message }}">
                                        {{ Str::limit($fb->message, 40) ?? '—' }}
                                    </small>
                                </td>
                                <td data-label="Status">
                                    <div>
                                        @if($fb->status === 'pending')
                                            <select class="feedback-status-change" data-id="{{ $fb->id }}">
                                                <option value="" selected disabled>Pending</option>
                                                <option value="reviewed">Mark Reviewed</option>
                                                <option value="replied">Mark Replied</option>
                                                <option value="closed">Mark Closed</option>
                                            </select>
                                        @else
                                            <span class="badge {{ $fb->status }}">{{ ucfirst($fb->status) }}</span>
                                            
                                            @if($fb->status === 'reviewed' && $fb->review_note)
                                                <small class="note-box reviewed" title="{{ $fb->review_note }}">
                                                    <strong>Review:</strong> {{ Str::limit($fb->review_note, 25) }}
                                                </small>
                                            @elseif($fb->status === 'replied' && $fb->reply_message)
                                                <small class="note-box replied" title="{{ $fb->reply_message }}">
                                                    <strong>Reply:</strong> {{ Str::limit($fb->reply_message, 25) }}
                                                </small>
                                            @elseif($fb->status === 'closed' && $fb->close_note)
                                                <small class="note-box closed" title="{{ $fb->close_note }}">
                                                    <strong>Closed:</strong> {{ Str::limit($fb->close_note, 25) }}
                                                </small>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Created">{{ $fb->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                <td data-label="Actions">
                                    <button class="icon-btn btn-view-quiz" data-id="{{ $fb->id }}" title="View Details">👁</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--ink-faint);padding:20px;">No feedback found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $feedbacks])
            </div>
        </div>
    </div>

    @include('superadmin.quiz-user-feedback.components.details-modal')
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Status Change Logic
        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('feedback-status-change')) return;

            const id = e.target.dataset.id;
            const status = e.target.value;
            const selectElement = e.target;
            if (!status) return;

            let title = '';
            let placeholder = '';
            let confirmColor = '';
            let confirmText = '';

            if (status === 'reviewed') {
                title = 'Mark as Reviewed';
                placeholder = 'Enter your review note...';
                confirmColor = '#2563eb';
                confirmText = 'Mark Reviewed';
            } else if (status === 'replied') {
                title = 'Mark as Replied';
                placeholder = 'Enter your reply message...';
                confirmColor = '#9333ea';
                confirmText = 'Mark Replied';
            } else if (status === 'closed') {
                title = 'Close Feedback';
                placeholder = 'Enter closing note/reason...';
                confirmColor = '#dc2626';
                confirmText = 'Close Feedback';
            }

            Swal.fire({
                title: title,
                input: 'textarea',
                inputLabel: 'Note is required',
                inputPlaceholder: placeholder,
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Note cannot be empty!';
                    }
                },
                showCancelButton: true,
                confirmButtonText: confirmText,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateFeedbackStatus(id, status, result.value, selectElement);
                } else {
                    selectElement.selectedIndex = 0;
                }
            });
        });

        function updateFeedbackStatus(id, status, note, selectElement) {
            fetch("{{ route('superadmin.quiz_user_feedback.changeStatus') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: id, status: status, note: note })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Warning', data.message || 'Unable to update status.', 'warning');
                    selectElement.selectedIndex = 0;
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Something went wrong.', 'error');
                selectElement.selectedIndex = 0;
            });
        }

        // ✅ MODAL CLOSE FUNCTION (Reusable)
        function closeModal(modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                
                // Remove backdrop
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }

        // ✅ MODAL OPEN FUNCTION (Reusable)
        function openModal(modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                // Create backdrop if not exists
                if (!document.querySelector('.modal-backdrop')) {
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
                
                modalElement.style.display = 'block';
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
                
                // Trigger reflow for animation
                setTimeout(() => {
                    modalElement.classList.add('show');
                }, 10);
            }
        }

        // View Details Modal Logic
        document.addEventListener('click', function(e) {
            // Handle View Button Click
            if (e.target.closest('.btn-view-quiz')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-view-quiz');
                const id = btn.dataset.id;
                
                const showUrl = "{{ route('superadmin.quiz_user_feedback.show', '__ID__') }}".replace('__ID__', id);
                
                console.log("Fetching URL:", showUrl);

                fetch(showUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    console.log("Response Data:", data);
                    
                    if (data.success) {
                        const d = data.data;
                        
                        // Set data
                        document.getElementById('modalSubject').innerText = d.subject;
                        document.getElementById('modalMessage').innerText = d.message;
                        document.getElementById('modalUser').innerText = `${d.user.name} (${d.user.mobile})`;
                        document.getElementById('modalStatus').innerText = d.status;
                        
                        document.getElementById('modalReviewNote').innerText = d.review.note !== '—' ? d.review.note : 'No review note added yet';
                        document.getElementById('modalReviewBy').innerText = d.review.by !== '—' ? `${d.review.by} on ${d.review.at}` : 'Not reviewed yet';

                        document.getElementById('modalReplyMsg').innerText = d.reply.message !== '—' ? d.reply.message : 'No reply message sent yet';
                        document.getElementById('modalReplyBy').innerText = d.reply.by !== '—' ? `${d.reply.by} on ${d.reply.at}` : 'Not replied yet';

                        document.getElementById('modalCloseNote').innerText = d.close.note !== '—' ? d.close.note : 'Not closed yet';
                        document.getElementById('modalCloseBy').innerText = d.close.by !== '—' ? `${d.close.by} on ${d.close.at}` : 'Not closed yet';

                        // Open Modal
                        openModal('feedbackDetailsModal');
                        
                    } else {
                        Swal.fire('Error', data.message || 'Unknown error occurred', 'error');
                    }
                })
                .catch(err => {
                    console.error('Fetch Error Details:', err);
                    Swal.fire('Error', 'Failed to load feedback details.', 'error');
                });
            }
            
            // ✅ Handle Close Button Click (Both header X and footer Close button)
            if (e.target.closest('.close-modal-btn')) {
                const modalId = e.target.closest('.close-modal-btn').dataset.modalId;
                closeModal(modalId);
            }
            
            // ✅ Handle Backdrop Click (Click outside modal to close)
            if (e.target.classList.contains('modal-backdrop')) {
                const modalElement = e.target.nextElementSibling;
                if (modalElement && modalElement.id) {
                    closeModal(modalElement.id);
                }
            }
        });

        // ✅ Handle Escape Key to Close Modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        });
    </script>
@endpush
