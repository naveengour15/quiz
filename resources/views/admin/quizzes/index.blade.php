@extends('admin.layouts.app')
@section('title', 'Quizzes')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@push('styles')
    <style>
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
        .approve-status-change{
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

            .approve-status-change:focus{
                border-color: #4f46e5;
                box-shadow: 0 0 0 2px rgba(79,70,229,.15);
            }

            .question-box{
                max-width: 250px;
                font-weight: 600;
                word-wrap: break-word;
            }

            @media(max-width:767px){
                 .question-box{
                    text-align: end;
                }
            }
    </style>
@endpush

@section('content')
    <div class="panel active">
        <div class="page-head" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Quizzes</h1>
                <p>Assemble quizzes from the question bank, configure access rules, and control draft/publish/schedule state.</p>
            </div>
            <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary" style="white-space:nowrap;"><i class="fas fa-plus"></i> Create Quiz</a>
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
                            <th>Quiz</th>
                            <th>Shedule Time</th>
                            <th>Category</th>
                            <th>Level</th>
                            <th>Quiz Type</th>
                            <th>Questions</th>
                            <th>Visibility</th>
                            <th>Quiz Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quizzes as $quiz)
                            <tr>
                                <td data-label="Quiz">{{ $quiz->title }}</td>
                                
                                {{-- ****Shedule time show start***** --}}
                                 @include('admin.quizzes.component.quiz-timer')
                                

                                {{-- ****Shedule time show end***** --}}
                                <td data-label="Category">{{ $quiz->category->name ?? '—' }}</td>
                                <td data-label="Level">{{ $quiz->level->name ?? '—' }}</td>
                                <td data-label="Quiz Type">
                                    {{ $quiz->quizType->name ?? '—' }}
                                </td>
                                <td data-label="Questions" class="mono">
                                    <div class="div">
                                    {{ $quiz->questions_count }}
                                    </div>
                                </td>
                                <td data-label="Visibility">
                                    @if ($quiz->visibility === 'public')
                                        <span class="badge info">Public</span>

                                    @elseif ($quiz->visibility === 'private')
                                        <span class="badge warn">Private · 🔒</span>

                                    @elseif ($quiz->visibility === 'leaderboard')
                                        <span class="badge success">Leaderboard 🏆</span>

                                    @elseif ($quiz->visibility === 'practice')
                                        <span class="badge primary">Practice 📝</span>

                                    @elseif ($quiz->visibility === 'mahaquiz')
                                        <span class="badge danger">Mahaquiz 🎯</span>

                                    @endif
                                </td>
                             
                                <td>
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
                                        <button class="icon-btn btn-view-quiz" data-id="{{ $quiz->id }}" title="View Details">👁</button>
                                        @if($quiz->complete_status != 'completed')
                                            <a class="icon-btn" href="{{ route('admin.quizzes.edit', $quiz) }}" title="Edit">✎</a>
                                            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}">
                                                @csrf @method('DELETE')
                                                <button class="icon-btn danger" type="submit" onclick="return confirm('Are you sure you want to delete this quiz?');" title="Delete">🗑</button>
                                            </form>
                                        @endif   
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:var(--ink-faint);">No quizzes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                <button type="button" class="quiz-modal-tab-btn" data-tab="modal-rewards">Rank Rewards</button>
                <button type="button" class="quiz-modal-tab-btn" data-tab="modal-filter">Quiz Filter</button>
            </div>

            <div class="quiz-modal-body quiz-modal-body-02">
                <div class="quiz-modal-tab-pane active" id="modal-quiz-details">
                    <div class="detail-grid">
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
                    </div>
                </div>

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
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="quiz-modal-tab-pane" id="modal-rewards">
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
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="quiz-modal-tab-pane" id="modal-filter">
                    <div class="detail-grid">
                        <div class="detail-item"><span class="detail-label">State:</span> <span id="v_filter_state" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">City:</span> <span id="v_filter_city" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Area:</span> <span id="v_filter_area" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Age Range:</span> <span id="v_filter_age" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Gender:</span> <span id="v_filter_gender" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Hobby:</span> <span id="v_filter_hobby" class="detail-val"></span></div>
                        <div class="detail-item"><span class="detail-label">Filter Status:</span> <span id="v_filter_status" class="detail-val"></span></div>
                    </div>
                </div>
            </div>

            <div class="quiz-modal-footer">
                <button class="btn btn-ghost" type="button" onclick="closeQuizModal()">Close</button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
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
        gap: 16px 16px;
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
</style>
@endpush

@push('scripts')
    <script>
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

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-quiz');
            if (!btn) return;

            const quizId = btn.dataset.id;
            
            const modal = document.getElementById('quizViewModal');
            modal.style.display = 'flex';

            document.querySelectorAll('.quiz-modal-tab-btn').forEach((b, idx) => {
                b.classList.toggle('active', idx === 0);
            });
            document.querySelectorAll('.quiz-modal-tab-pane').forEach((p, idx) => {
                p.classList.toggle('active', idx === 0);
            });

            fetch(`/admin/quizzes/${quizId}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success) return;
                    const q = res.data;

                    document.getElementById('modalQuizTitle').innerText = q.title || 'Quiz Details';
                    
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
                                <td data-label="Question">
                                    <div class="question-box">
                                         ${item.question_text || '—'}
                                    </div>
                                </td>
                                <td data-label="Options">${optionsFormatted}</td>
                                <td data-label="Correct"><span class="badge success" style="text-transform: none;">${item.correct_answer || '—'}</span></td>
                                <td data-label="Order">${item.pivot ? (item.pivot.order ?? '—') : '—'}</td>
                                <td data-label="Bot Correct">${item.pivot && item.pivot.bot_correct_answer ? item.pivot.bot_correct_answer : '—'}</td>
                            `;
                            qTbody.appendChild(tr);
                        });
                    } else {
                        qTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--ink-faint);">No questions selected yet.</td></tr>';
                    }

                    const rTbody = document.getElementById('v_rewards_list');
                    rTbody.innerHTML = '';
                    if (q.rank_rewards && q.rank_rewards.length > 0) {
                        q.rank_rewards.forEach(r => {
                            let valStr = '—';
                            if (r.type === 'point') {
                                valStr = (r.reward ?? '0') + ' Points';
                            } else if (r.type === 'coupon') {
                                valStr = r.coupon ? (r.coupon.code + ' (' + r.coupon.title + ')') : 'Coupon ID: ' + (r.coupon_id ?? '—');
                            }
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td data-label="Rank Range">Rank ${r.rank_range || '—'}</td>
                                <td data-label="Reward Type"><span class="badge ${r.type === 'point' ? 'info' : 'warn'}">${r.type ? r.type.toUpperCase() : '—'}</span></td>
                                <td data-label="Reward Value">${valStr}</td>
                            `;
                            rTbody.appendChild(tr);
                        });
                    } else {
                        rTbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--ink-faint);">No rank rewards configured.</td></tr>';
                    }

                    const f = q.filter;
                    if (f) {
                        document.getElementById('v_filter_state').innerText = f.state ? (f.state.name || f.state) : '—';
                        document.getElementById('v_filter_city').innerText = f.city ? (f.city.name || f.city) : '—';
                        document.getElementById('v_filter_area').innerText = f.area || '—';
                        document.getElementById('v_filter_age').innerText = f.age_range || '—';
                        document.getElementById('v_filter_gender').innerText = f.gender ? f.gender.toUpperCase() : '—';
                        document.getElementById('v_filter_hobby').innerText = f.hobby || '—';
                        document.getElementById('v_filter_status').innerText = f.status ? f.status.toUpperCase() : '—';
                    } else {
                        document.getElementById('v_filter_state').innerText = '—';
                        document.getElementById('v_filter_city').innerText = '—';
                        document.getElementById('v_filter_area').innerText = '—';
                        document.getElementById('v_filter_age').innerText = '—';
                        document.getElementById('v_filter_gender').innerText = '—';
                        document.getElementById('v_filter_hobby').innerText = '—';
                        document.getElementById('v_filter_status').innerText = '—';
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', 'Error', 'Failed to fetch quiz details.');
                });
        });

        document.addEventListener('change', function(e) {
            if (!e.target.classList.contains('quiz-status-change')) return;

            fetch("{{ route('admin.quizzes.changeStatus') }}", {
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
                    showAlert('success', 'Success', 'Quiz status updated successfully.');
                } else {
                    showAlert('warning', 'Warning', 'Unable to update quiz status.');
                }
            })
            .catch(() => {
                showAlert('error', 'Error', 'Something went wrong.');
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
            fetch("{{ route('admin.approve.quizzes.changeStatus') }}", {
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
@endpush
