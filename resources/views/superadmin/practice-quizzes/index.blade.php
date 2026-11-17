@extends('superadmin.layouts.app')
@section('title', 'Practice Quizzes')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
@push('styles')
        
    <style>
        /* ===== Quiz Page Styles ===== */
        .quiz-status-change {
            min-width: 150px;
            padding: 8px 12px;
            border: 1px solid #dcdfe6;
            border-radius: 8px;
            background: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .quiz-status-change:focus {
            outline: none;
            border-color: #4F46E5;
        }

        .quiz-title{
            white-space: nowrap;
        }

        .approve-status-change {
            width: 140px;
            padding: 6px 30px 6px 10px;
            border: 1px solid #dcdfe6;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            outline: none;
        }

        .approve-status-change:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79,70,229,.15);
        }


        /* ===== Stats Toggle Button ===== */
        .btn-stats-toggle {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
            margin-left: 8px;
        }

        .btn-stats-toggle:hover {
            transform: scale(1.1);
        }

        .btn-stats-toggle.active {
            background: linear-gradient(135deg, #10b981, #059669);
            transform: rotate(180deg);
        }

        /* ===== Stats Panel ===== */
        .stats-detail-row {
            animation: slideDown 0.3s ease-out;
        }

        .stats-detail-row td {
            padding: 0 !important;
            border: none !important;
        }

        .stats-panel {
            background: #f8fafc;
            padding: 20px;
            border-left: 4px solid #3b82f6;
            border-radius: 0 0 8px 8px;
        }

        .stats-section-title {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 12px;
        }

        .stats-section-title:first-child {
            margin-top: 0;
        }

        /* ===== Stats Grid ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (max-width: 991px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 991px) {
            .quiz-performance-card-grid{
                grid-template-columns: repeat(4, 1fr) !important;
            }
            .stats-detail-row td {
                width: 100% !important;
                min-width: 100% !important;
                max-width: none !important;
                white-space: normal !important;
                display: table-cell !important;
            }

            .stats-panel {
                width: 100%;
                box-sizing: border-box;
            }

            /* Agar table-wrap horizontal scroll deta hai, to stats row ko us scroll se bahar nikal kar full width dikhayen */
            .quizzes-table-wrapper {
                table-layout: auto;
            }

            .stats-detail-row td {
                padding: 0 !important;
            }
            .stats-panel {
                width: 96vw;
                max-width: 100vw;
                margin-left: calc(-1 * (100vw - 100%) / 2);
                box-sizing: border-box;
                position: relative;
                left: 50%;
                transform: translateX(-50%);
            }
        }

        @media (max-width: 767px) {
            .quiz-performance-card-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 575px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quiz-modal-tab-btn {
                padding: 12px !important;
            }
            .detail-val {
                font-size: 11px !important;
            }
            .practice-detail-grid{
                grid-template-columns: repeat(1, 1fr) !important;
            }
            .question-text,.option-text {
                text-align: end;
            }
        }

        /* ===== Stat Card (Simple) ===== */
        .stat-card {
            background: white;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            background: #f3f4f6;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-meta {
            font-size: 11px;
            color: #64748b;
        }

        /* ===== Icon Colors ===== */
        .stat-card.blue .stat-icon { background: #dbeafe; }
        .stat-card.blue .stat-value { color: #1e40af; }

        .stat-card.green .stat-icon { background: #dcfce7; }
        .stat-card.green .stat-value { color: #166534; }

        .stat-card.yellow .stat-icon { background: #fef3c7; }
        .stat-card.yellow .stat-value { color: #92400e; }

        .stat-card.pink .stat-icon { background: #fce7f3; }
        .stat-card.pink .stat-value { color: #9f1239; }

        .stat-card.purple .stat-icon { background: #e9d5ff; }
        .stat-card.purple .stat-value { color: #6b21a8; }

        .stat-card.orange .stat-icon { background: #fed7aa; }
        .stat-card.orange .stat-value { color: #9a3412; }

        /* ===== Footer ===== */
        .stats-footer {
            margin-top: 12px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 12px;
            border: 1px solid #e5e7eb;
        }

        .stats-footer-item {
            color: #475569;
        }

        .stats-footer-item strong {
            color: #1e293b;
        }

        @media (max-width: 575px) {
            .stats-panel {
                padding: 16px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stats-footer {
                flex-direction: column;
                gap: 8px;
            }
        }

        /* ===== Animation ===== */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Player Count Circle Styles */
            .player-circle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                border-radius: 50%;
                font-weight: 800;
                font-size: 14px;
                text-decoration: none;
                transition: all 0.2s ease;
            }

            /* Active State (> 0 Players) - Green & Clickable */
            .player-circle.active {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3);
            }

            .player-circle.active:hover {
                transform: translateY(-2px) scale(1.05);
                box-shadow: 0 5px 15px rgba(16, 185, 129, 0.5);
                color: white;
            }

            /* Inactive State (0 Players) - Gray & Not Clickable */
            .player-circle.inactive {
                background: #f3f4f6;
                color: #9ca3af;
                border: 1px solid #e5e7eb;
                cursor: default;
            }
    </style>

@endpush
    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1> Practice Quizzes</h1>
                <p>Assemble quizzes from the question bank, configure access rules, and control draft/publish/schedule
                    state.</p>
            </div>
            {{-- Practice Quiz Refresh Button --}}
            <a href="{{ route('superadmin.practice-quizzes.refresh') }}" 
            class="btn btn-primary" 
            onclick="return confirm('Are you sure you want to refresh completed quizzes into Practice Quizzes? This will skip duplicates.');">
                <i class="fas fa-sync-alt"></i> Refresh Practice Quizzes
            </a>

            {{-- <a href="{{ route('superadmin.quizzes.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Create Quiz</a> --}}
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="quizzes-search-filter-box">
                     <div class="quizzes-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input" name="search" value="{{ request('search') }}" placeholder="Search quiz title…">
                        </div>

                         <div class="input-field">
                            <label class="lbl" for="search">Status</label>
                            <select class="tb-select" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach (['draft', 'scheduled', 'published', 'archived'] as $s)
                                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                         </div>

                         <div class="input-field">
                             <label class="lbl" for="search">Visibility</label>
                            <select class="tb-select" name="visibility" onchange="this.form.submit()">
                                <option value="">All Visibility</option>
                                <option value="public" @selected(request('visibility') === 'public')>Public</option>
                                <option value="private" @selected(request('visibility') === 'private')>Private</option>
                                 <option value="leaderboard" @selected(request('visibility') === 'leaderboard')>Leaderboard</option>
                                <option value="fastest_finger_first" @selected(request('visibility') === 'fastest_finger_first')>Fast Finger First</option>
                            </select>
                         </div>
                     </div>
                     <div class="quizzes-search-filter-btn">
                        <div class="tb-spacer"></div>
                        <button class="btn btn-primary" type="submit">Filter</button>
                     </div>
                </div>
            </form>
        </div>

        <div class="table-wrap table-wrap-res-01" style="overflow: hidden">
            <div class="quizzes-table-box">
                <table class="quizzes-table-wrapper ">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Quiz</th>
                            <th>Shedule Time</th>
                            <th>Category</th>                    
                            <th>Level</th>
                            <th>Quiz Type</th>
                            {{-- <th>Questions</th> --}}
                            <th>Questions</th>
                            <th>Max Players</th>
                            <th>Joined Players</th>
                            <th>Visibility</th>
                            <th>Quiz Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quizzes as $quiz)
                            <tr>
                                 <td data-label="#">
                                    {{ $quizzes->total() - ($quizzes->firstItem() + $loop->index) + 1 }}
                                 </td>
                                <td data-label="Quiz" class="quiz-title">{{ $quiz->title }}</td>
                                {{-- <td data-label="Schedule Time">{{ $quiz->scheduled_at ? $quiz->scheduled_at->format('Y-m-d H:i') : '—' }}</td> --}}

                                {{-- ****Shedule time show start***** --}}
                                 @include('superadmin.quizzes.component.quiz-timer')
                                

                                {{-- ****Shedule time show end***** --}}
                               
                                <td data-label="Category">{{ $quiz->category->name ?? '—' }} 
                                    {{-- @if ($quiz->level)
                                        / {{ $quiz->level }}
                                    @endif --}}
                                </td>

                            
                                <td data-label="Level">{{ $quiz->level->name ?? '—' }}</td>

                                <td data-label="Quiz Type">{{ $quiz->quizType->name ?? '—' }}</td>

                                {{-- <td data-label="Questions" class="mono">
                                    <div>
                                        {{ $quiz->questions_count }}

                                    </div>
                                </td> --}}

                                {{-- 1. Questions Column --}}
                                <td data-label="Questions" class="mono text-center">
                                    {{ $quiz->questions_count ?? 0 }}
                                </td>

                                {{-- 2. Max Players Column --}}
                                <td data-label="Max Players" class="mono text-center">
                                    {{ $quiz->no_of_players ?? 0 }}
                                </td>

                                {{-- 3. Joined Players Column (Smart Circle Logic) --}}
                                <td data-label="Joined Players" class="mono text-center">
                                    @php
                                        $joinedCount = $quiz->users_count?? 0;
                                    @endphp

                                    @if($joinedCount > 0)
                                        {{-- ✅ 0 se zyada hai: Green Clickable Circle (Anchor Tag) --}}
                                        <a href="{{ route('superadmin.practice-quiz.joined-users', $quiz->id) }}"
                                            class="player-circle active" 
                                            title="View Joined Players">
                                                {{ $joinedCount }}
                                        </a>
                                    @else
                                        {{-- ❌ 0 hai: Gray Non-Clickable Circle (Span) --}}
                                        <span class="player-circle inactive" title="No players joined yet">
                                            {{ $joinedCount }}
                                        </span>
                                    @endif
                                </td>
                                {{-- ************ --}}
                            
                                <td data-label="Visibility">
                                    @if ($quiz->visibility === 'public')
                                        <span class="badge info">Public</span>

                                    @elseif ($quiz->visibility === 'private')
                                        <span class="badge warn">Private · 🔒</span>

                                    @elseif ($quiz->visibility === 'leaderboard')
                                        <span class="badge success">Leaderboard 🏆</span>

                                    @elseif ($quiz->visibility === 'practice')
                                        <span class="badge primary">Practice 📝</span>
                                    @elseif ($quiz->visibility === 'leaderboard')
                                        <span class="badge success">Leaderboard 🏆</span>  
                                     @elseif ($quiz->visibility === 'fastest_finger_first')
                                        <span class="badge danger">Fast Finger First 🎯</span>
                                
                                    @elseif ($quiz->visibility === 'mahaquiz')
                                        <span class="badge danger">Mahaquiz 🎯</span>

                                    @endif
                                </td>


                                {{-- <td>
                                    @php $stClass = ['draft'=>'neutral','scheduled'=>'warn','published'=>'success','archived'=>'neutral'][$quiz->status] ?? 'neutral'; @endphp
                                    <span class="badge {{ $stClass }}">{{ ucfirst($quiz->status) }}</span>
                                </td> --}}

                                <td data-label="Approve Status">

                                    @if($quiz->complete_status == 'completed')

                                        @if($quiz->approve_status == 'approved')
                                            <span class="badge success">Approved</span>
                                        @elseif($quiz->approve_status == 'rejected')
                                            <span class="badge warn" title="{{ $quiz->approval_note ?? '' }}">Rejected</span>
                                        @elseif($quiz->approve_status == 'cancelled')
                                            <span class="badge danger">Cancelled</span>
                                        @else
                                            <span class="badge secondary">Pending</span>
                                        @endif

                                    @elseif($quiz->approve_status == 'cancelled')

                                        <span class="badge danger">Cancelled</span>

                                    @else

                                        <select class="form-select form-select-sm approve-status-change"
                                                data-id="{{ $quiz->id }}">
                                            <option selected disabled>
                                                {{ ucfirst($quiz->approve_status) }}
                                            </option>
                                            <option value="cancelled">Cancel Quiz</option>
                                        </select>

                                    @endif

                                </td>
                                <td data-label="Status">
                                    <div>
                                        @if($quiz->complete_status == 'completed')
                                            <span class="badge success">Completed</span>

                                            {{-- ***********result data **** --}}
             
                                            @if($quiz->quizStatus)
                                                <button 
                                                    type="button" 
                                                    class="btn-stats-toggle" 
                                                    data-quiz-id="{{ $quiz->id }}"
                                                    title="View Detailed Statistics"
                                                >
                                                    📊
                                                </button>
                                            @else
                                                <span style="font-size:11px; color:var(--ink-faint); font-style:italic;">No stats</span>
                                            @endif
     
                                            {{-- ***********result data **** --}}
                                        @else
                                            <select class="quiz-status-change" data-id="{{ $quiz->id }}">
                                                <option value="draft" {{ $quiz->status == 'draft' ? 'selected' : '' }}>
                                                    Draft
                                                </option>

                                                <option value="scheduled" {{ $quiz->status == 'scheduled' ? 'selected' : '' }}>
                                                    Scheduled
                                                </option>

                                                <option value="published" {{ $quiz->status == 'published' ? 'selected' : '' }}>
                                                    Published
                                                </option>

                                                <option value="archived" {{ $quiz->status == 'archived' ? 'selected' : '' }}>
                                                    Archived
                                                </option>
                                            </select>
                                        @endif    
                                    </div>
                                </td>
                                <td data-label="Actions" class="row-actions">
                                    <div style="display: flex; gap:10px;">
                                        {{-- <a class="icon-btn" href="{{ route('superadmin.quizzes.winners', $quiz) }}" title="Winner List">🏆</a> --}}
                                        <button
                                                class="icon-btn btn-view-quiz"
                                                data-id="{{ $quiz->id }}"
                                                data-url="{{ route('superadmin.practice-quizzes.show-all', $quiz->id) }}"
                                                title="View Details">
                                                👁
                                            </button>
                                        @if($quiz->complete_status == 'completed')
                                            
                                        @else
                                        <a class="icon-btn" href="{{ route('superadmin.quizzes.edit', $quiz) }}" title="Edit">✎</a>
                                        <form method="POST" action="{{ route('superadmin.quizzes.destroy', $quiz) }}">
                                            @csrf @method('DELETE')
                                            <button class="icon-btn danger" type="submit" onclick="return confirm('Are you sure you want to delete this quiz?');" title="Delete">🗑</button>
                                        </form>
                                        @endif   
                                    </div>
                                </td>
                            </tr>

                            {{-- ***********result data show here********** --}}
                            {{-- 👇 Stats Detail Row (Hidden by default) --}}
                         
                            <tr class="stats-detail-row" id="stats-{{ $quiz->id }}" style="display:none;">
                                <td colspan="10">
                                    @if($quiz->quizStatus)
                                        <div class="stats-panel">
                                            
                                            {{-- Header --}}
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                                <h4 style="margin:0; font-size:15px; font-weight:700; color:#1e293b;">
                                                    📈 Quiz Performance Analytics
                                                </h4>
                                                {{-- <span style="font-size:11px; color:#64748b; font-weight:500;">
                                                    🕒 Updated: {{ $quiz->quizStatus->updated_at ? $quiz->quizStatus->updated_at->diffForHumans() : 'N/A' }}
                                                </span> --}}
                                            </div>

                                            {{-- Section 1: User Engagement --}}
                                            <div class="stats-section-title">👥 User Engagement</div>
                                            <div class="stats-grid quiz-performance-card-grid">

                                                 {{-- Exited (Played - Completed) --}}
                                              

                                                <div class="stat-card green">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">🎫</div>

                                                        <div class="stat-label">Player Limit</div>
                                                    </div>
                                                    <div class="stat-value">
                                                       {{ number_format($quiz->no_of_players ?? 0) }}
                                                    </div>
                                                   
                                                </div>
                                                
                                                {{-- Total Enrolled --}}
                                                <div class="stat-card blue">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">👥</div>
                                                        <div class="stat-label">Joined</div>
                                                    </div>
                                                    <div class="stat-value">
                                                        {{ number_format($quiz->quizStatus->total_enroll_user ?? 0) }}
                                                    </div>
                                                    <div class="stat-meta">Total registrations</div>
                                                </div>

                                                {{-- Total Played --}}
                                                <div class="stat-card yellow">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">🎮</div>
                                                        <div class="stat-label">Played</div>
                                                    </div>
                                                    <div class="stat-value">
                                                        {{ number_format($quiz->quizStatus->total_played_user ?? 0) }}
                                                    </div>
                                                    @if(($quiz->quizStatus->total_enroll_user ?? 0) > 0)
                                                        <div class="stat-meta">
                                                            {{ round((($quiz->quizStatus->total_played_user ?? 0) / $quiz->quizStatus->total_enroll_user) * 100, 1) }}% participation
                                                        </div>
                                                    @else
                                                        <div class="stat-meta">No participants yet</div>
                                                    @endif
                                                </div>

                                              

                                                {{-- Total Interested --}}
                                                <div class="stat-card pink">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">💖</div>
                                                        <div class="stat-label">Interested</div>
                                                    </div>
                                                    <div class="stat-value">
                                                        {{ number_format($quiz->quizStatus->total_intrested_user ?? 0) }}
                                                    </div>
                                                    <div class="stat-meta">Wishlist additions</div>
                                                </div>

                                            </div>

                                            {{-- Section 2: Ad Analytics --}}
                                            <div class="stats-section-title">📊 Ad Analytics</div>
                                            <div class="stats-grid quiz-performance-card-grid">
                                                
                                                {{-- Total Impressions --}}
                                                <div class="stat-card purple">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">👁</div>
                                                        <div class="stat-label">Impressions</div>
                                                    </div>
                                                    <div class="stat-value">
                                                        100
                                                    </div>
                                                    <div class="stat-meta">Total views on listing</div>
                                                </div>

                                                {{-- Total Views --}}
                                                <div class="stat-card blue">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">📖</div>
                                                        <div class="stat-label">Detail Views</div>
                                                    </div>
                                                    <div class="stat-value">
                                                      {{ $quiz->ad_view_history_count ?? 0 }}
                                                    </div>
                                                    <div class="stat-meta">Quiz detail page visits</div>
                                                </div>

                                                {{-- Total Clicks --}}
                                                <div class="stat-card orange">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">🖱</div>
                                                        <div class="stat-label">Start Clicks</div>
                                                    </div>
                                                    <div class="stat-value">
                                                         {{ $quiz->ad_click_history_count ?? 0 }}
                                                    </div>
                                                 
                                                        <div class="stat-meta">No clicks yet</div>
                                                
                                                </div>

                                                {{-- Average Score --}}
                                                <div class="stat-card green">
                                                    <div class="stat-card-header">
                                                        <div class="stat-icon">🎯</div>
                                                        <div class="stat-label">Avg Score</div>
                                                    </div>
                                                    <div class="stat-value">
                                                       
                                                        80%
                                                    </div>
                                                    <div class="stat-meta">Average accuracy</div>
                                                </div>

                                            </div>

                                           

                                        </div>
                                    @else
                                        <div style="padding:40px; text-align:center; color:#64748b;">
                                            <div style="font-size:48px; margin-bottom:12px;">📊</div>
                                            <p style="margin:0; font-size:14px; font-weight:600;">No statistics available yet</p>
                                            <p style="margin:8px 0 0; font-size:12px;">Stats will appear once users start interacting with this quiz.</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            {{-- ***********result data show end********** --}}
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:var(--ink-faint);">No quizzes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- <div class="pagination"><span>Showing {{ $quizzes->firstItem() }}–{{ $quizzes->lastItem() }} of
                    {{ $quizzes->total() }} quizzes</span>{{ $quizzes->links() }}
            </div> --}}

             <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $quizzes])
            </div>        
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="quizViewModal" class="quiz-modal" style="display: none;">
        <div class="quiz-modal-backdrop" onclick="closeQuizModal()"></div>
        <div class="quiz-modal-dialog">
            <div class="quiz-modal-header">
                <h3 id="modalQuizTitle">Quiz Details</h3>
                <button type="button" class="quiz-modal-close" onclick="closeQuizModal()">&times;</button>
            </div>
            
            <div class="quiz-modal-tabs quiz-modal-tabs-02">
                <button type="button" class="quiz-modal-tab-btn active" data-tab="modal-quiz-details">Quiz Details</button>
                <button type="button" class="quiz-modal-tab-btn" data-tab="modal-questions">Selected Questions</button>
                {{-- <button type="button" class="quiz-modal-tab-btn" data-tab="modal-rewards">Rank Rewards</button>
                <button type="button" class="quiz-modal-tab-btn" data-tab="modal-filter">Quiz Filter</button> --}}
            </div>

            <div class="quiz-modal-body quiz-modal-body-02">
                <!-- Tab 1: Quiz Details -->
                <div class="quiz-modal-tab-pane active" id="modal-quiz-details">
                    <div class="detail-grid practice-detail-grid">
                        <div class="detail-item"><span class="detail-label">Title:</span> <span id="v_title" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Category:</span> <span id="v_category" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Sub Category:</span> <span id="v_sub_category" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Level:</span> <span id="v_level" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Quiz Type:</span> <span id="v_type" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Visibility:</span> <span id="v_visibility" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Certificate on Pass:</span> <span id="v_certificate" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Question Limit:</span> <span id="v_question_limit" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Time / Question (Sec):</span> <span id="v_per_que_time" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Max Players:</span> <span id="v_no_of_players" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Negative Marking:</span> <span id="v_negative_marking" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Status:</span> <span id="v_status" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Schedule Date & Time:</span> <span id="v_scheduled_at" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Before Time Entry (Min):</span> <span id="v_before_minute" class="detail-val"></span></div>
                         <div class="detail-item"><span class="detail-label">Availability Start:</span> <span id="v_availability_start" class="detail-val"></span></div>
                          <div class="detail-item"><span class="detail-label">Availability End:</span> <span id="v_availability_end" class="detail-val"></span></div>
                    </div>
                </div>

                <!-- Tab 2: Questions -->
                <div class="quiz-modal-tab-pane" id="modal-questions">
                    <div id="v_questions_container" class="quiz-modal-table-box">
                        <table class="modal-table quiz-modal-table-wrapper">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Question Text</th>
                                    <th>Options</th>
                                    <th>Correct Answer</th>
                                    <th>Order</th>
                                    <th>Bot Correct</th>
                                </tr>
                            </thead>
                            <tbody id="v_questions_list">
                                <!-- Dynamic Questions -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Rewards -->
                {{-- <div class="quiz-modal-tab-pane" id="modal-rewards">
                    <div id="v_rewards_container" class="quiz-reward-table-box">
                        <table class="modal-table quiz-reward-table-wrapper">
                            <thead>
                                <tr>
                                    <th>Rank Range</th>
                                    <th>Reward Type</th>
                                    <th>Reward Value</th>
                                </tr>
                            </thead>
                            <tbody id="v_rewards_list">
                                <!-- Dynamic Rewards -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 4: Filter -->
                <div class="quiz-modal-tab-pane" id="modal-filter">
                    <div class="detail-grid">
                        <div class="detail-item"><span class="detail-label">Campaign:</span> <span id="v_filter_ads_campaign" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">State:</span> <span id="v_filter_state" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">City:</span> <span id="v_filter_city" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Area:</span> <span id="v_filter_area" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Age Range:</span> <span id="v_filter_age" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Gender:</span> <span id="v_filter_gender" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Hobby:</span> <span id="v_filter_hobby" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Filter Status:</span> <span id="v_filter_status" class="detail-val"></span></div>
                    </div>
                </div> --}}
            </div>

            <div class="quiz-modal-footer">
                <button class="btn btn-ghost" type="button" onclick="closeQuizModal()">Close</button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Modal Styles */
    .quiz-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quiz-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(16, 19, 43, 0.6);
        backdrop-filter: blur(4px);
    }

    .quiz-modal-dialog {
        position: relative;
        background: #ffffff;
        width: 90%;
        max-width: 850px;
        max-height: 85vh;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        z-index: 1051;
        overflow: hidden;
        animation: modalFadeIn 0.25s ease-out;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .quiz-modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid var(--line);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #FBFAFF;
    }

    .quiz-modal-header h3 {
        font-family: var(--font-display);
        font-size: 17px;
        font-weight: 700;
        color: var(--ink);
    }

    .quiz-modal-close {
        background: transparent;
        border: none;
        font-size: 24px;
        color: var(--ink-faint);
        cursor: pointer;
        line-height: 1;
    }

    .quiz-modal-tabs {
        display: flex;
        background: var(--paper);
        border-bottom: 1px solid var(--line);
        padding: 0 16px;
    }

    .quiz-modal-tab-btn {
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 600;
        color: var(--ink-soft);
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .quiz-modal-tab-btn:hover {
        color: var(--indigo);
    }

    .quiz-modal-tab-btn.active {
        color: var(--indigo);
        border-bottom-color: var(--indigo);
        background: #fff;
    }

    .quiz-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .quiz-modal-tab-pane {
        display: none;
    }

    .quiz-modal-tab-pane.active {
        display: block;
        animation: paneFade 0.2s ease-in;
    }

    @keyframes paneFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
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

    .detail-val {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
    }

    .modal-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modal-table th {
        font-size: 11px;
        font-weight: 700;
        color: var(--ink-faint);
        text-transform: uppercase;
        padding: 10px 12px;
        background: #F9FAFB;
        border-bottom: 1px solid var(--line);
        text-align: left;
    }

    .modal-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--line);
        font-size: 12.5px;
    }

    .quiz-modal-footer {
        padding: 14px 24px;
        border-top: 1px solid var(--line);
        display: flex;
        justify-content: flex-end;
        background: #FBFAFF;
    }

    @media(max-width:575px){
        .detail-grid {
            gap: 10px 10px;
        }
    }
</style>
@endpush

@push('scripts')
    <script>
        // Modal Tab Switching
        document.querySelectorAll('.quiz-modal-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.quiz-modal-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.quiz-modal-tab-pane').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                const targetTab = this.dataset.tab;
                document.getElementById(targetTab).classList.add('active');
            });
        });

        function closeQuizModal() {
            document.getElementById('quizViewModal').style.display = 'none';
        }

        // Click on View Button
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-quiz');
            if (!btn) return;

            const quizId = btn.dataset.id;
            const url = btn.dataset.url;
            
            // Show loading or open modal
            const modal = document.getElementById('quizViewModal');
            modal.style.display = 'flex';

            // Reset tabs
            document.querySelectorAll('.quiz-modal-tab-btn').forEach((b, idx) => {
                b.classList.toggle('active', idx === 0);
            });
            document.querySelectorAll('.quiz-modal-tab-pane').forEach((p, idx) => {
                p.classList.toggle('active', idx === 0);
            });

            // Fetch Quiz Details
           
            fetch(url)
                .then(res => res.json())
                .then(res => {
                    if (!res.success) return;
                    const q = res.data;

                    document.getElementById('modalQuizTitle').innerText = q.title || 'Quiz Details';
                    
                    // Tab 1: Basic Details
                    document.getElementById('v_title').innerText = q.title || '—';
                    document.getElementById('v_category').innerText = q.category ? q.category.name : '—';
                    document.getElementById('v_sub_category').innerText = q.sub_category ? q.sub_category.name : '—';
                    document.getElementById('v_level').innerText = q.level ? q.level.name : '—';
                    document.getElementById('v_type').innerText = q.quiz_type ? q.quiz_type.name : '—';
                    document.getElementById('v_visibility').innerText = q.visibility ? q.visibility.toUpperCase() : '—';
                    document.getElementById('v_certificate').innerText = q.certificate_on_pass ? 'Yes' : 'No';
                    document.getElementById('v_question_limit').innerText = q.question_limit ?? '10';
                    document.getElementById('v_per_que_time').innerText = q.per_que_time_limit_sec ?? '0';
                    document.getElementById('v_no_of_players').innerText = q.no_of_players ?? '0';
                    document.getElementById('v_negative_marking').innerText = q.negative_marking ? 'Yes' : 'No';
                    document.getElementById('v_status').innerText = q.status ? q.status.toUpperCase() : '—';
                    document.getElementById('v_scheduled_at').innerText = q.scheduled_at ? new Date(q.scheduled_at).toLocaleString() : '—';
                    document.getElementById('v_before_minute').innerText = q.before_minute_entry ?? '0';
                    document.getElementById('v_availability_start').innerText = q.availability_start ? new Date(q.availability_start).toLocaleString() : '—';
                    document.getElementById('v_availability_end').innerText = q.availability_end ? new Date(q.availability_end).toLocaleString() : '—';

                    // Tab 2: Questions
                    const qTbody = document.getElementById('v_questions_list');
                    qTbody.innerHTML = '';
                    if (q.questions && q.questions.length > 0) {
                        q.questions.forEach((item, index) => {
                            let optionsFormatted = '—';
                            if (item.options) {
                                let opts = item.options;
                                if (typeof opts === 'string') {
                                    try { opts = JSON.parse(opts); } catch(e){}
                                }
                                if (Array.isArray(opts)) {
                                    optionsFormatted = '<ol style="margin-left: 16px; padding-left: 0; font-size: 11.5px;">' + 
                                        opts.map(o => `<li>${o}</li>`).join('') + 
                                        '</ol>';
                                } else if (typeof opts === 'object') {
                                    optionsFormatted = '<ul style="list-style: none; padding-left: 0; font-size: 11.5px;">' + 
                                        Object.entries(opts).map(([k, v]) => `<li><b>${k}:</b> ${v}</li>`).join('') + 
                                        '</ul>';
                                }
                            }

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td data-label="#">${index + 1}</td>
                                <td data-label="Question" style="font-weight:600;">
                                    <div class="question-text">
                                         ${item.question_text || '—'}
                                    </div>
                                </td>
                                <td data-label="Options">
                                    <div class="option-text">
                                        ${optionsFormatted}
                                    </div>
                                </td>
                                <td data-label="Correct"><span class="badge success" style="text-transform: none;">${item.correct_answer || '—'}</span></td>
                                <td data-label="Order">${item.pivot ? (item.pivot.order ?? '—') : '—'}</td>
                                <td data-label="Bot Correct">${item.pivot && item.pivot.bot_correct_answer ? item.pivot.bot_correct_answer : '—'}</td>
                            `;
                            qTbody.appendChild(tr);
                        });
                    } else {
                        qTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--ink-faint);">No questions selected yet.</td></tr>';
                    }

                    // Tab 3: Rewards
                    // const rTbody = document.getElementById('v_rewards_list');
                    // rTbody.innerHTML = '';
                    // if (q.rank_rewards && q.rank_rewards.length > 0) {
                    //     q.rank_rewards.forEach(r => {
                    //         let valStr = '—';
                    //         if (r.type === 'point') {
                    //             valStr = (r.reward ?? '0') + ' Points';
                    //         } else if (r.type === 'voucher') {
                    //             if (r.voucher && r.voucher.voucher_histories?.length) {
                    //                 const codes = r.voucher.voucher_histories.map(v => v.code).join(', ');
                    //                 valStr = `${codes}`;
                    //             } else {
                    //                 valStr = 'Voucher ID: ' + (r.voucher_id ?? '—');
                    //             }
                    //         }
                    //         const tr = document.createElement('tr');
                    //         tr.innerHTML = `
                    //             <td data-label="Rank Range">
                    //                 ${r.start_rank == r.end_rank
                    //                     ? `Rank ${r.start_rank}`
                    //                     : `Rank ${r.start_rank} - ${r.end_rank || '—'}`}
                    //             </td>
                    //             <td data-label="Reward Type"><span class="badge ${r.type === 'point' ? 'info' : 'warn'}">${r.type ? r.type.toUpperCase() : '—'}</span></td>
                    //             <td data-label="Reward Value">${valStr}</td>
                    //         `;
                    //         rTbody.appendChild(tr);
                    //     });
                    // } else {
                    //     rTbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--ink-faint);">No rank rewards configured.</td></tr>';
                    // }

                    // // Tab 4: Filter
                    // const f = q.filter;
                    // if (f) {
                    //     document.getElementById('v_filter_state').innerText = f.state ? (f.state.name || f.state) : '—';
                    //     document.getElementById('v_filter_ads_campaign').innerText = f.ads_campaign ? (f.ads_campaign.campaign_name || '—') : '—';
                    //     document.getElementById('v_filter_city').innerText = f.city ? (f.city.name || f.city) : '—';
                    //     document.getElementById('v_filter_area').innerText = f.area || '—';
                    //     document.getElementById('v_filter_age').innerText = f.age_range || '—';
                    //     document.getElementById('v_filter_gender').innerText = f.gender ? f.gender.toUpperCase() : '—';
                    //     document.getElementById('v_filter_hobby').innerText = f.hobby || '—';
                    //     document.getElementById('v_filter_status').innerText = f.status ? f.status.toUpperCase() : '—';
                    // } else {
                    //     document.getElementById('v_filter_state').innerText = '—';
                    //     document.getElementById('v_filter_ads_campaign').innerText = '—';
                    //     document.getElementById('v_filter_city').innerText = '—';
                    //     document.getElementById('v_filter_area').innerText = '—';
                    //     document.getElementById('v_filter_age').innerText = '—';
                    //     document.getElementById('v_filter_gender').innerText = '—';
                    //     document.getElementById('v_filter_hobby').innerText = '—';
                    //     document.getElementById('v_filter_status').innerText = '—';
                    // }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', 'Error', 'Failed to fetch quiz details.');
                });
        });

        // Existing status change JS
        document.addEventListener('change', function(e) {

            if (!e.target.classList.contains('quiz-status-change')) {
                return;
            }

            fetch("{{ route('superadmin.quizzes.changeStatus') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: e.target.dataset.id,
                        status: e.target.value
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if (data.success) {

                        showAlert(
                            'success',
                            'Success',
                            'Quiz status updated successfully.'
                        );

                    } else {

                        showAlert(
                            'warning',
                            'Warning',
                            'Unable to update quiz status.'
                        );

                    }

                })
                .catch(() => {

                    showAlert(
                        'error',
                        'Error',
                        'Something went wrong.'
                    );

                });

        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script>
    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('approve-status-change')) {
            return;
        }

        const id = e.target.dataset.id;
        const status = e.target.value;
        const selectElement = e.target;
     

        // Agar galti se Pending select ho jaye to kuch na kare
        if (status === 'pending') {
            return; 
        }

        if (status === 'rejected') {
            // Reject ke liye Note zaroori hai
            Swal.fire({
                title: 'Reject Quiz',
                input: 'textarea',
                inputLabel: 'Enter a reason/note for rejection (required)',
                inputPlaceholder: 'Type your reason here...',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Rejection note cannot be empty!';
                    }
                },
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc3545', // Red color
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateApproveStatus(id, status, result.value, selectElement);
                } else {
                    selectElement.value = 'pending'; // Cancel par wapas pending par set karein
                }
            });
        } else {
            // Approve ya Cancelled ke liye (Note optional hai)
            let modalConfig = {
                title: status === 'approved' ? 'Approve Quiz' : 'Cancel Quiz',
                input: 'textarea',
                inputLabel: `Enter a note for ${status === 'approved' ? 'approval' : 'cancelled'} (optional)`,
                inputPlaceholder: 'Type your note here...',
                showCancelButton: true,
                confirmButtonText: status === 'approved' ? 'Approve' : 'Cancel Quiz',
                confirmButtonColor: status === 'approved' ? '#28a745' : '#ffc107', // Green for Approve, Yellow for Cancel
                cancelButtonColor: '#6c757d'
            };

            Swal.fire(modalConfig).then((result) => {
                if (result.isConfirmed) {
                    updateApproveStatus(id, status, result.value, selectElement);
                } else {
                    selectElement.value = 'pending'; // Cancel par wapas pending par set karein
                }
            });
        }
    });

    // Reusable Function for Fetch Request
    function updateApproveStatus(id, status, note, selectElement) {
        fetch("{{ route('superadmin.approve.quizzes.changeStatus') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                id: id,
                approve_status: status,
                note: note
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Success', 'Quiz status updated successfully.');
                // Page reload taake dropdown hide ho jaye aur final badge show ho jaye
                setTimeout(() => location.reload(), 500); 
            } else {
                showAlert('warning', 'Warning', data.message || 'Unable to update status.');
                selectElement.value = 'pending'; // Error aaye to wapas pending par
            }
        })
        .catch(() => {
            showAlert('error', 'Error', 'Something went wrong.');
            selectElement.value = 'pending'; // Error aaye to wapas pending par
        });
    }
</script>
<script>
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-allocate-times');
        if (!btn) return;

        const quizId = btn.dataset.id;
        const originalText = btn.innerHTML;
        
        // Button disable karein loading state ke saath
        btn.disabled = true;
        btn.innerHTML = '⏳ Allocating...';

        const allocateUrl = "{{ route('superadmin.quizzes.allocateTimes', ['quiz' => '__ID__']) }}".replace('__ID__', quizId);

        fetch(allocateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(
                    'success', 
                    'Success', 
                    `${data.message} Total Questions: ${data.data.total_questions}, Duration: ${data.data.total_duration}`
                );
                
                // Page reload taake updated times dikhein
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('warning', 'Warning', data.message || 'Unable to allocate times.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(() => {
            showAlert('error', 'Error', 'Something went wrong.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Stats toggle script loaded');
    
    // Event delegation for stats toggle
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-stats-toggle');
        if (!btn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const quizId = btn.dataset.quizId;
        const statsRow = document.getElementById('stats-' + quizId);
        
        console.log('🎯 Clicked quiz:', quizId, '| Row found:', statsRow ? 'YES' : 'NO');
        
        if (!statsRow) {
            console.error('❌ Stats row not found for quiz:', quizId);
            return;
        }
        
        // Close all other open rows
        document.querySelectorAll('.stats-detail-row').forEach(row => {
            if (row !== statsRow && row.style.display !== 'none') {
                row.style.display = 'none';
            }
        });
        
        // Reset all other buttons
        document.querySelectorAll('.btn-stats-toggle').forEach(b => {
            if (b !== btn) {
                b.classList.remove('active');
            }
        });
        
        // Toggle current row
        const isVisible = statsRow.style.display === 'table-row';
        
        if (isVisible) {
            statsRow.style.display = 'none';
            btn.classList.remove('active');
        } else {
            statsRow.style.display = 'table-row';
            btn.classList.add('active');
            
            // Smooth scroll to stats
            setTimeout(() => {
                statsRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    });
    
    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.btn-stats-toggle') && 
            !e.target.closest('.stats-detail-row') &&
            !e.target.closest('.stats-panel')) {
            
            document.querySelectorAll('.stats-detail-row').forEach(row => {
                if (row.style.display !== 'none') {
                    row.style.display = 'none';
                }
            });
            document.querySelectorAll('.btn-stats-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
        }
    });
});
</script>


{{-- ***********Test use ony entyr whatsup************ --}}
<!-- Button -->

<script>
function createWhatsAppEntries() {
    const btn = document.getElementById('prepWaBtn');
    const statusBox = document.getElementById('prepWaStatus');
    
    // Loading state
    btn.disabled = true;
    btn.innerHTML = '⏳ Processing Entries... Please wait';
    statusBox.style.display = 'none';

    fetch("{{ route('superadmin.prepare.whatsapp.entries') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span>⚙️</span> Prepare WhatsApp Entries (Next Quiz - 15 Min)';
        statusBox.style.display = 'block';

        if (data.success) {
            statusBox.style.background = '#dcfce7';
            statusBox.style.color = '#166534';
            statusBox.style.border = '1px solid #86efac';
            statusBox.innerHTML = `
                <strong>✅ Entries Successfully Created!</strong><br>
                📌 Quiz: <strong>${data.data.quiz_name}</strong><br>
                ⏰ Quiz Time: ${data.data.quiz_time}<br>
                ⏳ Notification Time: <strong>${data.data.activation_time}</strong> (15 mins before)<br>
                👥 Total Users Added: <strong>${data.data.total_entries}</strong>
            `;
        } else {
            statusBox.style.background = '#fef3c7';
            statusBox.style.color = '#92400e';
            statusBox.style.border = '1px solid #fcd34d';
            statusBox.innerHTML = `<strong>⚠️ Notice:</strong> ${data.message}`;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<span>⚙️</span> Prepare WhatsApp Entries (Next Quiz - 15 Min)';
        statusBox.style.display = 'block';
        statusBox.style.background = '#fee2e2';
        statusBox.style.color = '#991b1b';
        statusBox.style.border = '1px solid #fca5a5';
        statusBox.innerHTML = `<strong>❌ Error:</strong> Something went wrong. Check console.`;
        console.error(error);
    });
}
</script>
{{-- ***********Test use ony entyr whatsup************ --}}

@endpush

