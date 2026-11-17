@extends('fsrapp.layouts.app')

@section('content')
    <div id="quiz-spa-container">
        <div id="stage-waiting" class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'waiting' ? '' : 'hidden' }}">
            @include('fsrapp.quizes.components.waiting')
        </div>
        <div id="stage-start-counter"
            class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'start-counter' ? '' : 'hidden' }}">
            @include('fsrapp.quizes.components.start-counter')
        </div>
        <div id="stage-question"
            class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'question' ? '' : 'hidden' }}">
            @include('fsrapp.quizes.components.question')
        </div>
        <div id="stage-review-counter"
            class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'review-counter' ? '' : 'hidden' }}">
            @include('fsrapp.quizes.components.review-counter')
        </div>
        <div id="stage-answer-sheet"
            class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'answer-sheet' ? '' : 'hidden' }}">
            @include('fsrapp.quizes.components.answer-sheet')
        </div>
        {{-- 
    <div id="stage-final-counter" class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'final-counter' ? '' : 'hidden' }}">
        @include('fsrapp.quizes.components.final-counter')
    </div>
    <div id="stage-leaderboard" class="quiz-stage-component {{ ($initialStage ?? 'waiting') === 'leaderboard' ? '' : 'hidden' }}">
        @include('fsrapp.quizes.components.leaderboard')
    </div>
    --}}
    </div>

    <script>
        window.quizId = {{ $id }};
        try {
            localStorage.setItem('quiz_joined_' + window.quizId, '1');
            localStorage.setItem('fsr_quiz_joined_' + window.quizId, '1');
        } catch(e) {}
        window.quizCompleteStatus = "{{ $quiz->complete_status ?? '' }}";
        window.scheduledTimeMs = {{ $scheduledTimeMs ? (int) $scheduledTimeMs : 'null' }};
        window.availabilityEndMs = {{ $availabilityEndMs ? (int) $availabilityEndMs : 'null' }};
        window.quizAvailabilityEndMs = {{ !empty($quizAvailabilityEndMs) ? (int) $quizAvailabilityEndMs : 'null' }};
        window.serverNowMs = {{ $serverNowMs ? (int) $serverNowMs : 'Date.now()' }};
        window.clockOffset = window.serverNowMs - Date.now();
        window.totalQuestionsCount = {{ (int) ($qCount ?? (!empty($quiz->question_limit) ? $quiz->question_limit : 10)) }};
        window.initialQuestions = @json($initialQuestions ?? []);
        window.initialSwitchQuestions = @json($initialSwitchQuestions ?? []);
        window.initialUserAnswers = @json($initialUserAnswers ?? (object)[]);

        // Saved answers restore UI state; the schedule, not answer count, selects the active slot.
        window.serverAnsweredCount = {{ count($initialUserAnswers ?? []) }};
        window.userLifeline = @json($userLifeline ?? null);

        if (window.userLifeline) {
            try {
                if (window.userLifeline.fifty_fifty) {
                    sessionStorage.setItem('qx_used_5050_' + window.quizId, 'true');
                    localStorage.setItem('qx_used_5050_' + window.quizId, 'true');
                    sessionStorage.setItem('qx_any_lifeline_used_' + window.quizId, 'true');
                    localStorage.setItem('qx_any_lifeline_used_' + window.quizId, 'true');
                    if (window.userLifeline.question_id) {
                        sessionStorage.setItem('qx_5050_qid_' + window.quizId, String(window.userLifeline.question_id));
                        localStorage.setItem('qx_5050_qid_' + window.quizId, String(window.userLifeline.question_id));
                    }
                    if (window.userLifeline.fifty_fifty_data) {
                        const dStr = JSON.stringify(window.userLifeline.fifty_fifty_data);
                        sessionStorage.setItem('qx_5050_data_' + window.quizId, dStr);
                        localStorage.setItem('qx_5050_data_' + window.quizId, dStr);
                    }
                }
                if (window.userLifeline.switch_option) {
                    sessionStorage.setItem('qx_used_switch_' + window.quizId, 'true');
                    localStorage.setItem('qx_used_switch_' + window.quizId, 'true');
                    sessionStorage.setItem('qx_any_lifeline_used_' + window.quizId, 'true');
                    localStorage.setItem('qx_any_lifeline_used_' + window.quizId, 'true');
                    if (window.userLifeline.question_id) {
                        let set = new Set();
                        const s = sessionStorage.getItem('qx_switched_q_ids_' + window.quizId) || localStorage.getItem('qx_switched_q_ids_' + window.quizId);
                        if (s) {
                            try {
                                JSON.parse(s).forEach(x => { set.add(x); set.add(Number(x)); set.add(String(x)); });
                            } catch(err) {}
                        }
                        set.add(window.userLifeline.question_id);
                        set.add(Number(window.userLifeline.question_id));
                        set.add(String(window.userLifeline.question_id));
                        sessionStorage.setItem('qx_switched_q_ids_' + window.quizId, JSON.stringify(Array.from(set)));
                        localStorage.setItem('qx_switched_q_ids_' + window.quizId, JSON.stringify(Array.from(set)));
                    }
                }
            } catch(e) {}
        }

        window.getEffectiveTotalQuestionsCount = function() {
            const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + window.quizId) === 'true' ||
                                 localStorage.getItem('qx_used_switch_' + window.quizId) === 'true' ||
                                 Boolean(window.userLifeline && window.userLifeline.switch_option);
            let count = window.totalQuestionsCount || 10;
            if (isSwitchUsed) {
                count += 1;
            }
            return count;
        };

        window.getQuizScheduleTimeMs = function() {
            const startCounterKey = 'qx_start_counter_time_' + window.quizId;
            let saved = parseInt(sessionStorage.getItem(startCounterKey) || localStorage.getItem(startCounterKey) || '0', 10);
            if (window.scheduledTimeMs && (!saved || Math.abs(saved - window.scheduledTimeMs) > 1000)) {
                saved = window.scheduledTimeMs;
                try {
                    sessionStorage.setItem(startCounterKey, saved);
                    localStorage.setItem(startCounterKey, saved);
                } catch(e){}
            }
            if (!saved) {
                saved = window.serverNowMs || Date.now();
                try {
                    sessionStorage.setItem(startCounterKey, saved);
                    localStorage.setItem(startCounterKey, saved);
                } catch(e){}
            }
            return saved;
        };

        window.getQuestionStartTimeMs = function() {
            const sched = window.getQuizScheduleTimeMs();
            const qflowKey = 'qx_qflow_start_' + window.quizId;
            const qflowStart = sched + 10000;
            try {
                sessionStorage.setItem(qflowKey, qflowStart);
                localStorage.setItem(qflowKey, qflowStart);
            } catch(e){}
            return qflowStart;
        };

        // If quiz is in waiting or start-counter stage, clear any stale completed/reviewed flags
        if (['waiting', 'start-counter'].includes('{{ $initialStage ?? "waiting" }}')) {
            try {
                sessionStorage.removeItem('qx_completed_' + window.quizId);
                localStorage.removeItem('qx_completed_' + window.quizId);
                sessionStorage.removeItem('qx_reviewed_' + window.quizId);
                localStorage.removeItem('qx_reviewed_' + window.quizId);
                sessionStorage.removeItem('qx_anssheet_generated_' + window.quizId);
                localStorage.removeItem('qx_anssheet_generated_' + window.quizId);
                sessionStorage.removeItem('qx_anssheet_release_at_v2_' + window.quizId);
                localStorage.removeItem('qx_anssheet_release_at_v2_' + window.quizId);
            } catch(e) {}
        }

        // Helper to check if current user completed quiz submissions
        window.isQuizUserCompleted = function() {
            if (window.__isQuizCompleted) return true;
            const qLimit = typeof window.getEffectiveTotalQuestionsCount === 'function'
                ? window.getEffectiveTotalQuestionsCount()
                : {{ !empty($quiz->question_limit) ? (int) $quiz->question_limit : 10 }};
            try {
                const isCompleted = sessionStorage.getItem('qx_completed_' + window.quizId) === 'true' ||
                    localStorage.getItem('qx_completed_' + window.quizId) === 'true';
                if (isCompleted) {
                    window.__isQuizCompleted = true;
                    return true;
                }
                const sheetKey = 'qx_anssheet_' + window.quizId;
                const rawSheet = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
                if (rawSheet) {
                    const sheet = JSON.parse(rawSheet);
                    if (Array.isArray(sheet) && sheet.length >= qLimit) {
                        window.__isQuizCompleted = true;
                        return true;
                    }
                }
            } catch (e) {}

            // Server-confirmed answers count check: agar DB mein sare answers saved hain
            if (window.serverAnsweredCount >= qLimit) {
                window.__isQuizCompleted = true;
                return true;
            }

            return false;
        };

        // Quiz deadline includes the scheduled question slots and the review counter.
        window.getQuizExpiryTimeMs = function() {
            const sched = window.getQuizScheduleTimeMs();
            return sched + 10000 + (window.getEffectiveTotalQuestionsCount() * 17000) + 10000;
        };

        // Keep a returning player in the scheduled review/answer-sheet flow.
        window.checkAndHandleQuizExpiry = function() {
            const now = Date.now() + (window.clockOffset || 0);
            const schedule = window.getQuizScheduleTimeMs();
            const questionEnd = schedule + 10000 + (window.getEffectiveTotalQuestionsCount() * 17000);
            const reviewEnd = questionEnd + 10000;

            if (now >= reviewEnd && window.QuizSpa && window.QuizSpa.currentStage === 'review-counter') {
                window.QuizSpa.switchStage('answer-sheet');
                return true;
            }

            return false;
        };

        // Immediate guard
        (function checkImmediateQuizOut() {
            try {
                if (window.isQuizUserCompleted()) {
                    window.__isQuizCompleted = true;
                } else {
                    window.checkAndHandleQuizExpiry();
                }
            } catch (e) {}
        })();

        window.handleUserQuizExit = function(reason, redirectUrl, alertTitle) {
            console.log('[Quiz OUT condition disabled]', reason);
        };

        window.exitQuizDueToExpiration = function(message) {
            console.log('[Quiz expiration exit disabled]', message);
        };

        window.exitQuizDueToReload = function(message) {
            console.log('[Quiz reload exit disabled]', message);
        };

        const activeQuizPlayStages = ['start-counter', 'question'];

        window.QuizSpa = {
            activeTimers: [],
            currentStage: '{{ $initialStage ?? 'waiting' }}',

            registerTimer(timerId) {
                if (timerId) this.activeTimers.push(timerId);
            },

            clearAllTimers() {
                this.activeTimers.forEach(id => clearInterval(id));
                this.activeTimers = [];
                if (window.activeQuestionAudioTimer) {
                    clearTimeout(window.activeQuestionAudioTimer);
                    window.activeQuestionAudioTimer = null;
                }
                if (window.activeQuestionAudio) {
                    try {
                        window.activeQuestionAudio.pause();
                    } catch (e) {}
                    window.activeQuestionAudio = null;
                }
            },

            switchStage(stageName) {
                console.log('[Quiz SPA] Switching stage from', this.currentStage, 'to:', stageName);
                this.clearAllTimers();
                if (window.stopAllQuizAudios) window.stopAllQuizAudios();

                // If leaving waiting stage, hide audio unlock banner
                if (stageName !== 'waiting' && typeof window.hideGlobalAudioUnlockBanner === 'function') {
                    window.hideGlobalAudioUnlockBanner();
                }

                this.currentStage = stageName;

                // Track active stage in session storage
                try {
                    sessionStorage.setItem('qx_active_stage_' + window.quizId, stageName);
                } catch (e) {}

                // Hide all components
                document.querySelectorAll('.quiz-stage-component').forEach(el => {
                    el.classList.add('hidden');
                    el.style.display = 'none';
                });

                // Show target stage component
                const targetEl = document.getElementById('stage-' + stageName);
                if (targetEl) {
                    targetEl.classList.remove('hidden');
                    targetEl.style.display = 'block';
                }

                // Update history state so intermediate quiz stages don't pile up in history
                try {
                    const newPath = `/fsrapp/quizes/${stageName}/${window.quizId}`;
                    if (window.location.pathname !== newPath) {
                        window.history.replaceState({
                            stage: stageName,
                            quizId: window.quizId
                        }, '', newPath);
                    }
                } catch (e) {}

                if (stageName === 'answer-sheet' || stageName === 'leaderboard') {
                    try {
                        window.history.pushState({
                            stage: stageName,
                            quizId: window.quizId
                        }, '', `/fsrapp/quizes/${stageName}/${window.quizId}`);
                    } catch (e) {}
                }

                if (stageName === 'answer-sheet') {
                    if (window.releaseQuizProtection) {
                        window.releaseQuizProtection();
                    }
                    document.documentElement.style.overflowY = 'auto';
                    document.body.style.overflowY = 'auto';
                    document.documentElement.style.overscrollBehaviorY = 'auto';
                    document.body.style.overscrollBehaviorY = 'auto';
                } else {
                    document.documentElement.style.overflowY = 'auto';
                    document.body.style.overflowY = 'auto';
                    document.documentElement.style.overscrollBehaviorY = 'auto';
                    document.body.style.overscrollBehaviorY = 'auto';
                }

                // Trigger stage initializer
                if (stageName === 'waiting' && typeof window.initWaitingStage === 'function') {
                    window.initWaitingStage();
                } else if (stageName === 'start-counter' && typeof window.initStartCounterStage === 'function') {
                    window.initStartCounterStage();
                } else if (stageName === 'question' && typeof window.initQuestionStage === 'function') {
                    window.initQuestionStage();
                } else if (stageName === 'review-counter' && typeof window.initReviewCounterStage === 'function') {
                    window.initReviewCounterStage();
                } else if (stageName === 'answer-sheet' && typeof window.initAnswerSheetStage === 'function') {
                    window.initAnswerSheetStage();
                }
            }
        };

        function determineTimelineStage(initialParam) {
            const now = Date.now() + (window.clockOffset || 0);
            const sched = window.getQuizScheduleTimeMs();
            const qflowStart = sched + 10000;
            const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + window.quizId) === 'true' ||
                                 localStorage.getItem('qx_used_switch_' + window.quizId) === 'true';
            const baseQ = window.totalQuestionsCount || 10;
            const userQCount = isSwitchUsed ? (baseQ + 1) : baseQ;
            const userQflowEnd = qflowStart + (userQCount * 17000);
            const userReviewEnd = userQflowEnd + 10000;
            if (now >= userReviewEnd) {
                if (initialParam === 'leaderboard') return 'leaderboard';
                return 'answer-sheet';
            }

            if (now < sched) {
                return 'waiting';
            } else if (now < qflowStart) {
                return 'start-counter';
            } else if (now < userQflowEnd) {
                return 'question';
            } else if (now < userReviewEnd) {
                return 'review-counter';
            } else {
                return 'answer-sheet';
            }
        }

        function syncActiveTimelineStageOnWakeUp() {
            if (window.checkAndHandleQuizExpiry && window.checkAndHandleQuizExpiry()) {
                return;
            }
            if (!window.QuizSpa) return;
            const targetStage = determineTimelineStage();
            const current = window.QuizSpa.currentStage;

            // If currently on question stage and speed round is still within total question time, do NOT interrupt user unless completed
            if (current === 'question' && targetStage !== 'question') {
                const now = Date.now() + (window.clockOffset || 0);
                const sched = window.getQuizScheduleTimeMs();
                const qflowStart = sched + 10000;
                const baseQ = window.totalQuestionsCount || 10;
                const isSwitchUsed = sessionStorage.getItem('qx_used_switch_' + window.quizId) === 'true' ||
                                     localStorage.getItem('qx_used_switch_' + window.quizId) === 'true';
                const userQCount = isSwitchUsed ? (baseQ + 1) : baseQ;
                const userQflowEnd = qflowStart + (userQCount * 17000);
                if (now < userQflowEnd && !window.isQuizUserCompleted()) {
                    return;
                }
            }

            // If user is on waiting, start-counter, or earlier stage and time has moved forward
            if (current !== targetStage && (current === 'waiting' || current === 'start-counter' || targetStage === 'answer-sheet' || targetStage === 'review-counter')) {
                console.log('[Quiz Screen On / WakeUp Sync] Switching stage from', current, 'to', targetStage);
                window.QuizSpa.switchStage(targetStage);
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                syncActiveTimelineStageOnWakeUp();
            }
        });
        window.addEventListener('focus', syncActiveTimelineStageOnWakeUp);
        window.addEventListener('pageshow', syncActiveTimelineStageOnWakeUp);

        // Real-time WebSocket synchronization (Laravel Echo)
        function initQuizWebSocket() {
            if (window.Echo && window.quizId) {
                try {
                    window.Echo.channel('quiz.' + window.quizId)
                        .listen('QuizStarted', (e) => {
                            console.log('[WebSocket] QuizStarted:', e);
                            if (e.start_time_ms) {
                                window.scheduledTimeMs = e.start_time_ms;
                                sessionStorage.setItem('qx_start_counter_time_' + window.quizId, e.start_time_ms);
                                localStorage.setItem('qx_start_counter_time_' + window.quizId, e.start_time_ms);
                            }
                            if (e.server_now_ms) {
                                window.serverNowMs = e.server_now_ms;
                                window.clockOffset = e.server_now_ms - Date.now();
                            }
                            syncActiveTimelineStageOnWakeUp();
                        })
                        .listen('QuizStageSync', (e) => {
                            console.log('[WebSocket] QuizStageSync:', e);
                            if (e.server_now_ms) {
                                window.serverNowMs = e.server_now_ms;
                                window.clockOffset = e.server_now_ms - Date.now();
                            }
                            if (e.stage && window.QuizSpa) {
                                window.QuizSpa.switchStage(e.stage);
                            }
                        })
                        .listen('QuizTimeSync', (e) => {
                            if (e.server_now_ms) {
                                window.serverNowMs = e.server_now_ms;
                                window.clockOffset = e.server_now_ms - Date.now();
                            }
                            if (e.scheduled_time_ms) {
                                window.scheduledTimeMs = e.scheduled_time_ms;
                            }
                            syncActiveTimelineStageOnWakeUp();
                        });
                } catch (err) {
                    console.warn('[WebSocket] Init channel error:', err);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Immediate check for quiz expiry
            if (window.checkAndHandleQuizExpiry && window.checkAndHandleQuizExpiry()) {
                return;
            }

            // Periodic 1-second quiz expiry check
            const periodicExpiryTimer = setInterval(() => {
                if (window.checkAndHandleQuizExpiry && window.checkAndHandleQuizExpiry()) {
                    clearInterval(periodicExpiryTimer);
                }
            }, 1000);
            if (window.QuizSpa && window.QuizSpa.registerTimer) {
                window.QuizSpa.registerTimer(periodicExpiryTimer);
            }

            // 1. Preload all MP3 sounds into browser memory immediately
            if (typeof window.preloadAllQuizSounds === 'function') {
                window.preloadAllQuizSounds();
            }

            // 2. Initialize real-time WebSocket sync
            initQuizWebSocket();

            const initial = '{{ $initialStage ?? 'waiting' }}';
            const targetStage = determineTimelineStage(initial);

            try {
                const targetPath = `/fsrapp/quizes/${targetStage}/${window.quizId}`;
                if (window.location.pathname !== targetPath) {
                    history.replaceState({
                        stage: targetStage,
                        quizId: window.quizId
                    }, '', targetPath);
                }
            } catch (e) {}

            window.QuizSpa.switchStage(targetStage);
        });

        // Global back button listener: if on answer-sheet or leaderboard or completed, redirect directly to home
        window.addEventListener('popstate', function(e) {
            if (window.QuizSpa && ['answer-sheet', 'leaderboard'].includes(window.QuizSpa.currentStage)) {
                window.location.replace('/fsrapp/home');
                return;
            }
            if (window.isQuizUserCompleted && window.isQuizUserCompleted()) {
                window.location.replace('/fsrapp/home');
                return;
            }
        });
    </script>
@endsection
