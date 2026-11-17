@extends('superadmin.layouts.app')
@section('title', 'Levels')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

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

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Levels</h1>
                <p>Manage difficulty levels for quizzes and questions.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModal()">Add Level</button>
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                             <input class="tb-input search-field" name="search" value="{{ request('search') }}" placeholder="Search name" onchange="this.form.submit()">
                        </div>
                         
                        <div class="input-field">
                              <label class="lbl" for="search">Status</label>
                            <select class="tb-select status-field" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="users-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ request()->url() }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap level-table-box">
            <table class="level-table-wrapper">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Level Name</th>
                        <th>Number of Quiz</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($levels as $level)
                        <tr>
                            <td data-label="#" class="mono">{{ $levels->firstItem() + $loop->index }}</td>
                            <td data-label="Level Name"><b>{{ $level->name }}</b></td>
                            <td data-label="Number of Quiz">{{ $level->number_quiz }}</td>
                            <td data-label="Status">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $level->id }}"
                                            {{ $level->status == 'active' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>

                                    <span class="status-text">
                                        {{ ucfirst($level->status) }}
                                    </span>
                                </div>
                            </td>
                            <td data-label="Actions" class="row-actions">
                                <div style="display: flex; gap:10px;">
                                    <a class="icon-btn"
                                        href="{{ route('superadmin.levels.index', ['edit' => $level->id]) }}">✎</a>

                                    <form method="POST" action="{{ route('superadmin.levels.destroy', $level) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn danger" type="submit">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--ink-faint);">No levels found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $levels])
            </div> 
        </div>
         
    </div>

    {{-- Modal placed outside .panel for clean fullscreen backdrop overlay --}}
    <div id="levelModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Level' : 'Add Level' }}</h3>
                <button type="button" class="icon-btn" onclick="closeModal()"
                    style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST"
                action="{{ $editing ? route('superadmin.levels.update', $editing) : route('superadmin.levels.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid form-grid-02">
                    <div class="field">
                        <label>Level Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ $editing->name ?? old('name') }}"
                            placeholder="e.g. Easy, Medium, Hard, Expert" required>
                    </div>

                    <div class="field">
                        <label>Number of Quiz <span class="req">*</span></label>
                        <input type="number" name="number_quiz" min="0" value="{{ old('number_quiz', $editing->number_quiz ?? 0) }}" placeholder="Enter number of quizzes" required>
                    </div>

                    <div class="field full">
                        <label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            <option value="active" @selected(($editing->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(($editing->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-foot">
                    <button class="btn btn-ghost" type="button" onclick="closeModal()">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Level</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function openModal() {
            document.getElementById('levelModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('levelModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.levels.index') }}";
            }
        }
    </script>

    <script>
        document.querySelectorAll('.status-toggle').forEach(function(toggle) {

            toggle.addEventListener('change', function() {

                let checkbox = this;

                fetch("{{ url('superadmin/levels') }}/" + checkbox.dataset.id + "/status", {
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
                                'Level status updated successfully.'
                            );

                        } else {

                            checkbox.checked = !checkbox.checked;
                            showAlert(
                                'warning',
                                'Warning',
                                'Level status could not be updated.'
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
