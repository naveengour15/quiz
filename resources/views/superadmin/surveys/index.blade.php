@extends('superadmin.layouts.app')
@section('title', 'Survey')
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

            .tenant-dropdown{
                display:none;
                position:absolute;
                width:100%;
                background:#fff;
                border:1px solid #ddd;
                border-top:none;
                max-height:220px;
                overflow-y:auto;
                z-index:9999;
                box-shadow:0 4px 10px rgba(0,0,0,.1);
            }

            .tenant-item{
                padding:10px;
                cursor:pointer;
            }

            .tenant-item:hover{
                background:#f5f5f5;
            }

            .survey-detail-modal {
                position: fixed;
                inset: 0;
                z-index: 1050;
                display: none;
                align-items: center;
                justify-content: center;
            }

            .survey-detail-modal.active {
                display: flex;
            }

            .survey-detail-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(16, 19, 43, 0.6);
                backdrop-filter: blur(4px);
            }

            .survey-detail-dialog {
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
                animation: surveyModalFadeIn 0.25s ease-out;
            }

            @keyframes surveyModalFadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }

            .survey-detail-header {
                padding: 16px 24px;
                border-bottom: 1px solid var(--line);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #FBFAFF;
            }

            .survey-detail-header h3 {
                margin: 0;
                font-size: 17px;
                font-weight: 700;
                color: var(--ink);
            }

            .survey-detail-close {
                background: transparent;
                border: none;
                font-size: 24px;
                color: var(--ink-faint);
                cursor: pointer;
                line-height: 1;
            }

            .survey-detail-body {
                padding: 24px;
                overflow-y: auto;
                flex: 1;
            }

            .survey-detail-grid {
                display: grid;
                grid-template-columns: repeat(2,1fr);
                gap: 16px 24px;
            }

            .survey-detail-item {
                display: flex;
                flex-direction: column;
                gap: 4px;
                background: #F9FAFB;
                padding: 10px 14px;
                border-radius: 8px;
                border: 1px solid #F3F4F6;
            }

            .survey-detail-label {
                font-size: 11px;
                font-weight: 700;
                color: var(--ink-faint);
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .survey-detail-value {
                font-size: 13px;
                font-weight: 600;
                color: var(--ink);
            }

            /* Survey Detail Questions */
            .survey-questions-list{
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .survey-question-box{
                background: #F9FAFB;
                border: 1px solid #E5E7EB;
                border-radius: 10px;
                padding: 12px;
                margin-bottom: 12px;
            }

            .survey-question-title{
                font-size: 14px;
                font-weight: 600;
                color: #111827;
                margin-bottom: 10px;
            }

            .survey-question-options{
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .survey-option{
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }

            .survey-option.yes{
                background: #DCFCE7;
                color: #15803D;
            } 

            .survey-option.no{
                background: #DCFCE7;
                color: #15803D;
            } 

            /* .survey-questions-list,
            .survey-rewards-list {
                margin: 0;
                padding-left: 18px;
                color: var(--ink);
            } */

            /* .question-item {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
                align-items: flex-start;
            }

            .question-item textarea.form-control {
                flex: 1;
                min-height: 72px;
                resize: vertical;
            }

            .question-item .btn-danger {
                flex-shrink: 0;
            } */

            /* Survey Question Design */
                #question-wrapper {
                    background: #fff;
                }

                .question-item {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    padding: 12px;
                    margin-bottom: 10px;

                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    background: #fff;
                }

                .question-item:last-child {
                    margin-bottom: 0;
                }

                .question-input,
                .option-input {
                    min-width: 0;
                }

                .question-item label {
                    display: block;
                    margin-bottom: 5px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #5b6170;
                }

                .question-item textarea,
                .question-item input {
                    width: 100%;
                    box-sizing: border-box;
                }

                .question-item textarea {
                    min-height: 42px;
                    resize: vertical;
                }

                .question-item .remove-question {
                    white-space: nowrap;
                    height: 34px;
                    padding: 0 14px;
                }

                .question-option-box{
                    display: grid;
                    grid-template-columns: repeat(3,1fr);
                    gap: 10px;
                    align-items: flex-end;
                }

                /* Mobile */
                @media (max-width: 768px) {
                    .question-item {
                        grid-template-columns: 1fr 1fr;
                    }

                    .question-input {
                        grid-column: 1 / -1;
                    }

                    .question-item .remove-question {
                        width: 100%;
                    }
                }

            .report-modal,
            .report-users-modal {
                position: fixed;
                inset: 0;
                z-index: 1100;
                display: none;
                align-items: center;
                justify-content: center;
            }

            .report-modal.active,
            .report-users-modal.active {
                display: flex;
            }

            .report-backdrop,
            .report-users-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.66);
                backdrop-filter: blur(4px);
            }

            .report-dialog,
            .report-users-dialog {
                position: relative;
                z-index: 1101;
                width: min(760px, calc(100vw - 32px));
                max-height: 86vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.24);
            }

            .report-table {
                width: 100%;
                border-collapse: collapse;
            }

            .report-table th,
            .report-table td {
                padding: 12px 14px;
                border-bottom: 1px solid #e8e8ef;
                text-align: left;
                vertical-align: top;
            }

            .report-table thead {
                background: #f6f4ff;
            }

            .report-count-btn {
                padding: 6px 10px;
                border: none;
                border-radius: 999px;
                background: #e9f7ef;
                color: #0f7a3a;
                cursor: pointer;
                font-weight: 700;
            }

            .report-count-btn.no {
                background: #fff0f0;
                color: #b42318;
            }

            .report-users-list {
                list-style: none;
                margin: 0;
                padding: 0;
                max-height: 320px;
                overflow-y: auto;
                display: block;
            }

            .report-users-list li {
                padding: 10px 12px;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
            }

            .report-users-table {
                width: 100%;
                border-collapse: collapse;
            }

            .report-users-table th,
            .report-users-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #e8e8ef;
                text-align: left;
                vertical-align: top;
            }

            .report-users-table thead {
                background: #f6f4ff;
            }

            /* .survey-row-actions{
                display: flex;
                gap: 10px;
            } */

            @media(max-width:575px){
                .survey-detail-body {
                    padding: 12px;
                }

                 .question-option-box {
                    grid-template-columns: repeat(1, 1fr);
                }
            }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Survey Types</h1>
                <p>Manage Survey types and format classifications.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModal()">Add Survey Type</button>
        </div>

        {{-- ========================= Search / Filters ========================= --}}
        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="users-search-filter-box">
                    <div class="users-search-filter-wrapper">
                        {{-- Search --}}
                        <div class="input-field">
                            <label class="lbl">Search</label>
                            <input type="text" name="search" class="tb-input search-field" value="{{ request('search') }}"  placeholder="Search survey name">
                        </div>
                        {{-- Tenant --}}
                        <div class="input-field">
                            <label class="lbl">Tenant</label>
                            <select name="tenant_id" class="tb-select">
                                <option value="">All Tenants</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->name }} ({{ $tenant->phone }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Survey Type --}}
                        {{-- <div class="input-field">
                            <label class="lbl">Type</label>
                            <input type="text" name="type" class="tb-input" value="{{ request('type') }}" placeholder="Survey type">
                        </div> --}}

                        {{-- Reward Type --}}
                        <div class="input-field">
                            <label class="lbl">Reward Type</label>
                            <select name="reward_type" class="tb-select">
                                <option value="">All Reward Types</option>
                                <option value="point" @selected(request('reward_type') == 'point')> Point </option>
                                <option value="quiz"  @selected(request('reward_type') == 'quiz')>  Quiz </option>
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="input-field">
                            <label class="lbl">Status</label>
                            <select name="status" class="tb-select">
                                <option value="">All Status</option>
                                <option value="active" @selected(request('status') == 'active')> Active  </option>
                                <option value="inactive"  @selected(request('status') == 'inactive')> Inactive  </option>
                            </select>
                        </div>
                    </div>
                    <div class="users-filter-btn">
                        <button type="submit" class="btn btn-primary">  Filter </button>
                        <a href="{{ route('superadmin.survey.index') }}" class="btn btn-ghost">  Reset </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01">
            <div class="survey-type-table-box">
                <table class="survey-type-table-wrapper">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Reward Type</th>
                            <th>S.Complete</th>
                            <th>Quiz</th>
                            <th>Point</th>
                            {{-- <th>Quiz Open</th> --}}
                            <th>No. Question </th>
                            {{-- <th> Yes </th>
                            <th>No</th> --}}
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($surveys as $survey)
                            <tr>
                                <td data-label="#" class="mono">{{$surveys->firstItem() + $loop->index }}</td>
                                <td data-label="Tenant"> {{ $survey->tenant->company_name ?? $survey->tenant->name ?? '-' }} </td>
                                <td data-label="Name"> {{ $survey->name?? '-' }} </td>
                                <td data-label="Type"> {{ $survey->type ?? '-' }}</td>
                                <td data-label="Reward Type"> {{ $survey->reward_type ?? '-'}}</td>
                                 <td data-label="S.Complete">
                                    <span class="badge badge-success">
                                        {{ $survey->completed_count ?? 0 }}
                                    </span>
                                </td>
                                <td data-label="Quiz"> {{($survey->quiz ?? '-') }} </td>
                                <td data-label="Point">{{ $survey->point ?? '-' }} </td>

                                {{-- <td>  {{ $survey->quiz_open }}</td> --}}

                                <td data-label="No. Question">{{ $survey->questions_count }}</td>

                                {{-- <td data-label="Yes">
                                    <span class="badge badge-success"> {{ $survey->yes_count }} </span>
                                </td>

                                <td data-label="No">
                                    <span class="badge badge-danger"> {{ $survey->no_count }} </span>
                                </td> --}}

                                <td data-label="Status">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <label class="switch">
                                            <input type="checkbox" class="status-toggle" data-id="{{ $survey->id }}" {{ $survey->status == 'active' ? 'checked' : '' }}>
                                            <span class="slider"></span>
                                        </label>

                                        <span class="status-text"> {{ ucfirst($survey->status) }}</span>
                                    </div>
                                </td>

                                <td data-label="Actions" class="survey-row-actions">
                                    <div style="display: flex; gap:10px;">
                                        <button type="button" class="icon-btn btn-report-survey" data-id="{{ $survey->id }}" title="Survey Report">  📊 </button>
                                        <button type="button" class="icon-btn btn-view-survey" data-id="{{ $survey->id }}" title="View Details">  👁  </button>
                                        <a class="icon-btn"  href="{{ route('superadmin.survey.index', ['edit' => $survey->id]) }}">  ✎ </a>

                                        <form method="POST" action="{{ route('superadmin.survey.destroy', $survey) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn danger" type="submit"> 🗑 </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10"  style="text-align:center;color:var(--ink-faint);"> No surveys found. </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $surveys])
            </div>
        </div>
    </div>

    {{-- Modal placed outside .panel for clean fullscreen backdrop overlay --}}
    <div id="surveyModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
        <div class="modal-backdrop" onclick="closeModal()"></div>

        <div class="modal-content form-card" style="margin-bottom:0;">
            <div class="fh">
                <h3>{{ $editing ? 'Edit Survey' : 'Add Survey' }}</h3>

                <button type="button"
                    class="icon-btn"
                    onclick="closeModal()"
                    style="border:none;background:transparent;box-shadow:none;">
                    ✕
                </button>
            </div>

            <form method="POST"
                action="{{ $editing ? route('superadmin.survey.update', $editing) : route('superadmin.survey.store') }}">

                @csrf

                @if ($editing)
                    @method('PUT')
                @endif
             
                <div class="form-grid form-grid-02">

                    <div class="field">
                        <label>Tenant <span class="req"></span></label>

                        @php
                            $selectedTenantId = old('tenant_id', $editing->tenant_id ?? null);
                            $selectedTenant = $tenants->firstWhere('id', $selectedTenantId);
                        @endphp

                        <input type="text" id="tenant_search_input" class="form-control" list="tenant-options" placeholder="Search tenant by name or mobile" autocomplete="off" value="{{ old('tenant_search_input', ($selectedTenant ? $selectedTenant->name . ' (' . $selectedTenant->phone . ')' : '')) }}">

                        <datalist id="tenant-options">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->name }} ({{ $tenant->phone }})" data-id="{{ $tenant->id }}"></option>
                            @endforeach
                        </datalist>

                        <input type="hidden" name="tenant_id" id="tenant_id" value="{{ $selectedTenantId ?? '' }}" >
                    </div>

                    {{-- Survey Name --}}
                    <div class="field">
                        <label>Survey Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $editing->name ?? '') }}" required>
                    </div>

                    {{-- Type --}}
                    <div class="field full">
                        <label>Survey Type</label>
                        <input type="text" name="type" value="{{ old('type', $editing->type ?? '') }}" placeholder="e.g. General, Health, Education">
                    </div>


                    <div class="field full">
                        <label>Survey Questions <span class="req">*</span></label>

                        <div id="question-wrapper"
                            style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:10px;border-radius:8px;">

                            @php
                                $savedQuestions = old('questions');
                                $savedYes = old('yes');
                                $savedNo = old('no');
                            @endphp

                            @if(!empty($savedQuestions))

                                @foreach($savedQuestions as $index => $questionValue)

                                    <div class="question-item">

                                        <div class="question-input">
                                            <label>Question {{ $index + 1 }}</label>

                                            <textarea
                                                name="questions[]"
                                                class="form-control"
                                                placeholder="Enter Question"
                                                rows="2"
                                                required>{{ $questionValue }}</textarea>
                                        </div>

                                        <div class="question-option-box">
                                            <div class="option-input">
                                                <label>Option 1</label>

                                                <input
                                                    type="text"
                                                    name="yes[]"
                                                    class="form-control"
                                                    value="{{ $savedYes[$index] ?? 'Yes' }}"
                                                    placeholder="Yes"
                                                    required>
                                            </div>

                                            <div class="option-input">
                                                <label>Option 2</label>

                                                <input
                                                    type="text"
                                                    name="no[]"
                                                    class="form-control"
                                                    value="{{ $savedNo[$index] ?? 'No' }}"
                                                    placeholder="No"
                                                    required>
                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-danger remove-question">
                                                × <span class="remove-icon-box">Remove</span>
                                            </button>
                                        </div>
                                       
                                    </div>

                                @endforeach

                            @elseif($editing && $editing->questions->count())

                                @foreach($editing->questions as $index => $question)

                                    <div class="question-item">

                                        <div class="question-input">
                                            <label>Question {{ $index + 1 }}</label>

                                            <textarea
                                                name="questions[]"
                                                class="form-control"
                                                placeholder="Enter Question"
                                                rows="2"
                                                required>{{ $question->question }}</textarea>
                                        </div>
                                        <div class="question-option-box">
                                            <div class="option-input">
                                                <label>Option 1</label>

                                                <input
                                                    type="text"
                                                    name="yes[]"
                                                    class="form-control"
                                                    value="{{ $question->yes ?: 'Yes' }}"
                                                    placeholder="Yes"
                                                    required>
                                            </div>

                                            <div class="option-input">
                                                <label>Option 2</label>

                                                <input
                                                    type="text"
                                                    name="no[]"
                                                    class="form-control"
                                                    value="{{ $question->no ?: 'No' }}"
                                                    placeholder="No"
                                                    required>
                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-danger remove-question">
                                                × <span class="remove-icon-box">Remove</span>
                                            </button>
                                        </div>

                                    </div>

                                @endforeach

                            @else

                                {{-- Default Question --}}
                                <div class="question-item">

                                    <div class="question-input">
                                        <label>Question 1</label>

                                        <textarea
                                            name="questions[]"
                                            class="form-control"
                                            placeholder="Enter Question"
                                            rows="2"
                                            required></textarea>
                                    </div>
                                        <div class="question-option-box">
                                        <div class="option-input">
                                            <label>Option 1</label>

                                            <input
                                                type="text"
                                                name="yes[]"
                                                class="form-control"
                                                value="Yes"
                                                placeholder="Yes"
                                                required>
                                        </div>

                                        <div class="option-input">
                                            <label>Option 2</label>

                                            <input
                                                type="text"
                                                name="no[]"
                                                class="form-control"
                                                value="No"
                                                placeholder="No"
                                                required>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-danger remove-question">
                                            × <span class="remove-icon-box">Remove</span>
                                        </button>
                                    </div>
                                </div>

                            @endif

                        </div>

                        <button
                            type="button"
                            id="add-question"
                            class="btn btn-primary"
                            style="margin-top:10px;width:100%;">
                            + Add More Question
                        </button>
                    </div>

                    <div class="field">
                        <label>Reward Type <span class="req">*</span></label>
                        <select name="reward_type" id="reward_type">
                            <option value="point" {{ old('reward_type',$editing->reward_type ?? '')=='point'?'selected':'' }}> Point </option>
                            <option value="quiz" {{ old('reward_type',$editing->reward_type ?? '')=='quiz'?'selected':'' }}> Quiz </option>
                        </select>
                    </div>

                    {{-- Point --}}
                    <div class="field"  id="point-div">
                        <label>Point <span class="req">*</span></label>
                        <input type="number"   id="point" name="point" min="0" value="{{ old('point', $editing->point ?? '') }}" placeholder="Point">
                    </div>

                    <div class="field" id="quiz-div" style="display:none;">
                        <label>Quiz Number <span class="req">*</span></label>
                        <input type="number"    id="quiz" name="quiz"  min="0"  value="{{ old('quiz', $editing?->quiz ?? ($editing && $editing->rewards->where('reward_type', 'quiz')->first()?->quiz ?? '')) }}"  placeholder="Quiz Number">
                    </div>
                </div>

                <div class="form-foot">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()"> Cancel </button>
                    <button type="submit" class="btn btn-primary"> {{ $editing ? 'Update Survey' : 'Save Survey' }}</button>
                </div>

            </form>
        </div>
    </div>

    <div id="surveyDetailModal" class="survey-detail-modal" style="display: none;">
        <div class="survey-detail-backdrop" onclick="closeSurveyDetailModal()"></div>
        <div class="survey-detail-dialog">
            <div class="survey-detail-header">
                <h3 id="surveyDetailTitle">Survey Details</h3>
                <button type="button" class="survey-detail-close" onclick="closeSurveyDetailModal()">&times;</button>
            </div>

            <div class="survey-detail-body">
                <div class="survey-detail-grid">
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Tenant</span>
                        <span id="v_survey_tenant" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Survey Name</span>
                        <span id="v_survey_name" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Type</span>
                        <span id="v_survey_type" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Reward Type</span>
                        <span id="v_survey_reward_type" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Point</span>
                        <span id="v_survey_point" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item">
                        <span class="survey-detail-label">Quiz</span>
                        <span id="v_survey_quiz" class="survey-detail-value">—</span>
                    </div>
                    <div class="survey-detail-item" style="grid-column: 1 / -1;">
                        <span class="survey-detail-label">Questions</span>
                        <ol id="v_survey_questions" class="survey-questions-list"></ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="surveyReportModal" class="report-modal" style="display: none;">
        <div class="report-backdrop" onclick="closeSurveyReportModal()"></div>
        <div class="report-dialog">
            <div class="survey-detail-header">
                <h3 id="surveyReportTitle">Survey Report</h3>
                <button type="button" class="survey-detail-close" onclick="closeSurveyReportModal()">&times;</button>
            </div>
            <div class="survey-detail-body survey-test-report-table-box">
                <table class="report-table survey-test-report-table-wrapper">
                    <thead>
                        <tr>
                            <th>Question</th>
                            {{-- <th>YES Count</th>
                            <th>NO Count</th> --}}
                            <th id="reportYesHeader"> Count</th>
                            <th id="reportNoHeader"> Count</th>
                        </tr>
                    </thead>
                    <tbody id="surveyReportBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="surveyUsersModal" class="report-users-modal" style="display: none;">
        <div class="report-users-backdrop" onclick="closeSurveyUsersModal()"></div>
        <div class="report-users-dialog">
            <div class="survey-detail-header">
                <h3 id="surveyUsersTitle">Users</h3>
                <button type="button" class="survey-detail-close" onclick="closeSurveyUsersModal()">&times;</button>
            </div>
            <div class="survey-detail-body">
                <p id="surveyUsersQuestion" style="margin:0 0 14px 0;"></p>
                <div class="survey-report-users-table-box" style="max-height: 320px; overflow-y: auto;">
                    <table class="report-users-table survey-report-users-table-wrapper">
                        <thead>
                            <tr>
                                <th>Name</th>
                                {{-- <th>Email</th> --}}
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody id="surveyUsersList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
    <script>
        function openModal() {
            document.getElementById('surveyModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('surveyModal').classList.remove('active');
            if (window.location.search.includes('edit=')) {
                window.location.href = "{{ route('superadmin.survey.index') }}";
            }
        }
    </script>

    <script>
        function closeSurveyDetailModal() {
            const modal = document.getElementById('surveyDetailModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        function closeSurveyReportModal() {
            const modal = document.getElementById('surveyReportModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        function closeSurveyUsersModal() {
            const modal = document.getElementById('surveyUsersModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-view-survey');
            if (!btn) {
                return;
            }
            const surveyId = btn.dataset.id;
            const modal = document.getElementById('surveyDetailModal');
            if (!modal) {
                return;
            }
            modal.style.display = 'flex';
            modal.classList.add('active');
            fetch(`/superadmin/survey/${surveyId}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data) {
                        return;
                    }
                    const survey = res.data;
                    const tenantName = survey.tenant?.company_name || survey.tenant?.name || '—';
                    const questions = survey.questions || [];
                    document.getElementById('surveyDetailTitle').innerText = survey.name || 'Survey Details';
                    document.getElementById('v_survey_tenant').innerText = tenantName;
                    document.getElementById('v_survey_name').innerText = survey.name || '—';
                    document.getElementById('v_survey_type').innerText = survey.type || '—';
                    document.getElementById('v_survey_reward_type').innerText = survey.reward_type || '—';
                    document.getElementById('v_survey_point').innerText = survey.point ?? '—';
                    document.getElementById('v_survey_quiz').innerText = survey.quiz ?? '—';
                    const list = document.getElementById('v_survey_questions');
                    list.innerHTML = '';

                   /* if (questions.length) {
                        questions.forEach(function (question) {
                            const li = document.createElement('li');
                            li.innerText = question.question || '—';
                            list.appendChild(li);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.innerText = 'No questions found.';
                        list.appendChild(li);
                    }*/

                    if (questions.length) {

                        questions.forEach(function (question, index) {

                            const li = document.createElement('li');

                            li.innerHTML = `
                                <div class="survey-question-box">
                                    <div class="survey-question-title">
                                        <strong>Q${index + 1}.</strong> ${question.question || '—'}
                                    </div>

                                    <div class="survey-question-options">
                                        <span class="survey-option yes">
                                            Option 1: ${question.yes || 'Yes'}
                                        </span>

                                        <span class="survey-option no">
                                            Option 2: ${question.no || 'No'}
                                        </span>
                                    </div>
                                </div>
                            `;

                            list.appendChild(li);
                        });

                    } else {

                        list.innerHTML = `<li>No questions found.</li>`;
                    }
                })
                .catch(() => {
                    closeSurveyDetailModal();
                    showAlert('error', 'Error', 'Failed to fetch survey details.');
                });
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-report-survey');
            if (!btn) {
                return;
            }

            const surveyId = btn.dataset.id;
            const modal = document.getElementById('surveyReportModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'flex';
            modal.classList.add('active');

            fetch(`/superadmin/survey/${surveyId}/report`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data?.questions) {
                        showAlert('error', 'Error', res.message || 'Failed to fetch survey report.');
                        return;
                    }

                    const report = res.data.questions || [];
                    const body = document.getElementById('surveyReportBody');
                    body.innerHTML = '';

                    document.getElementById('surveyReportTitle').innerText = (res.data.survey?.name || 'Survey') + ' Report';

                    if (!report.length) {
                        body.innerHTML = '<tr><td colspan="3">No answers found for this survey.</td></tr>';
                        return;
                    }

                    report.forEach(function (item) {
                        const yesLabel = item.yes_label || 'Yes';
                        const noLabel = item.no_label || 'No';
                        const row = document.createElement('tr');

                        row.innerHTML = `
                            <td data-label="Question">
                                ${item.question || '—'}
                            </td>

                            <td data-label="${yesLabel}">
                                <button type="button" class="report-count-btn yes"  data-survey-id="${surveyId}" data-question-id="${item.question_id}" data-answer="yes">
                                    ${item.yes_count || 0} ${yesLabel}
                                </button>
                            </td>

                            <td data-label="${noLabel}">
                                <button type="button" class="report-count-btn no" data-survey-id="${surveyId}" data-question-id="${item.question_id}" data-answer="no">
                                    ${item.no_count || 0} ${noLabel}
                                </button>
                            </td>
                        `;

                        body.appendChild(row);
                    });
                })
                .catch(() => {
                    closeSurveyReportModal();
                    showAlert('error', 'Error', 'Failed to fetch survey report.');
                });
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.report-count-btn');
            if (!btn) {
                return;
            }

            const surveyId = btn.dataset.surveyId;
            const questionId = btn.dataset.questionId;
            const answer = btn.dataset.answer;
            const usersModal = document.getElementById('surveyUsersModal');
            const usersList = document.getElementById('surveyUsersList');
            const usersTitle = document.getElementById('surveyUsersTitle');
            const usersQuestion = document.getElementById('surveyUsersQuestion');

            if (!usersModal || !usersList || !usersTitle || !usersQuestion) {
                return;
            }

            usersModal.style.display = 'flex';
            usersModal.classList.add('active');
            // usersTitle.innerText = answer === 'yes' ? 'Yes Users' : 'No Users';
            usersTitle.innerText = answer === 'yes' ? 'Yes Users' : 'No Users';
            usersQuestion.innerText = 'Loading question...';
            usersList.innerHTML = '<tr><td colspan="2">Loading users...</td></tr>';

            fetch(`/superadmin/survey/${surveyId}/report-users?question_id=${questionId}&answer=${answer}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data?.users) {
                        usersList.innerHTML = '<tr><td colspan="2">No users found.</td></tr>';
                        return;
                    }

                    // usersQuestion.innerHTML = '<strong>Question:</strong> ' + (res.data.question || 'Question');
                    usersQuestion.innerHTML = '<strong>Question:</strong> ' + (res.data.question || 'Question') +'<br><strong>Selected Option:</strong> ' + (res.data.answer_label || answer);
                    usersList.innerHTML = '';

                    if (!res.data.users.length) {
                        usersList.innerHTML = '<tr><td colspan="2">No users found for this answer.</td></tr>';
                        return;
                    }

                    res.data.users.forEach(function (user) {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td data-label="Name">${user.name || 'Unknown User'}</td>
                            
                            <td data-label="Phone">${user.mobile || '—'}</td>
                        `;
                        usersList.appendChild(row);
                    });
                })
                .catch(() => {
                    usersList.innerHTML = '<tr><td colspan="2">Failed to load users.</td></tr>';
                });
        });
    </script>

    <script>
        document.querySelectorAll('.status-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                let checkbox = this;
                fetch("{{ url('superadmin/surveys') }}/" + checkbox.dataset.id + "/status", {
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
                                'Survey Type status updated successfully.'
                            );

                        } else {

                            checkbox.checked = !checkbox.checked;
                            showAlert(
                                'warning',
                                'Warning',
                                'Survey Type status could not be updated.'
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

    <script>
        $(document).ready(function () {
            toggleReward();
            // Reward Type Change
            $('#reward_type').on('change', function () {
                toggleReward();
            });
            function toggleReward() {
                let reward = $('#reward_type').val();
                let pointInput = $('#point-div input[name="point"]');
                let quizInput  = $('#quiz-div input[name="quiz"]');
                if (reward === 'point') {
                    $('#point-div').show();
                    $('#quiz-div').hide();

                    // Point Required
                    pointInput.prop('required', true);

                    // Quiz Optional
                    quizInput.prop('required', false);


                } else {
                    $('#point-div').hide();
                    $('#quiz-div').show();

                    // Quiz Required
                    quizInput.prop('required', true);

                    // Point Optional
                    pointInput.prop('required', false);

                }
            }
            /* Add Question */
           $('#add-question').click(function () {

                let questionNumber = $('.question-item').length + 1;

                $('#question-wrapper').append(`
                    <div class="question-item">

                        <div class="question-input">
                            <label>Question ${questionNumber}</label>
                            <textarea name="questions[]" class="form-control" placeholder="Enter Question" rows="2" required></textarea>
                        </div>

                        <div class="question-option-box">
                        <div class="option-input">
                            <label>Option 1</label>
                            <input type="text"  name="yes[]"  class="form-control" value="Yes"  placeholder="Yes" required>
                        </div>

                        <div class="option-input">
                            <label>Option 2</label>
                            <input type="text" name="no[]" class="form-control" value="No"  placeholder="No" required>
                        </div>

                        <button type="button" class="btn btn-danger remove-question"> 
                             × <span class="remove-icon-box">Remove</span>
                         </button>
                         </div>
                    </div>
                `);
            });
            /* Remove Question  */
            $(document).on('click', '.remove-question', function () {
                if ($('.question-item').length > 1) {
                    $(this).closest('.question-item').remove();
                }
            });
            /*Add Quiz  */
            $('#add-quiz').click(function () {
                $('#quiz-wrapper').append(`
                    <div class="quiz-item"style="display:flex;gap:10px;margin-bottom:10px;">
                        <input type="number" name="quiz[]" class="form-control" placeholder="Quiz Number"style="flex:1;">
                        <button type="button" class="btn btn-danger remove-quiz"> × </button>
                    </div>
                `);

            });
            /* Remove Quiz */
            $(document).on('click', '.remove-quiz', function () {
                if ($('.quiz-item').length > 1) {
                    $(this).closest('.quiz-item').remove();
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tenantInput = document.getElementById('tenant_search_input');
            const tenantHidden = document.getElementById('tenant_id');
            if (!tenantInput || !tenantHidden) {
                return;
            }
            const tenantMap = {};
            document.querySelectorAll('#tenant-options option').forEach(function (option) {
                tenantMap[option.value] = option.dataset.id || '';
            });
            function syncTenantHiddenField() {
                const selectedValue = tenantInput.value;
                tenantHidden.value = tenantMap[selectedValue] || '';
            }
            tenantInput.addEventListener('input', syncTenantHiddenField);
            tenantInput.addEventListener('change', syncTenantHiddenField);
            syncTenantHiddenField();
        });
    </script>
@endpush
