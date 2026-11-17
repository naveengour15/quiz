@extends('admin.layouts.app')

@section('title', 'Joined Players - ' . ($quiz->title ?? 'Quiz'))
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

{{-- deepak css start --}}
<style>
    .quizwinner-search-filter-box {
        width: 100%;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        margin-bottom: 24px;
    }

    .quizwinner-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .quizwinner-filter-btn {
        width: 100%;
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .quizwinner-filter-btn .btn {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .winner-user-box {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge.success {
        background: #dcfce7;
        color: #166534;
    }

    .badge.warn {
        background: #fef3c7;
        color: #92400e;
    }

    .badge.neutral {
        background: #f3f4f6;
        color: #4b5563;
    }

    /* ✅ NEW: Stats Boxes Styling */
    .stats-box-wrapper {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-box {
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid transparent;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .stat-content {
        display: flex;
        flex-direction: column;
    }

    .stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
    }

    /* Joined Box Colors (Blue) */
    .joined-box {
        background: #fff;
        border-color: #bfdbfe;
    }

    .joined-box .stat-icon {
        background: #E0F2FE;
        color: #ffffff;
    }

    .joined-box .stat-label {
        color: #1e40af;
    }

    .joined-box .stat-value {
        color: #1e3a8a;
    }

    /* Played Box Colors (Green) */
    .played-box {
        background: #fff;
        border-color: #10B981;
    }

    .played-box .stat-icon {
        background: #DCFCE7;
        color: #ffffff;
    }

    .played-box .stat-label {
        color: #166534;
    }

    .played-box .stat-value {
        color: #14532d;
    }

    /* Left Box Colors (Red) */
    .left-box {
        background: #fff;
        border-color: #fecaca;
    }

    .left-box .stat-icon {
        background: #FEF3C7;
        color: #ffffff;
    }

    .left-box .stat-label {
        color: #991b1b;
    }

    .left-box .stat-value {
        color: #7f1d1d;
    }

    .joined-user-search-filter-box {
        width: 100%;
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .joined-user-search-filter-wrapper {
        width: 100%;
        display: grid;
        grid-template-columns: repeat(1, 1fr);
    }

    .joined-user-filter-btn {
        width: 100%;
        display: flex;
        gap: 10px;
    }

    .joined-user-filter-btn .btn {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .joined-date-box {
        display: flex;
        flex-direction: column;
        gap: 5px;
        font-size: 13px;
        color: var(--ink);
        white-space: nowrap;
    }

    .joined-player-btn-box {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    @media(max-width: 1024px) {
        .quizwinner-search-filter-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media(max-width: 991px) {
        .joined-date-box {
            text-align: end;
        }

        .table-wrap-res-02 {
            background: transparent !important;
            border: none !important;
        }
    }

    @media(max-width: 768px) {
        .stats-box-wrapper {
            grid-template-columns: repeat(2, 1fr);
        }

        .quizwinner-search-filter-box {
            flex-direction: column;
            align-items: stretch;
        }

        .quizwinner-search-filter-wrapper {
            grid-template-columns: repeat(1, 1fr);
        }

        .quizwinner-filter-btn {
            flex-direction: row;
        }
    }

    @media(max-width: 575px) {
        .joined-user-search-filter-box {
            flex-direction: column;
        }

        .stats-box-wrapper {
            grid-template-columns: repeat(1, 1fr);
        }

        .joined-player-btn-box {
            flex-wrap: wrap;
        }
    }
</style>
{{-- deepak css end --}}

@section('content')
    <div class="panel active">
        <div class="page-head"
            style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px; margin-bottom: 10px;">
            <div>
                <h1>👥 Joined Players</h1>
                <p>Players who have joined this quiz: <strong>{{ $quiz->title }}</strong></p>
            </div>
         
            <div class="joined-player-btn-box">

                @if(!empty($canShowUpdatePlayDataBtn))
                {{--  RED BUTTON with Data Attributes --}}
                <button type="button" id="updateQuizPlayBtn" data-quiz-id="{{ $quiz->id }}"
                    data-visibility="{{ $quiz->visibility }}"
                    data-complete-status="{{ $quiz->complete_status ?? '' }}"
                    data-url="{{ route('admin.quizzes.play.update-data', $quiz->id) }}"
                    data-scheduled-at="{{ $quiz->scheduled_at }}"
                    data-answer-end-time="{{ $answerEndTime ? $answerEndTime->toISOString() : '' }}"
                    class="btn btn-danger"
                    style="{{ !empty($showUpdatePlayDataBtn) ? 'display: inline-block;' : 'display: none !important;' }}background-color: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-weight: 600;">
                    🔄 Update Quiz Play Data
                </button>
                @endif

                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">← Back to Quizzes</a>
            </div>
        </div>

        {{-- ✅ SUCCESS/ERROR MESSAGE SHOW KARNE KE LIYE --}}
        <div id="updateResponseMsg"
            style="display:none; margin: 15px 0; padding: 12px; border-radius: 5px; font-weight: 500;"></div>


        {{-- ✅ NEW: 3 Stats Boxes (Joined, Played, Left) --}}
        <div class="stats-box-wrapper">
            <!-- Box 1: Joined Users -->
            <div class="stat-box joined-box">
                <div class="stat-icon">👤</div>
                <div class="stat-content">
                    <div class="stat-label">Joined Users</div>
                    <div class="stat-value">{{ $totalJoinedCount ?? 0 }}</div>
                </div>
            </div>

            <!-- Box 2: Played Users -->
            <div class="stat-box played-box">
                <div class="stat-icon">🎮</div>
                <div class="stat-content">
                    <div class="stat-label">Played Users</div>
                    <div class="stat-value">{{ $totalPlayedCount ?? 0 }}</div>
                </div>
            </div>

            <!-- Box 3: Left Users -->
            <div class="stat-box left-box">
                <div class="stat-icon">🚪</div>
                <div class="stat-content">
                    <div class="stat-label">Left Users</div>
                    <div class="stat-value">{{ $totalLeftCount ?? 0 }}</div>
                </div>
            </div>
        </div>

        {{-- ✅ Search & Filter Box --}}
        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                <div class="joined-user-search-filter-box">
                    <div class="joined-user-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search Player</label>
                            <input class="tb-input" name="search" value="{{ request('search') }}"
                                placeholder="Name, mobile, email…">
                        </div>
                    </div>
                    <div class="joined-user-filter-btn">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('admin.quizzes.joined-users', $quiz->encrypted_id) }}" class="btn btn-ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Data Table --}}
        <div class="table-wrap table-wrap-res-02">
            <div class="quiz-winners-table-box">
                <table class="quiz-winners-table-wrapper">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>User Details</th>
                            <th>Joined Date & Time</th>
                            <th>Fifty-Fifty</th>
                            <th>Switch Option</th>
                            <th>Play Status</th>
                            <th>Out Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($joinedUsers as $index => $joined)
                            <tr>
                                <td data-label="#" class="mono">
                                    {{ $joinedUsers->firstItem() + $index }}
                                </td>

                                <td data-label="User Details">
                                    <div class="winner-user-box">
                                        <div style="font-weight: 600; color: var(--ink);">
                                            {{ $joined->user->name ?? 'Unknown User' }}</div>
                                        <div style="font-size: 12px; color: var(--ink-faint);">
                                            📱 {{ $joined->user->mobile ?? 'No mobile' }}
                                        </div>
                                    </div>
                                </td>

                                {{-- <td data-label="Joined Date" class="mono" style="font-size: 13px; color: var(--ink); white-space: nowrap;">
                                    {{ $joined->created_at ? $joined->created_at->format('d M Y, h:i A') : 'N/A' }}
                                </td> --}}
                                <td data-label="Joined Date" class="mono">
                                    <div class="joined-date-box">
                                        <span>
                                            📅 {{ $joined->created_at ? $joined->created_at->format('d M Y') : 'N/A' }}
                                        </span>
                                        <span>
                                            🕒 {{ $joined->created_at ? $joined->created_at->format('h:i A') : '' }}
                                        </span>
                                    </div>
                                </td>

                                <td data-label="Fifty-Fifty" style="text-align: center;">
                                    @if ($joined->fifty_fifty == 1)
                                        <span class="badge success">✅ Used</span>
                                    @else
                                        <span class="badge neutral">❌ Not Used</span>
                                    @endif
                                </td>

                                <td data-label="Switch Option" style="text-align: center;">
                                    @if ($joined->switch_option == 1)
                                        <span class="badge success">✅ Used</span>
                                    @else
                                        <span class="badge neutral">❌ Not Used</span>
                                    @endif
                                </td>

                                <td data-label="Play Status" style="text-align: center;">
                                    @php
                                        $ansCount = $joined->answer_count ?? 0;
                                        $targetThreshold = ($quiz->visibility === 'fastest_finger_first') ? 1 : 10;
                                    @endphp

                                    @if ($ansCount == 0)
                                        <!-- 0 Answers: Joined (Yellow) -->
                                        <span class="badge warn">
                                            📌 Joined
                                        </span>
                                    @elseif($ansCount < $targetThreshold)
                                        <!-- Below threshold Answers: Played in warning color -->
                                        <span class="badge" style="background: #fee2e2; color: #991b1b;">
                                            🎮 Played (<span
                                                style="color: #dc2626; font-weight: 800;">{{ $ansCount }}</span> Ans)
                                        </span>
                                    @else
                                        <!-- Target or more Answers: Played in green color -->
                                        <span class="badge success">
                                            🎮 Played (<span
                                                style="color: #166534; font-weight: 800;">{{ $ansCount }}</span> Ans)
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Out Status" style="text-align: center;">
                                    @php
                                        $ansCount = $joined->answer_count ?? 0;
                                        $rawOutStatus = $joined->out_status ?? null;

                                        if ($rawOutStatus === 'out') {
                                            $displayStatus = 'out';
                                        } elseif ($ansCount > 0 || $rawOutStatus === 'in') {
                                            $displayStatus = 'in';
                                        } else {
                                            $displayStatus = 'waiting';
                                        }
                                    @endphp
                                    @if ($displayStatus === 'in')
                                        <span class="badge success" style="font-weight: 700;">
                                            🟢 In
                                        </span>
                                    @elseif ($displayStatus === 'out')
                                        <span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 700;" title="{{ $joined->remark ?? 'Out of quiz' }}">
                                            🔴 Out
                                            @if(!empty($joined->remark))
                                                <br><small style="font-size: 10px; font-weight: 500; opacity: 0.85;">{{ Str::limit($joined->remark, 25) }}</small>
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge warn" style="font-weight: 700;">
                                            ⏳ Waiting
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--ink-faint); padding: 40px 0;">
                                    No players have joined this quiz yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $joinedUsers])
            </div>
        </div>
    </div>
@endsection


{{-- SweetAlert2 CDN (Agar aapke layout mein pehle se nahi hai, toh ye add kar lein) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


{{--  AJAX SCRIPT WITH SWEETALERT (DATE CHECK ADDED) --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const updateBtn = document.getElementById('updateQuizPlayBtn');

            if (updateBtn) {
                // If button is hidden waiting for quiz answer time to finish, automatically reveal when time expires
                const answerEndTimeStr = updateBtn.getAttribute('data-answer-end-time');
                if (answerEndTimeStr && updateBtn.style.display.indexOf('none') !== -1) {
                    const answerEndTimeMs = new Date(answerEndTimeStr).getTime();
                    const checkAnswerFinishTimer = setInterval(() => {
                        if (Date.now() >= answerEndTimeMs) {
                            updateBtn.style.removeProperty('display');
                            updateBtn.style.display = 'inline-block';
                            clearInterval(checkAnswerFinishTimer);
                        }
                    }, 1000);
                }

                updateBtn.addEventListener('click', function() {
                    const updateUrl = this.getAttribute('data-url');
                    const quizId = this.getAttribute('data-quiz-id');
                    const visibility = this.getAttribute('data-visibility');
                    const completeStatus = this.getAttribute('data-complete-status') || '';
                    const answerEndTimeVal = this.getAttribute('data-answer-end-time');

                    if (!['processing', 'completed'].includes(completeStatus) && answerEndTimeVal && new Date() < new Date(answerEndTimeVal)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Wait!',
                            text: 'Quiz answer submission time has not finished yet.'
                        });
                        return;
                    }

                    // 2️ CONFIRMATION DIALOG
                    Swal.fire({
                        title: 'Are you sure?',
                        html: `
                    <div style="text-align: left; font-size: 14px;">
                        This action will update:
                        <ul style="margin-top: 10px; margin-bottom: 10px;">
                            <li>🏆 <b>Quiz Winners Table:</b> Recalculate ranks based on correct answers & time.</li>
                            <li>📊 <b>Leaderboard History:</b> Update leaderboard points based on correct answers.</li>
                            <li>💰 <b>User Wallets / Rewards:</b> Apply rank rewards if configured.</li>
                            <li>⚡ <b>Cron Job History:</b> Mark status as <b>Success</b>.</li>
                        </ul>
                        <span style="color: #28a745; font-weight: bold;">Note: Ranks and points will be recalculated accurately.</span>
                    </div>
                `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Update Data!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // 3️ LOADING STATE
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Recalculating ranks and updating wallets. Please wait.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // 4️ AJAX CALL
                            fetch(updateUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        quiz_id: quizId
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: data.message,
                                            confirmButtonColor: '#28a745'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Failed!',
                                            text: data.message,
                                            confirmButtonColor: '#dc3545'
                                        });
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: 'Something went wrong: ' + error.message,
                                        confirmButtonColor: '#dc3545'
                                    });
                                });
                        }
                    });
                });
            }
        });
    </script>
@endpush
