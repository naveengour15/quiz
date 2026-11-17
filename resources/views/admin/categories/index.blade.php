@extends('admin.layouts.app')
@section('title', 'Categories')

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
                max-width: 550px;
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
            <div class="category-page-title">
                <h1>Categories</h1>
                <p>The taxonomy every quiz and question is organized under.</p>

                <div class="tab-menu" style="margin:10px 0px 0px 0px;display:flex;gap:10px;">
                    <a href="{{ route('admin.categories.index', ['tab' => 'category']) }}"
                        class="btn {{ $tab == 'category' ? 'btn-primary' : 'btn-ghost' }}">
                        Category
                    </a>
                    <a href="{{ route('admin.categories.index', ['tab' => 'subcategory']) }}"
                        class="btn {{ $tab == 'subcategory' ? 'btn-primary' : 'btn-ghost' }}">
                        Sub Category
                    </a>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModal()">Add Category</button>
        </div>

        <div class="table-wrap table-wrap-res-02" style="overflow: hidden;">
            <div class="category-table-box">
                <table class="category-table-wapper">
                    <thead>
                        <tr>
                            <th> {{ $tab == 'subcategory' ? 'Sub Category' : 'Category' }}</th>
                            @if ($tab == 'subcategory')
                                <th>Category</th>
                            @endif
                            <th>Questions</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                            <tr>
                                <td data-label="Sub Category">
                                    @if (!$cat->parent_id)
                                        <b>{{ $cat->name }}</b>
                                    @else
                                        {{ $cat->name }}
                                    @endif
                                </td>
                                @if ($tab == 'subcategory')
                                    <td data-label="Category">{{ $cat->parent->name ?? '—' }}</td>
                                @endif
                                <td data-label="Questions" class="mono">{{ $cat->questions_count }}</td>
                                <td data-label="Order" class="mono">{{ $cat->display_order }}</td>
                                <td data-label="Status">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <label class="switch">
                                            <input type="checkbox" class="status-toggle" data-id="{{ $cat->id }}"
                                                {{ $cat->status == 'active' ? 'checked' : '' }}>
                                            <span class="slider"></span>
                                        </label>

                                        <span class="status-text">
                                            {{ ucfirst($cat->status) }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions">
                                    <div style="display: flex; gap:10px;">
                                        <a class="icon-btn"
                                            href="{{ route('admin.categories.index', ['edit' => $cat->id, 'tab' => $tab]) }}">✎</a>

                                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="tab" value="{{ $tab }}">
                                            <button class="icon-btn danger" type="submit">🗑</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $tab == 'subcategory' ? 6 : 5 }}"
                                    style="text-align:center;color:var(--ink-faint);">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $categories])
            </div>   
        </div>
    </div>

    {{-- Modal placed outside .panel for clean fullscreen backdrop overlay --}}
    <div id="categoryModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Category' : 'Add Category' }}</h3>
                <button type="button" class="icon-btn" onclick="closeModal()"
                    style="border:none;background:transparent;box-shadow:none;">✕</button>
            </div>
            <form method="POST"
                action="{{ $editing ? route('admin.categories.update', $editing) : route('admin.categories.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="form-grid form-grid-02">
                    <div class="field full">
                        <label>Category Name <span class="req">*</span></label>
                        <input type="text"
                            name="name" value="{{ $editing->name ?? old('name') }}"
                            placeholder="e.g. Environment & Energy" required>
                    </div>

                    <div class="field">
                        <label>Parent Category <span class="hint">(leave blank for top-level)</span></label>
                        <select name="parent_id">
                            <option value="">— None (top-level) —</option>
                            @foreach ($parents as $p)
                                <option value="{{ $p->id }}" @selected(($editing->parent_id ?? '') == $p->id)>{{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Icon <span class="hint">(optional emoji)</span></label>
                        <input type="text" name="icon" value="{{ $editing->icon ?? old('icon') }}">
                    </div>

                    <div class="field full">
                        <label>Description <span class="hint">(optional)</span></label>
                        <textarea name="description">{{ $editing->description ?? old('description') }}</textarea>
                    </div>
                </div>
                <div class="form-foot">
                    <button class="btn btn-ghost" type="button" onclick="closeModal()">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Category</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function openModal() {
            document.getElementById('categoryModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('admin.categories.index') }}?tab={{ $tab }}";
            }
        }
    </script>

    <script>
        document.querySelectorAll('.status-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                let checkbox = this;
                fetch("{{ url('admin/categories') }}/" + checkbox.dataset.id + "/status", {
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
                                'Category status updated successfully.'
                            );
                        } else {
                            checkbox.checked = !checkbox.checked;
                            showAlert(
                                'warning',
                                'Warning',
                                'Category status could not be updated.'
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
