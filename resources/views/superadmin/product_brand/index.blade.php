@extends('superadmin.layouts.app')

@section('title', 'Product Brands')

<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')

    @push('styles')
        <style>
            /*   PRODUCT BRAND MODAL========================================================= */

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


            /*      BRAND   */

            .brand-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .brand-photo {
                width: 42px;
                height: 42px;
                border-radius: 9px;
                object-fit: cover;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
            }

            .brand-photo-placeholder {
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

            .brand-details {
                min-width: 0;
            }

            .brand-details strong {
                display: block;
                font-size: 14px;
                color: var(--ink, #0f172a);
            }

            .brand-details small {
                display: block;
                margin-top: 2px;
                color: var(--ink-faint, #94a3b8);
                font-size: 11px;
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

            .text-danger {
                display: block;
                margin-top: 5px;
                color: #dc2626;
                font-size: 12px;
            }


            /*  RESPONSIVE TABLE*/

            .product-brand-table-box {
                overflow-x: auto;
            }

            .product-brand-table {
                min-width: 900px;
            }


            /*   SEARCH / FILTER */

            .product-brand-search-filter-box {
                width: 100%;
                display: flex;
                gap: 10px;
                align-items: flex-end;
            }

            .product-brand-search-filter-wrapper {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .product-brand-filter-btn {
                width: 100%;
                display: flex;
                gap: 10px;
            }

            .product-brand-filter-btn .btn{
                width: 100%;
                display: flex;
                justify-content: center;
            }



            /*  STATUS TOGGLE */

            .brand-status-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .brand-switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
                flex-shrink: 0;
            }

            .brand-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .brand-slider {
                position: absolute;
                cursor: pointer;
                inset: 0;
                background: #cbd5e1;
                transition: .25s;
                border-radius: 50px;
            }

            .brand-slider:before {
                content: "";
                position: absolute;
                width: 18px;
                height: 18px;
                left: 3px;
                top: 3px;
                background: #fff;
                transition: .25s;
                border-radius: 50%;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            }

            .brand-switch input:checked + .brand-slider {
                background: #16a34a;
            }

            .brand-switch input:checked + .brand-slider:before {
                transform: translateX(20px);
            }

            .brand-switch input:disabled + .brand-slider {
                cursor: not-allowed;
                opacity: .6;
            }


            /* =RESPONSIVE = */

            @media(max-width:767px) {

                .product-brand-table {
                    min-width: 100%;
                }
            }

            @media(max-width:575px) {
                .product-brand-search-filter-box {
                    flex-direction: column;
                }
                .product-brand-search-filter-wrapper {
                    grid-template-columns: repeat(1, 1fr);
                }

                .product-brand-filter-btn {
                    width: 100%;
                }

                .product-brand-filter-btn .btn {
                    width: 100%;
                    display: flex;
                    justify-content: center;
                }

            }
        </style>
    @endpush


    <div class="panel active">
        <div class="page-head">
            <div><h1>Product Brands</h1>
                <p> Manage product brands.</p>
            </div>
            <button type="button" class="btn btn-primary"  onclick="openBrandModal()"> Add Brand  </button>
        </div>

        {{-- SEARCH / FILTER --}}

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="product-brand-search-filter-box">
                    <div class="product-brand-search-filter-wrapper">
                        {{-- Search --}}
                        <div class="input-field">
                            <label class="lbl" for="search">  Search </label>
                            <input class="tb-input search-field"  type="text" id="search" name="search" value="{{ request('search') }}"  placeholder="Search brand">
                        </div>

                        {{-- Status --}}

                        <div class="input-field">
                            <label class="lbl" for="status">  Status </label>
                            <select  class="tb-select status-field" id="status" name="status"  onchange="this.form.submit()">
                                <option value="">  All Statuses </option>
                                <option value="active"  @selected(request('status') === 'active')> Active </option>
                                <option value="inactive" @selected(request('status') === 'inactive')>  Inactive </option>
                            </select>
                        </div>
                    </div>

                    <div class="product-brand-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter  </button>
                        <a href="{{ request()->url() }}" class="btn btn-ghost">  Reset </a>
                    </div>
                </div>
            </form>
        </div>


        {{-- BRAND TABLE--}}

        <div class="table-wrap table-wrap-res-02">
            <div class="product-brand-table-box">
                <table class="level-table-wrapper product-brand-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Brand</th>
                            <th>Slug</th>
                            <th>Summary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                            <tr>
                                <td data-label="#"  class="mono"> {{ $brands->firstItem() + $loop->index }} </td>

                                {{-- Brand --}}

                                <td data-label="Brand">
                                    <div class="brand-info">
                                        @if($brand->photo)
                                            <img src="{{ Storage::disk('s3')->url($brand->photo) }}" alt="{{ $brand->name }}" class="brand-photo">
                                        @else
                                            <div class="brand-photo-placeholder">🏷️</div>
                                        @endif


                                        <div class="brand-details">
                                            <strong> {{ $brand->name }} </strong>
                                            {{-- <small> ID: #{{ $brand->id }}</small> --}}
                                        </div>
                                    </div>
                                </td>

                                {{-- Slug --}}

                                <td data-label="Slug"> <span class="mono slug-text"> {{ $brand->slug }} </span>  </td>

                                {{-- Summary --}}

                                <td data-label="Summary">
                                    @if($brand->summary)
                                        <span class="summary-text"> {{ \Illuminate\Support\Str::limit($brand->summary, 60) }} </span>
                                    @else
                                        <span style="color:var(--ink-faint);">   —  </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                {{-- <td data-label="Status">
                                    <div class="brand-status-wrapper">
                                        <label class="brand-switch">
                                            <input type="checkbox"  class="brand-status-toggle" data-id="{{ $brand->id }}" {{ $brand->status === 'active' ? 'checked' : '' }}>
                                            <span class="brand-slider"></span>
                                        </label>

                                        <span id="brand-status-text-{{ $brand->id }}"  class="status-badge {{ $brand->status === 'active' ? 'active' : 'inactive' }}">
                                            {{ ucfirst($brand->status) }}
                                        </span>
                                    </div>
                                </td> --}}

                                <td data-label="Status">
                                    <div class="brand-status-wrapper">

                                        <label class="brand-switch">
                                            <input
                                                type="checkbox"
                                                class="brand-status-toggle"
                                                data-id="{{ $brand->id }}"
                                                {{ $brand->status === 'active' ? 'checked' : '' }}
                                            >

                                            <span class="brand-slider"></span>
                                        </label>

                                        <span
                                            id="brand-status-text-{{ $brand->id }}"
                                            class="status-badge status-text {{ $brand->status === 'active' ? 'active' : 'inactive' }}"
                                        >
                                            {{ ucfirst($brand->status) }}
                                        </span>

                                    </div>
                                </td>


                                {{-- Actions --}}

                                <td  data-label="Actions" class="row-actions">
                                    <div  style=" display:flex; gap:10px;">
                                        {{-- Edit --}}
                                        <a class="icon-btn" href="{{ route('superadmin.product-brands.index', ['edit' => $brand->id]) }}"  title="Edit"> ✎</a>
                                        {{-- Delete --}}

                                        <form  method="POST" action="{{ route('superadmin.product-brands.destroy', $brand) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn danger"  type="submit" title="Delete">🗑 </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--ink-faint); padding:40px;">  No product brands found. </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}

            <div class="pagination">
                @include('common.pagination.superadmin_pagination',['pageData' => $brands])
            </div>
        </div>
    </div>

    <div id="brandModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeBrandModal()"> </div>
        <div class="modal-content form-card" style="margin-bottom:0;">
            {{-- Modal Header --}}
            <div class="fh">
                <h3> {{ $editing  ? 'Edit Product Brand' : 'Add Product Brand' }}  </h3>
                <button type="button" class="icon-btn"  onclick="closeBrandModal()" style="border:none; background:transparent;  box-shadow:none;"> ✕ </button>
            </div>
            {{-- Form --}}
            <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('superadmin.product-brands.update', $editing) : route('superadmin.product-brands.store')}}">
                @csrf
                @if($editing)
                    @method('PUT')
                @endif

                <div class="form-grid form-grid-02">
                    {{-- Brand Name --}}
                    <div class="field">
                        <label> Brand Name <span class="req">*</span> </label>
                        <input type="text" name="name" value="{{ old('name', $editing->name ?? '') }}" placeholder="e.g. Nike"  required>
                        @error('name')
                            <small class="text-danger"> {{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="field">
                        <label>  Slug </label>
                        <input  type="text" name="slug"  value="{{ old('slug', $editing->slug ?? '') }}"  placeholder="nike">
                        @error('slug')
                            <small class="text-danger"> {{ $message }}  </small>
                        @enderror
                    </div>

                    {{-- Photo --}}
                    <div class="field">
                        <label>   Brand Photo </label>
                        <input type="file" name="photo" accept="image/*">
                        {{-- Current S3 Photo --}}
                        @if($editing && $editing->photo)
                            <div class="current-photo">
                                <img  src="{{ Storage::disk('s3')->url($editing->photo) }}" alt="{{ $editing->name }}">
                            </div>
                        @endif
                        @error('photo')
                            <small class="text-danger"> {{ $message }} </small>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="field">
                        <label> Status <span class="req">*</span> </label>
                        <select  name="status" required>
                            <option value="active"@selected(old('status',$editing->status ?? 'active') === 'active')>  Active  </option>
                            <option value="inactive" @selected(old('status', $editing->status ?? '') === 'inactive')>   Inactive </option>
                        </select>
                        @error('status')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Summary --}}
                    <div class="field full">
                        <label> Summary</label>
                        <textarea name="summary" rows="4" placeholder="Enter brand summary...">{{ old('summary', $editing->summary ?? '') }}</textarea>
                        @error('summary')
                            <small class="text-danger"> {{ $message }} </small>
                        @enderror
                    </div>
                </div>
                {{-- Modal Footer --}}
                <div class="form-foot">
                    <button  class="btn btn-ghost" type="button"  onclick="closeBrandModal()"> Cancel</button>
                    <button  class="btn btn-primary"  type="submit">{{ $editing ? 'Update Brand'  : 'Save Brand' }} </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')

<script>

    function openBrandModal()
    {
        document.getElementById('brandModal').classList.add('active');
    }

    function closeBrandModal()
    {
        document.getElementById('brandModal').classList.remove('active');

        if (
            window.location.search.includes('edit=')
        ) {
            window.location.href = "{{ route('superadmin.product-brands.index') }}";
        }
    }

    const brandNameInput = document.querySelector(  '#brandModal input[name="name"]'  );
    const brandSlugInput = document.querySelector(  '#brandModal input[name="slug"]'  );

    if (  brandNameInput && brandSlugInput) {
        brandNameInput.addEventListener('input',
            function () {
                if (!brandSlugInput.dataset.manual) {

                    brandSlugInput.value =
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

        brandSlugInput.addEventListener(
            'input',
            function () {
                this.dataset.manual = 'true';
            }
        );
    }

  document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.brand-status-toggle').forEach(function (toggle) {

        toggle.addEventListener('change', function () {

            let checkbox = this;

            fetch("{{ route('superadmin.product-brands.changeStatus') }}", {
                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },

                body: JSON.stringify({
                    id: checkbox.dataset.id,
                    status: checkbox.checked ? "active" : "inactive"
                })
            })

            .then(res => res.json())

            .then(data => {

                if (data.success) {

                    checkbox.closest("td")
                        .querySelector(".status-text")
                        .innerText = checkbox.checked
                            ? "Active"
                            : "Inactive";

                    showAlert(
                        'success',
                        'Success',
                        'Brand status updated successfully.'
                    );

                } else {

                    checkbox.checked = !checkbox.checked;

                    showAlert(
                        'warning',
                        'Warning',
                        'Brand status could not be updated.'
                    );
                }

            })

            .catch(() => {

                checkbox.checked = !checkbox.checked;

                showAlert(
                    'error',
                    'Error',
                    'Something went wrong.'
                );

            });

        });

    });

});

</script>

@endpush