<!-- ===== Hero: Today's / Latest Quiz Component (Auto Carousel) ===== -->

@php
    $joinedArray = $joinedQuizIds ?? [];
    $inactiveArray = $inactiveQuizIds ?? [];
    $notifiedArray = $notifiedQuizIds ?? [];
    $userLimit = (int) (\Illuminate\Support\Facades\Auth::user()?->available_quiz_limit ?? 0);

    // Filter upcoming quizzes up to 10
    $upcomingQuizzes = collect($todayQuizzes ?? [])
        ->filter(function ($q) {
            $isFinished =
                $q->complete_status == 'completed' ||
                ($q->availability_end && \Carbon\Carbon::parse($q->availability_end)->isPast());
            return !$isFinished;
        })
        ->take(10);
@endphp

@if ($upcomingQuizzes && $upcomingQuizzes->count() > 0)
    <!-- Section Header -->
    {{-- <div class="flex items-center justify-between mb-3.5 px-1">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-[#ffd76a]/15 border border-[#ffd76a]/30 flex items-center justify-center">
                <span class="material-symbols-outlined text-[17px] text-[#ffd76a]">bolt</span>
            </div>
            <h2 class="font-display text-lg font-extrabold text-[#e8ddff] tracking-wide">Latest Quiz</h2>
        </div>
        <a href="{{ route('fsrapp.quiz.list') }}"
            class="text-[12px] font-bold text-[#e879f9] hover:text-white flex items-center gap-0.5 transition-colors group">
            View all

        </a>
    </div> --}}

    <!-- ===== Info Banner: Latest Quiz (Animated Text & Icon) ===== -->
    {{-- <div class="glass-card p-3 mb-3 rounded-xl relative overflow-hidden fsr-info-banner-card">
        <div class="absolute -left-8 -top-8 w-24 h-24 bg-[#ffd76a]/15 rounded-full blur-xl pointer-events-none"></div>

        <div class="flex items-center gap-2.5 relative z-10">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 fsr-info-icon-pulse"
                style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);">
                <span class="material-symbols-outlined text-[17px]" style="color:#1a0b3d;">emoji_events</span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display text-[13px] font-extrabold leading-tight fsr-info-shimmer-text">
                    Play Today's Quiz & Win Real Points!
                </h3>
                <p class="text-[10.5px] mt-0.5 leading-snug truncate" style="color:rgba(232,221,255,0.85);">
                    Join live quizzes to top the <span class="fsr-info-highlight font-bold">Leaderboard</span> & claim
                    rewards! 🏆
                </p>
            </div>
        </div>
    </div> --}}

    <!-- Auto-Moving Horizontal Slider -->
    <div class="relative w-full mb-4">
        <div id="latest-quizzes-slider" class="flex gap-4 overflow-x-auto scrollbar-none pb-2 pt-1"
            style="scrollbar-width: none; -ms-overflow-style: none; min-height: 180px;">
            @php $isSingleCard = $upcomingQuizzes->count() === 1; @endphp
            @foreach ($upcomingQuizzes as $quiz)
                @php
                    $hasStarted = $quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->isPast();
                    $startTimetamp = $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)->timestamp : 0;
                    $secondsUntilStart = $quiz->scheduled_at
                        ? \Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($quiz->scheduled_at), false)
                        : 0;
                    $canJoin = $secondsUntilStart <= 2400; // 40 minutes = 2400 seconds
                    $maxPoints =
                        $quiz->rankRewards && $quiz->rankRewards->count() > 0
                            ? $quiz->rankRewards->max('reward')
                            : $quiz->no_of_points ?? '500';

                    $hasJoined = in_array($quiz->id, $joinedArray);
                    $hasInactive = in_array($quiz->id, $inactiveArray);
                    $hasNotified = in_array($quiz->id, $notifiedArray);
                    $isFinished =
                        $quiz->complete_status == 'completed' ||
                        ($quiz->availability_end && \Carbon\Carbon::parse($quiz->availability_end)->isPast());

                    $quizTypeName = $quiz->quizType?->name ?? 'Quiz';
                    $quizStartTime = $quiz->scheduled_at
                        ? \Carbon\Carbon::parse($quiz->scheduled_at)->format('h:i A')
                        : null;
                    $isLimitExhausted = $userLimit <= 0 && !$hasJoined && !$hasInactive;
                @endphp
                <div class="fsr-hero p-5 {{ $isSingleCard ? 'w-full' : 'w-[88vw] max-w-[340px]' }} shrink-0 snap-center quiz-card-item"
                    id="hero-quiz-{{ $quiz->id }}" data-quiz-id="{{ $quiz->id }}" data-time="{{ $startTimetamp }}"
                    data-has-started="{{ $hasStarted ? 'true' : 'false' }}">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-1.5">
                            <span class="fsr-live-dot"
                                style="{{ $hasStarted && !$isFinished ? 'background:#ef4444;box-shadow:0 0 8px #ef4444;' : ($isFinished ? 'background:#6b7280;box-shadow:none;' : '') }}"
                                id="status-dot-{{ $quiz->id }}"></span>
                            <span class="text-[11px] font-bold tracking-wide"
                                style="{{ $hasStarted && !$isFinished ? 'color:#fca5a5;' : ($isFinished ? 'color:#9ca3af;' : 'color:#86efac;') }}"
                                id="status-text-{{ $quiz->id }}">{{ $isFinished ? 'COMPLETED' : ($hasStarted ? 'STARTED' : 'UPCOMING') }}</span>
                        </div>

                        @if (!$hasStarted && !$isFinished)
                            <div class="flex items-center gap-1 bg-white/10 px-2.5 py-1 rounded-full border border-white/10 shadow-sm"
                                id="timer-badge-{{ $quiz->id }}">
                                <span class="material-symbols-outlined text-[13px] text-[#ffd76a]">timer</span>
                                <span class="font-mono text-[11px] font-bold timer-val text-[#ffe9b0] inline-block text-center"
                                    style="font-variant-numeric: tabular-nums; min-width: 36px;"
                                    id="timer-val-{{ $quiz->id }}" data-time="{{ $startTimetamp }}">
                                    {{ $hasStarted ? '00:00' : '--:--' }}
                                </span>
                                <span class="text-[9px] text-white/50">left</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-[12.5px] font-semibold" style="color:rgba(232,221,255,0.75);">
                                {{ $quizTypeName }}
                            </p>
                            <h2 class="font-display text-xl font-extrabold leading-tight mt-0.5 line-clamp-1"
                                style="color:#ffe9b0;">
                                {{ $quiz->title ?? 'General Knowledge' }}
                                <span id="quiz-start-time-{{ $quiz->id }}" class="text-sm font-bold text-[#e8ddff]/70 whitespace-nowrap">{{ $quizStartTime ? ' | ' . $quizStartTime : '' }}</span>
                            </h2>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-[12px] flex items-center gap-1" style="color:rgba(232,221,255,0.65);">
                                    <span class="material-symbols-outlined text-[14px]">groups</span>
                                    {{ number_format($quiz->no_of_players ?? 0) }} playing
                                </span>
                                {{-- <span class="text-[12px] flex items-center gap-1" style="color:#ffd76a;">
                                    <span class="material-symbols-outlined text-[14px]">military_tech</span> Win up to
                                    {{ $maxPoints }} pts
                                </span> --}}

                                @if ($quiz->visibility === 'leaderboard')
                                    <span class="text-[12px] flex items-center gap-1" style="color:#ffd76a;">
                                        <span class="material-symbols-outlined text-[14px]">leaderboard</span> Earn Marks & Rank Up!
                                    </span>
                                @elseif ($quiz->visibility === 'fastest_finger_first')
                                    <span class="text-[12px] flex items-center gap-1" style="color:#ffd76a;">
                                        <span class="material-symbols-outlined text-[14px]">bolt</span> Be the Fastest!
                                    </span>
                                @else
                                    <span class="text-[12px] flex items-center gap-1" style="color:#ffd76a;">
                                        <span class="material-symbols-outlined text-[14px]">military_tech</span> Win up to {{ $maxPoints }} pts
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2.5 mt-3">
                        <button onclick="openPrizesSheet({{ $quiz->id }})"
                            class="py-2 px-3.5 rounded-full font-display font-bold text-[12px] flex items-center justify-center gap-1 whitespace-nowrap bg-white/5 border border-white/10 text-[#e8ddff] hover:bg-white/10 transition-all">
                            <span class="material-symbols-outlined text-[16px]">emoji_events</span>
                           @if ($quiz->visibility === 'leaderboard') 
                             Marks
                            @elseif ($quiz->visibility === 'fastest_finger_first')
                            Marks 
                           @else
                             Prizes
                           @endif 
                        </button>

                        @if ($isFinished)
                            {{-- <a id="play-btn-{{ $quiz->id }}" href="{{ route('fsrapp.quiz.winners', $quiz->id) }}"
                                class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">leaderboard</span>
                                <span id="play-btn-text-{{ $quiz->id }}">Leaderboard</span>
                            </a> --}}
                        @elseif ($hasJoined)
                            <script>try{localStorage.setItem('quiz_joined_{{ $quiz->id }}','1');localStorage.setItem('fsr_quiz_joined_{{ $quiz->id }}','1');}catch(e){}</script>
                            <a id="play-btn-{{ $quiz->id }}" data-joined="true"
                                onclick="if(window.markAudioUnlocked){window.markAudioUnlocked();}else{try{sessionStorage.setItem('fsr_audio_unlocked','1');sessionStorage.setItem('fsr_joined_from_home','1');localStorage.setItem('fsr_audio_unlocked','1');}catch(e){}}"
                                href="{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.waiting', $quiz->id) : route('fsrapp.quizes.waiting', $quiz->id) }}"
                                class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] play-icon-{{ $quiz->id }}">play_arrow</span>
                                <span id="play-btn-text-{{ $quiz->id }}">Play Quiz</span>
                            </a>
                        @elseif ($hasInactive)
                            <button id="play-btn-{{ $quiz->id }}" data-joined="false" type="button"
                                onclick="if(window.showAppAlertModal) { window.showAppAlertModal('⚠️ Is quiz mein player limit full ho gayi hai. Aap is quiz mein join nahi kar sakte. Aap next quiz mein apply/join kar sakte hain.', 'warning'); } else { toast('Is quiz mein player limit full ho gayi hai.', 'warning'); }"
                                class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 opacity-60 bg-red-500/20 border border-red-500/40 text-red-300">
                                <span
                                    class="material-symbols-outlined text-[16px] play-icon-{{ $quiz->id }}">block</span>
                                <span id="play-btn-text-{{ $quiz->id }}">Limit Full</span>
                            </button>
                        @elseif ($hasStarted || $quiz->status === 'processing' || $secondsUntilStart <= 0)
                            <button id="play-btn-{{ $quiz->id }}" data-joined="false" disabled
                                class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 opacity-50 cursor-not-allowed">
                                <span class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span id="play-btn-text-{{ $quiz->id }}">Processing...</span>
                            </button>
                        @elseif ($isLimitExhausted)
                            <button id="play-btn-{{ $quiz->id }}" data-joined="false" type="button"
                                onclick="toast('Aapki quiz limit khatam ho gayi hai! 🎯', 'warning')"
                                class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 opacity-50 cursor-not-allowed">
                                <span
                                    class="material-symbols-outlined text-[16px] play-icon-{{ $quiz->id }}">play_circle</span>
                                <span id="play-btn-text-{{ $quiz->id }}">Play Now</span>
                            </button>
                        @elseif (!$canJoin)
                            {{-- Form for Notify --}}
                            <div id="notify-container-{{ $quiz->id }}" class="flex-1 flex">
                                <form action="{{ route('fsrapp.quizes.notify', $quiz->id) }}" method="POST"
                                    class="flex-1 flex" onclick="event.stopPropagation();">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 transition-all {{ $hasNotified ? 'bg-purple-600/30 border border-purple-500/50 text-[#e879f9]' : 'bg-gradient-to-r from-amber-500 to-purple-600 text-white' }}">
                                        <span
                                            class="material-symbols-outlined text-[16px]">{{ $hasNotified ? 'notifications_active' : 'notifications' }}</span>
                                        <span>{{ $hasNotified ? 'Notified' : 'Notify Me' }}</span>
                                    </button>
                                </form>
                            </div>
                            
                            {{-- Play Now Button (Hidden initially) --}}
                            <div id="play-container-{{ $quiz->id }}" class="flex-1 flex hidden">
                                <button id="play-btn-{{ $quiz->id }}" data-joined="false"
                                    onclick="openQuizJoinSheet({{ $quiz->id }})"
                                    class="fsr-play-btn flex-1 w-full py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5">
                                    <span
                                        class="material-symbols-outlined text-[16px] play-icon-{{ $quiz->id }}">play_circle</span>
                                    <span id="play-btn-text-{{ $quiz->id }}">Play Now</span>
                                </button>
                            </div>
                        @else
                            {{-- Play Now Button --}}
                            <div id="play-container-{{ $quiz->id }}" class="flex-1 flex">
                                <button id="play-btn-{{ $quiz->id }}" data-joined="false"
                                    onclick="openQuizJoinSheet({{ $quiz->id }})"
                                    class="fsr-play-btn flex-1 w-full py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5">
                                    <span
                                        class="material-symbols-outlined text-[16px] play-icon-{{ $quiz->id }}">play_circle</span>
                                    <span id="play-btn-text-{{ $quiz->id }}">Play Now</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

            @endforeach
        </div>
    </div>
@endif

@push('scripts')
    <script>
        function openPrizesSheet(quizId) {
            const sheet = document.getElementById('prizes-sheet-' + quizId);
            const content = document.getElementById('prizes-sheet-content-' + quizId);
            if (sheet && content) {
                sheet.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('translate-y-full');
                }, 10);
            }
        }

        function closePrizesSheet(quizId) {
            const sheet = document.getElementById('prizes-sheet-' + quizId);
            const content = document.getElementById('prizes-sheet-content-' + quizId);
            if (sheet && content) {
                content.classList.add('translate-y-full');
                setTimeout(() => {
                    sheet.classList.add('hidden');
                }, 300);
            }
        }

        function openQuizJoinSheet(quizId) {
            // Agar locally join status saved hai toh seedha quiz ke andar bhejo (sheet open mat karo)
            try {
                if (localStorage.getItem('quiz_joined_' + quizId) === '1' || localStorage.getItem('fsr_quiz_joined_' + quizId) === '1') {
                    if (window.markAudioUnlocked) {
                        window.markAudioUnlocked();
                    } else {
                        try {
                            sessionStorage.setItem('fsr_audio_unlocked', '1');
                            sessionStorage.setItem('fsr_joined_from_home', '1');
                            localStorage.setItem('fsr_audio_unlocked', '1');
                        } catch(e) {}
                    }
                    window.location.href = '/fsrapp/quizes/waiting/' + quizId;
                    return;
                }
            } catch (e) {}

            const sheet = document.getElementById('quiz-join-sheet-' + quizId);
            const content = document.getElementById('quiz-join-sheet-content-' + quizId);
            if (sheet && content) {
                sheet.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('translate-y-full');
                }, 10);
            }
        }

        function closeQuizJoinSheet(quizId) {
            const sheet = document.getElementById('quiz-join-sheet-' + quizId);
            const content = document.getElementById('quiz-join-sheet-content-' + quizId);
            if (sheet && content) {
                content.classList.add('translate-y-full');
                setTimeout(() => {
                    sheet.classList.add('hidden');
                }, 300);
            }
        }

        async function handleTodayQuizJoin(event, quizId) {
            event.preventDefault();
            if (window.markAudioUnlocked) {
                window.markAudioUnlocked();
            } else {
                try {
                    sessionStorage.setItem('fsr_audio_unlocked', '1');
                    sessionStorage.setItem('fsr_joined_from_home', '1');
                    localStorage.setItem('fsr_audio_unlocked', '1');
                } catch(e) {}
            }
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block"></span> Joining...';
            }

            try {
                const formData = new FormData(form);
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data && data.success && data.redirect) {
                    try {
                        localStorage.setItem('quiz_joined_' + quizId, '1');
                        localStorage.setItem('fsr_quiz_joined_' + quizId, '1');
                    } catch(e) {}
                    window.location.href = data.redirect;
                } else {
                    closeQuizJoinSheet(quizId);
                    if (window.showAppAlertModal) {
                        window.showAppAlertModal(data.message ||
                            '⚠️ Quiz player limit complete try next time',
                            'warning', null);
                    } else if (typeof toast === 'function') {
                        toast(data.message || '⚠️ Quiz player limit complete try next time', 'warning');
                    }
                    const heroPlayBtn = document.getElementById('play-btn-' + quizId);
                    if (heroPlayBtn) {
                        heroPlayBtn.outerHTML =
                            `<button id="play-btn-${quizId}" data-joined="false" type="button" onclick="if(window.showAppAlertModal) { window.showAppAlertModal('⚠️ Quiz player limit complete try next time', 'warning'); } else { toast('⚠️ Quiz player limit complete try next time', 'warning'); }" class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 opacity-60 bg-red-500/20 border border-red-500/40 text-red-300"><span class="material-symbols-outlined text-[16px]">block</span><span>Limit Full</span></button>`;
                    }
                }
            } catch (e) {
                console.error('Join error', e);
                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Check local join status: agar locally joined hai, toh Play Now button ko seedha Play Quiz bana do
            try {
                document.querySelectorAll('[id^="play-btn-"]').forEach(btn => {
                    const qId = btn.id.replace('play-btn-', '');
                    if (!qId) return;
                    const isJoinedLocally = localStorage.getItem('quiz_joined_' + qId) === '1' || localStorage.getItem('fsr_quiz_joined_' + qId) === '1';
                    if (isJoinedLocally && btn.getAttribute('data-joined') !== 'true' && !btn.disabled && !btn.innerText.includes('Limit Full')) {
                        btn.outerHTML = `<a id="play-btn-${qId}" data-joined="true"
                            onclick="if(window.markAudioUnlocked){window.markAudioUnlocked();}else{try{sessionStorage.setItem('fsr_audio_unlocked','1');sessionStorage.setItem('fsr_joined_from_home','1');localStorage.setItem('fsr_audio_unlocked','1');}catch(e){}}"
                            href="/fsrapp/quizes/waiting/${qId}"
                            class="fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] play-icon-${qId}">play_arrow</span>
                            <span id="play-btn-text-${qId}">Play Quiz</span>
                        </a>`;
                    }
                });
            } catch(e) {}
        });
    </script>
@endpush
