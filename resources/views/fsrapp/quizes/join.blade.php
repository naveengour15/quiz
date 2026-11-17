@extends('fsrapp.layouts.app')

@section('content')
    <main class="min-h-screen px-container-margin pt-6 pb-8 flex flex-col justify-between kbc-stage text-white relative">
        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>

        <!-- Top Header & Mute Controls -->
        <div class="relative z-[1] flex items-center justify-between w-full mb-3">
            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/fsrapp/home'"
                class="kbc-icon-badge">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            </button>
            <span class="font-label-caps kbc-title-glow text-[13px] tracking-widest font-bold">FSRQUIZ</span>
            <button id="kbc-mute-toggle" class="kbc-icon-badge" onclick="toggleGlobalMute()">
                <span class="material-symbols-outlined text-[18px]">volume_up</span>
            </button>
        </div>

        <!-- Top Refresh Warning Note -->
        <div class="relative z-[2] w-full max-w-md mx-auto mb-3">
            <div
                class="flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl bg-amber-500/15 border border-amber-400/40 text-amber-200 text-xs font-semibold shadow-lg text-center leading-snug">
                <span class="text-sm">⚠️</span>
                <span>Do not refresh or reload this page.</span>
            </div>
        </div>

        <!-- Main KBC Card -->
        <div class="relative z-[1] w-full max-w-md mx-auto my-auto space-y-6">
            <div class="text-center space-y-2">
                <div
                    class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-primary/20 border border-primary/40 text-primary font-label-caps text-[11px] font-bold tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-mint animate-ping"></span>
                    STEP 1: JOIN QUIZ
                </div>
                <h1 class="font-display text-2xl font-black text-white kbc-title-glow uppercase tracking-wide">
                    {{ $quiz->title ?? 'Live Challenge' }}
                </h1>
            </div>

            <div class="glass-card kbc-card kbc-glow-inner rounded-3xl p-6 shadow-2xl space-y-5">
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="bg-surface-container/60 rounded-2xl p-4 border border-border-glass">
                        <p class="font-label-caps text-[10px] text-on-surface-variant">PRIZE POOL</p>
                        <p class="font-headline-md text-xl font-bold text-tertiary mt-1">
                            ₹{{ $quiz->total_reward_amount ?? '500' }}</p>
                    </div>
                    <div class="bg-surface-container/60 rounded-2xl p-4 border border-border-glass">
                        <p class="font-label-caps text-[10px] text-on-surface-variant">QUESTIONS</p>
                        <p class="font-headline-md text-xl font-bold text-mint mt-1">{{ $quiz->question_limit ?? '10' }} Qs
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5 pt-2 border-t border-border-glass">
                    <div class="flex justify-between items-center text-xs text-on-surface-variant">
                        <span>Audio Engine:</span>
                        <span class="text-mint font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">cached</span> Single Fetch Cache
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-on-surface-variant">
                        <span>Questions & Options:</span>
                        <span class="text-primary font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">dataset</span> Session Storage
                        </span>
                    </div>
                </div>

                <button id="btn-join-quiz" onclick="handleJoinQuiz()"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-primary-container via-purple-600 to-secondary-container text-white font-action font-bold text-lg shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2 group cursor-pointer">
                    <span>Join Quiz Now</span>
                    <span
                        class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-[1] text-center font-label-caps text-[11px] text-on-surface-variant/60">
            FSRQuiz Session-Cached Engine
        </div>
    </main>

    <script>
        // Check local join status immediately: agar user pehle se join hai toh seedha quiz ke andar bhejo
        (function() {
            try {
                const quizId = "{{ $id }}";
                if (localStorage.getItem('quiz_joined_' + quizId) === '1' || localStorage.getItem('fsr_quiz_joined_' + quizId) === '1') {
                    const targetUrl = "{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.waiting', $id) : route('fsrapp.quizes.waiting', $id) }}";
                    window.location.replace(targetUrl);
                    return;
                }
            } catch (e) {}
        })();

        // Initialize sound cache ONE TIME on Join Page
        window.QuizSoundEngine = (() => {
            let soundsCache = {};
            let preloadedAudios = {};

            async function initSoundCache() {
                try {
                    const cached = sessionStorage.getItem('qx_cached_sound_urls');
                    if (cached) {
                        soundsCache = JSON.parse(cached);
                    } else {
                        const res = await fetch('/fsrapp/quizes/api/sounds', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (res.ok) {
                            const json = await res.json();
                            if (json && json.data) {
                                soundsCache = json.data;
                                sessionStorage.setItem('qx_cached_sound_urls', JSON.stringify(soundsCache));
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Sound cache init failed', e);
                }

                Object.keys(soundsCache).forEach(key => {
                    const url = soundsCache[key];
                    if (url && !preloadedAudios[key]) {
                        const a = new Audio();
                        a.preload = 'auto';
                        a.src = url;
                        preloadedAudios[key] = a;
                    }
                });
            }

            function playSound(key) {
                if (window.isGlobalMuted && window.isGlobalMuted()) return;
                initSoundCache();
                const url = soundsCache[key];
                if (!url) return;
                if (window.playQuizAudio) {
                    window.playQuizAudio(url, 0.7);
                }
            }

            return {
                initSoundCache,
                playSound
            };
        })();

        document.addEventListener('DOMContentLoaded', () => {
            window.QuizSoundEngine.initSoundCache();
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_join') === '1' || urlParams.get('auto_join') === 'true') {
                handleJoinQuiz();
            }
        });

        async function handleJoinQuiz() {
            if (window.markAudioUnlocked) {
                window.markAudioUnlocked();
            } else {
                try {
                    sessionStorage.setItem('fsr_audio_unlocked', '1');
                    sessionStorage.setItem('fsr_joined_from_home', '1');
                    localStorage.setItem('fsr_audio_unlocked', '1');
                } catch(e) {}
            }
            const btn = document.getElementById('btn-join-quiz');
            btn.disabled = true;
            btn.innerHTML =
                '<span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> Joining...';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch("{{ route('fsrapp.quizes.join.post', $id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data && data.success) {
                    try {
                        localStorage.setItem('quiz_joined_{{ $id }}', '1');
                        localStorage.setItem('fsr_quiz_joined_{{ $id }}', '1');
                    } catch(e) {}
                    window.location.href = data.redirect || "{{ route('fsrapp.quizes.waiting', $id) }}";
                } else if (data && data.redirect) {
                    if (window.showAppAlertModal) {
                        window.showAppAlertModal(data.message ||
                            '⚠️ Quiz player limit complete try next time',
                            'warning', data.redirect);
                    } else {
                        window.location.href = data.redirect;
                    }
                } else {
                    if (window.showAppAlertModal) {
                        window.showAppAlertModal(data.message || 'Could not join quiz.', 'warning',
                            "{{ route('fsrapp.home') }}");
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<span>Join Quiz Now</span>';
                }
            } catch (e) {
                console.error('Join failed', e);
                if (window.showAppAlertModal) {
                    window.showAppAlertModal('Quiz join karne mein dikkat aayi. Kripya punah prayas karein.', 'warning',
                        "{{ route('fsrapp.home') }}");
                } else {
                    window.location.href = "{{ route('fsrapp.home') }}";
                }
            }
        }
    </script>
@endsection
