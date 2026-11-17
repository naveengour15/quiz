@extends('fsrapp.layouts.app')

@section('content')
<style>
    html, body {
        touch-action: pan-y !important;
        -webkit-overflow-scrolling: touch !important;
        overflow-y: auto !important;
    }
    #fff-quiz-spa-container,
    #stage-waiting,
    #stage-start-counter,
    #stage-question,
    #stage-review-counter,
    #stage-answer-sheet,
    #stage-leaderboard,
    .fff-stage-component {
        touch-action: pan-y !important;
        -webkit-overflow-scrolling: touch !important;
        overflow-y: visible !important;
    }
</style>
<script>
    window.fffQuizId = {{ $id }};
    try {
        localStorage.setItem('quiz_joined_' + window.fffQuizId, '1');
        localStorage.setItem('fsr_quiz_joined_' + window.fffQuizId, '1');
    } catch(e) {}
    window.serverNowMs = {{ $serverNowMs ? (int)$serverNowMs : 'Date.now()' }};
    window.clockOffset = window.serverNowMs - Date.now();
    window.scheduledTimeMs = {{ $scheduledTimeMs ? (int)$scheduledTimeMs : 'null' }};
    window.anssheetReleaseTimeMs = {{ isset($anssheetReleaseTimeMs) && $anssheetReleaseTimeMs ? (int)$anssheetReleaseTimeMs : 'null' }};
    window.quizCompleteStatus = "{{ $quiz->complete_status ?? '' }}";
    window.totalQuestionsCount = {{ (int) ($totalQuestionsCount ?? (!empty($quiz->question_limit) ? $quiz->question_limit : 15)) }};
    window.initialSheetData = @json($initialSheetData ?? null);
    window.initialFffQuestions = @json($initialFffQuestions ?? []);
    window.encryptedFffPayload = @json($encryptedFffPayload ?? '');
    window.initialUserAnswers = @json($initialUserAnswers ?? (object)[]);

    window.isAllAnswered = {{ ($isAllAnswered ?? false) ? 'true' : 'false' }};
    window.userFinishTimeMs = {{ isset($userFinishTimeMs) && $userFinishTimeMs ? (int)$userFinishTimeMs : 'null' }};

    if (window.isAllAnswered && window.userFinishTimeMs) {
        try {
            sessionStorage.setItem('fff_completed_' + window.fffQuizId, 'true');
            sessionStorage.setItem('fff_finish_reason_' + window.fffQuizId, 'all_answered');
            sessionStorage.setItem('fff_finish_time_' + window.fffQuizId, window.userFinishTimeMs);
            localStorage.setItem('fff_completed_' + window.fffQuizId, 'true');
            localStorage.setItem('fff_finish_reason_' + window.fffQuizId, 'all_answered');
            localStorage.setItem('fff_finish_time_' + window.fffQuizId, window.userFinishTimeMs);
        } catch(e) {}
    }

    window.getFffQuizScheduleTimeMs = function() {
        const startCounterKey = 'fff_start_counter_time_' + window.fffQuizId;
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

    window.getFffQuestionStartTimeMs = function() {
        const sched = window.getFffQuizScheduleTimeMs();
        const qflowKey = 'fff_qflow_start_' + window.fffQuizId;
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
            sessionStorage.removeItem('fff_completed_' + window.fffQuizId);
            localStorage.removeItem('fff_completed_' + window.fffQuizId);
            sessionStorage.removeItem('fff_reviewed_' + window.fffQuizId);
            localStorage.removeItem('fff_reviewed_' + window.fffQuizId);
            sessionStorage.removeItem('fff_finish_reason_' + window.fffQuizId);
            localStorage.removeItem('fff_finish_reason_' + window.fffQuizId);
        } catch(e) {}
    }

    // Helper to check if current user completed all FFF quiz submissions or finished the round
    window.isFffUserCompleted = function() {
        if (window.__isFffCompleted) return true;
        try {
            const isCompleted = sessionStorage.getItem('fff_completed_' + window.fffQuizId) === 'true' ||
                localStorage.getItem('fff_completed_' + window.fffQuizId) === 'true' ||
                window.isAllAnswered;
            if (isCompleted) {
                window.__isFffCompleted = true;
                return true;
            }
            const att = parseInt(sessionStorage.getItem('fff_attempted_' + window.fffQuizId) || localStorage.getItem('fff_attempted_' + window.fffQuizId) || '0', 10);
            if (window.totalQuestionsCount > 0 && att >= window.totalQuestionsCount) {
                window.__isFffCompleted = true;
                return true;
            }
        } catch (e) {}
        return false;
    };

    // Helper to check if current user has attempted any answers (used only for expiry guard so user is not marked OUT)
    window.hasUserAttemptedAnyAnswers = function() {
        try {
            const att = parseInt(sessionStorage.getItem('fff_attempted_' + window.fffQuizId) || localStorage.getItem('fff_attempted_' + window.fffQuizId) || '0', 10);
            if (att > 0) return true;
            const sheetKey = 'fff_anssheet_' + window.fffQuizId;
            const rawSheet = sessionStorage.getItem(sheetKey) || localStorage.getItem(sheetKey);
            if (rawSheet) {
                const sheet = JSON.parse(rawSheet);
                if (sheet && typeof sheet === 'object' && Object.keys(sheet).length > 0) return true;
            }
        } catch (e) {}
        return false;
    };

    // FFF Quiz Expiry Time = Quiz.schedule_time + 10s (start counter) + 75s (speed round question time) = 85s
    window.getFffQuizExpiryTimeMs = function() {
        const sched = window.getFffQuizScheduleTimeMs();
        return sched + 10000 + 75000; // 85 seconds total
    };

    // Additional validation: If total answer submission time completed and user did not submit answers, automatically exit
    window.checkAndHandleFffQuizExpiry = function() {
        if (window.__isExitingFffQuiz) return true;
        if (window.isFffUserCompleted() || window.hasUserAttemptedAnyAnswers()) return false;

        const now = Date.now() + (window.clockOffset || 0);
        const expiryTimeMs = window.getFffQuizExpiryTimeMs();

        if (now >= expiryTimeMs) {
            console.warn('[FFF Quiz Expiry Validation] Total answer submission time completed. Automatically exiting quiz.', {
                now: now,
                expiryTimeMs: expiryTimeMs
            });
            window.__isExitingFffQuiz = true;

            if (window.FffSpa && typeof window.FffSpa.clearAllTimers === 'function') {
                window.FffSpa.clearAllTimers();
            }
            if (window.stopAllQuizAudios) {
                window.stopAllQuizAudios();
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(
                        `/fsrapp/fff/api/mark-out/${window.fffQuizId}`,
                        new Blob([JSON.stringify({
                            reason: 'Schedule Time Passed',
                            _token: csrf
                        })], { type: 'application/json' })
                    );
                }
            } catch (e) {}

            try {
                sessionStorage.removeItem('active_fff_quiz_id');
                sessionStorage.removeItem('fff_active_stage_' + window.fffQuizId);
            } catch (e) {}

            window.location.replace("{{ route('fsrapp.home') }}");
            return true;
        }
        return false;
    };

    function determineFffTimelineStage(initialParam) {
        const now = Date.now() + (window.clockOffset || 0);
        const sched = window.getFffQuizScheduleTimeMs();
        const qflowStart = sched + 10000; // 10s start counter
        const qflowEnd = qflowStart + 75000; // 75s questions duration
        const globalReviewEnd = qflowEnd + 10000; // 10s review counter (sched + 95s total)

        const isCompleted = window.isFffUserCompleted();

        // Additional validation: If total answer submission time completed and user did not submit answers, automatically exit
        if (now >= qflowEnd && !isCompleted && !window.hasUserAttemptedAnyAnswers()) {
            if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                return 'waiting';
            }
        }

        const finishReason = sessionStorage.getItem('fff_finish_reason_' + window.fffQuizId) || 
                             localStorage.getItem('fff_finish_reason_' + window.fffQuizId) ||
                             (window.isAllAnswered ? 'all_answered' : null);
        const finishTime = parseInt(sessionStorage.getItem('fff_finish_time_' + window.fffQuizId) || 
                                    localStorage.getItem('fff_finish_time_' + window.fffQuizId) || 
                                    (window.userFinishTimeMs || '0'), 10);

        let reviewEndTime = globalReviewEnd;
        if (finishReason === 'all_answered' && finishTime > 0 && finishTime < qflowEnd) {
            reviewEndTime = Math.min(globalReviewEnd, finishTime + 10000);
        }

        if (window.quizCompleteStatus === 'completed') {
            if (initialParam === 'answer-sheet') return 'answer-sheet';
            return 'leaderboard';
        }

        const hasFffReviewed = sessionStorage.getItem('fff_reviewed_' + window.fffQuizId) === 'true' ||
                               localStorage.getItem('fff_reviewed_' + window.fffQuizId) === 'true';

        // When user has completed all questions or the 75s round has elapsed
        if (isCompleted || now >= qflowEnd) {
            if (now >= reviewEndTime || hasFffReviewed) {
                if (['start-counter', 'question', 'review-counter'].includes(initialParam)) {
                    window.location.replace('/fsrapp/home');
                    return 'answer-sheet';
                }
                if (initialParam === 'leaderboard') return 'leaderboard';
                return 'answer-sheet';
            }
            return 'review-counter';
        }

        if (initialParam === 'answer-sheet' || initialParam === 'leaderboard') {
            if (now >= reviewEndTime || (isCompleted && finishReason === 'all_answered' && now >= (finishTime + 10000))) {
                if (isCompleted) {
                    return initialParam;
                } else if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                    return 'waiting';
                }
                return initialParam;
            }
        }

        if (now < sched) {
            return 'waiting';
        } else if (now < qflowStart) {
            return 'start-counter';
        } else if (now < qflowEnd) {
            return 'question';
        } else if (now < globalReviewEnd) {
            if (hasFffReviewed) {
                return 'answer-sheet';
            }
            return 'review-counter';
        } else {
            if (['start-counter', 'question', 'review-counter'].includes(initialParam)) {
                window.location.replace('/fsrapp/home');
                return 'answer-sheet';
            }
            return 'answer-sheet';
        }
    }

    // Immediate guard for user OUT status & expiry
    (function checkImmediateQuizOut() {
        try {
            if (window.isFffUserCompleted()) {
                window.__isFffCompleted = true;
            } else {
                window.checkAndHandleFffQuizExpiry();
            }
        } catch(e) {}
    })();

    // Default sounds matching standard quiz
    window.FFF_DEFAULT_SOUNDS = {
        background_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/background_sound_1787214373.mp3",
        one_min_before: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/one_min_before_1789466274.mp3",
        one_minute_before: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/one_min_before_1789466274.mp3",
        one_minute_before_quiz_start_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/one_min_before_1789466274.mp3",
        start_quiz_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/start_quiz_sound_1787231006.mp3",
        counter_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/counter_sound_1787230996.mp3",
        question_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/question_appear_sound_1787231022.mp3",
        say_ans_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/say_ans_appear_sound_1787231057.mp3",
        ans_select_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/say_ans_appear_sound_1787231057.mp3",
        answer_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/answer_appear_sound_1787231046.mp3",
        answer_sheet_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/answer_sheet_appear_sound_1787231086.mp3",
        leaderboard_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/leaderboard_appear_sound_1787231102.mp3"
    };

    window.getSoundUrl = function(key) {
        try {
            const cached = sessionStorage.getItem('qx_sound_settings') ||
                           sessionStorage.getItem('qx_cached_sound_urls') ||
                           localStorage.getItem('qx_sound_settings') ||
                           localStorage.getItem('qx_cached_sound_urls');
            if (cached) {
                const sounds = JSON.parse(cached);
                if (sounds && sounds[key]) return sounds[key];
                if ((key === 'one_min_before' || key === 'one_minute_before' || key === 'one_minute_before_quiz_start_sound') && sounds) {
                    const match = sounds.one_min_before || sounds.one_minute_before || sounds.one_minute_before_quiz_start_sound;
                    if (match) return match;
                }
            }
        } catch(e) {}
        if (key === 'one_min_before' || key === 'one_minute_before' || key === 'one_minute_before_quiz_start_sound') {
            return window.FFF_DEFAULT_SOUNDS['one_min_before'] || (window.DEFAULT_SOUND_URLS && (window.DEFAULT_SOUND_URLS.one_min_before || window.DEFAULT_SOUND_URLS.one_minute_before_quiz_start_sound)) || "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/one_min_before_1789466274.mp3";
        }
        return window.FFF_DEFAULT_SOUNDS[key] || (window.DEFAULT_SOUND_URLS && window.DEFAULT_SOUND_URLS[key]) || null;
    };

    window.playNamedSound = function(key, volume = 0.7, loop = false, channelType = 'sfx') {
        if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return null;
        const url = window.getSoundUrl(key);
        if (!url) return null;
        if (window.playQuizAudio) {
            return window.playQuizAudio(url, volume, loop, channelType);
        }
        return null;
    };

    // Global sound cache preloader
    (async function initSoundSettingsCache() {
        try {
            if (!sessionStorage.getItem('qx_sound_settings')) {
                const res = await fetch('/fsrapp/quizes/api/sounds', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.data) {
                        sessionStorage.setItem('qx_sound_settings', JSON.stringify(json.data));
                        sessionStorage.setItem('qx_cached_sound_urls', JSON.stringify(json.data));
                    }
                }
            }
        } catch(e) {}
    })();

    // Compatibility Sound wrapper
    window.FffSound = {
        play: function(type) {
            if (type === 'tick') return window.playNamedSound('counter_sound', 0.7, false, 'sfx');
            if (type === 'start') return window.playNamedSound('start_quiz_sound', 0.8, false, 'sfx');
            if (type === 'click' || type === 'select') return null; // Say Answer Appear sound disabled
            if (type === 'question') return window.playNamedSound('question_appear_sound', 0.85, false, 'question');
            if (type === 'finish' || type === 'sheet') return window.playNamedSound('answer_sheet_appear_sound', 0.7, false, 'sfx');
            if (type === 'leaderboard') return window.playNamedSound('leaderboard_appear_sound', 0.6, true, 'bg');
        }
    };

    // Master SPA Controller
    window.FffSpa = {
        currentStage: '{{ $initialStage ?? "waiting" }}',
        activeTimers: [],

        registerTimer: function(t) {
            if (t) this.activeTimers.push(t);
        },

        clearAllTimers: function() {
            this.activeTimers.forEach(id => clearInterval(id));
            this.activeTimers = [];
            if (window._fffWaitingInterval) {
                clearInterval(window._fffWaitingInterval);
                window._fffWaitingInterval = null;
            }
            if (window._fffStartCounterInterval) {
                clearInterval(window._fffStartCounterInterval);
                window._fffStartCounterInterval = null;
            }
            if (window._fffReviewInterval) {
                clearInterval(window._fffReviewInterval);
                window._fffReviewInterval = null;
            }
            if (window.stopAllQuizAudios) {
                window.stopAllQuizAudios();
            }
        },

        showStage: function(stageName) {
            console.log('[FFF SPA] Showing Stage:', stageName);
            this.clearAllTimers();

            // If leaving waiting stage, hide audio unlock banner
            if (stageName !== 'waiting' && typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }

            this.currentStage = stageName;

            document.querySelectorAll('.fff-stage-component').forEach(function(el) {
                el.classList.add('hidden');
                el.style.display = 'none';
            });
            const target = document.getElementById('stage-' + stageName);
            if (target) {
                target.classList.remove('hidden');
                target.style.display = 'block';
            }

            try {
                history.replaceState(null, null, '/fsrapp/fff/' + stageName + '/' + window.fffQuizId);
            } catch(e) {}

            if (stageName === 'waiting') {
                if (typeof window.initFffWaitingTimer === 'function') window.initFffWaitingTimer();
            } else if (stageName === 'start-counter') {
                if (typeof window.startFffStartCounter === 'function') window.startFffStartCounter();
            } else if (stageName === 'question') {
                if (typeof window.initFffQuestionScreen === 'function') window.initFffQuestionScreen();
            } else if (stageName === 'review-counter') {
                if (typeof window.startFffReviewCounter === 'function') window.startFffReviewCounter();
            } else if (stageName === 'answer-sheet') {
                if (typeof window.loadFffAnswerSheet === 'function') window.loadFffAnswerSheet();
            } else if (stageName === 'leaderboard') {
                if (typeof window.loadFffLeaderboard === 'function') window.loadFffLeaderboard();
            }

            if (stageName === 'question' || stageName === 'start-counter') {
                if (typeof window.enforceQuizProtection === 'function') window.enforceQuizProtection();
                try {
                    document.documentElement.style.overflowY = 'auto';
                    document.body.style.overflowY = 'auto';
                    document.documentElement.style.touchAction = 'pan-y';
                    document.body.style.touchAction = 'pan-y';
                } catch(e) {}
            } else if (stageName === 'answer-sheet' || stageName === 'leaderboard') {
                if (typeof window.releaseQuizProtection === 'function') window.releaseQuizProtection();
                try {
                    document.documentElement.style.overflowY = 'auto';
                    document.body.style.overflowY = 'auto';
                    document.documentElement.style.touchAction = 'pan-y';
                    document.body.style.touchAction = 'pan-y';
                    history.pushState({ fffQuizActive: true, stage: stageName }, document.title, location.href);
                } catch(e) {}
            }

            if (typeof window.updateSpeakerIcon === 'function') {
                window.updateSpeakerIcon();
            }
        },

        handleExit: function(reason) {
            console.log('[FFF handleExit disabled]', reason);
        }
    };

    // ── Dedicated Strict Anti-Refresh & Safety Listeners for FFF Quiz ──────────
    function showFffAntiRefreshToast() {
        let toast = document.getElementById('fff-refresh-blocked-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'fff-refresh-blocked-toast';
            toast.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[9999] bg-error/95 text-white text-xs sm:text-sm font-bold px-4 py-2.5 rounded-full shadow-2xl border border-white/20 flex items-center gap-2 pointer-events-none transition-all duration-300 opacity-0 transform -translate-y-2';
            toast.innerHTML = '<span class="material-symbols-outlined text-[18px]">warning</span><span>Live Quiz: Page refresh karna mana hai!</span>';
            document.body.appendChild(toast);
        }
        toast.classList.remove('opacity-0', '-translate-y-2');
        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(() => {
            toast.classList.add('opacity-0', '-translate-y-2');
        }, 2500);
    }

    // 1. Intercept refresh keyboard shortcuts (F5, Ctrl+R, Cmd+R, Ctrl+F5, Ctrl+Shift+R)
    window.addEventListener('keydown', function(e) {
        if (window.FffSpa && ['question', 'start-counter'].includes(window.FffSpa.currentStage)) {
            if (
                e.key === 'F5' ||
                e.keyCode === 116 ||
                ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R' || e.keyCode === 82)) ||
                (e.ctrlKey && (e.key === 'F5' || e.keyCode === 116)) ||
                (e.shiftKey && (e.key === 'F5' || e.keyCode === 116)) ||
                (e.ctrlKey && e.shiftKey && (e.key === 'r' || e.key === 'R' || e.keyCode === 82))
            ) {
                e.preventDefault();
                e.stopPropagation();
                showFffAntiRefreshToast();
                return false;
            }

            // Intercept Alt + Left/Right navigation
            if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.keyCode === 37 || e.keyCode === 39)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            // Intercept Backspace navigation outside text fields
            if ((e.key === 'Backspace' || e.keyCode === 8) &&
                !['INPUT', 'TEXTAREA'].includes(e.target.tagName) &&
                !e.target.isContentEditable) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    }, true);



    // 3. Prevent accidental reload/tab close warning
    window.addEventListener('beforeunload', function(e) {
        if (window.__isExitingQuiz) return;
        if (window.FffSpa && ['question', 'start-counter'].includes(window.FffSpa.currentStage)) {
            e.preventDefault();
            e.returnValue = 'Live FFF quiz chal raha hai. Page refresh karna mana hai.';
            return e.returnValue;
        }
    });

    // 4. Trap back navigation: If on answer-sheet/leaderboard or completed, redirect directly to home
    window.addEventListener('popstate', function(e) {
        if (window.FffSpa && ['answer-sheet', 'leaderboard'].includes(window.FffSpa.currentStage)) {
            window.location.replace('/fsrapp/home');
            return;
        }
        if (window.isFffUserCompleted && window.isFffUserCompleted()) {
            window.location.replace('/fsrapp/home');
            return;
        }
        if (window.FffSpa && ['question', 'start-counter'].includes(window.FffSpa.currentStage)) {
            try {
                history.pushState({ fffQuizActive: true, stage: window.FffSpa.currentStage }, document.title, location.href);
            } catch(err) {}
        }
    });
</script>

<div id="fff-quiz-spa-container">
    <div id="stage-waiting" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'waiting' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.waiting')
    </div>
    <div id="stage-start-counter" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'start-counter' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.start-counter')
    </div>
    <div id="stage-question" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'question' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.question')
    </div>
    <div id="stage-review-counter" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'review-counter' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.review-counter')
    </div>
    <div id="stage-answer-sheet" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'answer-sheet' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.answer-sheet')
    </div>
    <div id="stage-leaderboard" class="fff-stage-component {{ ($initialStage ?? 'waiting') === 'leaderboard' ? '' : 'hidden' }}">
        @include('fsrapp.fff.components.leaderboard')
    </div>
</div>

<script>
    function syncActiveFffTimelineStageOnWakeUp() {
        if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
            return;
        }
        if (!window.FffSpa) return;
        const targetStage = typeof determineFffTimelineStage === 'function' ? determineFffTimelineStage() : window.FffSpa.currentStage;
        const current = window.FffSpa.currentStage;

        // If currently on question stage and speed round is still within 75s, do NOT interrupt user unless round completed
        if (current === 'question' && targetStage !== 'question') {
            const now = Date.now() + (window.clockOffset || 0);
            const sched = window.getFffQuizScheduleTimeMs();
            const qflowEnd = sched + 10000 + 75000;
            if (now < qflowEnd && !window.isFffUserCompleted()) {
                return;
            }
        }

        if (current !== targetStage && (current === 'waiting' || current === 'start-counter' || targetStage === 'answer-sheet' || targetStage === 'review-counter')) {
            console.log('[FFF Screen On / WakeUp Sync] Switching stage from', current, 'to', targetStage);
            window.FffSpa.showStage(targetStage);
        }
    }

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            syncActiveFffTimelineStageOnWakeUp();
        }
    });
    window.addEventListener('focus', syncActiveFffTimelineStageOnWakeUp);
    window.addEventListener('pageshow', syncActiveFffTimelineStageOnWakeUp);

    // Real-time WebSocket synchronization for FFF Quiz (Laravel Echo)
    function initFffWebSocket() {
        if (window.Echo && window.fffQuizId) {
            try {
                const channelName = 'quiz.' + window.fffQuizId;
                window.Echo.channel(channelName)
                    .listen('QuizStarted', function(e) {
                        console.log('[FFF WebSocket] QuizStarted:', e);
                        if (e.start_time_ms) {
                            window.scheduledTimeMs = e.start_time_ms;
                            sessionStorage.setItem('fff_start_counter_time_' + window.fffQuizId, e.start_time_ms);
                            localStorage.setItem('fff_start_counter_time_' + window.fffQuizId, e.start_time_ms);
                        }
                        if (e.server_now_ms) {
                            window.serverNowMs = e.server_now_ms;
                            window.clockOffset = e.server_now_ms - Date.now();
                        }
                        syncActiveFffTimelineStageOnWakeUp();
                    })
                    .listen('QuizStageSync', function(e) {
                        console.log('[FFF WebSocket] QuizStageSync:', e);
                        if (e.server_now_ms) {
                            window.serverNowMs = e.server_now_ms;
                            window.clockOffset = e.server_now_ms - Date.now();
                        }
                        if (e.stage && window.FffSpa) {
                            window.FffSpa.showStage(e.stage);
                        }
                    })
                    .listen('QuizTimeSync', function(e) {
                        if (e.server_now_ms) {
                            window.serverNowMs = e.server_now_ms;
                            window.clockOffset = e.server_now_ms - Date.now();
                        }
                        if (e.scheduled_time_ms) {
                            window.scheduledTimeMs = e.scheduled_time_ms;
                        }
                    });
            } catch (err) {
                console.warn('[FFF WebSocket] Init channel error:', err);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Immediate check for FFF quiz expiry
        if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
            return;
        }

        // Periodic 1-second FFF quiz expiry check
        const periodicFffExpiryTimer = setInterval(() => {
            if (window.checkAndHandleFffQuizExpiry && window.checkAndHandleFffQuizExpiry()) {
                clearInterval(periodicFffExpiryTimer);
            }
        }, 1000);
        if (window.FffSpa && window.FffSpa.registerTimer) {
            window.FffSpa.registerTimer(periodicFffExpiryTimer);
        }

        // 1. Preload all MP3 sounds into browser memory immediately
        if (typeof window.preloadAllQuizSounds === 'function') {
            window.preloadAllQuizSounds();
        }

        // 2. Initialize real-time WebSocket sync
        initFffWebSocket();

        const initial = '{{ $initialStage ?? "waiting" }}';
        const targetStage = typeof determineFffTimelineStage === 'function' ? determineFffTimelineStage(initial) : initial;
        if (window.FffSpa) {
            window.FffSpa.showStage(targetStage);
        }
    });
</script>
@endsection
