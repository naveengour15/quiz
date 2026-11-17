@extends('superadmin.layouts.app')

@section('title', 'Banner')
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>

    /*  Banner Image */

    .banner-table-image {
        width: 120px;
        height: 65px;
        object-fit: contain;
        border: 1px solid var(--line);
        border-radius: 7px;
        background: #fff;
        padding: 3px;
    }

    .banner-preview {
        margin-top: 10px;
    }

    .banner-preview img {
        width: 220px;
        max-height: 130px;
        object-fit: contain;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 4px;
        background: #fff;
    }

    /*   Actions */
    .banner-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    /*  Status */
    .status-toggle-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
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
        inset: 0;
        cursor: pointer;
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
        background: #fff;
        transition: .3s;
        border-radius: 50%;
    }

    .switch input:checked + .slider {
        background: #28a745;
    }

    .switch input:checked + .slider:before {
        transform: translateX(24px);
    }

    /*Empty */

    .banner-empty {
        text-align: center;
        color: var(--ink-faint);
        padding: 35px !important;
    }

    /* Modal */
    #bannerModal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        visibility: hidden;
        opacity: 0;
        transition: visibility .25s ease, opacity .25s ease;
        padding: 20px;
    }

    #bannerModal.active {
        visibility: visible;
        opacity: 1;
    }

    .banner-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .65);
        backdrop-filter: blur(4px);
    }

    .banner-modal-content {
        position: relative;
        z-index: 2;
        width: 100%;
        /* max-width: 560px; */
          max-width: 900px;  
        max-height: 90vh;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transform: translateY(15px) scale(.96);
        transition: transform .25s cubic-bezier(.16,1,.3,1);
    }

    

    #bannerModal.active .banner-modal-content {
        transform: translateY(0) scale(1);
    }

    /*  Modal Header */

    .banner-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FBFAFF;
    }

    .banner-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }

    /*Modal Body*/

    .banner-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    /*  Modal Footer */

    .banner-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--line);
        background: #FBFAFF;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media(max-width:991px){
         .url-box{
            text-align: end;
         }
    }


    @media(max-width:575px) {
        .banner-table-image {
            width: 90px;
            height: 55px;
        }

        .banner-actions {
            justify-content: flex-start;
        }

        .banner-modal-content {
            max-height: 95vh;
        }

        .banner-modal-body {
            padding: 18px;
        }

        .banner-modal-header {
            padding: 16px 18px;
        }

        .banner-modal-footer {
            padding: 14px 18px;
        }
    }

    .banner-modal-content{
        max-height: 90vh;
        overflow-y: auto;
    }

   
     /* Banner Type Checkbox Grid */
    /* ===== Banner Type Checkbox Design ===== */

.banner-type-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 3 columns */
    gap: 10px;
    margin-top: 10px;
}

.banner-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    transition: 0.2s ease;
    min-height: 42px;
}

.banner-checkbox:hover {
    border-color: #635BFF;
    background: #f8f7ff;
}

.banner-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #635BFF;
    flex-shrink: 0;
}

.banner-checkbox span {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    line-height: 1.2;
}

/* Tablet */
@media (max-width: 992px) {
    .banner-type-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mobile */
@media (max-width: 576px) {
    .banner-type-grid {
        grid-template-columns: 1fr;
    }
}

.banner-group-title{
    margin:18px 0 10px;
    padding:8px 12px;
    font-size:14px;
    font-weight:700;
    color:#2563eb;
    background:#eff6ff;
    border-left:4px solid #2563eb;
    border-radius:6px;
}

.banner-type-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(240px,1fr));
    gap:10px;
    margin-bottom:10px;
}

/* Image */
/* Banner Image Popup */
.banner-image-popup {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0,0,0,.82);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.banner-image-popup.show {
    display: flex;
}

.banner-image-popup-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.banner-image-popup-content img {
    max-width: 85vw;
    max-height: 78vh;
    object-fit: contain;
    border-radius: 12px;
    background: #fff;
    padding: 4px;
    box-shadow: 0 10px 40px rgba(0,0,0,.5);
}

.banner-image-popup-close {
    position: absolute;
    top: -18px;
    right: -18px;
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 50%;
    background: #fff;
    color: #222;
    font-size: 28px;
    line-height: 35px;
    cursor: pointer;
}

.banner-image-popup-title {
    margin-top: 12px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
}

body.banner-popup-open {
    overflow: hidden;
}
</style>
@endpush


<div class="panel active">
    {{-- ========================= Header ========================= --}}
    <div class="page-head" style=" display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; ">
        <div>
            <h1>Banner</h1>
            <p> Manage system banners. </p>
        </div>
        {{-- ADD BUTTON --}}
        <button type="button"class="btn btn-primary"onclick="openAddBannerModal()">  + Add Banner </button>
    </div>

 <div class="search-filter-box">
        <form method="GET" class="toolbar">
            <div class="users-search-filter-box">

                <div class="users-search-filter-wrapper">

                    {{-- Search --}}
                    <div class="input-field">
                        <label class="lbl">Search</label>
                        <input type="text"   name="search" class="tb-input search-field" value="{{ request('search') }}"  placeholder="Search banner name or URL">
                    </div>

                    {{-- Status --}}
                    <div class="input-field">
                        <label class="lbl">Status</label>
                        <select name="status" class="tb-select status-field">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>   Active   </option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>   Inactive   </option>
                        </select>
                    </div>

                </div>

                <div class="users-filter-btn">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('superadmin.banners.index') }}" class="btn btn-ghost"> Reset </a>
                </div>

            </div>
        </form>
        </div>
    {{-- ========================= Banner Table========================= --}}

    <div class="table-wrap table-wrap-res-01">
        <div style="overflow-x:auto;">
            <table class="retailer-table-wrapper">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Banner</th>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Type</th>
                        <th>Views</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Created On</th>
                        <th style="text-align:right;">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($banners as $banner)
                        <tr>
                            {{-- # --}}
                            <td data-label="#">{{ $banners->firstItem() + $loop->index }}</td>

                            {{-- Banner Image --}}
                            <td data-label="Banner">
                                @if($banner->banner_file)
                                    {{-- <img src="{{ Storage::disk('s3')->url($banner->banner_file) }}" class="banner-table-image" alt="{{ $banner->name }}"> --}}
                                  
                                    <img src="{{ Storage::disk('s3')->url($banner->banner_file) }}" class="banner-table-image" alt="{{ $banner->name }}" style="cursor:pointer" onclick="openBannerImagePopup('{{ Storage::disk('s3')->url($banner->banner_file) }}','{{ $banner->name }}')">
                               
                                @else
                                    <div style=" width:120px; height:65px; display:flex; align-items:center; justify-content:center;border:1px solid var(--line); border-radius:7px; color:var(--ink-faint);font-size:11px; "> No Image</div>
                                @endif
                            </td>

                            {{-- Name --}}
                            <td data-label="Name"> {{ $banner->name }} </td>

                            {{-- URL --}}
                            <td data-label="URL">
                                <div class="url-box">
                                    @if($banner->url)
                                        <a href="{{ $banner->url }}" target="_blank" style="word-break:break-all;">{{ $banner->url }}</a>
                                    @else
                                        <span style="color:var(--ink-faint);"> No URL </span>
                                    @endif
                                </div>
                            </td>

                            <td data-label="Type">
                                @if(is_array($banner->type) && count($banner->type))
                                   <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    @foreach($banner->type as $type)
                                        <span  class="badge success">
                                            {{ ucwords(str_replace('_', ' ', $type)) }}
                                        </span>
                                    @endforeach
                                 </div>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Views Count --}}
                            <td data-label="Views">
                                <a href="{{ url('superadmin/ad/'.$banner->id.'/detail?tab=views') }}"
                                class="badge success"
                                style="text-decoration:none;">
                                    👁 {{ $banner->total_views }}
                                </a>
                            </td>

                            {{-- Clicks Count --}}
                            <td data-label="Clicks">
                                <a href="{{ url('superadmin/ad/'.$banner->id.'/detail?tab=clicks') }}"
                                class="badge warning"
                                style="text-decoration:none;">
                                    🖱 {{ $banner->total_clicks }}
                                </a>
                            </td>

                            {{-- Status --}}
                            <td data-label="Status">
                                <div class="status-toggle-wrap">
                                    <label class="switch">
                                        <input type="checkbox" class="banner-status-toggle" data-id="{{ $banner->id }}" {{ $banner->status === 'active' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>

                                    <span class="status-text"> {{ ucfirst($banner->status) }} </span>
                                </div>
                            </td>

                            {{-- Created --}}
                            <td data-label="Created On"> {{ $banner->created_at->format('d M, Y') }} </td>

                            {{-- Actions --}}
                            <td data-label="Actions">
                                <div class="banner-actions">
                                    {{-- EDIT --}}
                                    <button type="button" class="icon-btn" title="Edit Banner" onclick="openEditBannerModal( {{ $banner->id }}, @js($banner->name), @js($banner->url),  @js($banner->type), @js($banner->banner_file ? Storage::disk('s3')->url($banner->banner_file) : ''))"> ✎ </button>

                                    {{-- DELETE --}}
                                    <form method="POST"action="{{ route( 'superadmin.banners.destroy', $banner->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn danger" title="Delete Banner" > 🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td  colspan="8" class="banner-empty">
                                No banners found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
          <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $banners])
            </div> 
    </div>
</div>

{{-- ========================================================= SAME ADD / EDIT BANNER MODAL--}}

<div id="bannerModal" class="{{ $errors->any() ? 'active' : '' }}">
    {{-- Backdrop --}}
    <div class="banner-modal-backdrop" onclick="closeBannerModal()"></div>
    {{-- Modal Content --}}
    <div class="banner-modal-content">
        {{-- =========================Header========================= --}}

        <div class="banner-modal-header">
            <h3 id="bannerModalTitle">  Add Banner </h3>
            <button type="button" class="icon-btn" onclick="closeBannerModal()" style=" border:none; background:transparent; box-shadow:none;font-size:18px;" > ✕</button>
        </div>

        {{-- =========================Form--}}

        <form method="POST" enctype="multipart/form-data" id="bannerForm" action="{{ route('superadmin.banners.store') }}">
            @csrf
            {{-- PUT method only for edit --}}
            <input type="hidden" name="_method"  id="bannerMethod" value="POST">

            {{-- Hidden Banner ID --}}

            <input type="hidden" id="bannerId">

            {{-- =========================Body========================= --}}
            <div class="banner-modal-body">
                <div class="form-grid form-grid-02">
                    {{-- Banner Name --}}
                    <div class="field full">
                        <label> Banner Name <span class="req">*</span> </label>
                        <input type="text"  name="name" id="bannerName" value="{{ old('name') }}"  placeholder="Enter banner name"  required>
                    </div>

                    {{-- Banner Image --}}
                    <div class="field full">
                        <label id="bannerFileLabel"> Banner Image <span class="req">*</span></label>
                        <input  type="file"  name="banner_file"  id="bannerFile" accept="image/*"  onchange="previewBanner(this)" >
                        <div style=" font-size:11px; color:var(--ink-faint);  margin-top:5px;">JPG, JPEG, PNG, WEBP 10 Mb . </div>
                        {{-- Preview --}}
                        <div id="bannerPreview"  class="banner-preview"></div>
                    </div>

                    {{-- Banner Type --}}
                    {{-- <div class="field full">
                        <label>Banner Type <span class="req">*</span></label>

                        @php
                            $bannerTypes = [
                                'all' => 'All',
                                'join' => 'Join',
                                'home' => 'Home',
                                'wallet' => 'Wallet',
                                'redeem_request' => 'Redeem Request',
                                'question' => 'Question',
                                'yearly_leaderboard' => 'Yearly Leaderboard',
                                'quiz_leaderboard' => 'Quiz Leaderboard',
                                'quiz_answer_sheet' => 'Quiz Answer Sheet',
                                // 'quiz_list' => 'Quiz List',
                                'quiz_history_leaderboard' => 'Quiz History Leaderboard',
                                'quiz_history_answer_sheet' => 'Quiz History Answer Sheet',
                                'quiz_history_list' => 'Quiz History List',
                                'practices_list' => 'Practices List',
                                'practices_start' => 'Practices Start',
                                'before_practice_count' => 'Before Practice Count',
                                'practice_answer_sheet' => 'Practice Answer Sheet',
                                'practice_before_answer_sheet' => 'Practice Before Answer Sheet',

                                'latest_quiz' => 'Latest Quiz',
                                'before_quiz_start_count' => 'Before Quiz Start Count',
                                'quiz_start_10s_count' => 'Quiz Start 10 Seconds Count',
                                'before_answer_count' => 'Before Answer Count',
                                // 'before_leaderboard_count' => 'Before Leaderboard Count',

                                 // Fastest Finger First (FFF)
                                'fff_before_quiz_start_count' => 'FFF Before Quiz Start Count',
                                'fff_quiz_start_10s_count' => 'FFF Quiz Start 10 Seconds Count',
                                'fff_before_answer_count' => 'FFF Before Answer Count',
                                'fff_quiz_leaderboard' => 'FFF Quiz Leaderboard',
                                'fff_quiz_answer_sheet' => 'FFF Quiz Answer Sheet',
                                'fff_question' => 'FFF Question',
                            ];
                        @endphp

                        <div class="banner-type-grid">
                            @foreach($bannerTypes as $value => $label)
                                <label class="banner-checkbox">
                                    <input
                                        type="checkbox"
                                        name="type[]"
                                        value="{{ $value }}"
                                        class="banner-type-checkbox"
                                        {{ in_array($value, old('type', [])) ? 'checked' : '' }}
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div> --}}

                    <div class="field full">
                        <label>Banner Type <span class="req">*</span></label>

                        @php
                            $bannerGroups = [
                                'General' => [
                                    'all' => 'All',
                                    'join' => 'Join',
                                    'home' => 'Home',
                                    'wallet' => 'Wallet',
                                    'redeem_request' => 'Redeem Request',
                                    'latest_quiz' => 'Latest Quiz',
                                    'yearly_leaderboard' => 'Yearly Leaderboard',
                                ],

                                'Main Quiz' => [
                                    'quiz_leaderboard' => 'Quiz Leaderboard',
                                    'quiz_answer_sheet' => 'Quiz Answer Sheet',
                                    'before_quiz_start_count' => 'Before Quiz Start Count',
                                    'quiz_start_10s_count' => 'Quiz Start 10 Seconds Count',
                                    'before_answer_count' => 'Before Answer Count',
                                    'question' => 'Question',
                                ],

                                'Practice Quiz' => [
                                    'practices_list' => 'Practices List',
                                    'practices_start' => 'Practices Start',
                                    'before_practice_count' => 'Before Practice Count',
                                    'practice_answer_sheet' => 'Practice Answer Sheet',
                                    'practice_before_answer_sheet' => 'Practice Before Answer Sheet',
                                ],

                                'Quiz History' => [
                                    'quiz_history_leaderboard' => 'Quiz History Leaderboard',
                                    'quiz_history_answer_sheet' => 'Quiz History Answer Sheet',
                                    'quiz_history_list' => 'Quiz History List',
                                ],

                                'Fastest Finger First (FFF)' => [
                                    'fff_question' => 'FFF Question',
                                    'fff_quiz_start_10s_count' => 'FFF Quiz Start 10 Seconds Count',
                                    'fff_before_quiz_start_count' => 'FFF Before Quiz Start Count',
                                    'fff_before_answer_count' => 'FFF Before Answer Count',
                                    'fff_quiz_answer_sheet' => 'FFF Quiz Answer Sheet',
                                    'fff_quiz_leaderboard' => 'FFF Quiz Leaderboard',
                                ],
                            ];
                        @endphp

                        @foreach($bannerGroups as $heading => $types)
                            <div class="banner-group-title">{{ $heading }}</div>

                            <div class="banner-type-grid">
                                @foreach($types as $value => $label)
                                    <label class="banner-checkbox">
                                        <input
                                            type="checkbox"
                                            name="type[]"
                                            value="{{ $value }}"
                                            class="banner-type-checkbox"
                                            {{ in_array($value, old('type', [])) ? 'checked' : '' }}
                                        >
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        @error('type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    {{-- URL --}}

                    <div class="field full">
                        <label> URL </label>
                        <input type="url" name="url" id="bannerUrl" value="{{ old('url') }}" placeholder="https://example.com">
                    </div>
                </div>
            </div>

            {{-- ========================= Footer ========================= --}}
            <div class="banner-modal-footer">
                {{-- Cancel --}}
                <button type="button"class="btn btn-ghost"onclick="closeBannerModal()">Cancel</button>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary" id="bannerSubmitButton" > Save Banner </button>
            </div>
        </form>
    </div>
</div>


<!-- Banner Image Popup -->
<div id="bannerImagePopup" class="banner-image-popup">
    <div class="banner-image-popup-content">
        <button type="button"
                class="banner-image-popup-close"
                onclick="closeBannerImagePopup()">×</button>

        <img id="bannerPopupImage" src="" alt="Banner">

        <div id="bannerPopupTitle" class="banner-image-popup-title"></div>
    </div>
</div>
@endsection


@push('scripts')

<script>

    /* ===================================================== MODAL ELEMENTS ===================================================== */

    const bannerModal = document.getElementById('bannerModal');
    const bannerForm = document.getElementById('bannerForm');
    const bannerName = document.getElementById('bannerName');
    const bannerUrl = document.getElementById('bannerUrl');
    const bannerFile =  document.getElementById('bannerFile');
    const bannerPreview = document.getElementById('bannerPreview');
    const bannerModalTitle = document.getElementById('bannerModalTitle');
    const bannerSubmitButton = document.getElementById('bannerSubmitButton');
    const bannerMethod = document.getElementById('bannerMethod');
    const bannerFileLabel = document.getElementById('bannerFileLabel');
    const bannerType = document.getElementById('bannerType');

    /* =====================================================  OPEN ADD MODAL ===================================================== */

    function openAddBannerModal()
    {
        if (!bannerModal) {
            return;
        }
        /* * Reset form */
        bannerForm.reset();
        /** Store route */
        bannerForm.action ="{{ route('superadmin.banners.store') }}";
        /* * POST */
        bannerMethod.value = 'POST';
        /* * Modal title*/
        bannerModalTitle.innerText ='Add Banner';
        /* * Button text */
        bannerSubmitButton.innerText = 'Save Banner';
        /** Image required */
        bannerFile.required = true;
        /*  * Required star */
        bannerFileLabel.innerHTML = 'Banner Image <span class="req">*</span>';
        /* * Remove old preview  */
        bannerPreview.innerHTML = '';

      // Banner type checkboxes reset
        document.querySelectorAll('.banner-type-checkbox').forEach(cb => {
            cb.checked = false;
        });

        handleBannerTypeSelection();
        /* * Open modal */
        bannerModal.classList.add('active');
       
    }

    /* ===================================================== OPEN EDIT MODAL ===================================================== */

    function openEditBannerModal(
        id,
        name,
        url,
        type,
        image
    )
    {
        if (!bannerModal) {
            return;
        }

        /* * Banner ID */
        document.getElementById('bannerId').value = id;
        /* * Fill existing data*/
        bannerName.value = name || '';
        bannerUrl.value = url || '';
        /* * Update URL*/
        bannerForm.action = "{{ url('superadmin/banners') }}/" + id;
        /*  * PUT */
        bannerMethod.value = 'PUT';
        /** Modal title*/
        bannerModalTitle.innerText = 'Edit Banner';
        /*  * Button text*/
        bannerSubmitButton.innerText = 'Update Banner';
        /* * Image is optional while editing*/
        bannerFile.required = false;
        bannerFileLabel.innerHTML = 'Banner Image';
        
        /* * Show existing image */
        if (image) {
            bannerPreview.innerHTML = `
                <img src="${image}" alt="Banner Preview">
            `;

        } else {
            bannerPreview.innerHTML = '';
        }

            document.querySelectorAll('.banner-type-checkbox').forEach(cb => {
                    cb.checked = false;
                });

                if (type) {
                    if (typeof type === "string") {
                        try {
                            type = JSON.parse(type);
                        } catch (e) {
                            type = [type];
                        }
                    }

                    if (Array.isArray(type)) {
                        type.forEach(value => {
                            const checkbox = document.querySelector(
                                `.banner-type-checkbox[value="${value}"]`
                            );

                            if (checkbox) checkbox.checked = true;
                        });
                    }
                }

                handleBannerTypeSelection();

        /* * Open modal*/
        bannerModal.classList.add('active');
    }


    /* ===================================================== CLOSE MODAL ===================================================== */

    function closeBannerModal()
    {
        if (!bannerModal) {
            return;
        }
        bannerModal.classList.remove('active');
    }

    /* =====================================================IMAGE PREVIEW===================================================== */

    function previewBanner(input)
    {
        if (!bannerPreview) {
            return;
        }

        bannerPreview.innerHTML = '';

        if (
            input.files &&
            input.files.length > 0
        ) {
            const file = input.files[0];
            const reader =new FileReader();
            reader.onload =
                function(e)
                {

                    bannerPreview.innerHTML = `
                        <img src="${e.target.result}" alt="Banner Preview">
                    `;

                };

            reader.readAsDataURL(file);
        }
    }

    /* ===================================================== STATUS TOGGLE ===================================================== */

    document.querySelectorAll('.banner-status-toggle').forEach(function(toggle)
        {

            toggle.addEventListener(
                'change',
                function()
                {
                    const checkbox = this;
                    const oldStatus =!checkbox.checked;
                    const status = checkbox.checked ? 'active' : 'inactive';

                    fetch(
                        "{{ route('superadmin.banners.changeStatus' ) }}",
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept':'application/json',
                                'X-CSRF-TOKEN':"{{ csrf_token() }}"
                            },

                            body:
                                JSON.stringify({
                                    id:checkbox.dataset.id,
                                    status: status
                                })
                        }
                    )
                    .then(function(response)
                    {
                        return response.json();
                    })
                    .then(function(data)
                    {

                        if (data.success) {

                            const text = checkbox.closest('td').querySelector('.status-text');
                            if (text) {
                                text.innerText = data.status === 'active' ? 'Active': 'Inactive';
                            }

                            if (
                                typeof showAlert ==='function'
                            ) {
                                showAlert(
                                    'success',
                                    'Success',
                                    'Banner status updated successfully.'
                                );
                            }
                        } else {
                            checkbox.checked = oldStatus;
                        }
                    })
                    .catch(function()
                    {
                        checkbox.checked = oldStatus;

                        if (
                            typeof showAlert === 'function'
                        ) {

                            showAlert(
                                'error',
                                'Error',
                                'Something went wrong.'
                            );
                        }
                    });
                }
            );
        });

    /* =====================================================ESC KEY===================================================== */

    document.addEventListener('keydown',
        function(e)
        {
            if (e.key !== 'Escape') {
                return;
            }
            if (
                bannerModal &&
                bannerModal.classList.contains('active')
            ) {
                closeBannerModal();

            }
        }
    );


   /* =====================================================Banner Type Checkbox Rules */

        function handleBannerTypeSelection() {

            const allCheckbox = document.querySelector('.banner-type-checkbox[value="all"]');
            const otherCheckboxes = document.querySelectorAll('.banner-type-checkbox:not([value="all"])');

            if (!allCheckbox) return;

            // ALL selected
            if (allCheckbox.checked) {
                otherCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
                return;
            }

            // Check if any other checkbox selected
            const anyOtherChecked = [...otherCheckboxes].some(cb => cb.checked);

            if (anyOtherChecked) {
                allCheckbox.checked = false;
                allCheckbox.disabled = true;
            } else {
                allCheckbox.disabled = false;
            }

            // Re-enable others if ALL is not selected
            otherCheckboxes.forEach(cb => cb.disabled = false);
        }

        // Event Binding
        document.querySelectorAll('.banner-type-checkbox').forEach(cb => {
            cb.addEventListener('change', handleBannerTypeSelection);
        });




        /* ================= Banner Image Popup ================= */

        function openBannerImagePopup(imageUrl, bannerName) {
            document.getElementById('bannerPopupImage').src = imageUrl;
            document.getElementById('bannerPopupTitle').innerText = bannerName || '';

            document.getElementById('bannerImagePopup').classList.add('show');
            document.body.classList.add('banner-popup-open');
        }

        function closeBannerImagePopup() {
            document.getElementById('bannerImagePopup').classList.remove('show');
            document.body.classList.remove('banner-popup-open');

            document.getElementById('bannerPopupImage').src = '';
        }

        document.getElementById('bannerImagePopup').addEventListener('click', function (e) {
            if (e.target === this) {
                closeBannerImagePopup();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeBannerImagePopup();
            }
        });
</script>

@endpush