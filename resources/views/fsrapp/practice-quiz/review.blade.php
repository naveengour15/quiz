@extends('fsrapp.layouts.app')

@push('styles')
    <style>
        /* Premium translucent backgrounds matching History Answer Sheet */
        .ans-card {
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .ans-card.correct {
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .ans-card.correct:hover {
            background: rgba(16, 185, 129, 0.07);
            border-color: rgba(16, 185, 129, 0.4);
        }

        .ans-card.incorrect {
            background: rgba(239, 68, 68, 0.04);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .ans-card.incorrect:hover {
            background: rgba(239, 68, 68, 0.07);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .status-badge {
            font-family: var(--font-display, sans-serif);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.05em;
        }

        .time-badge {
            font-size: 10px;
            color: rgba(232, 221, 255, 0.5);
            font-family: monospace;
            background: rgba(255, 255, 255, 0.06);
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .q-text {
            font-family: var(--font-display, sans-serif);
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.5;
            margin: 12px 0;
        }

        .q-sub-text {
            font-size: 12px;
            color: rgba(232, 221, 255, 0.6);
            line-height: 1.5;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-family: sans-serif;
        }

        .ans-info-row {
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ans-info-row p {
            margin: 0;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .label-correct {
            color: #10b981;
            font-weight: 700;
            font-family: var(--font-display, sans-serif);
        }

        .val-correct {
            color: #a7f3d0;
            font-weight: 600;
        }

        .label-wrong {
            color: #ef4444;
            font-weight: 700;
            font-family: var(--font-display, sans-serif);
        }

        .val-wrong {
            color: #fca5a5;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div id="practice-review-header-placeholder"></div>
    <main class="min-h-screen px-container-margin pt-20 pb-10 kbc-stage">
        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
        <div id="review-root" class="relative z-[1] flex flex-col items-center py-16">
            <div class="kbc-drumroll-ring"></div>
            <p class="text-on-surface-variant mt-5 kbc-title-glow font-headline-md">Tallying your practice answers...</p>
            <p class="text-tertiary font-label-caps text-sm mt-2">Answers revealing in <span id="review-tally-counter"
                    class="font-display text-xl text-primary font-bold">10</span>s</p>

         @include('fsrapp.home.components.banners', [ 'banners' => $banners, 'pageType' => 'practice_before_answer_sheet'])
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.enforceQuizProtection) window.enforceQuizProtection();
            const quizId = @json($id ?? null) || (typeof params !== 'undefined' ? params.id : null) || (
                location.pathname.split('/').pop());
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function qs(sel, root = document) {
                return root.querySelector(sel);
            }

            function unwrap(json) {
                if (json == null) return json;
                if (json.data !== undefined) return json.data;
                if (json.result !== undefined) return json.result;
                return json;
            }

            function escapeHtml(s) {
                return (s ?? '').toString().replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c]));
            }

            function navigateTo(path) {
                window.location.href = path;
            }

            async function practiceApi(path, options = {}) {
                const url = path.startsWith('http') ? path : (path.startsWith('/fsrapp') || path.startsWith(
                        '/practice-quiz') ? window.location.origin + path : window.location.origin +
                    '/fsrapp' + path);
                const method = options.method || 'GET';
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                };
                try {
                    const res = await fetch(url, {
                        method,
                        headers,
                        body: options.body ? (typeof options.body === 'string' ? options.body : JSON
                            .stringify(options.body)) : undefined
                    });
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        data = {
                            raw: text
                        };
                    }
                    if (!res.ok) {
                        throw new Error((data && (data.message || data.error)) ||
                            `Request failed (${res.status})`);
                    }
                    return data;
                } catch (e) {
                    console.error('[Practice Quiz Request Error]', e);
                    throw e;
                }
            }

            const hdr = qs('#practice-review-header-placeholder');
            if (hdr) {
                if (typeof topHeader === 'function') {
                    hdr.outerHTML = topHeader({
                        title: 'Practice Answer Sheet',
                        showBack: true,
                        backTo: '/fsrapp/home'
                    });
                } else {
                    hdr.innerHTML = `
                        <header class="fixed top-0 w-full z-50 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2" style="background: rgba(13, 6, 32, 0.88); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                            <div class="flex justify-between items-center px-4 h-full w-full">
                                <div class="flex items-center gap-3">
                                    <a href="/fsrapp/home" class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10 text-[#e8ddff] hover:bg-white/10 active:scale-95 transition-all">
                                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                                    </a>
                                    <h1 class="font-display text-lg font-bold text-[#e8ddff] tracking-wide">Practice Answer Sheet</h1>
                                </div>
                            </div>
                        </header>
                    `;
                }
            }

            let soundUrls = {};
            async function initPracticeSounds() {
                try {
                    const cached = sessionStorage.getItem('qx_sound_settings') ||
                                   sessionStorage.getItem('qx_cached_sound_urls') ||
                                   localStorage.getItem('qx_sound_settings');
                    if (cached) {
                        soundUrls = JSON.parse(cached);
                    }
                    if (!soundUrls || !soundUrls.answer_sheet_appear_sound) {
                        const res = await fetch("{{ route('fsrapp.quizes.api.sounds') }}");
                        const json = await res.json();
                        if (json && json.data) {
                            soundUrls = json.data;
                            sessionStorage.setItem('qx_cached_sound_urls', JSON.stringify(soundUrls));
                            sessionStorage.setItem('qx_sound_settings', JSON.stringify(soundUrls));
                        }
                    }
                    if (typeof Sound !== 'undefined' && Sound.setCustomSounds) {
                        Sound.setCustomSounds(soundUrls);
                    }
                    Object.keys(soundUrls).forEach(k => {
                        if (soundUrls[k] && window.preloadAudioUrl) {
                            window.preloadAudioUrl(soundUrls[k]);
                        }
                    });
                } catch(e) {}
            }
            initPracticeSounds();

            function playSoundByKey(key, volume = 0.75) {
                if (window.isGlobalMuted && window.isGlobalMuted()) return;
                let url = soundUrls[key];
                if (!url && key === 'answer_sheet_appear_sound') url = soundUrls.answer_sheet_appear_sound;
                if (!url && key === 'counter_sound') url = soundUrls.counter_sound;

                if (url && window.playQuizAudio) {
                    window.playQuizAudio(url, volume);
                } else if (typeof Sound !== 'undefined') {
                    if (key === 'answer_sheet_appear_sound' && Sound.answerSheet) Sound.answerSheet();
                    else if (key === 'counter_sound' && Sound.tick) Sound.tick(true);
                }
            }

            if (window.stopAllQuizAudios) window.stopAllQuizAudios();
            if (typeof Sound !== 'undefined') {
                if (Sound.ensureCtx) Sound.ensureCtx();
                if (Sound.stopBackground) Sound.stopBackground();
            }

            let log = [];
            let apiFailed = false;

            let summaryStats = {
                total_time: 0,
                correct_answers: 0,
                wrong_answers: 0,
                total_questions: 10
            };
            let winnerComp = null;

            const fetchP = practiceApi(`/fsrapp/practice-quiz/answer-sheet/${quizId}`, {
                    method: 'GET'
                })
                .then(res => {
                    const raw = unwrap(res) || res || {};
                    let list = [];
                    if (raw.result && Array.isArray(raw.result.answers)) {
                        list = raw.result.answers;
                        summaryStats.total_time = parseFloat(raw.result.total_time) || 0;
                        summaryStats.correct_answers = raw.result.correct_answers ?? 0;
                        summaryStats.wrong_answers = raw.result.wrong_answers ?? 0;
                        summaryStats.total_questions = raw.result.total_questions ?? list.length;
                        winnerComp = raw.result.winner_comparison || null;
                    } else if (Array.isArray(raw.answers)) {
                        list = raw.answers;
                        summaryStats.total_time = parseFloat(raw.total_time) || 0;
                        winnerComp = raw.winner_comparison || null;
                    } else if (Array.isArray(raw)) {
                        list = raw;
                    }

                    return list.map(item => {
                        const qText = item.question || item.question_text || 'Question';
                        const hindiText = item.hindi_question || item.hindi_question_text || null;
                        const audioUrl = item.question_audio || item.audio_url || null;
                        const mediaUrl = item.media_url || item.image_url || null;
                        const chosenText = item.selected_answer ?? item.your_answer ?? item
                            .user_answer ?? null;
                        const correctText = item.correct_answer ?? null;
                        const totalTime = item.total_time ?? null;
                        const showTime = item.show_time ?? null;
                        const ansTime = item.ans_time ?? null;
                        let isCorrect = item.is_correct;
                        if (isCorrect === undefined && chosenText != null && correctText != null) {
                            isCorrect = String(chosenText).trim().toLowerCase() === String(correctText)
                                .trim().toLowerCase();
                        }
                        return {
                            text: qText,
                            hindiText,
                            audioUrl,
                            mediaUrl,
                            chosenText,
                            correctText,
                            isCorrect,
                            totalTime,
                            showTime,
                            ansTime
                        };
                    });
                })
                .catch(e => {
                    apiFailed = true;
                    return [];
                });

            let tallySec = 10;
            // Play 10-second counter sound before answer sheet
            playSoundByKey('counter_sound');

            const tallyTimer = setInterval(() => {
                tallySec = Math.max(tallySec - 1, 0);
                const el = qs('#review-tally-counter');
                if (el) el.textContent = String(tallySec);

                if (tallySec <= 0) {
                    clearInterval(tallyTimer);
                    renderAnswerSheet();
                }
            }, 1000);

            function clearPracticeSoundCacheAndSession() {
                try {
                    sessionStorage.removeItem('qx_sound_settings');
                    sessionStorage.removeItem('qx_cached_sound_urls');
                    localStorage.removeItem('qx_sound_settings');
                    localStorage.removeItem('qx_cached_sound_urls');

                    if (window.soundsCache) window.soundsCache = {};
                    if (window._preloadedUrls) window._preloadedUrls = {};
                    if (window._preloadedQuestionAudios) window._preloadedQuestionAudios = {};
                    if (window.QuizSoundEngine && window.QuizSoundEngine.soundsCache) {
                        window.QuizSoundEngine.soundsCache = {};
                    }

                    sessionStorage.removeItem('pq_session_' + quizId);
                    localStorage.removeItem('pq_session_' + quizId);
                    sessionStorage.removeItem('qx_practice_sound_' + quizId);
                    localStorage.removeItem('qx_practice_sound_' + quizId);
                } catch(e) {}
            }

            async function renderAnswerSheet() {
                log = await fetchP;
                const root = qs('#review-root');
                if (!root) return;

                if (window.stopAllQuizAudios) window.stopAllQuizAudios();
                if (typeof Sound !== 'undefined') {
                    if (Sound.ensureCtx) Sound.ensureCtx();
                    if (Sound.stopBackground) Sound.stopBackground();
                }

                // 6. Answer Sheet Appear sound
                playSoundByKey('answer_sheet_appear_sound');

                // Clear sound session & cache
                clearPracticeSoundCacheAndSession();

                const total = log.length || summaryStats.total_questions || 10;
                const correctCount = log.filter(x => x.isCorrect === true).length;
                const wrongCount = total - correctCount;
                const sumTime = log.reduce((acc, x) => acc + (parseFloat(x.totalTime) || 0), 0);
                const totalTimeFormatted = (summaryStats.total_time > 0 ? summaryStats.total_time : sumTime).toFixed(1);

                root.className = 'relative z-[1] max-w-md mx-auto w-full';
                root.innerHTML = `
                    <!-- ===== Summary Stats Cards ===== -->
                    <div class="grid grid-cols-4 gap-2 text-center mb-4">
                        <div class="glass-card rounded-2xl p-2.5 border border-white/10" style="background: rgba(255, 255, 255, 0.02);">
                            <p class="font-label-caps text-[8px] uppercase text-white/50 tracking-wider">TOTAL QS</p>
                            <p class="font-headline-md text-base font-bold text-white mt-1">${total}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-emerald-500/30" style="background: rgba(16, 185, 129, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-emerald-400 tracking-wider">CORRECT</p>
                            <p class="font-headline-md text-base font-bold text-emerald-400 mt-1">${correctCount}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-red-500/30" style="background: rgba(239, 68, 68, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-red-400 tracking-wider">WRONG</p>
                            <p class="font-headline-md text-base font-bold text-red-400 mt-1">${wrongCount}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-amber-400/30" style="background: rgba(251, 191, 36, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-amber-300 tracking-wider">TOTAL TIME</p>
                            <p class="font-headline-md text-base font-bold text-amber-300 mt-1">${totalTimeFormatted}s</p>
                        </div>
                    </div>

                    <!-- ===== Rank #1 Winner Comparison Card ===== -->
                    ${winnerComp && winnerComp.has_winner ? `
                        <div class="glass-card rounded-2xl p-4 mb-5 border ${winnerComp.is_equal_or_better ? 'border-amber-400/40 bg-gradient-to-br from-amber-400/10 via-purple-900/20 to-emerald-500/10' : 'border-purple-500/30 bg-purple-950/30'} shadow-lg relative overflow-hidden">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🏆</span>
                                    <div>
                                        <h3 class="font-display text-sm font-bold text-amber-300 flex items-center gap-1.5">
                                            Rank #1 Winner Benchmark
                                        </h3>
                                        <p class="text-[11px] text-white/60">Winner: <span class="text-white font-semibold">${escapeHtml(winnerComp.winner_name)}</span></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${winnerComp.is_better ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : (winnerComp.is_equal ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-white/10 text-white/70 border border-white/10')}">
                                    ${winnerComp.is_better ? '🔥 Champion Beater' : (winnerComp.is_equal ? '⭐ Equal to Winner' : '⚡ Keep Trying')}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 text-center mb-3">
                                <!-- Winner Record -->
                                <div class="bg-black/30 rounded-xl p-2.5 border border-white/5">
                                    <p class="text-[10px] font-bold text-white/50 uppercase tracking-wider">Rank #1 Record</p>
                                    <div class="flex items-center justify-center gap-3 mt-1.5">
                                        <div>
                                            <span class="text-xs text-emerald-400 font-bold block">${winnerComp.winner_correct_ans}</span>
                                            <span class="text-[9px] text-white/40">Correct</span>
                                        </div>
                                        <div class="w-px h-5 bg-white/10"></div>
                                        <div>
                                            <span class="text-xs text-amber-300 font-bold block">${winnerComp.winner_total_time}s</span>
                                            <span class="text-[9px] text-white/40">Time</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Your Stats -->
                                <div class="bg-black/30 rounded-xl p-2.5 border ${winnerComp.is_equal_or_better ? 'border-amber-400/30' : 'border-white/5'}">
                                    <p class="text-[10px] font-bold text-amber-300/80 uppercase tracking-wider">Your Performance</p>
                                    <div class="flex items-center justify-center gap-3 mt-1.5">
                                        <div>
                                            <span class="text-xs ${winnerComp.user_correct_ans >= winnerComp.winner_correct_ans ? 'text-emerald-400' : 'text-white'} font-bold block">${winnerComp.user_correct_ans}</span>
                                            <span class="text-[9px] text-white/40">Correct</span>
                                        </div>
                                        <div class="w-px h-5 bg-white/10"></div>
                                        <div>
                                            <span class="text-xs ${(winnerComp.user_correct_ans > winnerComp.winner_correct_ans || (winnerComp.user_correct_ans == winnerComp.winner_correct_ans && winnerComp.user_total_time <= winnerComp.winner_total_time)) ? 'text-amber-300' : 'text-white'} font-bold block">${winnerComp.user_total_time}s</span>
                                            <span class="text-[9px] text-white/40">Time</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[11px] font-semibold text-center ${winnerComp.is_equal_or_better ? 'text-amber-200' : 'text-white/70'}">
                                ${escapeHtml(winnerComp.message)}
                            </p>
                        </div>
                    ` : ''}

                     @include('fsrapp.home.components.banners', [ 'banners' => $banners, 'pageType' => 'practice_answer_sheet'])
                   
                     <!-- ===== Questions Answer Sheet List ===== -->
                    <div class="space-y-3.5 mb-6">
                        ${log.map((item, idx) => {
                            const isCorrect = item.isCorrect === true;
                            const chosenText = item.chosenText || 'Not Answered';
                            const correctText = item.correctText || '-';
                            const totalTimeStr = item.totalTime ? (typeof item.totalTime === 'number' ? item.totalTime.toFixed(1) + 's' : item.totalTime + 's') : '';

                            let audioBtnHtml = '';
                            if (item.audioUrl && String(item.audioUrl).trim().length > 0) {
                                audioBtnHtml = `
                                    <button type="button" class="q-audio-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-500/20 hover:bg-purple-500/30 border border-purple-400/40 text-purple-300 active:scale-95 text-xs font-bold shrink-0 shadow-sm transition-all cursor-pointer" data-audio-url="${escapeHtml(item.audioUrl)}" onclick="toggleQuestionSheetAudio(this)" title="Play Question Audio">
                                        <span class="material-symbols-outlined text-[15px] q-audio-icon">volume_up</span>
                                        <span class="q-audio-label text-[10px] uppercase font-bold tracking-wider">Audio</span>
                                    </button>
                                `;
                            }

                            let mediaImgHtml = '';
                            if (item.mediaUrl && String(item.mediaUrl).trim().length > 0) {
                                mediaImgHtml = `
                                    <div class="q-img-wrap my-2.5 rounded-xl overflow-hidden border border-white/10 bg-black/40 shadow-inner max-h-52 flex items-center justify-center pointer-events-none select-none">
                                        <img src="${escapeHtml(item.mediaUrl)}" alt="Question Media" class="w-full h-auto max-h-52 object-contain rounded-xl select-none pointer-events-none" loading="lazy" onerror="this.closest('.q-img-wrap').style.display='none'">
                                    </div>
                                `;
                            }

                            return `
                                <div class="ans-card ${isCorrect ? 'correct' : 'incorrect'}">
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="status-badge ${isCorrect ? 'text-emerald-400' : 'text-red-400'}">
                                            <span class="material-symbols-outlined text-[17px]" style="font-variation-settings: 'FILL' 1;">${isCorrect ? 'check_circle' : 'cancel'}</span>
                                            Q${idx + 1}. ${isCorrect ? 'Correct' : 'Incorrect'}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            ${audioBtnHtml}
                                            ${totalTimeStr ? `<span class="time-badge">${totalTimeStr}</span>` : ''}
                                        </div>
                                    </div>

                                    ${mediaImgHtml}

                                    <div class="q-text">
                                        ${(item.hindiText && String(item.hindiText).trim().length > 0) ? `<p class="text-white font-bold">${escapeHtml(item.hindiText)}</p>` : ''}
                                        <p class="${(item.hindiText && String(item.hindiText).trim().length > 0) ? 'q-sub-text' : 'text-white font-bold'}">${escapeHtml(item.text)}</p>
                                    </div>

                                    <div class="ans-info-row">
                                        <p>
                                            <span class="label-correct">Correct Answer:</span>
                                            <span class="val-correct">${escapeHtml(correctText)}</span>
                                        </p>
                                        <p>
                                            <span class="text-white/60 font-semibold">Your Answer:</span>
                                            <span class="${isCorrect ? 'val-correct' : 'val-wrong'}">${escapeHtml(chosenText)}</span>
                                        </p>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>

                    <div class="flex flex-col gap-3">
                        <button id="btn-back-home" class="w-full py-3.5 rounded-full font-bold text-sm bg-white/5 border border-white/10 text-[#e8ddff] hover:bg-white/10 transition-all text-center cursor-pointer flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-lg">home</span>
                            <span>Back to Home</span>
                        </button>
                    </div>
                `;

                const backHomeBtn = qs('#btn-back-home');
                if (backHomeBtn) {
                    backHomeBtn.addEventListener('click', () => navigateTo('/fsrapp/home'));
                }
            }

            let activeQuestionAudio = null;
            let activeQuestionBtn = null;

            window.toggleQuestionSheetAudio = function(btn) {
                const audioUrl = btn.getAttribute('data-audio-url');
                if (!audioUrl) return;

                if (activeQuestionBtn === btn && activeQuestionAudio && !activeQuestionAudio.paused) {
                    window.stopQuestionSheetAudio();
                    return;
                }

                window.stopQuestionSheetAudio();

                try {
                    const audio = new Audio(audioUrl);
                    audio.volume = 1.0;
                    activeQuestionAudio = audio;
                    activeQuestionBtn = btn;

                    btn.classList.add('!bg-purple-600', '!text-white', '!border-purple-400', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                    btn.classList.remove('bg-purple-500/20', 'text-purple-300');
                    const icon = btn.querySelector('.q-audio-icon');
                    if (icon) icon.textContent = 'pause';
                    const label = btn.querySelector('.q-audio-label');
                    if (label) label.textContent = 'Playing';

                    audio.play().catch(e => {
                        console.warn('Failed to play question audio:', e);
                        window.stopQuestionSheetAudio();
                    });

                    audio.onended = function() {
                        window.stopQuestionSheetAudio();
                    };
                    audio.onerror = function() {
                        window.stopQuestionSheetAudio();
                    };
                } catch (e) {
                    console.error('Question audio error:', e);
                    window.stopQuestionSheetAudio();
                }
            };

            window.stopQuestionSheetAudio = function() {
                if (activeQuestionAudio) {
                    try {
                        activeQuestionAudio.pause();
                        activeQuestionAudio.currentTime = 0;
                    } catch (e) {}
                    activeQuestionAudio = null;
                }
                if (activeQuestionBtn) {
                    activeQuestionBtn.classList.remove('!bg-purple-600', '!text-white', '!border-purple-400', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                    activeQuestionBtn.classList.add('bg-purple-500/20', 'text-purple-300');
                    const icon = activeQuestionBtn.querySelector('.q-audio-icon');
                    if (icon) icon.textContent = 'volume_up';
                    const label = activeQuestionBtn.querySelector('.q-audio-label');
                    if (label) label.textContent = 'Audio';
                    activeQuestionBtn = null;
                }
            };
        });
    </script>
@endpush
