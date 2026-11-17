@extends('superadmin.layouts.app')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

<style>
    /* .product-create-page {
        padding: 24px;
        background: #f8fafc;
        min-height: 100vh;
    } */

    /* .page-header {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px 22px;
        margin-bottom: 20px;
    } */

    /* .page-title {
        font-size: 23px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 3px;
    } */

    /* .page-subtitle {
        color: #6b7280;
        font-size: 13px;
    } */

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

    #specWrapper{
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    /* .specification-row{
      display: flex;
       gap: 10px;
    }

    .specification-col{
        width: 100%;
        display: grid;
       grid-template-columns: repeat(2, 1fr);
       gap: 10px;
    } */

    /* ================= SPECIFICATIONS ================= */

#specifications-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.specification-saved-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: #f8fafc;
}

.specification-saved-col {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.specification-value {
    padding: 8px 10px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 7px;
    font-size: 12px;
}

.specification-add-btn {
    width: 90px;
}

.specification-input-row {
    display: flex;
    align-items: end;
    gap: 10px;
   
}

  .specification-col{
        width: 100%;
        display: grid;
       grid-template-columns: repeat(2, 1fr);
       gap: 10px;
    } 

@media(max-width: 576px) {

    .specification-input-row {
        flex-direction: column;
        align-items: stretch;
    }

    .specification-add-btn {
        width: 100%;
    }

    .specification-col,
    .specification-saved-col {
        grid-template-columns: 1fr;
    }

    .specification-saved-row {
        align-items: stretch;
        flex-direction: column;
    }
}

    #attributeWrapper{
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

    .upload-input-box{
        display:inline-block;
        margin-top: 10px;
    }

        .upload-input-box input{
        display:inline-block;
        margin-top: 10px;
    }

/* 
    .product-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        margin-bottom: 18px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    } */

    /* .product-card-header {
        padding: 15px 18px;
        border-bottom: 1px solid #eef0f3;
        font-size: 15px;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        justify-content: space-between;
    } */

    /* .product-card-header i {
        margin-right: 7px;
        color: #4f46e5;
    } */

    /* .product-card-body {
        padding: 20px;
    } */

    /* .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 7px;
    } */

    .required {
        color: #dc2626;
    }

    /* .form-control,
    .form-select {
        min-height: 43px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 14px;
    } */


    /* ================= EDITOR ================= */
/* 
    .note-editor.note-frame {
        border: 1px solid #d1d5db !important;
        border-radius: 9px;
        overflow: hidden;
        box-shadow: none;
    }

    .note-editor .note-toolbar {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 8px;
    }

    .note-editor .note-editable {
        min-height: 280px;
        font-size: 14px;
        line-height: 1.7;
        padding: 16px;
    }

    .note-statusbar {
        background: #f8fafc;
    } */

    /* ================= IMAGE UPLOAD ================= */

    .upload-box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 28px 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: .2s;
    }

    .upload-box:hover {
        border-color: #6366f1;
        background: #f5f3ff;
    }

    .upload-box i {
        font-size: 32px;
        color: #6366f1;
        margin-bottom: 10px;
    }

    .upload-box-title {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
    }

    .upload-box-text {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .preview-wrapper {
        position: relative;
    }

    .preview-img {
        width: 100%;
        height: 125px;
        object-fit: cover;
        border-radius: 9px;
        border: 1px solid #e5e7eb;
    }

    /* ================= DYNAMIC ROWS ================= */
/* 
    .spec-row,
    .attribute-row {
        background: #f8fafc;
        border: 1px solid #eef0f3;
        padding: 10px;
        border-radius: 9px;
    } */

    .section-note {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 5px;
    }

    /* ================= STICKY ================= */

    .sticky-card {
        position: sticky;
        top: 20px;
    }

    /* ================= SAVE ================= */

    /* .save-btn {
        min-height: 50px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(79,70,229,.18);
    } */

    /* .back-btn {
        border-radius: 9px;
        min-height: 42px;
    } */

    /* .input-group-text {
        background: #f8fafc;
        border-color: #d1d5db;
        color: #64748b;
        font-weight: 600;
    } */

    /* Responsive */

    @media(max-width:991px){
       .product-card-body {
            padding: 0px 10px 10px 10px;
        }
    }

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


    @media(max-width: 576px) {
        .product-create-page {
            padding: 12px;
        }

        .page-header {
            padding: 16px;
        }

        .product-card-body {
            padding: 15px;
        }
    }

        @media(max-width:400px){
        .product-list-02 {
            grid-template-columns: repeat(1, 1fr);
        }
    }
</style>


<div class="container-fluid product-create-page">
    {{-- ================= HEADER ================= --}}
    <div class="page-head">
        <div>
            <h1>Add Product</h1>
            <p>Create and publish a new product for your store</p>
        </div>
        <a href="{{ route('superadmin.product.index') }}"class="btn btn-ghost"> Back </a>
    </div>

    {{-- ================= ERRORS ================= --}}

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <strong> <i class="fa fa-exclamation-circle me-1"></i> Please fix the following errors:</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('superadmin.product.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
         <div class="product-edit-card-box">
            <div class="product-edit-card-left-box">
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Basic Information </strong>
                    </div>
                    <div class="product-card-body">
                        <div class="product-list">
                            <div class="input-field">
                                <label class="lbl"> Product Name <span class="required">*</span> </label>
                                <input type="text"  name="name" class="form-control" placeholder="Enter product name" value="{{ old('name') }}" required>
                            </div>

                            {{-- <div class="mb-3">
                                    <label class="form-label"> Summary  </label>
                                    <textarea name="summary"  class="form-control" rows="3"  placeholder="Write a short summary about this product">{{ old('summary') }}</textarea>
                                </div> --}}
                                {{-- 
                                <div class="col-12 mb-3">
                                    <label class="form-label" for="product-description">Product description:</label>
                                    <div class="create-product-description-textarea">
                                        <textarea class="tinymce d-none" data-tinymce="data-tinymce" name="description" id="product-description"></textarea>
                                    </div>
                                    @error('description') <div class="text-danger">{{ $message }}</div> @enderror
                                </div>
                            --}}

                            <div class="input-field">
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
                                        </div>

                                    </div>

                                    <!-- Actual value submit karne ke liye -->
                                <textarea name="description" id="descriptionValue" hidden></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ================= MEDIA ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Product Media </strong>
                    </div>
                    <div class="product-card-body">
                    <div class="upload-box">
                        <label for="photos" class="d-block">
                            <div class="upload-box-title">
                                Upload Product Images
                            </div>
                            <div class="upload-box-text">
                                JPG, JPEG, PNG or WEBP
                            </div>
                        </label>
                        <div class="upload-input-box">
                           <input type="file" name="photos[]" id="photos" multiple  accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="row mt-3" id="preview"></div>
                        </div> 
                    </div>
                </div>

                    {{-- ================= ATTRIBUTES ================= --}}
                    {{-- <div class="card-info-box">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <strong>Attributes</strong>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addAttribute()">
                              ADD
                            </button>
                        </div>
                        <div class="product-card-body">
                            <div id="attributeWrapper">
                                <div class="attributes-list">
                                    <div class="attribute-row">
                                        <div class="attribute-col">
                                            <div class="attribute-col-input">
                                               <input type="text"  name="attribute_type[]"  placeholder="Attribute Type" class="form-control mb-2">
                                             </div>
                                            <div class="attribute-col-input">
                                               <input type="text" name="attribute_value[]" placeholder="Attribute Value" class="form-control">
                                            </div>
                                        </div>
                                         <button type="button" class="btn btn-outline-danger btn-sm" onclick="$(this).closest('.attribute-row').remove()"> <i class="fa fa-trash me-1"></i>
                                            Remove
                                        </button>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}
       
                {{-- ================= INVENTORY ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Inventory </strong>
                    </div>
                    <div class="product-card-body">
                        <div class="product-list-02">
                            <div class="input-field">
                                <label class="lbl"> SKU </label>
                                <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="SKU">
                            </div>

                            {{-- <div class="input-field">
                                <label class="lbl"> Model Number </label>
                                <input type="text" name="modelno"  class="form-control"value="{{ old('modelno') }}" placeholder="Model number">
                            </div> --}}

                            <div class="input-field">
                                <label class="lbl"> Barcode </label>
                                <input type="text" name="isbn_upc_barcode" class="form-control"  value="{{ old('isbn_upc_barcode') }}"  placeholder="ISBN / UPC / Barcode">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ================= SPECIFICATIONS ================= --}}
                <div class="card-info-box">
                    <div class="card-header">
                        <strong>Specifications</strong>
                    </div>

                    <div class="product-card-body">
                        {{-- Added Specifications --}}
                        <div id="specifications-list"></div>

                        {{-- Input Area --}}
                        <div class="specification-input-row">
                            <div class="specification-col">
                                <div class="input-field">
                                    <input type="text"  id="specification-label" class="form-control" placeholder="Label">
                                </div>

                                <div class="input-field">
                                    <input type="text" id="specification-property"  class="form-control"  placeholder="Property" >
                                </div>
                            </div>

                            <div class="specification-add-btn">
                                <button type="button" class="btn btn-primary w-100"onclick="addSpecification()" >  <i class="fa fa-plus me-1"></i> Add </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="product-edit-card-right-box">
                <div class="product-sticky-sidebar">
                    {{-- ================= CATEGORY ================= --}}
                    <div class="card-info-box">
                        <div class="card-header">
                            <strong>Product Type</strong>
                        </div>
                        <div class="product-card-body">
                            <div class="product-list-02">
                            <div class="input-field">
                                <label class="lbl"> Category </label>
                                <select name="cat_id"  id="cat_id">
                                    <option value="">  Select Category </option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('cat_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- <div class="mb-3">
                                <label class="form-label"> Sub Category   </label>
                                <select name="parent_id" id="parent_id"  class="form-select">
                                    <option value="">
                                        Select Sub Category
                                    </option>
                                </select>
                            </div> --}}
                            <div class="input-field">
                                <label class="lbl">   Brand </label>
                                <select name="brand_id">
                                    <option value="">  Select Brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
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
                    <div class="card-header">
                        <strong> Pricing </strong>
                    </div>
                    <div class="product-card-body">
                        <div class="product-list-02">
                            <div class="input-field">
                                <label class="lbl">  Sale Price  </label>
                                <div class="input-group">
                                    <input type="number" step="0.01"  min="0"  name="sale_price" class="form-control"   value="{{ old('sale_price') }}" placeholder="0.00">
                                </div>
                            </div>

                            <div class="input-field">
                                <label class="lbl"> Compare Price</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" name="compare_price" class="form-control"  value="{{ old('compare_price') }}"  placeholder="0.00">
                                </div>
                            </div>
                            {{-- <div class="input-field">
                                <label class="lbl"> Buy Price</label>
                                <div class="input-group">
                                    <input type="number" step="0.01"  min="0" name="buy_price" class="form-control" value="{{ old('buy_price') }}" placeholder="0.00">
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>

                
                    {{-- ================= TAGS ================= --}}
                    <div class="card-info-box">
                        <div class="card-header">
                            <strong>Tags</strong>
                        </div>
                        <div class="product-card-body">
                             <div class="product-list">
                                <label class="lbl"> Add a keyword:</label>
                                <div class="input-field">
                                    <input type="text" name="tags"  class="form-control"  value="{{ old('tags') }}">
                                </div>
                            </div>
                           
                        </div>
                    </div>

                    {{-- ================= SHIPPING ================= --}}
                    <div class="card-info-box">
                        <div class="card-header">
                            <strong>Shipping</strong>
                        </div>
                        <div class="product-card-body">
                            <div class="input-field">
                            <label class="lbl"> Shipping Charge </label>
                            <div class="input-group">
                                <input type="number"  step="0.01" min="0" name="shipping_charge" class="form-control"  value="{{ old('shipping_charge', 0) }}">
                            </div>
                            </div>
                        </div>
                    </div>

                    {{-- ================= STOCK ================= --}}
                    <div class="card-info-box">
                        <div class="card-header">
                            <strong>Stock</strong>
                        </div>
                        <div class="product-card-body">
                            <div class="product-list-02">
                                <div class="input-field">
                                    <label class="lbl"> Available Stock </label>
                                    <input type="number"  name="available_stock" value="{{ old('available_stock', 1) }}" min="0"  class="form-control mb-3">
                                </div>
                                <div class="input-field">
                                    <label class="lbl">  Stock Status </label>
                                    <select name="stock_status" class="form-select">
                                        <option value="in_stock">   In Stock </option>
                                        <option value="out_of_stock">  Out Of Stock</option>
                                        <option value="pre_order">  Pre Order</option>
                                        <option value="2-3days">  2-3 Days </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- ================= SAVE ================= --}}
                    <button type="submit" class="btn btn-primary w-100 save-btn"> <i class="fa fa-save me-2"></i> Save Product   </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection


@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>

$(document).ready(function () {

    $('#photos').on('change', function () {
        $('#preview').html('');
        Array.from(this.files).forEach(function (file) {
            if (!file.type.startsWith('image/')) {
                return;
            }
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#preview').append(`
                    <div class="col-md-3 col-6 mb-3">
                        <div class="preview-wrapper">
                            <img src="${e.target.result}"
                                 class="preview-img">
                        </div>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });
    });

    $('#cat_id').on('change', function () {
        let id = $(this).val();
        if (!id) {
            $('#parent_id').html(
                '<option value="">Select Sub Category</option>'
            );
            return;
        }

        $('#parent_id').html(
            '<option value="">Loading...</option>'
        );

        $.get(
            "{{ url('superadmin/products/category') }}/" + id + "/subcategories",
            function (res) {

                let html =
                    '<option value="">Select Sub Category</option>';

                $.each(res, function (i, item) {

                    html += `
                        <option value="${item.id}">
                            ${item.name}
                        </option>
                    `;

                });

                $('#parent_id').html(html);

            }
        ).fail(function () {

            $('#parent_id').html(
                '<option value="">Unable to load sub categories</option>'
            );

        });

    });

    @if(old('cat_id'))

        $('#cat_id').trigger('change');

    @endif

});


function addSpecification() {

    let labelInput = document.getElementById('specification-label');
    let propertyInput = document.getElementById('specification-property');
    let label = labelInput.value.trim();
    let property = propertyInput.value.trim();
    // Empty validation
    if (label === '' || property === '') {
        alert('Please enter both label and property.');
        return;
    }

    let specList = document.getElementById('specifications-list');
    // Create saved specification row
    let row = document.createElement('div');
    row.className = 'specification-saved-row';
    row.innerHTML = `
        <div class="specification-saved-col">
            <div class="specification-value">
                <strong>${escapeHtml(label)}</strong>
                <input type="hidden"  name="spec_label[]" value="${escapeHtml(label)}">
            </div>

            <div class="specification-value">
                ${escapeHtml(property)}
                <input type="hidden" name="spec_property[]" value="${escapeHtml(property)}">
            </div>
        </div>
        <button type="button" class="btn btn-outline-danger"  onclick="removeSpecification(this)"  title="Remove specification" >  Remove </button>
    `;

    specList.appendChild(row);
    // Clear input fields
    labelInput.value = '';
    propertyInput.value = '';
    // Focus again on label
    labelInput.focus();
}


function removeSpecification(button) {
    button.closest('.specification-saved-row').remove();
}


// Prevent HTML injection in dynamically created content
function escapeHtml(value) {
    return $('<div>').text(value).html();
}

function addAttribute()
{
    $('#attributeWrapper').append(`
        <div class="attribute-row mb-3">
            <input type="text" name="attribute_type[]" class="form-control mb-2" placeholder="Attribute Type">
            <input type="text" name="attribute_value[]" class="form-control mb-2" placeholder="Attribute Value">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="$(this).closest('.attribute-row').remove()"> <i class="fa fa-trash me-1"></i>
                Remove
            </button>
        </div>
    `);
}

// Description editor sync
const editor = document.getElementById('descriptionEditor');
const hiddenInput = document.getElementById('descriptionValue');

// Typing ke time sync
editor.addEventListener('input', function () {
    hiddenInput.value = editor.innerHTML;
});

// Form submit ke time final sync
document.querySelector('form').addEventListener('submit', function () {
    hiddenInput.value = editor.innerHTML;
});
</script>

@endpush