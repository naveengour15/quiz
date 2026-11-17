@extends('superadmin.layouts.app')
@section('title', 'Ad')
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



            .tenant-dropdown {
                display: none;
                position: absolute;
                width: 100%;
                background: #fff;
                border: 1px solid #ddd;
                border-top: none;
                max-height: 220px;
                overflow-y: auto;
                z-index: 9999;
                box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
            }

            .tenant-item {
                padding: 10px;
                cursor: pointer;
            }

            .tenant-item:hover {
                background: #f5f5f5;
            }

            .detail-modal {
                position: fixed;
                inset: 0;
                z-index: 1050;
                display: none;
                align-items: center;
                justify-content: center;
            }

            .detail-modal.active {
                display: flex;
            }

            .detail-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(16, 19, 43, 0.6);
                backdrop-filter: blur(4px);
            }

            .detail-dialog {
                position: relative;
                background: #ffffff;
                width: 90%;
                max-width: 760px;
                max-height: 85vh;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
                display: flex;
                flex-direction: column;
                z-index: 1051;
                overflow: hidden;
                animation: createModalFadeIn 0.25s ease-out;
            }

            @keyframes createModalFadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .detail-header {
                padding: 16px 24px;
                border-bottom: 1px solid var(--line);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #FBFAFF;
            }

            .detail-header h3 {
                margin: 0;
                font-size: 17px;
                font-weight: 700;
                color: var(--ink);
            }

            .detail-close {
                background: transparent;
                border: none;
                font-size: 24px;
                color: var(--ink-faint);
                cursor: pointer;
                line-height: 1;
            }

            .detail-body {
                padding: 24px;
                overflow-y: auto;
                flex: 1;
            }

            .detail-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px 24px;
            }

            .detail-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
                background: #F9FAFB;
                padding: 10px 14px;
                border-radius: 8px;
                border: 1px solid #F3F4F6;
            }

            .detail-label {
                font-size: 11px;
                font-weight: 700;
                color: var(--ink-faint);
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .detail-value {
                font-size: 13px;
                font-weight: 600;
                color: var(--ink);
            }

            .ad-action-btn{
                display: flex;
                gap: 10px;
            }

            /* .image-field-box{
                display: grid;
                grid-template-columns: repeat(2,1fr);
                gap: 10px;
                margin-top: 10px;
            } */

            .status-change {
                min-width: 110px;
                padding: 6px 10px;
                border: 1px solid #dcdfe6;
                border-radius: 8px;
                background: #fff;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
            }

            @media(max-width:575px){
                .detail-grid {
                    gap: 10px 10px;
                }
            }

        </style>

        <style>
            #adAnalysisModal {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 25px;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(3px);
        }

        #adAnalysisModal.active {
            display: flex;
        }

        #adAnalysisModal .ad-analysis-dialog {
            width: 100%;
            max-width: 1250px;
            max-height: 90vh;
        }

        #adAnalysisModal .ad-analysis-content {
            width: 100%;
            max-height: 90vh;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }


        #adAnalysisModal .ad-analysis-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
        }

        #adAnalysisModal .ad-analysis-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        #adAnalysisModal .ad-analysis-subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: #6b7280;
        }

        #adAnalysisModal .ad-analysis-separator {
            margin: 0 5px;
        }

        #adAnalysisModal .ad-analysis-close {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 50%;
            background: #f3f4f6;
            color: #374151;
            font-size: 22px;
            line-height: 30px;
            cursor: pointer;
            flex-shrink: 0;
        }

        #adAnalysisModal .ad-analysis-close:hover {
            background: #e5e7eb;
        }

        #adAnalysisModal .ad-analysis-body {
            padding: 18px 20px 20px;
            max-height: calc(90vh - 75px);
            overflow-y: auto;
        }


        #adAnalysisModal .analysis-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        #adAnalysisModal .analysis-card {
            min-width: 0;
            padding: 16px 12px;
            text-align: center;
            border: 1px solid #e8eaee;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.04);
        }

        #adAnalysisModal .analysis-card-label {
            margin-bottom: 7px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
        }

        #adAnalysisModal .analysis-card-value {
            color: #111827;
            font-size: 25px;
            line-height: 1;
            font-weight: 700;
        }


        #adAnalysisModal .analysis-tabs {
            display: flex;
            gap: 5px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }

        #adAnalysisModal .analysis-tab-btn {
            border: 0;
            background: transparent;
            padding: 9px 15px;
            margin-bottom: -1px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        #adAnalysisModal .analysis-tab-btn:hover {
            color: #111827;
        }

        #adAnalysisModal .analysis-tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }


        #adAnalysisModal .analysis-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        #adAnalysisModal .analysis-table {
            width: 100%;
            min-width: 1050px;
            margin: 0;
            border-collapse: collapse;
            font-size: 12px;
            background: #fff;
        }

        #adAnalysisModal .analysis-table th {
            padding: 11px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        #adAnalysisModal .analysis-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #374151;
            vertical-align: middle;
        }

        #adAnalysisModal .analysis-table tbody tr:last-child td {
            border-bottom: 0;
        }

        #adAnalysisModal .analysis-table tbody tr:hover {
            background: #fafafa;
        }

        #adAnalysisModal .analysis-empty {
            padding: 25px !important;
            text-align: center !important;
            color: #9ca3af !important;
        }


        @media (max-width: 768px) {

            #adAnalysisModal {
                padding: 10px;
            }

            #adAnalysisModal .ad-analysis-content {
                max-height: 94vh;
            }

            #adAnalysisModal .ad-analysis-body {
                padding: 14px;
            }

            #adAnalysisModal .analysis-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #adAnalysisModal .analysis-card-value {
                font-size: 21px;
            }

        } 



        .banner-tabs{
    display:flex;
    gap:10px;
    margin:20px 0;
    border-bottom:1px solid #e5e7eb;
    padding-bottom:12px;
}

.ad-tab-btn{
    padding:10px 22px;
    border: 1px solid var(--line);
    border-radius:8px;
    background:#fff;
    color:#374151;
    cursor:pointer;
    font-weight:600;
    transition:.3s;
}

.ad-tab-btn.active{
    background:#4f46e5;
    color:#fff;
}

.tab-content{
    display:none;
}

.tab-content.active{
    display:block;
}

        </style>
    @endpush

    <div class="banner-tabs">
        <button type="button" class="ad-tab-btn active" data-tab="adsTab"> 📢 Ad </button>
        <button type="button" class="ad-tab-btn" data-tab="bannerTab"> 🖼 Banner Ad </button>
    </div>

    <div class="panel active">
      <div id="adsTab" class="tab-content active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Ad</h1>
                <p>Manage Ad and format classifications.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">Add Ad</button>
        </div>
  
        <div class="table-wrap table-wrap-res-01">
            <div class="ad-table-box">
                <table class="ad-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            {{-- <th>image</th> --}}
                            <th>Campaign</th>
                            <th>Retailer</th>
                            <th>Description</th>
                            <th>Ad Type</th>
                            <th>Budget</th>
                            <th>State /city</th>
                            <th>Gender</th>
                            <th>Age </th>
                            <th>Language</th>
                            <th>Interests</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($ads as $ad)
                            <tr>
                                <td data-label="#" class="mono">{{$ads->firstItem() + $loop->index }}</td>
                                {{-- <td>
                                    @if ($ad->media)
                                        <img src="{{ Storage::disk('s3')->url($ad->media) }}" width="70" height="50"
                                            style="object-fit:cover;border-radius:6px;">
                                    @else
                                        -
                                    @endif
                                </td> --}}

                                <td data-label="Campaign">{{ $ad->campaign_name ?? '-' }}</td>

                                <td data-label="Retailer"> {{ optional($tenants->where('id', $ad->tenant_id)->first())->name ?? '-' }} </td>
                                <td data-label="Description">{{ $ad->description ?? '-' }}</td>
                                <td data-label="Ad Type">{{ ucfirst($ad->ad_type) ?? '-' }}</td>
                                <td data-label="Budget"> ₹ {{ number_format($ad->budget, 2) ?? '-' }} </td>

                                <td data-label="State / City">{{ optional($ad->stateData)->name ?? '-' }} <br>
                                    {{ optional($ad->cityData)->name ?? '-' }}</td>

                                <td data-label="Gender"> {{ ucfirst($ad->gender) ?? '-' }}</td>
                                <td data-label="Age"> {{ ucfirst($ad->age_from) ?? '-' }} - {{ ucfirst($ad->age_to) ?? '-' }}</td>

                                <td data-label="Language"> {{ ucfirst($ad->language) ?? '-' }}</td>
                                <td data-label="Interests"> {{ ucfirst($ad->interests) ?? '-' }}</td>


                                <td data-label="Status">
                                    <div>
                                        <select class="status-change" data-id="{{ $ad->id }}">
                                            <option value="draft" {{ $ad->status == 'draft' ? 'selected' : '' }}> Draft </option>

                                            <option value="pending"{{ $ad->status == 'pending' ? 'selected' : '' }}> Pending
                                            </option>

                                            <option value="active" {{ $ad->status == 'active' ? 'selected' : '' }}>
                                                Active
                                            </option>

                                            <option value="paused" {{ $ad->status == 'paused' ? 'selected' : '' }}>
                                                Paused
                                            </option>

                                            <option value="completed" {{ $ad->status == 'completed' ? 'selected' : '' }}>
                                                Completed
                                            </option>

                                            <option value="rejected" {{ $ad->status == 'rejected' ? 'selected' : '' }}>
                                                Rejected
                                            </option>
                                        </select>
                                    </div>
                                </td>


                                <td data-label="Action" class="row-actions">
                                    <div class="ad-action-btn">
                                    <button type="button" class="icon-btn ad-analysis-btn"  data-id="{{ $ad->id }}" >
                                      📉
                                    </button>
                                    <button type="button" class="icon-btn btn-view-detail" data-id="{{ $ad->id }}" title="View Details"> 👁 </button>
                                    <a class="icon-btn" href="{{ route('superadmin.ad.index', ['edit' => $ad->id]) }}"> ✎ </a>
                                    <a href="{{ route('superadmin.ad.assign.quiz', $ad->id) }}" class="icon-btn" title="Assign Quiz"> 📋 </a>
                                    <form method="POST" action="{{ route('superadmin.ad.destroy', $ad) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn danger" type="submit"> 🗑 </button>
                                    </form>
                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="17" style="text-align:center;color:var(--ink-faint);"> No Ads found. </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>

            <div class="pagination">
                @include('common.pagination.superadmin_pagination', [
                    'pageData' => $ads,
                    'paginationPageName' => 'page',
                ])
            </div>  
        </div>
      </div>
         {{-- ====== BANNER ADS SECTION --}}
        <div id="bannerTab" class="tab-content">
                <div class="panel active" style="margin-top: 25px;">
                    <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h1>Banner Ads</h1>
                            <p>Manage banner advertisements and their performance.</p>
                        </div>
                    </div>

                    <div class="table-wrap table-wrap-res-01">
                        <div class="ad-table-box">
                            <table class="ad-table-wrapper">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Banner</th>
                                        <th>Banner Name</th>
                                        <th>Retailer</th>
                                        <th>Type</th>
                                        <th>URL</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        {{-- <th>Unique Views</th> --}}
                                        <th>Clicks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($banners as $banner)
                                        <tr>
                                            {{-- # --}}
                                            <td data-label="#" class="mono">{{ $banners->firstItem() + $loop->index }} </td>

                                            {{-- Banner Image --}}
                                            <td data-label="Banner">
                                                @if($banner->banner_url)
                                                    <img src="{{ $banner->banner_url }}" alt="{{ $banner->name }}" style=" width:120px;  max-height:70px; object-fit:contain; border:1px solid #ddd;border-radius:6px; padding:4px; background:#fff;">
                                                @else
                                                    <span>-</span>
                                                @endif
                                            </td>

                                            {{-- Banner Name --}}
                                            <td data-label="Banner Name"> {{ $banner->name ?? '-' }}</td>

                                            {{-- Retailer --}}
                                            <td data-label="Retailer"> {{ optional($banner->tenant)->name ?? '-' }}</td>

                                            {{-- Type --}}
                                            <td data-label="Type">
                                                @if(is_array($banner->type))
                                                    {{ implode(', ', $banner->type) }}
                                                @else
                                                    {{ $banner->type ?? '-' }}
                                                @endif
                                            </td>

                                            {{-- URL --}}
                                            <td data-label="URL">
                                                @if($banner->url)
                                                    <a href="{{ $banner->url }}" target="_blank" rel="noopener noreferrer"style="color:#2563eb; text-decoration:none;"> Visit</a>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Status --}}
                                            <td data-label="Status"><span>{{ ucfirst($banner->status ?? '-') }} </span> </td>

                                            {{-- Total Views --}}
                                            <td data-label="Views">
                                                <strong> {{ number_format($banner->total_views ?? 0) }} </strong>
                                            </td>

                                            {{-- Unique Views --}}
                                            {{-- <td data-label="Unique Views">  <strong>{{ number_format($banner->unique_views ?? 0) }} </strong></td> --}}

                                            {{-- Total Clicks --}}
                                            <td data-label="Clicks">
                                                <strong> {{ number_format($banner->total_clicks ?? 0) }}  </strong>
                                            </td>
                                            <td data-label="Action">
                                                <a href="{{ route('superadmin.ad.detail', $banner->id) }}"
                                                class="icon-btn"
                                                title="View Banner Details">
                                                    📊
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" style=" text-align:center;  color:var(--ink-faint); " >    No Banner Ads found.  </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            @include('common.pagination.superadmin_pagination', [
                                'pageData' => $banners,
                                'paginationPageName' => 'banner_page',
                            ])
                        </div>
                    </div>
                </div>
        </div>
    </div>

    {{-- Modal placed outside .panel for clean fullscreen backdrop overlay --}}
    <div id="createModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeModal()"></div>

        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Ad' : 'Add Ad' }}</h3>
                <button type="button" class="icon-btn" onclick="closeModal()"
                    style="border:none;background:transparent;box-shadow:none;"> ✕ </button>
            </div>

            <form method="POST"
                action="{{ $editing ? route('superadmin.ad.update', $editing) : route('superadmin.ad.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="form-grid form-grid-02">

                    {{-- Survey Name --}}
                    <div class="field full">
                        <label>Campaign Name<span class="req">*</span></label>
                        <input type="text" name="campaign_name"
                            value="{{ old('campaign_name', $editing->campaign_name ?? '') }}" required>
                    </div>

                    <div class="field full">
                        <label>Description</label>
                        <textarea name="description" rows="3">{{ old('description', $editing->description ?? '') }}</textarea>
                    </div>
                </div>

                 <div class="form-grid form-grid-02" style=" margin-top:16px">
                    <div class="field">
                        <label>Budget <span class="req">*</span></label>
                        <input type="number" step="0.01" name="budget" value="{{ old('budget', $editing->budget ?? '') }}" required>
                    </div>

                    {{-- State --}}
                    <div class="field">
                        <label>State</label>

                        <select name="state" id="state">
                            <option value="">Select State</option>

                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(old('state', $editing->state ?? '') == $state->id)>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- City --}}
                    <div class="field">
                        <label>City</label>

                        <select name="city" id="city">
                            <option value="">Select City</option>

                            @if (isset($editing) && $editing->state)

                                @foreach (\App\Models\City::where('state_id', $editing->state)->orderBy('name')->get() as $city)
                                    <option value="{{ $city->id }}" @selected(old('city', $editing->city) == $city->id)>
                                        {{ $city->name }}
                                    </option>
                                @endforeach

                            @endif

                        </select>
                    </div>

            
                    {{-- Age Range --}}
                    <div class="field">
                        <label>Age Range</label>
                        <input type="text"
                            name="age_range"
                            placeholder="18-30"
                            value="{{ old('age_range', isset($editing) && $editing->age_from && $editing->age_to ? $editing->age_from.'-'.$editing->age_to : '') }}">
                    </div>

                    {{-- Gender --}}
                    <div class="field">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="all" {{ old('gender', $editing->gender ?? '') == 'all' ? 'selected' : '' }}> All
                            </option>
                            <option value="male" {{ old('gender', $editing->gender ?? '') == 'male' ? 'selected' : '' }}> Male
                            </option>
                            <option value="female" {{ old('gender', $editing->gender ?? '') == 'female' ? 'selected' : '' }}>
                                Female </option>
                        </select>
                    </div>

                   
                    {{-- Language --}}
                    <div class="field">
                        <label>Language</label>
                        <select name="language">
                            <option value="">Select Language</option>

                            <option value="Hindi"
                                {{ old('language', $editing->language ?? '') == 'Hindi' ? 'selected' : '' }}>
                                Hindi
                            </option>

                            <option value="English"
                                {{ old('language', $editing->language ?? '') == 'English' ? 'selected' : '' }}>
                                English
                            </option>
                        </select>
                    </div>

                    {{-- Interests --}}

                    <div class="field full">
                        <label>Interests</label>
                        <textarea name="interests" rows="2" placeholder="e.g. Reading, Cooking, Dancing">{{ old('interests', $editing->interests ?? '') }}</textarea>
                    </div>
                    <div class="field full">
                        <label>Hobby</label>
                        <textarea name="hobby" rows="2" placeholder="e.g. Reading, Cooking, Dancing">{{ old('hobby', $editing->hobby ?? '') }}</textarea>
                    </div>



                    {{-- Status --}}
                    <div class="field full">
                        <label>Status</label>
                        <select name="status">
                             <option value="pending" {{ old('status', $editing->status ?? '') == 'pending' ? 'selected' : '' }}>
                                Pending </option>
                            <option value="draft" {{ old('status', $editing->status ?? '') == 'draft' ? 'selected' : '' }}>
                                Draft </option>
                           
                            <option value="active" {{ old('status', $editing->status ?? '') == 'active' ? 'selected' : '' }}>
                                Active </option>
                            <option value="paused" {{ old('status', $editing->status ?? '') == 'paused' ? 'selected' : '' }}>
                                Paused </option>
                            <option value="completed"
                                {{ old('status', $editing->status ?? '') == 'completed' ? 'selected' : '' }}> Completed </option>
                            <option value="rejected" {{ old('status', $editing->status ?? '') == 'rejected' ? 'selected' : '' }}>
                                Rejected </option>
                        </select>
                    </div>

                      <div class="field full">
                            <label>Ad Type <span class="req">*</span></label>
                             <select name="ad_type" id="type" required>
                            <option value="image"
                                {{ old('ad_type', $editing->ad_type ?? '') == 'image' ? 'selected' : '' }}>
                                Image
                            </option>

                            <option value="video"
                                {{ old('ad_type', $editing->ad_type ?? '') == 'video' ? 'selected' : '' }}>
                                Video
                            </option>
                        </select>
                       </div>

                </div>

                    <div id="imageFields" class="image-field-box" style="margin-top: 16px;">
                        <div class="field">
                            <label>Banner (320 × 50)</label>
                            <input type="file" name="banner" accept="image/*">
                              @if(isset($editing) && $editing->mediaFile?->banner)
                                <div style="margin-top:8px;">
                                    <img src="{{ Storage::disk('s3')->url($editing->mediaFile->banner) }}"
                                        width="120"
                                        height="40"
                                        style="border:1px solid #ddd;border-radius:6px;object-fit:cover;">

                  
                                </div>
                            @endif
                           
                        </div>

                        <div class="field" style="margin-top:16px;">
                            <label>Large Banner (320 × 100)</label>
                            <input type="file" name="large_banner" accept="image/*">
                             @if(isset($editing) && $editing->mediaFile?->large_banner)
                                <div style="margin-top:8px;">
                                    <img src="{{ Storage::disk('s3')->url($editing->mediaFile->large_banner) }}"
                                        width="120"
                                        height="40"
                                        style="border:1px solid #ddd;border-radius:6px;object-fit:cover;">
                                </div>
                            @endif
                        </div>

                        <div class="field" style="margin-top:16px;">
                            <label>Medium Banner (300 × 250)</label>
                            <input type="file" name="medium_banner" accept="image/*">

                            @if(isset($editing) && $editing->mediaFile?->medium_banner)
                                <div style="margin-top:8px;">
                                    <img src="{{ Storage::disk('s3')->url($editing->mediaFile->medium_banner) }}"
                                        width="80"
                                        height="80"
                                        style="border:1px solid #ddd;border-radius:6px;object-fit:cover;">
                                </div>
                            @endif
                        </div>

                        <div class="field" style="margin-top:16px;">
                            <label>Full Screen (1080 × 1920)</label>
                            <input type="file" name="full_screen" accept="image/*">
                               @if(isset($editing) && $editing->mediaFile?->full_screen)
                                    <div style="margin-top:8px;">
                                        <img src="{{ Storage::disk('s3')->url($editing->mediaFile->full_screen) }}"
                                            width="70"
                                            height="120"
                                            style="border:1px solid #ddd;border-radius:6px;object-fit:cover;">
                                    </div>
                                @endif
                        </div>
                    </div>

                     <div id="videoFields" style="display:none; margin-top:16px;">

                        <div class="field" style="margin-top:16px;">
                            <label>Portrait Video (1080 × 1920)</label>
                            <input type="file" name="video_portrait" accept="video/*">
                               @if(isset($editing) && $editing->mediaFile?->video_portrait)
                                    <video width="120" controls style="margin-top:8px;border-radius:6px;">
                                        <source src="{{ Storage::disk('s3')->url($editing->mediaFile->video_portrait) }}">
                                    </video>
                                @endif
                        </div>

                        <div class="field" style="margin-top:16px;">
                            <label>Landscape Video (1920 × 1080)</label>
                            <input type="file" name="video_landscape" accept="video/*">
                               @if(isset($editing) && $editing->mediaFile?->video_landscape)
                                    <video width="150" controls style="margin-top:8px;border-radius:6px;">
                                        <source src="{{ Storage::disk('s3')->url($editing->mediaFile->video_landscape) }}">
                                    </video>
                                @endif
                        </div>

                    </div>


                    <div class="form-foot">
                        <button type="button" class="btn btn-ghost" onclick="closeModal()"> Cancel </button>
                        <button type="submit" class="btn btn-primary">{{ $editing ? 'Update Ad' : 'Save Ad' }} </button>
                    </div>

            </form>
        </div>
    </div>

    <div id="detailModal" class="detail-modal" style="display: none;">
        <div class="detail-backdrop" onclick="closeDetailModal()"></div>
        <div class="detail-dialog">
            <div class="detail-header">
                <h3 id="detailTitle">Ad Details</h3>
                <button type="button" class="detail-close" onclick="closeDetailModal()">&times;</button>
            </div>

            <div class="detail-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Retailer</span>
                        <span id="v_tenant" class="detail-value">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Campaign Name</span>
                        <span id="v_campaign" class="detail-value">—</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Ad Type</span>
                        <span id="v_type" class="detail-value">—</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Budget</span>
                        <span id="v_budget" class="detail-value">—</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span id="v_status" class="detail-value">—</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">State</span>
                        <span id="v_state" class="detail-value">-</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">City</span>
                        <span id="v_city" class="detail-value">-</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Gender</span>
                        <span id="v_gender" class="detail-value">-</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Age</span>
                        <span id="v_age" class="detail-value">-</span>
                    </div>

                
                    <div class="detail-item">
                        <span class="detail-label">Language</span>
                        <span id="v_language" class="detail-value">-</span>
                    </div>

                     <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Description</span>
                        <span id="v_description" class="detail-value">-</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Interests</span>
                        <span id="v_interests" class="detail-value">-</span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Hobby</span>
                        <span id="v_hobby" class="detail-value">-</span>
                    </div>

                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Image</span>
                        {{-- <img id="v_media" src="" style="max-width:250px;border-radius:8px;display:none;"> --}}
                             <div id="v_media" style="display:flex;flex-wrap:wrap;gap:10px;"></div>
                    </div>

                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Video</span>
                    {{-- <video id="v_video" controls style="display:none;max-width:250px;"></video> --}}
                       <div id="v_video" style="display:flex;flex-wrap:wrap;gap:10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="adAnalysisModal" aria-hidden="true">
        <div class="ad-analysis-dialog">
            <div class="ad-analysis-content">
                <div class="ad-analysis-header">
                    <div>
                        <h5 class="ad-analysis-title">
                            {{-- Ad Analysis - <span id="analysisUserName"> </span> --}}
                            Ad Analysis - <span id="analysisCampaignName">-</span> 
                        </h5>

                        {{-- <div class="ad-analysis-subtitle">
                            {{-- <span id="analysisAdTitle">
                                -
                            </span> --}}
                            {{-- <span class="ad-analysis-separator">
                                •
                            </span> --}
                        </div> --}}
                    </div>
                    <button type="button" class="ad-analysis-close" onclick="closeAdAnalysisModal()" aria-label="Close"  >
                        &times;
                    </button>

                </div>


              
                <div class="ad-analysis-body">
                    <div class="analysis-summary-grid">
                        <div class="analysis-card">
                            <div class="analysis-card-label"> Impressions </div>
                            <div class="analysis-card-value" id="analysisImpressions">  0 </div>
                        </div>

                        <div class="analysis-card">
                            <div class="analysis-card-label">  Views  </div>
                            <div class="analysis-card-value" id="analysisViews" >  0 </div>
                        </div>

                        <div class="analysis-card">
                            <div class="analysis-card-label">  Unique Views </div>
                            <div class="analysis-card-value" id="analysisUniqueViews"> 0 </div>
                        </div>

                        <div class="analysis-card">
                            <div class="analysis-card-label"> Clicks </div>
                            <div class="analysis-card-value" id="analysisClicks"> 0 </div>
                        </div>
                    </div>

                    <div class="analysis-tabs">
                        <button type="button"  class="analysis-tab-btn active" onclick="showAnalysisTab('viewHistoryTab', this)"> View History </button>
                        <button type="button"class="analysis-tab-btn" onclick="showAnalysisTab('clickHistoryTab', this)"> Click History </button>
                    </div>

                    <div id="viewHistoryTab" class="analysis-tab-pane active">
                        <div class="analysis-table-wrapper ad-analysis-view-history-table-box">
                            <table class="analysis-table ad-analysis-view-history-table-wrapper">
                                <thead>
                                    <tr>
                                        <th>User</th> 
                                        <th>IP Address</th>
                                        <th>Device</th>
                                        <th>Browser</th>
                                        <th>OS</th>
                                        <th>Country</th>
                                        <th>State</th>
                                        <th>City</th>
                                        <th>Referrer</th>
                                        <th>Watch Time</th>
                                        <th>Unique</th>
                                    </tr>
                                </thead>
                                <tbody id="adViewHistoryBody">
                                    <tr>
                                        <td colspan="11" class="analysis-empty">  No data </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="clickHistoryTab" class="analysis-tab-pane" style="display:none;">
                        <div class="analysis-table-wrapper ad-analysis-click-history-table-box">
                            <table class="analysis-table ad-analysis-click-history-table-wrapper">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>IP Address</th>
                                        <th>Device</th>
                                        <th>Browser</th>
                                        <th>OS</th>
                                        <th>Country</th>
                                        <th>State</th>
                                        <th>City</th>
                                        <th>Destination</th>
                                        <th>Clicked At</th>
                                    </tr>
                                </thead>
                                <tbody id="adClickHistoryBody">
                                    <tr>
                                        <td colspan="10" class="analysis-empty" >  No data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.ad.index') }}";
            }
        }
    </script>


    <script>
        document.querySelectorAll('.status-change').forEach(function(select) {

            select.addEventListener('change', function() {

                let status = this.value;
                let id = this.dataset.id;
                let current = this;

                fetch("{{ url('superadmin/ads') }}/" + id + "/status", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {

                        if (data.success) {

                            showAlert(
                                'success',
                                'Success',
                                'Ad status updated successfully.'
                            );

                        } else {

                            showAlert(
                                'warning',
                                'Warning',
                                'Ad status could not be updated.'
                            );

                        }

                    })
                    .catch(function() {

                        showAlert(
                            'error',
                            'Failed',
                            'Server error. Please try again.'
                        );

                    });

            });

        });
    </script>

    <script>
        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        document.addEventListener('click', function(e) {

            const btn = e.target.closest('.btn-view-detail');
            if (!btn) return;

            const adId = btn.dataset.id;

            const modal = document.getElementById('detailModal');
            modal.style.display = 'flex';
            modal.classList.add('active');

            fetch(`/superadmin/ad/${adId}`)
                .then(response => response.json())
                .then(res => {

                    console.log(res);

                    if (!res.success) {
                        throw new Error("No data found");
                    }

                    const ad = res.data;

                    // Tenant
                    const tenant = document.getElementById('v_tenant');
                    if (tenant) {
                        tenant.innerText = ad.tenant?.name ?? '-';
                    }

                    // Campaign
                    const campaign = document.getElementById('v_campaign');
                    if (campaign) {
                        campaign.innerText = ad.campaign_name ?? '-';
                    }



                    // Description
                    const description = document.getElementById('v_description');
                    if (description) {
                        description.innerText = ad.description ?? '-';
                    }

                    // Type
                    const type = document.getElementById('v_type');
                    if (type) {
                        type.innerText = ad.ad_type ?? '-';
                    }

                    // Budget
                    const budget = document.getElementById('v_budget');
                    if (budget) {
                        budget.innerText = ad.budget ?? '-';
                    }

                    // Status
                    const status = document.getElementById('v_status');
                    if (status) {
                        status.innerText = ad.status ?? '-';
                    }

                    // State
                    const state = document.getElementById('v_state');
                    if (state) {
                        state.innerText = ad.state_data?.name ?? '-';
                    }

                    // City
                    const city = document.getElementById('v_city');
                    if (city) {
                        city.innerText = ad.city_data?.name ?? '-';
                    }

                    // Gender
                    const gender = document.getElementById('v_gender');
                    if (gender) {
                        gender.innerText = ad.gender ?? '-';
                    }

                    // Age
                    const age = document.getElementById('v_age');
                    if (age) {
                        age.innerText = `${ad.age_from ?? '-'} - ${ad.age_to ?? '-'}`;
                    }

                    // Language
                    const language = document.getElementById('v_language');
                    if (language) {
                        language.innerText = ad.language ?? '-';
                    }

                    // Interests
                    const interests = document.getElementById('v_interests');
                    if (interests) {
                        interests.innerText = ad.interests ?? '-';
                    }

                    const hobby = document.getElementById('v_hobby');
                    if (hobby) {
                        hobby.innerText = ad.hobby ?? '-';
                    }

                    const mediaBox = document.getElementById('v_media');
                    const videoBox = document.getElementById('v_video');

                    mediaBox.innerHTML = '';
                    videoBox.innerHTML = '';

                    // Images
                    const images = [
                        {
                            title: 'Banner (320×50)',
                            url: ad.media_file?.banner_url
                        },
                        {
                            title: 'Large Banner (320×100)',
                            url: ad.media_file?.large_banner_url
                        },
                        {
                            title: 'Medium Banner (300×250)',
                            url: ad.media_file?.medium_banner_url
                        },
                        {
                            title: 'Full Screen (1080×1920)',
                            url: ad.media_file?.full_screen_url
                        }
                    ];

                    images.forEach(function(item) {

                        if (item.url) {

                            mediaBox.innerHTML += `
                                <div style="text-align:center">
                                    <div style="font-size:12px;font-weight:600;margin-bottom:6px;">
                                        ${item.title}
                                    </div>

                                    <img src="${item.url}"
                                        style="width:180px;height:auto;border:1px solid #ddd;border-radius:8px;padding:4px;">
                                </div>
                            `;
                        }

                    });

                    // Videos
                    const videos = [
                        {
                            title: 'Portrait Video',
                            url: ad.media_file?.video_portrait_url
                        },
                        {
                            title: 'Landscape Video',
                            url: ad.media_file?.video_landscape_url
                        }
                    ];

                    videos.forEach(function(item) {

                        if (item.url) {

                            videoBox.innerHTML += `
                                <div style="text-align:center">
                                    <div style="font-size:12px;font-weight:600;margin-bottom:6px;">
                                        ${item.title}
                                    </div>

                                    <video controls
                                        style="width:250px;border:1px solid #ddd;border-radius:8px;">
                                        <source src="${item.url}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            `;
                        }

                    });
                    
                })
                .catch(function(err) {

                    console.error(err);

                    closeDetailModal();

                    showAlert(
                        'error',
                        'Error',
                        'Failed to fetch ad details.'
                    );

                });

        });
    </script>

    <script>
        $(document).ready(function() {

            $('#state').on('change', function() {

                let stateId = $(this).val();

                $('#city').html('<option value="">Loading...</option>');

                if (stateId == '') {
                    $('#city').html('<option value="">Select City</option>');
                    return;
                }

                $.ajax({
                    url: "{{ route('superadmin.ad.cities', ':id') }}".replace(':id', stateId),
                    type: "GET",
                    dataType: "json",

                    success: function(response) {

                        let options = '<option value="">Select City</option>';

                        $.each(response, function(key, city) {

                            options +=
                                '<option value="' + city.id + '">' +
                                city.name +
                                '</option>';

                        });

                        $('#city').html(options);
                    },

                    error: function() {
                        $('#city').html('<option value="">Select City</option>');
                    }

                });

            });

        });
    </script>

     {{-- //image  --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {

        const type = document.getElementById('type');
        const imageFields = document.getElementById('imageFields');
        const videoFields = document.getElementById('videoFields');

        function toggleMedia() {

            if (type.value === 'video') {
                imageFields.style.display = 'none';
                videoFields.style.display = 'block';
            } else {
                imageFields.style.display = 'block';
                videoFields.style.display = 'none';
            }
        }

        toggleMedia();

        type.addEventListener('change', toggleMedia);

        });

    </script>

    {{-- // Analy --}}
    <script>

        function escapeAnalysisHtml(value) {
            if (value === null || value === undefined || value === '') {
                return '-';
            }
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function closeAdAnalysisModal() {
            const modal = document.getElementById('adAnalysisModal');
            if (!modal) {
                return;
            }
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }


        function showAnalysisTab(tabId, button) {
          const modal = document.getElementById('adAnalysisModal');
            if (!modal) {
                return;
            }
            /* Hide all tabs */

            modal.querySelectorAll('.analysis-tab-pane').forEach(function (tab) {
                tab.style.display = 'none';
                tab.classList.remove('active');
            });
            /* Remove active button */
            modal.querySelectorAll('.analysis-tab-btn').forEach(function (btn) {
                btn.classList.remove('active');
            });
            /* Show selected */
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.style.display = 'block';
                selectedTab.classList.add('active');
            }
            /* Active button */
            if (button) {
                button.classList.add('active');
            }
        }

        document.addEventListener('click', function (e) {
            const modal = document.getElementById('adAnalysisModal');
            if (!modal) {
                return;
            }
            if (e.target === modal) {
                closeAdAnalysisModal();
            }
        });

        /* ====ESC KEY*/
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            const modal = document.getElementById('adAnalysisModal');
            if (!modal) {
                return;
            }
            if (modal.classList.contains('active')) {
                closeAdAnalysisModal();
            }
        });

        /* ======= DOM READY====== */
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                const button = e.target.closest('.ad-analysis-btn');
                if (!button) {
                    return;
                }
                e.preventDefault();
                /* ==AD ID=== */
                const adId = button.dataset.id;
                if (!adId) {
                    return;
                }
                /* ==== ELEMENTS===== */

                const modal = document.getElementById('adAnalysisModal');
                const impressions = document.getElementById('analysisImpressions');
                const views = document.getElementById('analysisViews');
                const uniqueViews = document.getElementById('analysisUniqueViews');
                const clicks =  document.getElementById('analysisClicks');
                // const adTitle =document.getElementById('analysisAdTitle');
                 const campaignName = document.getElementById('analysisCampaignName');
                const userName = document.getElementById('analysisUserName');
                const viewBody =  document.getElementById('adViewHistoryBody');
                const clickBody = document.getElementById('adClickHistoryBody');
                if (!modal) {  return;  }
                if (impressions) { impressions.textContent = '0'; }
                if (views) {  views.textContent = '0';  }
                if (uniqueViews) { uniqueViews.textContent = '0';  }
                if (clicks) { clicks.textContent = '0'; }

                /* ==RESET AD INFO ====== */
                // if (adTitle) {  adTitle.textContent = 'Loading...'; }
                if (userName) { userName.textContent = 'Loading...';  }
                if (campaignName) { campaignName.textContent = 'Loading...';}

                /* = RESET VIEW HISTORY ======= */

                if (viewBody) {
                    viewBody.innerHTML = `
                        <tr>
                            <td colspan="11" class="analysis-empty">
                                Loading...
                            </td>
                        </tr>
                    `;
                }

                /* === RESET CLICK HISTORY ========== */
                if (clickBody) {
                    clickBody.innerHTML = `
                        <tr>
                            <td colspan="10" class="analysis-empty">
                                Loading...
                            </td>
                        </tr>
                    `;
                }


                /* ======= RESET FIRST TAB===== */

                const firstTabButton =  modal.querySelector('.analysis-tab-btn');
                if (firstTabButton) {
                    showAnalysisTab(
                        'viewHistoryTab',
                        firstTabButton
                    );
                }

                /*const firstTabButton =  modal.querySelector('.analysis-tab-btn');

                if (firstTabButton) {
                    showAnalysisTab(
                        'adAnalysisTab',
                        firstTabButton
                    );
                }*/

                /* == OPEN MODAL =============== */

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');

                /* ======= FETCH ====== */

                fetch( `{{ url('/superadmin/ad') }}/${adId}/analysis`,
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }
                )
                .then(function (response) {
                    if (!response.ok) {
                        return response.json()
                            .catch(function () {
                                return {};
                            })
                            .then(function (errorData) {
                                throw new Error(
                                    errorData.message ||
                                    'Something went wrong.'
                                );
                            });
                    }
                    return response.json();
                })
                .then(function (response) {
                    console.log(
                        'Ad Analysis Response:',
                        response
                    );
                    if (!response.success) {
                        throw new Error(
                            response.message ||
                            'Unable to load analysis.'
                        );
                    }

                    /* ==== AD INFORMATION  === */
                    const ad = response.ad || {};

                    // if (adTitle) { adTitle.textContent = ad.title || '-'; }
                    if (campaignName) {
                        campaignName.textContent = ad.campaign_name || '-';
                    }

                    if (userName) {
                        userName.textContent = ad.user_name || '-';
                    }

                    /* ====== SUMMARY */
                    const summary =  response.summary || {};

                    if (impressions) {
                        impressions.textContent = summary.impressions ?? 0;
                    }

                    if (views) {
                        views.textContent = summary.views ?? 0;
                    }

                    if (uniqueViews) {
                        uniqueViews.textContent = summary.unique_views ?? 0;
                    }
                    if (clicks) {
                        clicks.textContent = summary.clicks ?? 0;
                    }

                    /* ========== VIEW HISTORY */

                    const viewHistory = response.view_history || [];

                    if (!viewBody) {
                        return;
                    }

                    viewBody.innerHTML = '';

                    if (viewHistory.length === 0) {
                        viewBody.innerHTML = `
                            <tr>
                                <td colspan="11" class="analysis-empty" >
                                    No view history found.
                                </td>
                            </tr>
                        `;

                    } else {

                        viewHistory.forEach(function (item) {
                            viewBody.innerHTML += `
                                <tr>
                                    <td data-label="User"> ${escapeAnalysisHtml( item.user_name )} </td>
                                    <td data-label="IP Address"> ${escapeAnalysisHtml(  item.ip_address )} </td>
                                    <td data-label="Device"> ${escapeAnalysisHtml( item.device )}  </td>
                                    <td data-label="Browser"> ${escapeAnalysisHtml( item.browser )} </td>
                                    <td data-label="OS"> ${escapeAnalysisHtml(  item.os )} </td>
                                    <td data-label="Country"> ${escapeAnalysisHtml( item.country )} </td>
                                    <td data-label="State"> ${escapeAnalysisHtml( item.state )}</td>
                                    <td data-label="City"> ${escapeAnalysisHtml( item.city )} </td>
                                    <td data-label="Referrer"> ${escapeAnalysisHtml(  item.referrer )}</td>
                                    <td data-label="Watch Time"> ${escapeAnalysisHtml( item.watch_time )} </td>
                                    <td data-label="Unique"> ${escapeAnalysisHtml( item.is_unique )} </td>
                                </tr>
                            `;
                        });
                    }

                    /* ===CLICK HISTORY=== */
                    const clickHistory = response.click_history || [];

                    if (!clickBody) {
                        return;
                    }

                    clickBody.innerHTML = '';
                    if (clickHistory.length === 0) {
                        clickBody.innerHTML = `
                            <tr>
                                <td colspan="10" class="analysis-empty" >
                                    No click history found.
                                </td>
                            </tr>
                        `;

                    } else {

                        clickHistory.forEach(function (item) {
                            clickBody.innerHTML += `
                                <tr>
                                    <td data-label="User">  ${escapeAnalysisHtml( item.user_name )}</td>
                                    <td data-label="IP Address">  ${escapeAnalysisHtml( item.ip_address )}  </td>
                                    <td data-label="Device">  ${escapeAnalysisHtml( item.device )} </td>
                                    <td data-label="Browser">  ${escapeAnalysisHtml( item.browser )} </td>
                                    <td data-label="OS">  ${escapeAnalysisHtml( item.os )} </td>
                                    <td data-label="Country"> ${escapeAnalysisHtml(  item.country )} </td>
                                    <td data-label="State"> ${escapeAnalysisHtml(  item.state )} </td>
                                    <td data-label="City">  ${escapeAnalysisHtml( item.city   )} </td>
                                    <td data-label="Destination"> ${escapeAnalysisHtml( item.destination_url  )}  </td>
                                    <td data-label="Clicked At">  ${escapeAnalysisHtml( item.clicked_at  )} </td>
                                </tr>
                            `;
                        });
                    }
                })

                /*ERROR */

                .catch(function (error) {
                    console.error(
                        'Ad Analysis Error:',
                        error
                    );

                    // if (adTitle) { adTitle.textContent = '-'; }
                    if (campaignName) {
                        campaignName.textContent = '-';
                    }
                    if (userName) {
                        userName.textContent = '-';
                    }

                    if (impressions) {
                        impressions.textContent = '0';
                    }

                    if (views) {
                        views.textContent = '0';
                    }

                    if (uniqueViews) {
                        uniqueViews.textContent = '0';
                    }

                    if (clicks) {
                        clicks.textContent = '0';
                    }

                    if (viewBody) {
                        viewBody.innerHTML = `
                            <tr>
                                <td colspan="11" class="analysis-empty" > Failed to load view history. </td>
                            </tr>
                        `;
                    }

                    if (clickBody) {
                        clickBody.innerHTML = `
                            <tr> <tdcolspan="10" class="analysis-empty" > Failed to load click history.</td></tr>
                        `;
                    }
                });
            });
        });

    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const buttons = document.querySelectorAll(".ad-tab-btn");
        const tabs = document.querySelectorAll(".tab-content");

        const sectionParam = 'section';

        function setActiveSection(button) {
            buttons.forEach(btn => btn.classList.remove("active"));
            tabs.forEach(tab => tab.classList.remove("active"));

            button.classList.add("active");
            document.getElementById(button.dataset.tab).classList.add("active");
        }

        const initialSection = new URLSearchParams(window.location.search).get(sectionParam);
        const initialButton = initialSection === 'banners'
            ? document.querySelector('[data-tab="bannerTab"]')
            : document.querySelector('[data-tab="adsTab"]');

        if (initialButton) {
            setActiveSection(initialButton);
        }

        buttons.forEach(button => {

            button.addEventListener("click", function () {
                setActiveSection(this);

                const url = new URL(window.location.href);
                if (this.dataset.tab === 'bannerTab') {
                    url.searchParams.set(sectionParam, 'banners');
                } else {
                    url.searchParams.delete(sectionParam);
                    url.searchParams.delete('banner_page');
                }
                window.history.replaceState({}, '', url);

            });

        });

    });
    </script>
@endpush
