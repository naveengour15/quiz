@extends('superadmin.layouts.app')

@section('title', 'Product Categories')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')

    @push('styles')
        <style>
            /* =========================================================
                       PRODUCT CATEGORY MODAL
                    ========================================================= */

            .modal {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                visibility: hidden;
                opacity: 0;
                transition: visibility .25s ease, opacity .25s ease;
            }

            .modal.active {
                visibility: visible;
                opacity: 1;
            }

            .modal-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, .65);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
            }

            .modal-content {
                position: relative;
                z-index: 10000;
                width: 100%;
                max-width: 560px;
                margin: 20px;
                max-height: 90vh;
                overflow-y: auto;
                background: var(--surface, #fff);
                border-radius: 16px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
                transform: translateY(15px) scale(.96);
                transition: transform .25s cubic-bezier(.16, 1, .3, 1);
            }

            .modal.active .modal-content {
                transform: translateY(0) scale(1);
            }

            /* =========================================================
                       CATEGORY
                    ========================================================= */

            .category-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .category-photo {
                width: 42px;
                height: 42px;
                border-radius: 9px;
                object-fit: cover;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
            }

            .category-photo-placeholder {
                width: 42px;
                height: 42px;
                border-radius: 9px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f1f5f9;
                color: #64748b;
                font-size: 18px;
                flex-shrink: 0;
            }

            .category-details {
                min-width: 0;
            }

            .category-details strong {
                display: block;
                font-size: 14px;
                color: var(--ink, #0f172a);
            }

            .category-details small {
                display: block;
                margin-top: 2px;
                color: var(--ink-faint, #94a3b8);
                font-size: 11px;
            }

            .parent-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 7px;
                background: #f1f5f9;
                color: #475569;
                font-size: 12px;
                font-weight: 600;
            }

            .main-category-badge {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border-radius: 7px;
                background: #eff6ff;
                color: #2563eb;
                font-size: 12px;
                font-weight: 600;
            }

            .slug-text {
                font-size: 12px;
                color: var(--ink-faint, #64748b);
                word-break: break-word;
            }

            .summary-text {
                max-width: 260px;
                line-height: 1.5;
                color: var(--ink-muted, #64748b);
                font-size: 13px;
            }

            .current-photo {
                margin-top: 10px;
            }

            .current-photo img {
                width: 65px;
                height: 65px;
                object-fit: cover;
                border-radius: 9px;
                border: 1px solid #e5e7eb;
            }

            /* =========================================================
                       RESPONSIVE TABLE
                    ========================================================= */

            .product-category-table-box {
                overflow-x: auto;
            }

            .product-category-table {
                min-width: 1050px;
            }

            .text-danger {
                display: block;
                margin-top: 5px;
                color: #dc2626;
                font-size: 12px;
            }
            
            .product-category-search-filter-box{
                width: 100%;
                display:flex;
                gap: 10px;
                align-items: flex-end;
            }

            .product-category-search-filter-wrapper{
                width: 100%;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .product-category-filter-btn{
                /* width: 100%; */
                display: flex;
                gap: 10px;
            }


            @media(max-width:767px){
                .product-category-table {
                    min-width: 100%;
                }
                .product-category-search-filter-box {
                   flex-direction: column;
                }
                            }

            @media(max-width:575px){
                .product-category-search-filter-wrapper {
                    grid-template-columns: repeat(1, 1fr);
                }

                .product-category-filter-btn{
                    width: 100%;
                }

                .product-category-filter-btn .btn{
                    width: 100%;
                    display: flex;
                    justify-content: center;
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
                <h1>Product Categories</h1>
                <p>
                    Manage product categories and sub-categories.
                </p>
            </div>

            <button type="button" class="btn btn-primary" onclick="openCategoryModal()">
                Add Category
            </button>

        </div>


        {{-- =========================================================
             SEARCH / FILTER
        ========================================================== --}}

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="product-category-search-filter-box">
                    <div class="product-category-search-filter-wrapper">
                        {{-- Search --}}

                        <div class="input-field">
                            <label class="lbl" for="search">
                                Search
                            </label>
                            <input class="tb-input" type="text" id="search" name="search"
                                value="{{ request('search') }}" placeholder="Search category">
                        </div>

                        {{-- Parent Category --}}

                        <div class="input-field">
                            <label class="lbl" for="parent_id">
                                Parent
                            </label>
                            <select class="tb-select" id="parent_id" name="parent_id" onchange="this.form.submit()">

                                <option value="">
                                    All Categories
                                </option>

                                @foreach ($parentCategories as $parent)
                                    <option value="{{ $parent->id }}" @selected((string) request('parent_id') === (string) $parent->id)>
                                        {{ $parent->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        {{-- Status --}}

                        <div class="input-field">
                            <label class="lbl" for="status">
                                Status
                            </label>

                            <select class="tb-select" id="status" name="status"
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
                        </div>
                    </div>


                    <div class="product-category-filter-btn">
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
             CATEGORY TABLE
        ========================================================== --}}

        <div class="table-wrap table-wrap-res-02">
            <div class="product-category-table-box">
                <table class="level-table-wrapper product-category-table">
                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Category</th>

                            <th>Slug</th>

                            <th>Parent</th>

                            <th>Summary</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($categories as $category)
                            <tr>

                                {{-- # --}}

                                <td data-label="#" class="mono">
                                    {{ $categories->firstItem() + $loop->index }}
                                </td>


                                {{-- Category --}}

                                <td data-label="Category">

                                    <div class="category-info">

                                        @if ($category->photo)
                                            <img src="{{ asset('storage/' . $category->photo) }}"
                                                alt="{{ $category->name }}" class="category-photo">
                                        @else
                                            <div class="category-photo-placeholder">
                                                📦
                                            </div>
                                        @endif


                                        <div class="category-details">

                                            <strong>
                                                {{ $category->name }}
                                            </strong>

                                            <small>
                                                ID: #{{ $category->id }}
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                {{-- Slug --}}

                                <td data-label="Slug">

                                    <span class="mono slug-text">
                                        {{ $category->slug }}
                                    </span>

                                </td>


                                {{-- Parent --}}

                                <td data-label="Parent">

                                    @if ($category->parent)
                                        <span class="parent-badge">
                                            {{ $category->parent->name }}
                                        </span>
                                    @else
                                        <span class="main-category-badge">
                                            Main Category
                                        </span>
                                    @endif

                                </td>


                                {{-- Summary --}}

                                <td data-label="Summary">

                                    @if ($category->summary)
                                        <span class="summary-text">

                                            {{ \Illuminate\Support\Str::limit($category->summary, 60) }}

                                        </span>
                                    @else
                                        <span
                                            style="
                                                color:var(--ink-faint);
                                            ">
                                            —
                                        </span>
                                    @endif

                                </td>


                                {{-- Status --}}

                                <td data-label="Status">

                                    @if ($category->status === 'active')
                                        <span class="status-badge active">
                                            Active
                                        </span>
                                    @else
                                        <span class="status-badge inactive">
                                            Inactive
                                        </span>
                                    @endif

                                </td>


                                {{-- Actions --}}

                                <td data-label="Actions" class="row-actions">

                                    <div
                                        style="
                                            display:flex;
                                            gap:10px;
                                        ">

                                        {{-- Edit --}}

                                        <a class="icon-btn"
                                            href="{{ route('superadmin.product-categories.index', ['edit' => $category->id]) }}"
                                            title="Edit">
                                            ✎
                                        </a>


                                        {{-- Delete --}}

                                        <form method="POST"
                                            action="{{ route('superadmin.product-categories.destroy', $category) }}"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this category?'
                                                );
                                            ">

                                            @csrf

                                            @method('DELETE')

                                            <button class="icon-btn danger" type="submit" title="Delete">
                                                🗑
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    style="
                                        text-align:center;
                                        color:var(--ink-faint);
                                        padding:40px;
                                    ">
                                    No product categories found.
                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>

            {{-- Pagination --}}

            <div class="pagination">

                @include('common.pagination.superadmin_pagination', ['pageData' => $categories])

            </div>

        </div>

    </div>


    {{-- =========================================================
         ADD / EDIT MODAL
    ========================================================== --}}

    <div id="categoryModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">

        <div class="modal-backdrop" onclick="closeCategoryModal()"></div>


        <div class="modal-content form-card" style="margin-bottom:0;">

            {{-- Modal Header --}}

            <div class="fh">

                <h3>
                    {{ $editing ? 'Edit Product Category' : 'Add Product Category' }}
                </h3>

                <button type="button" class="icon-btn" onclick="closeCategoryModal()"
                    style="
                        border:none;
                        background:transparent;
                        box-shadow:none;
                    ">
                    ✕
                </button>

            </div>


            {{-- Form --}}

            <form method="POST" enctype="multipart/form-data"
                action="{{ $editing
                    ? route('superadmin.product-categories.update', $editing)
                    : route('superadmin.product-categories.store') }}">

                @csrf

                @if ($editing)
                    @method('PUT')
                @endif


                <div class="form-grid form-grid-02">
                    {{-- Category Name --}}

                    <div class="field">
                        <label>
                            Category Name
                            <span class="req">*</span>
                        </label>

                        <input type="text" name="name"
                            value="{{ old('name', $editing->name ?? '') }}"
                            placeholder="e.g. Men's Clothing" required>

                        @error('name')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Slug --}}

                    <div class="field">

                        <label>
                            Slug
                        </label>

                        <input type="text" name="slug"
                            value="{{ old('slug', $editing->slug ?? '') }}"
                            placeholder="mens-clothing">

                        @error('slug')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Parent Category --}}

                    <div class="field">

                        <label>
                            Parent Category
                        </label>

                        <select name="parent_id">

                            <option value="">
                                Main Category
                            </option>

                            @foreach ($parentCategories as $parent)
                                @if (!$editing || $parent->id !== $editing->id)
                                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $editing->parent_id ?? '') === (string) $parent->id)>
                                        {{ $parent->name }}
                                    </option>
                                @endif
                            @endforeach

                        </select>

                        @error('parent_id')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>



                    {{-- Photo --}}

                    <div class="field">

                        <label>
                            Category Photo
                        </label>

                        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">


                        @if ($editing && $editing->photo)
                            <div class="current-photo">

                                <img src="{{ asset('storage/' . $editing->photo) }}"
                                    alt="{{ $editing->name }}">

                            </div>
                        @endif


                        @error('photo')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Status --}}

                    <div class="field">

                        <label>
                            Status
                            <span class="req">*</span>
                        </label>

                        <select name="status" required>

                            <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>
                                Active
                            </option>

                            <option value="inactive" @selected(old('status', $editing->status ?? '') === 'inactive')>
                                Inactive
                            </option>

                        </select>

                        @error('status')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                     {{-- Summary --}}

                    <div class="field full">

                        <label>
                            Summary
                        </label>

                        <textarea name="summary" rows="4" placeholder="Enter category summary...">{{ old('summary', $editing->summary ?? '') }}</textarea>

                        @error('summary')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                </div>


                {{-- Modal Footer --}}

                <div class="form-foot">

                    <button class="btn btn-ghost" type="button" onclick="closeCategoryModal()">
                        Cancel
                    </button>

                    <button class="btn btn-primary" type="submit">
                        {{ $editing ? 'Update Category' : 'Save Category' }}
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection


@push('scripts')
    <script>
        function openCategoryModal() {

            document
                .getElementById('categoryModal')
                .classList
                .add('active');

        }


        function closeCategoryModal() {

            document
                .getElementById('categoryModal')
                .classList
                .remove('active');


            if (
                window.location.search.includes('edit=')
            ) {

                window.location.href =
                    "{{ route('superadmin.product-categories.index') }}";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Auto Generate Slug
        |--------------------------------------------------------------------------
        */

        const nameInput =
            document.querySelector('input[name="name"]');

        const slugInput =
            document.querySelector('input[name="slug"]');


        if (nameInput && slugInput) {

            nameInput.addEventListener(
                'input',
                function() {

                    /*
                     * Only auto-generate when slug
                     * is empty or user has not manually
                     * changed it.
                     */

                    if (
                        !slugInput.dataset.manual
                    ) {

                        slugInput.value =
                            this.value
                            .toLowerCase()
                            .trim()
                            .replace(
                                /[^a-z0-9\s-]/g,
                                ''
                            )
                            .replace(
                                /\s+/g,
                                '-'
                            )
                            .replace(
                                /-+/g,
                                '-'
                            );

                    }

                }
            );


            slugInput.addEventListener(
                'input',
                function() {

                    this.dataset.manual = 'true';

                }
            );

        }
    </script>
@endpush
