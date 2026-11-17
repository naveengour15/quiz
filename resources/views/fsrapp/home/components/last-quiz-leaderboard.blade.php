@php
    $isAlwaysShow = !empty($alwaysShow);
    $cardQuizId = $lastQuizData['id'] ?? ($quiz->id ?? ($id ?? null));
    $cardTitle = !empty($lastQuizData['title']) ? $lastQuizData['title'] : ($quiz->title ?? 'Quiz Leaderboard');

    $completeStatus = $lastQuizData['complete_status'] ?? ($isAlwaysShow ? 'processing' : '');
    $isProcessing = $completeStatus !== 'completed';
    $isCompleted = $completeStatus === 'completed';

    $completedAtMs = $lastQuizData['completed_at_ms'] ?? (!empty($lastQuizData['completed_at']) ? (strtotime($lastQuizData['completed_at']) * 1000) : null);
    $nowMs = time() * 1000;
    $thirtyMinsMs = 30 * 60 * 1000; // 30 minutes in milliseconds
    $hideTimeMs = $completedAtMs ? ($completedAtMs + $thirtyMinsMs) : 0;

    $isExpired = !$isAlwaysShow && $isCompleted && $completedAtMs && ($nowMs >= $hideTimeMs);
    $shouldShow = $isAlwaysShow || (!empty($cardQuizId) && ($isProcessing || ($isCompleted && !$isExpired)));
    $remainingMsToHide = !$isAlwaysShow && ($isCompleted && $completedAtMs && !$isExpired) ? max(0, $hideTimeMs - $nowMs) : 0;
    $topWinner = (!$isProcessing && !empty($lastQuizData['winners'])) ? $lastQuizData['winners'][0] : null;
@endphp

@if ($shouldShow)
    <style>
        @keyframes bg-pan {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes float-img {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-bg-pan {
            background-size: 200% 200%;
            animation: bg-pan 6s ease-in-out infinite;
        }
        .animate-float-img {
            animation: float-img 4s ease-in-out infinite;
        }
        @keyframes btn-glow {
            0%, 100% { box-shadow: 0 0 10px rgba(250, 204, 21, 0.2); }
            50% { box-shadow: 0 0 20px rgba(250, 204, 21, 0.5); }
        }
        .btn-animated-bg {
            background: linear-gradient(90deg, #07241c, #115e45, #07241c);
            background-size: 200% 200%;
            animation: bg-pan 3s ease infinite, btn-glow 2s ease-in-out infinite;
            border: 1px solid rgba(250, 204, 21, 0.3);
        }
        .btn-animated-bg:hover {
            box-shadow: 0 0 20px rgba(250, 204, 21, 0.7);
            border-color: rgba(250, 204, 21, 0.6);
        }
    </style>

    <div class="relative overflow-hidden rounded-[20px] mb-4 text-left shadow-2xl bg-gradient-to-r from-[#04241d] via-[#094738] to-[#1fd675] animate-bg-pan"
        id="home-last-quiz-lb-card">
        <!-- Abstract wavy lines / patterns in background -->
        <div class="absolute inset-0 opacity-[0.15] pointer-events-none mix-blend-overlay"
            style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, #ffffff 10px, #ffffff 11px);">
        </div>
        <div
            class="absolute right-0 top-0 bottom-0 w-3/4 bg-gradient-to-l from-[#2cd66d]/50 to-transparent pointer-events-none">
        </div>

        <!-- Right Side: Winner Image -->
        <div class="absolute right-[-10px] bottom-[-15px] pointer-events-none flex items-end justify-end w-[240px] h-[120%] z-0">
            <img src="{{ asset('fsrapp/images/winner.png') }}" class="w-full h-full object-contain drop-shadow-2xl animate-float-img" alt="Winner">
        </div>

        <!-- Main Content -->
        <div class="relative z-10 pt-6 pb-6 px-6">
            <!-- Small Tag -->
            <div class="flex items-center gap-1.5 mb-2.5">
                <span class="text-[#facc15] text-[10px] font-black uppercase tracking-[0.25em]">LAST MATCH</span>
            </div>

            <!-- Big Title -->
            <h2 class="font-display text-xl font-extrabold text-white leading-tight mb-5 max-w-[200px]">
                {{ mb_strtoupper($cardTitle) }}<br>
                @if ($isProcessing)
                    <span id="lb-title-processing" class="text-[#facc15]">PROCESSING</span>
                    <span id="lb-title-completed" class="text-[#facc15]" style="display: none;">WINNERS OUT</span>
                @else
                    @if ($topWinner)
                        <span class="text-white text-lg block mt-1">🥇
                            {{ \Illuminate\Support\Str::limit($topWinner['user_name'], 15) }}</span>
                    @endif
                    <span class="text-[#facc15]">WINNERS OUT</span>
                @endif
            </h2>

            <div class="flex items-center">
                @if ($isProcessing)
                    <!-- 1st Button: Processing Button -->
                    <button id="lb-processing-btn" disabled
                        class="inline-flex items-center justify-center rounded-full px-5 py-2.5 transition-colors btn-animated-bg opacity-70 cursor-not-allowed">
                        <span class="text-[11px] font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined animate-spin text-[16px]">sync</span>
                            Processing...
                        </span>
                    </button>

                    <!-- 2nd Button: Hidden Leaderboard Button -->
                    <a id="lb-leaderboard-btn" href="#" style="display: none;"
                        class="items-center rounded-full pl-5 pr-1 py-1 transition-colors group btn-animated-bg">
                        <span class="text-[11px] font-bold text-white mr-4 drop-shadow-sm">View Leaderboard</span>
                        <div
                            class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#f59e0b] to-[#facc15] flex items-center justify-center shadow-inner group-hover:scale-105 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[#07241c] text-[18px] ml-0.5"
                                style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                        </div>
                    </a>

                    <script>
                        (function() {
                            let lbCheckTimeout = null;
                            let pollAttempt = 0;
                            let pollStartTime = Date.now();
                            let isCompletedState = false;

                            const getNextIntervalMs = () => {
                                pollAttempt++;
                                return 10000; // Exact 10 seconds
                            };

                            const scheduleNextCheck = (delayMs) => {
                                if (isCompletedState) return;
                                if (lbCheckTimeout) clearTimeout(lbCheckTimeout);
                                // Safety: Stop polling if running for more than 10 minutes
                                if (Date.now() - pollStartTime > 600000) return;
                                // Pause polling if page/tab is currently hidden in background
                                if (document.hidden) return;

                                lbCheckTimeout = setTimeout(checkStatus, delayMs || getNextIntervalMs());
                            };

                            const checkStatus = () => {
                                if (isCompletedState) return;

                                // If we are in Quiz SPA and NOT currently on answer-sheet, do NOT poll
                                const isSpa = !!document.getElementById('quiz-spa-container') || !!document.getElementById('fff-quiz-spa-container');
                                if (isSpa) {
                                    const isQuizAnswerSheet = window.QuizSpa && window.QuizSpa.currentStage === 'answer-sheet';
                                    const isFffAnswerSheet = window.FffSpa && window.FffSpa.currentStage === 'answer-sheet';
                                    if (!isQuizAnswerSheet && !isFffAnswerSheet) {
                                        return;
                                    }
                                }

                                let statusUrl = '{{ route('fsrapp.home.last-quiz-status') }}?_t=' + new Date().getTime();
                                @if (!empty($cardQuizId))
                                    statusUrl += '&quiz_id={{ $cardQuizId }}';
                                @endif

                                fetch(statusUrl, {
                                        method: 'GET',
                                        cache: 'no-store',
                                        credentials: 'same-origin',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data && data.complete_status === 'completed') {
                                            isCompletedState = true;
                                            if (lbCheckTimeout) clearTimeout(lbCheckTimeout);

                                            // Clear cache/session now that leaderboard has officially generated
                                            if (typeof window.clearCompletedQuizCacheAndSession === 'function') {
                                                window.clearCompletedQuizCacheAndSession();
                                            }

                                            // Hide processing button
                                            const procBtn = document.getElementById('lb-processing-btn');
                                            if (procBtn) {
                                                procBtn.style.setProperty('display', 'none', 'important');
                                                procBtn.classList.add('hidden');
                                                procBtn.classList.remove('inline-flex');
                                            }

                                            // Update URL and show leaderboard button
                                            const lbBtn = document.getElementById('lb-leaderboard-btn');
                                            const targetQuizId = data.id || '{{ $cardQuizId }}';
                                            if (lbBtn && targetQuizId) {
                                                let routeStr = '{{ route('fsrapp.history.leaderboard', 'TEMP_ID') }}';
                                                lbBtn.href = routeStr.replace('TEMP_ID', targetQuizId);
                                                lbBtn.style.setProperty('display', 'inline-flex', 'important');
                                                lbBtn.classList.remove('hidden');
                                                lbBtn.classList.add('inline-flex');
                                            }

                                            // Update Title
                                            const titleProc = document.getElementById('lb-title-processing');
                                            const titleComp = document.getElementById('lb-title-completed');
                                            if (titleProc) {
                                                titleProc.style.setProperty('display', 'none', 'important');
                                                titleProc.classList.add('hidden');
                                            }
                                            if (titleComp) {
                                                titleComp.style.setProperty('display', 'inline', 'important');
                                                titleComp.classList.remove('hidden');
                                            }

                                            @if (!$isAlwaysShow)
                                            // Auto-hide card after 30 minutes from completion
                                            setTimeout(() => {
                                                const card = document.getElementById('home-last-quiz-lb-card');
                                                if (card) {
                                                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                                    card.style.opacity = '0';
                                                    card.style.transform = 'scale(0.95)';
                                                    setTimeout(() => card.remove(), 500);
                                                }
                                            }, 30 * 60 * 1000);
                                            @endif
                                        } else {
                                            scheduleNextCheck();
                                        }
                                    })
                                    .catch(err => {
                                        scheduleNextCheck();
                                    });
                            };

                            window.startLastQuizStatusPolling = function() {
                                if (isCompletedState) return;
                                if (lbCheckTimeout) clearTimeout(lbCheckTimeout);
                                pollAttempt = 0;
                                pollStartTime = Date.now();
                                // First check after 10s on answer sheet
                                lbCheckTimeout = setTimeout(checkStatus, 10000);
                            };

                            // Resume check when user returns to active tab
                            document.addEventListener('visibilitychange', function() {
                                if (!document.hidden && !isCompletedState) {
                                    const isSpa = !!document.getElementById('quiz-spa-container') || !!document.getElementById('fff-quiz-spa-container');
                                    const onAnswerSheet = (window.QuizSpa && window.QuizSpa.currentStage === 'answer-sheet') || (window.FffSpa && window.FffSpa.currentStage === 'answer-sheet');
                                    if (!isSpa || onAnswerSheet) {
                                        if (lbCheckTimeout) clearTimeout(lbCheckTimeout);
                                        scheduleNextCheck(10000);
                                    }
                                }
                            });

                            // Auto-start polling on page load ONLY if NOT in Quiz SPA (e.g. on Home page)
                            const isQuizSpaPage = !!document.getElementById('quiz-spa-container') || !!document.getElementById('fff-quiz-spa-container');
                            if (!isQuizSpaPage) {
                                lbCheckTimeout = setTimeout(checkStatus, 10000);
                            }
                        })();
                    </script>
                @else
                    <!-- Dark Pill Button with Yellow Circle Play Icon -->
                    <a href="{{ route('fsrapp.history.leaderboard', $cardQuizId ?? $lastQuizData['id']) }}"
                        class="inline-flex items-center rounded-full pl-5 pr-1 py-1 transition-colors group btn-animated-bg">
                        <span class="text-[11px] font-bold text-white mr-4 drop-shadow-sm">View Leaderboard</span>
                        <div
                            class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#f59e0b] to-[#facc15] flex items-center justify-center shadow-inner group-hover:scale-105 transition-transform duration-300">
                            <span class="material-symbols-outlined text-[#07241c] text-[18px] ml-0.5"
                                style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                        </div>
                    </a>

                    @if ($remainingMsToHide > 0)
                        <script>
                            (function() {
                                const remainingMs = {{ $remainingMsToHide }};
                                setTimeout(() => {
                                    const card = document.getElementById('home-last-quiz-lb-card');
                                    if (card) {
                                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                        card.style.opacity = '0';
                                        card.style.transform = 'scale(0.95)';
                                        setTimeout(() => card.remove(), 500);
                                    }
                                }, remainingMs);
                            })();
                        </script>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endif
