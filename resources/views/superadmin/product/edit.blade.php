@extends('superadmin.layouts.app')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

<style>
    input,select,textarea {
        border: 1px solid var(--line);
        border-radius: 9px;
        padding: 9px 11px;
        font-family: var(--font-body);
        font-size: 10px;
        background: var(--paper);
        color: var(--ink);
    }

     input,select,textarea:focus {
        outline: none;
        box-shadow: none;
    }

    .input-field .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--ink-faint);
    }

    .product-edit-card-box{
        width: 100%;
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .product-edit-card-left-box{
        width: 70%;
         display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .product-edit-card-right-box{
        width: 30%;
         display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .card-info-box{
        width:100%;
        background-color: #fff;
        border-radius:12px;
        border: 1px solid var(--line);
    }

    .card-header{
        padding: 20px;
    }

    .product-card-body{
        padding:0px 20px 20px 20px;
    }

    .product-update-btn{
        background-color: #fff;
        border-radius:12px;
        padding: 20px 20px;
        display: flex;
        gap: 10px;
    }

    .product-list{
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    

    .input-field{
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .input-field .form-control{
        border: 1px solid var(--line);
        border-radius: 9px;
        padding: 9px 11px;
        font-family: var(--font-body);
        font-size: 12.5px;
        background: var(--paper);
         color: var(--ink);
    }

    .input-field .form-control:focus {
        outline: none;
        box-shadow: none;
    }

    .editor {
        width: 100%;
        border: 1px solid #d5dfed;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    /* Toolbar */
    .editor-toolbar {
        height: 36px;
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 8px;
        border-bottom: 1px solid #e1e7ef;
        background: #fff;
    }

    .editor-toolbar select {
        width:auto !important;
    }

    .editor-toolbar select {
        height: 30px;
        min-width: 140px;
        padding: 0 15px;
        border: 0;
        border-right: 1px solid #edf0f5;
        outline: none;
        background: #f8fafd;
        color: #64748b;
        font-size: 12px;
        cursor: pointer;
    }

    .editor-toolbar button {
        width: 32px;
        height: 30px;
        border: 0;
        background: transparent;
        border-radius: 5px;
        color: #5d6d82;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: .2s;
    }

    .editor-toolbar button:hover {
        background: #f1f5f9;
        color: #1d4ed8;
    }

    .editor-toolbar button b {
        font-size: 16px;
    }

    .editor-toolbar button i {
        font-size: 16px;
        font-family: Georgia, serif;
    }

    .toolbar-divider {
        width: 1px;
        height: 38px;
        background: #edf0f5;
        margin: 0 5px;
    }

    /* Description area */
    .description-editor {
        width: 100%;
        min-height: 140px;
        padding: 20px;
        font-size: 16px;
        line-height: 1.6;
        color: #1f2937;
        outline: none;
        background: #fff;
    }

    .description-editor:focus {
        outline: none;
    }

    .description-editor:empty:before {
        content: "";
    }

    /* Content styling */
    .description-editor h1 {
        font-size: 28px;
        margin: 10px 0;
    }

    .description-editor h2 {
        font-size: 24px;
        margin: 10px 0;
    }

    .description-editor h3 {
        font-size: 20px;
        margin: 10px 0;
    }

    .description-editor blockquote {
        margin: 10px 0;
        padding-left: 15px;
        border-left: 4px solid #3b82f6;
        color: #64748b;
    }

    .description-editor img {
        max-width: 100%;
        height: auto;
    }

    .description-editor table {
        width: 100%;
        border-collapse: collapse;
    }

    .description-editor table td,
    .description-editor table th {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #specifications-list{
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .specification-row{
      display: flex;
       gap: 10px;
    }

    .specification-col{
        width: 100%;
        display: grid;
       grid-template-columns: repeat(2, 1fr);
       gap: 10px;
    }

    #attributes-list{
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .attribute-row{
         display: flex;
         gap: 10px;
    }

    .attribute-col{
        width: 100%;
        display: grid;
       grid-template-columns: repeat(2, 1fr);
       gap: 10px;
    }

    .product-list-02{
       display: grid;
       grid-template-columns: repeat(2, 1fr);
       gap: 10px;
    }

    .product-sticky-sidebar{
        position: sticky;
        top: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }


    /* Responsive */
    @media (max-width: 767px) {
        .editor-toolbar {
            height: auto;
            flex-wrap: wrap;
        }

        .editor-toolbar select {
            width: 100%;
            border-right: 0;
            margin-bottom: 5px;
        }

        .description-editor {
            min-height: 200px;
        }

        .product-edit-card-box {
            flex-direction: column;
        }

        .product-edit-card-left-box {
            width: 100%;
        }
         .product-edit-card-right-box {
            width: 100%;
        }
    }

    @media(max-width:400px){
        .product-list-02 {
            grid-template-columns: repeat(1, 1fr);
        }
    }
</style>

<div class="container-fluid">
    {{-- Header --}}
    <div class="page-head">
        <div>
            <h1>Edit Product</h1>
            <p>Update product details</p>
        </div>

        <a href="{{ route('superadmin.product.index') }}"
           class="btn btn-ghost">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>


    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form action="{{ route('superadmin.product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="product-edit-card-box">
            <div class="product-edit-card-left-box">
                {{-- ================= BASIC INFORMATION ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Basic Information</strong>
                    </div>
                    <div class="product-card-body">
                        <div class="product-list">
                            {{-- Product Name --}}
                            <div class="input-field">
                                <label class="lbl"> Product Name <span class="text-danger">*</span> </label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                            </div>
                            {{-- Summary --}}
                            {{-- <div class="col-md-12">
                                <label class="lbl">Summary</label>

                                <textarea name="summary"
                                        class="form-control"
                                        rows="3">{{ old('summary', $product->summary) }}</textarea>
                            </div> --}}


                            {{-- Description --}}
                        <div class="input-field">
                                {{-- <textarea
                                    name="description"
                                    id="product-description"
                                    class="form-control tinymce"
                                >{!! old('description', $product->description ?? '') !!}
                                </textarea>

                                @error('description')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror --}}
                                    <label class="lbl" for="product-description">  Product Description</label>

                                    <div class="editor">

                                        <!-- Toolbar -->
                                        <div class="editor-toolbar">

                                            <select id="formatBlock" title="Format">
                                                <option value="p">Paragraph</option>
                                                <option value="h1">Heading 1</option>
                                                <option value="h2">Heading 2</option>
                                                <option value="h3">Heading 3</option>
                                            </select>

                                            <div class="toolbar-divider"></div>

                                            <button type="button" onclick="formatText('bold')" title="Bold">
                                                <b>B</b>
                                            </button>

                                            <button type="button" onclick="formatText('italic')" title="Italic">
                                                <i>I</i>
                                            </button>

                                            <button type="button" onclick="addLink()" title="Link">
                                                🔗
                                            </button>

                                            <button type="button" onclick="formatText('insertUnorderedList')" title="Bullet List">
                                                ☷
                                            </button>

                                            <button type="button" onclick="formatText('insertOrderedList')" title="Number List">
                                                ☷
                                            </button>

                                            <button type="button" onclick="addImage()" title="Image">
                                                🖼
                                            </button>

                                            <button type="button" onclick="formatText('formatBlock','blockquote')" title="Quote">
                                                ❝
                                            </button>

                                            <button type="button" onclick="addTable()" title="Table">
                                                ▦
                                            </button>

                                            <button type="button" onclick="addVideo()" title="Video">
                                                ▷
                                            </button>

                                            <button type="button" onclick="formatText('undo')" title="Undo">
                                                ↶
                                            </button>

                                            <button type="button" onclick="formatText('redo')" title="Redo">
                                                ↷
                                            </button>

                                        </div>

                                        <!-- Editable Area -->
                                        <div id="descriptionEditor"
                                            class="description-editor"
                                            contenteditable="true">
                                              {!! old('description', $product->description ?? '') !!}
                                        </div>

                                    </div>

                                    <!-- Actual value submit karne ke liye -->
                                    <textarea name="description" id="descriptionValue" hidden> {!! old('description', $product->description ?? '') !!}</textarea>
                            </div>
                        </div>
                    </div>
                </div>


                {{-- ================= IMAGES ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Product Images</strong>
                    </div>
                    <div class="product-card-body">
                        {{-- Existing Images --}}
                            @if(!empty($product->photos))
                                <div class="row g-3 mb-3">

                                    @foreach($product->photos as $index => $photo)

                                        @php
                                            $imagePath = '';

                                            if (is_string($photo)) {
                                                $imagePath = $photo;
                                            } elseif (is_array($photo)) {
                                                $imagePath = $photo['thumbnail']
                                                    ?? $photo['image']
                                                    ?? $photo['path']
                                                    ?? '';
                                            }
                                        @endphp

                                        @if(is_array($imagePath))
                                            @php
                                                $imagePath = $imagePath['path']
                                                    ?? $imagePath['url']
                                                    ?? $imagePath['image']
                                                    ?? '';
                                            @endphp
                                        @endif

                                        @if(!empty($imagePath) && is_string($imagePath))

                                            <div class="col-md-2">
                                                <div class="border rounded p-2 position-relative">

                                                    <img src="{{ Storage::disk('s3')->url($imagePath) }}"
                                                        class="img-fluid rounded"
                                                        style="width:100%;height:130px;object-fit:cover;">
                                                    <a href="{{ route('superadmin.product.deleteImage', [$product->id, $index]) }}" class="text-danger"
                                                         style="margin:10px 0px; display:inline-flex; color:var(--coral);">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </a>
                                                </div>
                                            </div>

                                        @endif

                                    @endforeach

                                </div>
                            @else

                                <div class="text-muted" style="margin-bottom: 10px;">
                                    No images uploaded.
                                </div>

                            @endif


                        {{-- New Images --}}
                         <div class="input-field">
                            <label class="lbl">Add New Images</label>
                            <input type="file"  name="photos[]"class="form-control"  multiple accept="image/jpeg,image/jpg,image/png,image/webp">
                         </div>
                        <small class="text-muted" style="display: block; margin-top: 10px;">
                            You can select multiple images.
                        </small>
                    </div>
                </div>

                {{-- ================= SPECIFICATIONS ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Specifications</strong>
                    </div>

                    <div class="product-card-body">
                        <div id="specifications-list">
                            @php
                                $specifications = is_string($product->specifications)
                                    ? json_decode($product->specifications, true)
                                    : ($product->specifications ?? []);

                                $specifications = is_array($specifications)
                                    ? $specifications
                                    : [];
                            @endphp
                            {{-- ExistingSpecifications --}}
                            @foreach($specifications as $spec)
                                <div class="specification-row">
                                    <div class="specification-col">
                                        <div class="specification-col-input">
                                            <input type="text"  name="label[]"class="form-control" value="{{ $spec['label'] ?? '' }}" placeholder="Label">
                                        </div>
                                        <div class="specification-col-input">
                                            <input type="text"   name="property[]" class="form-control" value="{{ $spec['property'] ?? '' }}" placeholder="Property">
                                        </div>
                                    </div>
                                    <div class="specification-col-btn">
                                        <button type="button"  class="btn btn-danger remove-specification">   Remove  </button>
                                    </div>
                                </div>
                            @endforeach

                        </div>

                        {{-- Add New Specification --}}
                        <div class="specification-row">
                            <div class="specification-col">
                                <div class="specification-col-input">
                                    <input type="text" id="specification-label" class="form-control" placeholder="Label">
                                </div>
                                <div class="specification-col-input">
                                    <input type="text" id="specification-property" class="form-control"placeholder="Property">
                                </div>
                            </div>
                            <div class="specification-col-btn">
                                <button type="button" class="btn btn-primary  btn-sm mt-2" id="addSpecification"> <i class="fa fa-plus me-1"></i> Add </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="card-info-box">

                    <div class="card-header">
                        <strong>Attributes</strong>
                    </div>

                    <div class="product-card-body">

                        @php
                            $attributes = is_string($product->attributes)
                                ? json_decode($product->attributes, true)
                                : ($product->attributes ?? []);
                        @endphp

                        <div id="attributes-list">

                            @if(!empty($attributes))

                                @foreach($attributes as $attr)

                                    <div class="attribute-row">
                                        <div class="attribute-col">
                                            <div class="attribute-col-input">

                                                <input type="text"
                                                    name="attribute_type[]"
                                                    class="form-control"
                                                    value="{{ $attr['attribute_type'] ?? '' }}"
                                                    placeholder="Attribute Type">

                                            </div>

                                            <div class="attribute-col-input">

                                                <input type="text"
                                                    name="attribute_value[]"
                                                    class="form-control"
                                                    value="{{ $attr['attribute_value'] ?? '' }}"
                                                    placeholder="Attribute Value">

                                            </div>
                                        </div>
                                        <div class="attribute-col-btn">
                                            <button type="button"
                                                    class="btn btn-danger remove-attribute w-100">
                                                Remove
                                            </button>
                                        </div>

                                    </div>

                                @endforeach

                            @endif

                        </div>

                        <button type="button"
                                class="btn btn-primary btn-sm mt-2"
                                id="addAttribute">
                            + Add Attribute
                        </button>

                    </div>

                </div> --}}

                    {{-- ================= PRODUCT INFORMATION ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Product Information</strong>
                    </div>

                    <div class="product-card-body">
                        <div class="product-list-02">

                            {{-- <div class="input-field">
                                <label class="lbl">Model Number</label>
                                <input type="text"  name="modelno" class="form-control" value="{{ old('modelno', $product->modelno) }}">
                            </div> --}}

                            <div class="input-field">
                                <label class="lbl">SKU</label>
                                <input type="text" name="sku"  class="form-control"  value="{{ old('sku', $product->sku) }}">
                            </div>


                            <div class="input-field">
                                <label class="lbl">ISBN / UPC / Barcode</label>
                                <input type="text" name="isbn_upc_barcode" class="form-control" value="{{ old('isbn_upc_barcode', $product->isbn_upc_barcode) }}">
                            </div>


                            {{-- <div class="input-field">
                                <label class="lbl">Item Size</label>
                                <input type="text"  name="item_size"  class="form-control"  value="{{ old('item_size', $product->item_size) }}">
                            </div> --}}


                            <div class="input-field">
                                <label class="lbl">Tags</label> 
                                <input type="text" name="tags" class="form-control" value="{{ old('tags', $product->tags) }}">
                            </div>


                            {{-- <div class="input-field">
                                <label class="lbl">Variant</label>
                                <input type="text"  name="variant" class="form-control" value="{{ old('variant', $product->variant) }}">
                            </div> --}}

                        </div>

                    </div>
                </div>
            </div>

    
            <div class="product-edit-card-right-box">
                <div class="product-sticky-sidebar">
                 {{-- ================= CATEGORY / BRAND ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Category & Brand</strong>
                    </div>
                    <div class="product-card-body">
                        <div class="product-list-02">
                            {{-- Category --}}
                            <div class="input-field">
                                <label class="lbl"> Category  </label>
                                <select name="cat_id" id="category"class="form-select">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('cat_id', $product->cat_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sub Category --}}
                            {{-- <div class="input-field">
                                <label class="lbl"> Sub Category </label>
                                <select name="parent_id"  id="subcategory"   class="form-select">
                                    <option value="">Select Sub Category</option>
                                    @foreach($subcategories as $subcategory)
                                        <option value="{{ $subcategory->id }}"
                                            {{ old('parent_id', $product->parent_id) == $subcategory->id ? 'selected' : '' }}>
                                            {{ $subcategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div> --}}


                            {{-- Brand --}}
                            <div class="input-field">

                                <label class="lbl">  Brand </label>
                                <select name="brand_id" class="form-select">
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}"
                                            {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= PRICING ================= --}}
                <div class="card-info-box">
                    <div class="card-header"> <strong>Pricing</strong> </div>
                    <div class="product-card-body">
                        <div class="product-list-02">
                            <div class="input-field">
                                <label class="lbl">   Sale Price </label>
                                <input type="number"  step="0.01"  min="0" name="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}">
                            </div>
                            <div class="input-field">
                                <label class="lbl"> Compare Price </label>
                                <input type="number"  step="0.01" min="0" name="compare_price" class="form-control" value="{{ old('compare_price', $product->compare_price) }}">
                            </div>


                            {{-- <div class="input-field">
                                <label class="lbl"> Buy Price </label>
                                <input type="number"  step="0.01" min="0" name="buy_price"  class="form-control" value="{{ old('buy_price', $product->buy_price) }}">
                            </div> --}}

                            <div class="input-field">
                                <label class="lbl"> Shipping Charge  </label>
                                <input type="number" step="0.01" min="0" name="shipping_charge" class="form-control"  value="{{ old('shipping_charge', $product->shipping_charge) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= STOCK ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Stock Information</strong>
                    </div>

                    <div class="product-card-body">
                        <div class="product-list-02">
                            <div class="input-field">
                                <label class="lbl"> Available Stock  </label>
                                <input type="number"  min="0"   name="available_stock"  class="form-control"   value="{{ old('available_stock', $product->available_stock) }}">
                            </div>

                            {{-- <div class="input-field">
                                <label class="lbl"> Minimum Order </label>

                                <input type="number"
                                    min="1"
                                    name="min_item_order"
                                    class="form-control"
                                    value="{{ old('min_item_order', $product->min_item_order) }}">

                            </div>


                            <div class="input-field">

                                <label class="lbl">
                                    Maximum Order
                                </label>

                                <input type="number"
                                    min="1"
                                    name="maximum_item_order"
                                    class="form-control"
                                    value="{{ old('maximum_item_order', $product->maximum_item_order) }}">

                            </div> --}}


                            <div class="input-field">
                                <label class="lbl">  Stock Status   </label>
                                <select name="stock_status" class="form-select">
                                    <option value="in_stock"
                                        {{ old('stock_status', $product->stock_status) == 'in_stock' ? 'selected' : '' }}>
                                        In Stock
                                    </option>
                                    <option value="out_of_stock"
                                        {{ old('stock_status', $product->stock_status) == 'out_of_stock' ? 'selected' : '' }}>
                                        Out Of Stock
                                    </option>
                                    <option value="pre_order"
                                        {{ old('stock_status', $product->stock_status) == 'pre_order' ? 'selected' : '' }}>
                                        Pre Order
                                    </option>
                                    <option value="2-3days"
                                        {{ old('stock_status', $product->stock_status) == '2-3days' ? 'selected' : '' }}>
                                        2-3 Days
                                    </option>
                                </select>
                            </div>

                            {{-- <div class="input-field">

                                <label class="lbl">
                                    Stock Available Date
                                </label>

                                <input type="date"
                                    name="stock_avaliable_date"
                                    class="form-control"
                                    value="{{ old(
                                        'stock_avaliable_date',
                                        $product->stock_avaliable_date
                                            ? \Carbon\Carbon::parse($product->stock_avaliable_date)->format('Y-m-d')
                                            : ''
                                    ) }}">

                            </div> --}}
                        </div>
                    </div>
                </div>
                </div>
            </div>
            {{-- ================= DIMENSIONS =================
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Dimensions & Weight</strong>
                </div>

                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="lbl">Length</label>

                            <input type="text"
                                name="length"
                                class="form-control"
                                value="{{ old('length', $product->length) }}">
                        </div>


                        <div class="col-md-3">
                            <label class="lbl">Breadth</label>

                            <input type="text"
                                name="breadth"
                                class="form-control"
                                value="{{ old('breadth', $product->breadth) }}">
                        </div>


                        <div class="col-md-3">
                            <label class="lbl">Height</label>

                            <input type="text"
                                name="height"
                                class="form-control"
                                value="{{ old('height', $product->height) }}">
                        </div>


                        <div class="col-md-3">
                            <label class="lbl">Weight</label>

                            <input type="text"
                                name="weight"
                                class="form-control"
                                value="{{ old('weight', $product->weight) }}">
                        </div>

                    </div>

                </div>
            </div> --}}
        </div>

           {{-- ================= SUBMIT ================= --}}
            <div class="card-info-box">
                <div class="product-update-btn d-flex justify-content-end gap-2">
                    <a href="{{ route('superadmin.product.index') }}"  class="btn btn-ghost">  Cancel </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                        Update Product
                    </button>
                </div>

            </div>
    </form>

</div>

@endsection


@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>

$(document).ready(function () {
    $('#category').on('change', function () {
        let categoryId = $(this).val();
        $('#subcategory').html(
            '<option value="">Loading...</option>'
        );
        if (!categoryId) {
            $('#subcategory').html(
                '<option value="">Select Sub Category</option>'
            );
            return;
        }
        $.ajax({
            url: "{{ url('superadmin/products/subcategories') }}/" + categoryId,
            type: "GET",
            success: function (data) {
                let html =
                    '<option value="">Select Sub Category</option>';
                $.each(data, function (key, value) {
                    html += `
                        <option value="${value.id}">
                            ${value.name}
                        </option>
                    `;
                });
                $('#subcategory').html(html);
            },
            error: function () {
                $('#subcategory').html(
                    '<option value="">Unable to load</option>'
                );
            }
        });
    });
});


$(document).on('click', '#addSpecification', function () {
    let label = $('#specification-label').val().trim();
    let property = $('#specification-property').val().trim();
    if (label === '' || property === '') {
        alert('Please enter both label and property.');
        return;
    }
    $('#specifications-list').append(`
        <div class="specification-row">
            <div class="specification-col">
                <div class="specification-col-input">
                    <input type="text" name="label[]" class="form-control" value="${label}" placeholder="Label">
                </div>
                <div class="specification-col-input">
                    <input type="text" name="property[]"  class="form-control"   value="${property}"  placeholder="Property">
                </div>
            </div>
            <div class="specification-col-btn">
                <button type="button" class="btn btn-danger remove-specification">  Remove  </button>
            </div>
        </div>
    `);

    // Clear input fields
    $('#specification-label').val('');
    $('#specification-property').val('');
});


$(document).on('click', '.remove-specification', function () {
    $(this).closest('.specification-row').remove();
});

$(document).on('click', '.remove-specification', function () {
    $(this).closest('.specification-row').remove();
});

$('#addAttribute').click(function () {

    $('#attributes-list').append(`
        <div class="attribute-row">
            <div class="attribute-col">
                <div class="attribute-col-input">
                    <input type="text"
                        name="attribute_type[]"
                        class="form-control"
                        placeholder="Attribute Type">
                </div>

                <div class="attribute-col-input">
                    <input type="text"
                        name="attribute_value[]"
                        class="form-control"
                        placeholder="Attribute Value">
                </div>
            </div>

            <div class="<div class="attribute-col-btn">
                <button type="button"
                        class="btn btn-danger remove-attribute w-100">
                    Remove
                </button>
            </div>

        </div>
    `);

});

$(document).on('click', '.remove-attribute', function () {
    $(this).closest('.attribute-row').remove();
});
</script>

<script>
const editor = document.getElementById("descriptionEditor");
const descriptionValue = document.getElementById("descriptionValue");
const formatBlock = document.getElementById("formatBlock");

/* Text formatting */
function formatText(command, value = null) {
    editor.focus();
    document.execCommand(command, false, value);
    updateValue();
}

/* Paragraph / Heading */
formatBlock.addEventListener("change", function () {
    editor.focus();
    document.execCommand("formatBlock", false, this.value);
    updateValue();
});

/* Link */
function addLink() {
    const url = prompt("Enter URL:");

    if (url) {
        editor.focus();
        document.execCommand("createLink", false, url);
        updateValue();
    }
}

/* Image */
function addImage() {
    const url = prompt("Enter image URL:");

    if (url) {
        editor.focus();
        document.execCommand("insertImage", false, url);
        updateValue();
    }
}

/* Table */
function addTable() {
    editor.focus();

    const table = `
        <table>
            <tbody>
                <tr>
                    <td>Column 1</td>
                    <td>Column 2</td>
                    <td>Column 3</td>
                </tr>
                <tr>
                    <td>Data</td>
                    <td>Data</td>
                    <td>Data</td>
                </tr>
            </tbody>
        </table>
        <p></p>
    `;

    document.execCommand("insertHTML", false, table);
    updateValue();
}

/* Video */
function addVideo() {
    const url = prompt("Enter video URL:");

    if (url) {
        editor.focus();

        const video = `
            <video controls style="max-width:100%;">
                <source src="${url}">
            </video>
        `;

        document.execCommand("insertHTML", false, video);
        updateValue();
    }
}

/* Save editor HTML into textarea */
function updateValue() {
    descriptionValue.value = editor.innerHTML;
}

editor.addEventListener("input", updateValue);

/* Form submit se pehle value update */
const form = editor.closest("form");

if (form) {
    form.addEventListener("submit", function () {
        updateValue();
    });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const editor = document.getElementById("descriptionEditor");
    const hidden = document.getElementById("descriptionValue");

    // Edit page value load
    hidden.value = editor.innerHTML;

    // Typing ke time sync
    editor.addEventListener("input", function () {
        hidden.value = editor.innerHTML;
    });
});
</script>

@endpush