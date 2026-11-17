@extends('superadmin.layouts.app')
@section('title', 'Products')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

<style>
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 13px;
    }

    .product-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        margin-bottom: 20px;
    }

    .filter-card {
        padding: 18px;
    }

    /* .form-control,
    .form-select {
        min-height: 42px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 14px;
    } */

    .form-control:focus,
    .form-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 .15rem rgba(99,102,241,.12);
    }

    .btn {
        border-radius: 8px;
    }

    /* .table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        padding: 13px;
    } */

    .table tbody td {
        /* font-family: var(--font-mono); */
        font-size: 11.5px;
        color: var(--ink-soft);
        background: #fff;
    }

    .product-img {
        width: 58px;
        height: 58px;
        object-fit: cover;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
    }

    .product-name {
        font-weight: 600;
        color: #1f2937;
    }

    .sku {
      font-size: 11px;
    color: var(--ink-faint);
    }

    .price {
        font-weight: 700;
        color: #111827;
    }

    .stock-number {
        font-weight: 600;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .status-inactive {
        background: #fee2e2;
        color: #b91c1c;
    }

    .stock-badge {
        font-size: 11px;
        color: #64748b;
    }

    .action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
    }

    .empty-box {
        padding: 60px 20px;
        text-align: center;
        color: #94a3b8;
    }

    .empty-box i {
        font-size: 45px;
        margin-bottom: 12px;
    }

    .pagination {
        margin-bottom: 0;
    }

    .status-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .custom-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
        margin: 0;
    }

    .custom-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .switch-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #d1d5db;
        border-radius: 30px;
        transition: 0.25s ease;
    }

    .switch-slider::before {
        content: "";
        position: absolute;
        width: 18px;
        height: 18px;
        left: 3px;
        top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: 0.25s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .custom-switch input:checked + .switch-slider {
        background-color: #20a843;
    }

    .custom-switch input:checked + .switch-slider::before {
        transform: translateX(24px);
    }

    .status-label {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }

    .product-search-filter-box{
        width: 100%;
        display: flex;
        align-items: end;
        gap: 10px;
    }

    .product-search-filter-wrapper{
        width: 100%;
        display:grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .product-search-filter-btn {
       display: flex;
       gap: 10px;
    }

    .product-title-box{
       padding: 15px 0px;
    }

    .product-list-table-box {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 12px;
    }

    .product-list-table-wrapper {
        width: 100%;
        min-width: 1000px;
        margin: 0;
        vertical-align: middle;
    }

    .product-list-table-wrapper th,
    .product-list-table-wrapper td {
        vertical-align: middle;
        white-space: nowrap;
    }


    @media (max-width: 991px) {

        .product-list-table-box {
            overflow: visible;
        }

        .product-list-table-wrapper {
            min-width: 0;
            width: 100%;
            border: 0;
            border-collapse: separate;
            /* border-spacing: 0 14px; */
        }

        /* Hide table header */
        .product-list-table-wrapper thead {
            display: none;
        }

        /* Card */
        .product-list-table-wrapper tbody,
        .product-list-table-wrapper tr {
            display: block;
            width: 100%;
        }

        .product-list-table-wrapper tr {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
            margin-bottom: 20px;
        }

        /* Each cell */
        .product-list-table-wrapper td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 42px;
            padding: 9px 0;
            border: 0 !important;
            border-bottom: 1px solid #f0f0f0 !important;
            white-space: normal;
            text-align: right;
        }

        .product-list-table-wrapper td:last-child {
            border-bottom: 0 !important;
        }

        /* Labels */
        .product-list-table-wrapper td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6b7280;
            text-align: left;
            margin-right: 15px;
            flex-shrink: 0;
        }

        /* Product image */
        .product-list-table-wrapper td:nth-child(2) {
            justify-content: flex-start;
            gap: 12px;
            padding-bottom: 14px;
        }

        .product-list-table-wrapper td:nth-child(1)::before {
            display: none;
        }

        .product-list-table-wrapper td:nth-child(2)::before {
            display: none;
        }

        /* Product name */
        .product-list-table-wrapper td:nth-child(3) {
            /* display: block; */
            text-align: left;
        }

        .product-list-table-wrapper td:nth-child(3)::before {
            display: block;
            margin-bottom: 4px;
        }

        /* Action buttons */
        .product-list-table-wrapper td:last-child {
            justify-content: flex-end;
            padding-top: 14px;
        }

        .product-list-table-wrapper td:last-child::before {
            margin-right: auto;
        }

        .product-list-table-wrapper td:last-child > div {
            justify-content: flex-end !important;
        }

       .product-info,.stock-info{
           text-align: end;
        }

            .product-card-02{
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    }


    /* reponsive css start  */
    @media(max-width:767px){
        .product-search-filter-box{
           flex-direction: column;
        }
        .product-search-filter-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }
    }

     @media(max-width:575px){
        .product-search-filter-wrapper {
            grid-template-columns: repeat(1, 1fr);
        }
        .product-search-filter-btn {
             width: 100%;
        }

        .product-search-filter-btn .btn{
             width: 100%;
             display: flex;
             justify-content: center;
        }

        .product-list-table-wrapper tr {
            padding: 13px;
            border-radius: 12px;
        }

        .product-list-table-wrapper td {
            font-size: 13px;
        }

        .product-list-table-wrapper td::before {
            font-size: 12px;
        }

        .product-list-table-wrapper .product-name {
            font-size: 15px;
            font-weight: 600;
        }

        .product-list-table-wrapper .cell-av {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-list-table-wrapper .product-img {
            width: 48px;
            height: 48px;
        }
    }

</style>

<div class="container-fluid product-page">

    {{-- HEADER --}}
    <div class="page-head">
        <div>
           <div class="page-title">Products</div>
            <div class="page-subtitle">
                Manage all your products from here
            </div>
        </div>
        <a href="{{ route('superadmin.product.create') }}"  class="btn btn-primary px-4"><i class="fa fa-plus me-1"></i>  Add Product </a>
    </div>


    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button"  class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif


    {{-- ERROR --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa fa-exclamation-circle me-1"></i>
            {{ session('error') }}
            <button type="button"  class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif


    {{-- FILTER --}}
    <div class="product-card filter-card mb-4">
        <form method="GET" action="{{ route('superadmin.product.index') }}">
            <div class="product-search-filter-box">
                <div class="product-search-filter-wrapper">
                    {{-- SEARCH --}}
                    <div class="input-field">
                        <label class="lbl"> Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">  <i class="fa fa-search text-muted"></i>  </span>
                            <input type="text" class="tb-input" name="search" class="form-control" placeholder="Search product, SKU..."  value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- CATEGORY --}}
                    <div class="input-field">
                        <label class="lbl">Category </label>
                        <select name="cat_id" class="tb-select form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('cat_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BRAND --}}
                    <div class="input-field">
                        <label class="lbl"> Brand</label>
                        <select name="brand_id" class="tb-select form-select">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div class="input-field">
                        <label class="lbl">   Status  </label>
                        <select name="status" class="tb-select form-select">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>  Active </option>
                            <option value="inactive"{{ request('status') == 'inactive' ? 'selected' : '' }}>     Inactive </option>
                        </select>
                    </div>
                </div>

                {{-- BUTTONS --}}
                <div class="product-search-filter-btn">
                    {{-- <label class="form-label d-block">&nbsp;</label> --}}
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fa fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('superadmin.product.index') }}" class="btn btn-ghost border">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                </div>
            </div>
        </form>
    </div>

    {{-- PRODUCT TABLE --}}

    <div class="product-title-box">
        <div>
            <strong>Product List</strong>
            <span class="text-muted small ms-2">
                {{ $products->total() }} Products
            </span>
        </div>
    </div>

    <div class="product-card product-card-02">
        <div class="table-responsive">
            <div class="product-list-table-box">
                <table class="table product-list-table-wrapper" style="over-flow:hidden;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    @forelse($products as $product)
                        <tr>
                            {{-- NUMBER --}}
                            <td> {{ $products->firstItem() + $loop->index }} </td>
                            {{-- IMAGE --}}
                        
                            <td>
                                @if(!empty($product->photos) && is_array($product->photos))

                                    @php
                                        $firstPhoto = $product->photos[0] ?? null;

                                        if (is_array($firstPhoto)) {
                                            $imagePath = $firstPhoto['thumbnail']
                                                ?? $firstPhoto['image']
                                                ?? $firstPhoto['path']
                                                ?? null;
                                        } else {
                                            $imagePath = $firstPhoto;
                                        }
                                    @endphp

                                    @if(!empty($imagePath) && is_string($imagePath))

                                        <img src="{{ Storage::disk('s3')->url($imagePath) }}"
                                            class="cell-av"
                                            alt="{{ $product->name }}">

                                    @else

                                        <div class="product-img d-flex align-items-center justify-content-center">
                                            <i class="fa fa-image text-muted"></i>
                                        </div>

                                    @endif

                                @else

                                    <div class="product-img d-flex align-items-center justify-content-center">
                                        <i class="fa fa-image text-muted"></i>
                                    </div>

                                @endif
                            </td>
                            {{-- PRODUCT --}}
                            <td data-label="Product">
                                <div class="product-info">
                                    <div class="product-name"> {{ $product->name }} </div>
                                    <div class="sku">  SKU:
                                        {{ $product->sku ?: 'N/A' }}
                                    </div>
                                    @if($product->modelno)
                                        <div class="sku">
                                            Model:
                                            {{ $product->modelno }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- CATEGORY --}}
                            <td data-label="Category">
                                <span class="text-muted">
                                    {{ optional($product->category)->name ?: '—' }}
                                </span>
                            </td>

                            {{-- BRAND --}}
                            <td data-label="Brand">
                                <span class="text-muted">
                                    {{ optional($product->brand)->name ?: '—' }}
                                </span>
                            </td>

                            {{-- PRICE --}}
                            <td data-label="Price">
                                <div class="price-info">
                                    <div class="price">
                                        ₹ {{ number_format($product->sale_price ?? 0, 2) }}
                                    </div>
                                    @if($product->compare_price > 0)
                                        <small class="text-muted text-decoration-line-through">
                                            ₹ {{ number_format($product->compare_price, 2) }}
                                        </small>
                                    @endif
                                 </div>
                            </td>

                            {{-- STOCK --}}
                            <td data-label="Stock">
                                 <div class="stock-info">
                                    <div class="stock-number">
                                        {{ $product->available_stock ?? 0 }}
                                    </div>

                                    <div class="stock-badge">
                                        {{ ucfirst(str_replace('_', ' ', $product->stock_status ?? '')) }}
                                    </div>
                                 </div>
                            </td>

                        
                        <td data-label="Status">
                                <div class="status-wrapper">
                                    <label class="custom-switch">
                                        <input type="checkbox"
                                            class="status-toggle"
                                            data-id="{{ $product->id }}"
                                            {{ $product->status === 'active' ? 'checked' : '' }}>

                                        <span class="switch-slider"></span>
                                    </label>

                                    <span class="status-label status-text">
                                        {{ $product->status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>

                                </div>
                            </td>
                            {{-- ACTION --}}
                            <td data-label="Action">
                                <div style="display:flex;gap:10px;">
                                    <a class="icon-btn" href="{{ route('superadmin.product.edit', $product->id) }}" title="Edit">✎ </a>
                                    <form action="{{ route('superadmin.product.destroy', $product->id) }}" method="POST"  class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn danger" title="Delete">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-box">
                                    <i class="fa fa-box-open d-block"></i>
                                    <strong>No Products Found</strong>
                                    <div class="small">Try changing your search or filters.  </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION --}}
        <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $products])
        </div> 
    </div>
</div>
@endsection

@push('scripts')
<script>

document.querySelectorAll('.status-toggle').forEach(function(toggle) {

    toggle.addEventListener('change', function() {

        let checkbox = this;

        let status = checkbox.checked ? 'active' : 'inactive';

        fetch("{{ route('superadmin.product.changeStatus') }}", {
            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },

            body: JSON.stringify({
                id: checkbox.dataset.id,
                status: status
            })
        })

        .then(res => res.json())

        .then(data => {

            if (data.success) {

                checkbox
                    .closest('.status-wrapper')
                    .querySelector('.status-text')
                    .innerText = checkbox.checked
                        ? 'Active'
                        : 'Inactive';

            } else {

                // Revert toggle
                checkbox.checked = !checkbox.checked;

                alert(
                    'Product status could not be updated.'
                );
            }

        })

        .catch(error => {

            console.error(error);

            // Revert toggle
            checkbox.checked = !checkbox.checked;

            alert(
                'Something went wrong while updating product status.'
            );

        });

    });

});

</script>
@endpush