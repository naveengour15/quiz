@extends('admin.layouts.app')
@section('title', 'Quiz Types')

@push('styles')
    <style>
            .modal {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                visibility: hidden;
                opacity: 0;
                transition: visibility 0.25s ease, opacity 0.25s ease;
            }
 
            .modal.active {
                visibility: visible;
                opacity: 1;
            }

            .modal-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
            }

            .modal-content {
                position: relative;
                z-index: 10000;
                width: 100%;
                max-width: 520px;
                margin: 20px;
                max-height: 90vh;
                overflow-y: auto;
                background: var(--surface, #ffffff);
                border-radius: 16px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                transform: translateY(15px) scale(0.96);
                transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .modal.active .modal-content {
                transform: translateY(0) scale(1);
            }

            .switch {
                position: relative;
                display: inline-block;
                width: 48px;
                height: 24px;
            }

            .switch input {
                display: none;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                inset: 0;
                background: #ccc;
                transition: .3s;
                border-radius: 24px;
            }

            .slider:before {
                position: absolute;
                content: "";
                width: 18px;
                height: 18px;
                left: 3px;
                top: 3px;
                background: white;
                transition: .3s;
                border-radius: 50%;
            }

            .switch input:checked+.slider {
                background: #28a745;
            }

            .switch input:checked+.slider:before {
                transform: translateX(24px);
            }
        </style>
    @endpush

    @section('content')
    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Quiz Types</h1>
                <p>Manage quiz types and format classifications.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModal()">Add Quiz Type</button>
        </div>

        <div class="table-wrap quiz-type-table-box">
            <table class="quiz-type-table-wrapper">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quiz Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quizTypes as $type)
                        <tr>
                            <td data-label="#" class="mono">{{ $loop->iteration }}</td>
                            <td data-label="Quiz Type"><b>{{ $type->name }}</b></td>
                            <td data-label="Status">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $type->id }}"
                                            {{ $type->status == 'active' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>

                                    <span class="status-text">
                                        {{ ucfirst($type->status) }}
                                    </span>
                                </div>
                            </td>
                            <td data-label="Actions" class="row-actions">
                                <div style="display:flex;gap:10px;">
                                    <a class="icon-btn"
                                        href="{{ route('admin.quiz-types.index', ['edit' => $type->id]) }}">✎</a>

                                    <form method="POST" action="{{ route('admin.quiz-types.destroy', $type) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn danger" type="submit">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:var(--ink-faint);">No quiz types found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $quizTypes])     
            </div>
        </div>
    </div>

    {{-- Modal placed outside .panel for clean fullscreen backdrop overlay --}}
    <div id="quizTypeModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Quiz Type' : 'Add Quiz Type' }}</h3>
                <button type="button" class="icon-btn" onclick="closeModal()"
                    style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST"
                action="{{ $editing ? route('admin.quiz-types.update', $editing) : route('admin.quiz-types.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid form-grid-02">
                    <div class="field">
                        <label>Quiz Type Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ $editing->name ?? old('name') }}"
                            placeholder="e.g. Multiple Choice, True/False, Speed Test" required>
                    </div>

                    <div class="field">
                        <label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            <option value="active" @selected(($editing->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(($editing->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-foot">
                    <button class="btn btn-ghost" type="button" onclick="closeModal()">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Quiz Type</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- <script>
        function openModal() {
            document.getElementById('quizTypeModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('quizTypeModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('admin.quiz-types.index') }}";
            }
        }
    </script> --}}

    <script>
        function openModal() {
            document.getElementById('quizTypeModal').classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('quizTypeModal');

            // Close animation
            modal.classList.remove('active');

            // Remove ?edit=... from URL without page reload
            if (window.location.search.includes('edit=')) {
                const cleanUrl = "{{ route('admin.quiz-types.index') }}";
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
    </script>

    <script>
        document.querySelectorAll('.status-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                let checkbox = this;
                fetch("{{ url('admin/quiz-types') }}/" + checkbox.dataset.id + "/status", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let statusText = checkbox.closest("td").querySelector(".status-text");
                            statusText.innerText =
                                data.status.charAt(0).toUpperCase() +
                                data.status.slice(1);
                            showAlert(
                                'success',
                                'Success',
                                'Quiz Type status updated successfully.'
                            );
                        } else {
                            checkbox.checked = !checkbox.checked;
                            showAlert(
                                'warning',
                                'Warning',
                                'Quiz Type status could not be updated.'
                            );
                        }
                    })
                    .catch(() => {
                        checkbox.checked = !checkbox.checked;
                        showAlert(
                            'error',
                            'Failed',
                            'Server error. Please try again.'
                        );
                    });
            });
        });
    </script>
@endpush
