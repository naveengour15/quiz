@extends('superadmin.layouts.app')
@section('title', 'Joined Players - ' . $practiceQuiz->title)

@section('content')
<style>
    /* Modal Styles */
    .quiz-modal {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        z-index: 1050; display: none; align-items: center; justify-content: center;
    }
    .quiz-modal-backdrop {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(16, 19, 43, 0.6); backdrop-filter: blur(4px);
    }
    .quiz-modal-dialog {
        position: relative; background: #ffffff; width: 90%; max-width: 900px;
        max-height: 85vh; border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        display: flex; flex-direction: column; z-index: 1051; overflow: hidden;
        animation: modalFadeIn 0.25s ease-out;
    }
    @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    
    .quiz-modal-header {
        padding: 16px 24px; border-bottom: 1px solid #e5e7eb;
        display: flex; justify-content: space-between; align-items: center; background: #FBFAFF;
    }
    .quiz-modal-header h3 { font-size: 17px; font-weight: 700; color: #111827; margin: 0; }
    .quiz-modal-close { background: transparent; border: none; font-size: 24px; color: #9ca3af; cursor: pointer; }
    .quiz-modal-body { padding: 0; overflow-y: auto; flex: 1; }
    .quiz-modal-footer { padding: 14px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; background: #FBFAFF; }

    .modal-table { border-collapse: collapse; width: 100%; }
    .modal-table th { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; background: #F9FAFB; position: sticky; top: 0; z-index: 10; }
    .modal-table td { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: top; }
    
    .badge.success { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge.danger { background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }

    .btn-icon-action {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; font-size: 16px;
        color: #4F46E5; background: #EEF2FF; border: 1px solid #C7D2FE;
        border-radius: 8px; cursor: pointer; transition: all 0.2s;
    }
    .btn-icon-action:hover { background: #4F46E5; color: white; border-color: #4F46E5; transform: scale(1.05); }
</style>

<div class="panel active">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 20px; font-weight: 800; color: #0f172a;">👥 Joined Players</h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
                Practice Quiz: <strong>{{ $practiceQuiz->title }}</strong> | 
                Total Players: <strong style="color: #4f46e5; font-size: 16px;">{{ $joinedUsers->total() }}</strong>
            </p>
        </div>
        <a href="{{ route('superadmin.practice-quizzes.index') }}" 
           style="padding: 10px 20px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid #e2e8f0;">
            ← Back to Quizzes
        </a>
    </div>

    <!-- ✅ Search & Filter Box -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 250px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 6px;">Search Player</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or mobile..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Search</button>
                <a href="{{ route('superadmin.practice-quiz.joined-users', $practiceQuiz->id) }}" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; font-weight: 600;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 60px;">S.No</th>
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Player Details</th>
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 140px;">Joined Date</th>
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 100px; text-align: center;">Attempts</th>
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 100px; text-align: center;">Correct</th>
                    <th style="padding: 14px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 80px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($joinedUsers as $index => $record)
                    @php
                        $userName = $record->user?->name ?? 'Deleted User';
                        $profileImage = !empty($record->user->profile_photo) 
                            ? \Illuminate\Support\Facades\Storage::disk('s3')->url($record->user->profile_photo) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? 'User') . '&background=6366f1&color=fff';
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 16px; font-size: 14px; color: #64748b; font-weight: 600;">
                            {{ ($joinedUsers->currentPage() - 1) * $joinedUsers->perPage() + $index + 1 }}
                        </td>
                        
                         {{-- ✅ Player Details: Image + Name + Mobile + User Created Date --}}
                        <td style="padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="{{ $profileImage }}" alt="User" style="width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 2px solid #f1f5f9;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <b style="font-size: 14px; color: #0f172a;">{{ $userName }}</b>
                                    <span style="font-size: 12px; color: #64748b; font-family: monospace;">{{ $record->user?->mobile ?? 'No Mobile' }}</span>
                                    
                                    {{-- ✅ FIXED LINE: Null-safe operator (?->) lagaya gaya hai --}}
                                    <span style="font-size: 11px; color: #94a3b8;">
                                        Joined App: {{ $record->user ? \Carbon\Carbon::parse($record->user->created_at)->format('d M Y') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td style="padding: 16px; font-size: 13px; color: #64748b;">
                            {{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d M Y, h:i A') : 'N/A' }}
                        </td>

                        <td style="padding: 16px; text-align: center;">
                            <span style="display: inline-block; padding: 4px 12px; background: #f1f5f9; color: #475569; border-radius: 99px; font-size: 13px; font-weight: 700;">
                                {{ $record->total_attempts ?? 0 }}
                            </span>
                        </td>

                        <td style="padding: 16px; text-align: center;">
                            <span style="display: inline-block; padding: 4px 12px; background: #dcfce7; color: #166534; border-radius: 99px; font-size: 13px; font-weight: 700;">
                                {{ $record->correct_attempts ?? 0 }}
                            </span>
                        </td>

                        <td style="padding: 16px; text-align: center;">
                            <button class="btn-icon-action" 
                                    data-quiz="{{ $practiceQuiz->id }}" 
                                    data-user="{{ $record->user_id }}"
                                    data-username="{{ $record->user->name ?? 'User' }}"
                                    title="View Detailed Answers">
                                📝
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 10px;">📭</div>
                            No players found matching your search.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ✅ Pagination (withQueryString se search query retain rahega) -->
    <div class="pagination" style="margin-top: 20px;">
        @include('common.pagination.superadmin_pagination', ['pageData' => $joinedUsers])
    </div>
</div>

<!-- ========================================== -->
<!-- ANSWERS VIEW MODAL                         -->
<!-- ========================================== -->
<div id="answersModal" class="quiz-modal">
    <div class="quiz-modal-backdrop" onclick="closeAnswersModal()"></div>
    <div class="quiz-modal-dialog">
        <div class="quiz-modal-header">
            <h3 id="modalAnswersTitle">User Answers</h3>
            <button type="button" class="quiz-modal-close" onclick="closeAnswersModal()">&times;</button>
        </div>
        
        <div class="quiz-modal-body" style="padding: 0;">
            <table class="modal-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Question</th>
                        <th>Your Answer</th>
                        <th>Correct Answer</th>
                        <th style="width: 90px; text-align: center;">Result</th>
                        <th style="width: 80px;">Time</th>
                    </tr>
                </thead>
                <tbody id="answersTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                            Loading answers...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="quiz-modal-footer">
            <button class="btn-icon-action" style="width: auto; padding: 0 16px; font-size: 13px; font-weight: 600; background: #f1f5f9; color: #475569; border-color: #cbd5e1;" type="button" onclick="closeAnswersModal()">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function closeAnswersModal() {
        document.getElementById('answersModal').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-icon-action');
        if (!btn || !btn.dataset.quiz) return;

        const quizId = btn.dataset.quiz;
        const userId = btn.dataset.user;
        const userName = btn.dataset.username;
        const modal = document.getElementById('answersModal');
        const tbody = document.getElementById('answersTableBody');
        const title = document.getElementById('modalAnswersTitle');

        title.innerText = `📝 Answers: ${userName}`;
        modal.style.display = 'flex';
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">Loading answers...</td></tr>`;

        const fetchUrl = `{{ route('superadmin.practice-quiz.user-answers', ['quiz_id' => ':quiz', 'user_id' => ':user']) }}`
            .replace(':quiz', quizId)
            .replace(':user', userId);

        fetch(fetchUrl)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data || res.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">No answer records found for this user.</td></tr>`;
                    return;
                }

                let html = '';
                res.data.forEach((item, index) => {
                    const isCorrect = item.status == 1;
                    const resultIcon = isCorrect 
                        ? '<span class="badge success">✅ Correct</span>' 
                        : '<span class="badge danger">❌ Incorrect</span>';
                    
                    const rowBg = isCorrect ? 'background: #f0fdf4;' : 'background: #fef2f2;';
                    const userAns = item.user_answer ? item.user_answer : '<span style="color:#ef4444; font-style:italic;">Skipped</span>';
                    const correctAns = item.correct_answer || 'N/A';

                    html += `
                        <tr style="${rowBg}">
                            <td style="font-weight: 600; color: #6b7280;">${index + 1}</td>
                            <td style="font-weight: 600; color: #111827; line-height: 1.4;">${item.question_text}</td>
                            <td style="font-weight: 600; color: ${isCorrect ? '#166534' : '#991b1b'};">${userAns}</td>
                            <td><span class="badge success" style="text-transform: none; background: #dcfce7;">${correctAns}</span></td>
                            <td style="text-align: center;">${resultIcon}</td>
                            <td style="font-family: monospace; font-weight: 600; color: #4f46e5;">${item.total_time || 0}s</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;
            })
            .catch(err => {
                console.error('Error fetching answers:', err);
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">Failed to load answers. Please try again.</td></tr>`;
            });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAnswersModal();
    });
</script>
@endpush