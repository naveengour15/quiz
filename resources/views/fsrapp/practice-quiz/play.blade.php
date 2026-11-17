@extends('fsrapp.layouts.app')
@push('styles')
<style>
    :root { --border-glass: rgba(255,255,255,0.1); }
    .glow-field { position: fixed; inset: 0; overflow: hidden; z-index: -1; pointer-events: none; }
    .glow-orb { position: absolute; border-radius: 999px; filter: blur(70px); mix-blend-mode: screen; }
    .glow-orb.o1 { width: 46vmax; height: 46vmax; top: -12vmax; left: -10vmax; background: radial-gradient(circle, rgba(168,85,247,0.30) 0%, transparent 68%); animation: driftA 12s ease-in-out infinite; }
    .glow-orb.o2 { width: 40vmax; height: 40vmax; bottom: -14vmax; right: -12vmax; background: radial-gradient(circle, rgba(217,70,239,0.26) 0%, transparent 68%); animation: driftB 14s ease-in-out infinite; }
    @keyframes driftA { 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(8vw,10vh) scale(1.15);} }
    @keyframes driftB { 0%,100%{transform:translate(0,0) scale(1);} 50%{transform:translate(-8vw,-8vh) scale(1.1);} }
    @media (prefers-reduced-motion: reduce) { .glow-orb { animation: none !important; } }

    header.fsr-header { background: rgba(13,6,32,0.75); backdrop-filter: blur(18px); border-bottom: 1px solid var(--border-glass); }

    .glass-card {
        background: linear-gradient(160deg, rgba(255,255,255,0.09), rgba(255,255,255,0.03));
        backdrop-filter: blur(14px);
        border: 1px solid rgba(196,158,255,0.18);
        border-radius: 20px;
    }

    .q-dot { width: 7px; height: 7px; border-radius: 999px; background: rgba(255,255,255,0.15); display: inline-block; }
    .q-dot.done { background: #4ade80; }
    .q-dot.current { background: #ffd76a; box-shadow: 0 0 6px rgba(255,215,106,0.7); }

    .dial-wrap { position: relative; width: 92px; height: 92px; flex-shrink: 0; }
    .dial-wrap svg { transform: rotate(-90deg); }
    .dial-track { fill: none; stroke: rgba(255,255,255,0.08); stroke-width: 6; }
    .dial-fill { fill: none; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset 1s linear, stroke 0.3s ease; filter: drop-shadow(0 0 5px rgba(244,191,50,0.5)); }
    .dial-num { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-family:'JetBrains Mono',monospace; font-weight: 700; font-size: 26px; color: #ffe9b0; }

    @keyframes pulseFade { 0%,100% { opacity: .35; } 50% { opacity: .7; } }
    .skel-opt { height: 56px; border-radius: 16px; border: 1.5px dashed var(--border-glass); background: rgba(255,255,255,0.03); animation: pulseFade 1.4s ease-in-out infinite; }

    .opt-btn {
        position: relative; display: flex; align-items: center; gap: 13px;
        padding: 14px 16px; border-radius: 16px; border: 1.5px solid var(--border-glass);
        background: rgba(255,255,255,0.03); transition: all .18s ease; cursor: pointer; width: 100%; text-align: left;
    }
    .opt-btn:active { transform: scale(0.98); }
    .opt-label {
        width: 32px; height: 32px; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 13px;
        background: rgba(255,255,255,0.08); color: #e8ddff; border: 1px solid var(--border-glass);
        transition: all .18s ease;
    }
    .opt-text { font-size: 14.5px; font-weight: 600; color: #e8ddff; }

    @keyframes selectPulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(255,215,106,0.5), 0 0 18px rgba(255,215,106,0.35); }
        50% { box-shadow: 0 0 0 5px rgba(255,215,106,0), 0 0 26px rgba(255,215,106,0.55); }
    }
    .opt-btn.selected {
        border-color: #ffd76a;
        background: linear-gradient(135deg, rgba(255,215,106,0.18), rgba(255,215,106,0.03));
        animation: selectPulse 1.3s ease-in-out infinite;
    }
    .opt-btn.selected .opt-label { background: linear-gradient(160deg, #ffe9b0, #ffd76a); color: #3a2400; border-color: transparent; }
    .opt-btn.selected .opt-check { opacity: 1; transform: scale(1); }
    .opt-check { opacity: 0; transform: scale(0.6); transition: all .2s ease; margin-left: auto; }

    .opt-btn.disabled-locked { opacity: .45; pointer-events: none; }
    .opt-btn.eliminated { opacity: .22; pointer-events: none; filter: grayscale(1); }
    .opt-btn[disabled] { pointer-events: none; }

    @keyframes correctPop {
        0%,100% { box-shadow: 0 0 0 0 rgba(74,222,128,0.45), 0 0 16px rgba(74,222,128,0.3); }
        50% { box-shadow: 0 0 0 6px rgba(74,222,128,0), 0 0 26px rgba(74,222,128,0.55); }
    }
    @keyframes wrongShake {
        0%,100% { transform: translateX(0); }
        20% { transform: translateX(-6px); } 40% { transform: translateX(5px); }
        60% { transform: translateX(-4px); } 80% { transform: translateX(3px); }
    }
    .opt-btn.reveal-correct {
        border-color: rgba(74,222,128,0.75) !important;
        background: linear-gradient(135deg, rgba(74,222,128,0.2), rgba(74,222,128,0.04)) !important;
        animation: correctPop 1s ease-in-out infinite;
        opacity: 1 !important;
    }
    .opt-btn.reveal-correct .opt-label { background: linear-gradient(160deg, #86efac, #22c55e) !important; color: #06280f !important; }
    .opt-btn.reveal-correct .opt-check { opacity: 1 !important; transform: scale(1) !important; color: #4ade80 !important; }
    .opt-btn.reveal-wrong {
        border-color: rgba(248,113,113,0.75) !important;
        background: linear-gradient(135deg, rgba(248,113,113,0.2), rgba(248,113,113,0.04)) !important;
        animation: wrongShake .5s ease-in-out 1;
        opacity: 1 !important;
    }
    .opt-btn.reveal-wrong .opt-label { background: linear-gradient(160deg, #fca5a5, #ef4444) !important; color: #3a0505 !important; }
    .opt-btn.reveal-wrong .opt-check { opacity: 1 !important; transform: scale(1) !important; color: #f87171 !important; }

    .lifeline-btn {
        display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1;
        padding: 10px 6px; border-radius: 14px; border: 1.5px solid var(--border-glass);
        background: rgba(255,255,255,0.03); transition: all .18s ease;
    }
    .lifeline-btn:active:not(.used) { transform: scale(0.95); }
    .lifeline-btn.used { opacity: .35; }
    .lifeline-icon-wrap {
        width: 34px; height: 34px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(160deg, rgba(217,70,239,0.24), rgba(124,58,237,0.12));
    }

    .phase-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px;
        font-size: 11.5px; font-weight: 700; letter-spacing: .3px;
    }
    .phase-chip.hold { background: rgba(232,221,255,0.1); color: rgba(232,221,255,0.7); border: 1px solid var(--border-glass); }
    .phase-chip.answer { background: rgba(255,215,106,0.14); color: #ffd76a; border: 1px solid rgba(255,215,106,0.4); }

    @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
    .blink-dot { animation: blink 1s ease-in-out infinite; }

    /* Answer Sheet Styles */
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
    <!-- Fixed Top Header -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    id="header-back-btn"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors active:scale-95"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass, rgba(255, 255, 255, 0.12));"
                    title="Back to Home">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-[15px] font-extrabold tracking-tight leading-none" style="color:#e8ddff;">{{ __('app.practice_quiz_title') }}</h1>
                    <p id="qProgressLabel" class="text-[11px] leading-none mt-1 font-semibold" style="color:rgba(232,221,255,0.45);">{{ __('app.practice_quiz') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5" id="progressDots"></div>
        </div>
    </header>

    <main class="min-h-screen px-container-margin pt-[70px] pb-10 flex flex-col kbc-stage">
        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
        <div id="quiz-play-root" class="w-full flex-1 flex flex-col items-center justify-start relative z-[1]">
            <!-- Render initial landing card via JS -->
        </div>
    </main>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const quizId = @json($id ?? null) || (typeof params !== 'undefined' ? params.id : null) || (
                location.pathname.split('/').pop());
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const practiceText = {
                practiceQuiz: @json(__('app.practice_quiz')),
                question: @json(__('app.question')),
                practiceReady: @json(__('app.practice_ready')),
                practiceDescription: @json(__('app.practice_description')),
                standardSet: @json(__('app.standard_set')),
                holdAndAnswerTime: @json(__('app.hold_and_answer_time')),
                startPractice: @json(__('app.start_practice_session')),
                getReady: @json(__('app.get_ready')),
                quizStarting: @json(__('app.practice_quiz_starting')),
                firstQuestionTimer: @json(__('app.first_question_timer')),
                skipWarmup: @json(__('app.skip_warmup')),
                questionProgress: @json(__('app.question_progress')),
                holdPhase: @json(__('app.hold_phase')),
                answerNow: @json(__('app.answer_now')),
                optionsAppearing: @json(__('app.options_appearing_in')),
                tapCorrectOption: @json(__('app.tap_correct_option')),
                playAudio: @json(__('app.play_question_audio')),
                loadingError: @json(__('app.error_loading_question')),
                submitError: @json(__('app.error_submitting_answer')),
                cannotUseLifeline: @json(__('app.cannot_use_lifeline')),
                lifelineApplied: @json(__('app.lifeline_applied')),
                lifelineError: @json(__('app.lifeline_error')),
                tallying: @json(__('app.tallying_answers')),
                tallyingAnswers: @json(__('app.tallying_practice_answers')),
                answersRevealing: @json(__('app.answers_revealing_in')),
                answerSheet: @json(__('app.practice_answer_sheet')),
                totalQuestions: @json(__('app.total_questions_short')),
                correct: @json(__('app.correct')),
                wrong: @json(__('app.wrong')),
                totalTime: @json(__('app.total_time')),
                winnerBenchmark: @json(__('app.rank_winner_benchmark')),
                winner: @json(__('app.winner')),
                championBeater: @json(__('app.champion_beater')),
                equalToWinner: @json(__('app.equal_to_winner')),
                keepTrying: @json(__('app.keep_trying')),
                rankRecord: @json(__('app.rank_record')),
                yourPerformance: @json(__('app.your_performance')),
                notAnswered: @json(__('app.not_answered')),
                incorrect: @json(__('app.incorrect')),
                correctAnswer: @json(__('app.correct_answer')),
                yourAnswer: @json(__('app.your_answer')),
                backHome: @json(__('app.back_to_home'))
            };
            function practiceLabel(key, replacements = {}) {
                return Object.entries(replacements).reduce(
                    (text, [name, value]) => text.replace(`:${name}`, value),
                    practiceText[key] || ''
                );
            }

            // Self-contained DOM & Utility Helpers
            function qs(sel, root = document) { return root.querySelector(sel); }
            function qsa(sel, root = document) { return [...root.querySelectorAll(sel)]; }
            function unwrap(json) {
                if (json == null) return json;
                if (json.data !== undefined) return json.data;
                if (json.result !== undefined) return json.result;
                return json;
            }
            function escapeHtml(s) {
                return (s ?? '').toString().replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            }
            function showToastMsg(msg, type = 'info') {
                if (typeof toast === 'function') {
                    toast(msg, type);
                } else {
                    const box = qs('#toast');
                    if (box) {
                        box.textContent = msg;
                        box.classList.remove('hidden');
                        setTimeout(() => box.classList.add('hidden'), 3200);
                    }
                }
            }
            function navigateTo(path) {
                window.location.href = path;
            }

            // Direct Fetch API caller for Practice Quiz Web Controller
            async function practiceApi(path, options = {}) {
                const url = path.startsWith('http') ? path : (path.startsWith('/fsrapp') || path.startsWith('/practice-quiz') ? window.location.origin + path : window.location.origin + '/fsrapp' + path);
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
                        body: options.body ? (typeof options.body === 'string' ? options.body : JSON.stringify(options.body)) : undefined
                    });
                    const text = await res.text();
                    let data;
                    try { data = JSON.parse(text); } catch(e) { data = { raw: text }; }
                    if (!res.ok) {
                        throw new Error((data && (data.message || data.error)) || `Request failed (${res.status})`);
                    }
                    return data;
                } catch (e) {
                    console.error('[Practice Quiz Request Error]', e);
                    throw e;
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
                    if (!soundUrls || !soundUrls.question_appear_sound) {
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
                if (!url && (key === 'answer_appear_sound' || key === 'option_appear_sound')) {
                    url = soundUrls.answer_appear_sound || soundUrls.option_appear_sound;
                }
                if (!url && (key === 'counter_10_sound' || key === 'counter_sound')) {
                    url = soundUrls.counter_sound;
                }
                if (!url && (key === 'start_counter_sound' || key === 'start_quiz_sound')) {
                    url = soundUrls.start_quiz_sound || soundUrls.counter_sound;
                }
                if (!url && key === 'answer_sheet_appear_sound') {
                    url = soundUrls.answer_sheet_appear_sound;
                }

                if (url && window.playQuizAudio) {
                    window.playQuizAudio(url, volume);
                } else if (typeof Sound !== 'undefined') {
                    if (key === 'question_appear_sound' && Sound.questionLoad) Sound.questionLoad();
                    else if ((key === 'answer_appear_sound' || key === 'option_appear_sound') && Sound.optionsReveal) Sound.optionsReveal();
                    else if ((key === 'counter_10_sound' || key === 'counter_sound') && Sound.tick) Sound.tick(true);
                    else if ((key === 'start_counter_sound' || key === 'start_quiz_sound') && Sound.startQuizSound) Sound.startQuizSound();
                    else if (key === 'answer_sheet_appear_sound' && Sound.answerSheet) Sound.answerSheet();
                }
            }

            if (typeof Sound !== 'undefined' && Sound.ensureCtx) {
                Sound.ensureCtx();
                Sound.stopBackground();
            }
            let bgStopped = false;

            const isObj = (val) => val !== null && typeof val === 'object';
            const DIAL_CIRCUMFERENCE = 251.2;

            let currentQIndex = 0;
            let totalQuestions = 10;
            let currentQuestionObj = null;
            let currentOptions = [];
            let questionTimer = null;
            let warmupTimer = null;
            let secondsLeft = 5;
            let phase = 'reveal'; // 'reveal' (5s question text only) or 'active' (10s options interactive)
            let sayAnsPlayed = false;
            let answered = false;
            let lifelines = {
                fifty_fifty: true,
                switch_option: true,
                used_fifty: false,
                used_switch: false,
                used_lifeline: false
            };
            let questionShowTime = null;
            let activeQuestionAudio = null;
            let questionAudioTimer = null;

            function playQuestionAudioIfAvailable(qObj) {
                if (activeQuestionAudio) {
                    try {
                        activeQuestionAudio.pause();
                        activeQuestionAudio.currentTime = 0;
                    } catch (e) {}
                    activeQuestionAudio = null;
                }
                if (questionAudioTimer) {
                    clearTimeout(questionAudioTimer);
                    questionAudioTimer = null;
                }

                const audioUrl = getPracticeQuestionAudioUrl(qObj);

                if (audioUrl && typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) {
                    try {
                        sessionStorage.setItem('qx_sound_muted_manual', 'false');
                        localStorage.setItem('qx_sound_muted', 'false');
                        localStorage.setItem('qx_mute', '0');
                        if (typeof window.updateSpeakerIcon === 'function') window.updateSpeakerIcon();
                    } catch(e) {}
                }

                if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted() && !audioUrl) {
                    return;
                }

                if (!audioUrl) {
                    playSoundByKey('question_appear_sound');
                    return;
                }

                try {
                    if (window.stopAllQuizAudios) {
                        window.stopAllQuizAudios();
                    }

                    const audio = new Audio(audioUrl);
                    audio.preload = 'auto';
                    audio.volume = 0.85;
                    activeQuestionAudio = audio;
                    if (window.activeQuizAudios && !window.activeQuizAudios.includes(audio)) {
                        window.activeQuizAudios.push(audio);
                    }

                    audio.onerror = () => {
                        playSoundByKey('question_appear_sound');
                    };

                    const playPromise = audio.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(err => {
                            playSoundByKey('question_appear_sound');
                        });
                    }

                    if (audio) {
                        audio.onended = () => {
                            activeQuestionAudio = null;
                        };
                    }
                } catch (e) {
                    playSoundByKey('question_appear_sound');
                }
            }

            function getPracticeQuestionAudioUrl(qObj) {
                if (!qObj) return null;
                let rawAudio = qObj.question_audio || qObj.audio_file || (typeof qObj.question === 'object' && qObj.question ? (qObj.question.question_audio || qObj.question.audio_file) : null);
                if (!rawAudio && qObj.media_url && typeof qObj.media_url === 'string' && /\.(mp3|wav|ogg|m4a|aac|flac)$/i.test(qObj.media_url.trim())) {
                    rawAudio = qObj.media_url.trim();
                }
                if (!rawAudio || typeof rawAudio !== 'string' || rawAudio.trim().length === 0) return null;
                if (/\.(jpe?g|png|webp|svg|gif)$/i.test(rawAudio.trim())) return null;
                let audioUrl = window.resolveAudioUrl ? window.resolveAudioUrl(rawAudio) : rawAudio.trim();
                return audioUrl || null;
            }

            window.replayPracticeQuestionAudio = function(e) {
                if (e && typeof e.stopPropagation === 'function') {
                    e.stopPropagation();
                }
                if (!currentQuestionObj) return;
                const audioUrl = getPracticeQuestionAudioUrl(currentQuestionObj);
                if (!audioUrl) return;

                if (window.isGlobalMuted && window.isGlobalMuted()) {
                    if (typeof window.toggleGlobalMute === 'function') {
                        window.toggleGlobalMute();
                    }
                }

                if (activeQuestionAudio) {
                    try {
                        activeQuestionAudio.pause();
                        activeQuestionAudio.currentTime = 0;
                    } catch (err) {}
                    activeQuestionAudio = null;
                }
                if (questionAudioTimer) {
                    clearTimeout(questionAudioTimer);
                    questionAudioTimer = null;
                }

                try {
                    if (window.stopAllQuizAudios) {
                        window.stopAllQuizAudios();
                    }
                    const audio = new Audio(audioUrl);
                    audio.preload = 'auto';
                    audio.volume = 0.95;
                    activeQuestionAudio = audio;
                    if (window.activeQuizAudios && !window.activeQuizAudios.includes(audio)) {
                        window.activeQuizAudios.push(audio);
                    }
                    audio.play().catch(() => {});
                } catch (err) {
                    console.error('Error replaying practice question audio:', err);
                }
            };

            // Render Initial Landing Screen
            renderLandingUI();

            function renderLandingUI() {
                const backBtn = qs('#header-back-btn');
                if (backBtn) backBtn.style.display = 'flex';
                const qLabel = qs('#qProgressLabel');
                if (qLabel) qLabel.textContent = practiceText.practiceQuiz;
                const dotsWrap = qs('#progressDots');
                if (dotsWrap) dotsWrap.innerHTML = '';

                const root = qs('#quiz-play-root');
                if (!root) return;

                root.innerHTML = `
                    <div class="w-full max-w-lg mx-auto text-center py-8 px-4 flex flex-col items-center">
                        <div class="w-20 h-20 mx-auto rounded-3xl bg-mint/10 border border-mint/30 flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(74,222,128,0.2)]">
                            <span class="material-symbols-outlined text-mint text-4xl">school</span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-mint/20 text-mint uppercase tracking-wider mb-3 inline-block">${practiceText.practiceQuiz}</span>
                        <h2 class="font-headline-lg text-2xl md:text-3xl font-extrabold text-on-surface mb-3">${practiceText.practiceReady}</h2>
                        <p class="text-on-surface-variant text-sm mb-6 max-w-sm mx-auto">${practiceText.practiceDescription}</p>
                        
                        <div class="grid grid-cols-2 gap-3 mb-8 w-full text-left">
                            <div class="p-3.5 rounded-2xl bg-surface-container-high/60 border border-outline-variant/20 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold">10</div>
                                <div>
                                    <p class="text-xs font-bold text-on-surface">${@json(__('app.questions'))}</p>
                                    <p class="text-[11px] text-on-surface-variant">${practiceText.standardSet}</p>
                                </div>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-surface-container-high/60 border border-outline-variant/20 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center text-secondary">
                                    <span class="material-symbols-outlined text-lg">timer</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-on-surface">15s / Q</p>
                                    <p class="text-[11px] text-on-surface-variant">${practiceText.holdAndAnswerTime}</p>
                                </div>
                            </div>
                        </div>
                       
                       @include('fsrapp.home.components.banners', [ 'banners' => $banners, 'pageType' => 'practices_start'])
                        <button id="btn-start-practice" class="w-full py-4 rounded-2xl bg-gradient-to-r from-secondary to-primary text-black font-headline-md font-extrabold text-base shadow-lg shadow-primary/20 hover:opacity-95 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <span>${practiceText.startPractice}</span>
                            <span class="material-symbols-outlined font-bold">arrow_forward</span>
                        </button>
                    </div>
                `;

                const startBtn = qs('#btn-start-practice');
                if (startBtn) {
                    startBtn.addEventListener('click', () => {
                        startPracticeQuiz();
                    });
                }
            }

            async function startPracticeQuiz() {
                try {
                    const res = await practiceApi(`/fsrapp/practice-quiz/start/${quizId}`, { method: 'POST' });
                    const rawInit = unwrap(res) || res || {};
                    if (rawInit.total_questions) {
                        totalQuestions = rawInit.total_questions;
                    }
                    if (rawInit.lifelines) {
                        lifelines = rawInit.lifelines;
                    }
                } catch (e) {
                    console.warn('[Practice Start Error]', e);
                }

                run20SecondWarmup();
            }

            function run20SecondWarmup() {
                const qLabel = qs('#qProgressLabel');
                if (qLabel) qLabel.textContent = practiceText.getReady;
                const dotsWrap = qs('#progressDots');
                if (dotsWrap) dotsWrap.innerHTML = '';

                const root = qs('#quiz-play-root');
                if (!root) return;

                if (window.stopAllQuizAudios) window.stopAllQuizAudios();
                // 1. Counter 10 second sound (Warmup)
                playSoundByKey('start_quiz_sound');

                let warmupSec = 10;
                root.innerHTML = `
                    <div class="w-full max-w-lg mx-auto text-center py-12 px-4 flex flex-col items-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-secondary/20 text-secondary uppercase tracking-wider mb-4 inline-block">${practiceText.getReady}</span>
                        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-2">${practiceText.quizStarting}</h2>
                        <p class="text-on-surface-variant text-sm mb-8">${practiceText.firstQuestionTimer}</p>
                        
                        <div class="relative w-36 h-36 rounded-full border-4 border-secondary/30 flex items-center justify-center mb-8 shadow-[0_0_30px_rgba(244,191,50,0.2)]">
                            <div class="absolute inset-0 rounded-full border-4 border-secondary border-t-transparent animate-spin"></div>
                            <span id="warmup-timer" class="font-display text-5xl font-extrabold text-secondary">10</span>
                        </div>

                        @include('fsrapp.home.components.banners', [ 'banners' => $banners, 'pageType' => 'before_practice_count'])

                        <button id="btn-skip-warmup" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-white/10 text-on-surface hover:bg-white/20 transition-all flex items-center gap-1.5 cursor-pointer">
                            <span>${practiceText.skipWarmup}</span>
                            <span class="material-symbols-outlined text-sm">fast_forward</span>
                        </button>
                    </div>
                `;

                if (warmupTimer) clearInterval(warmupTimer);
                warmupTimer = setInterval(() => {
                    warmupSec--;
                    const timerEl = qs('#warmup-timer');
                    if (timerEl) {
                        timerEl.textContent = Math.max(warmupSec, 0);
                    }

                    if (warmupSec <= 0) {
                        clearInterval(warmupTimer);
                        loadQuestion(0);
                    }
                }, 1000);

                const skipBtn = qs('#btn-skip-warmup');
                if (skipBtn) {
                    skipBtn.addEventListener('click', () => {
                        if (warmupTimer) clearInterval(warmupTimer);
                        loadQuestion(0);
                    });
                }
            }

            async function loadQuestion(index) {
                if (index >= totalQuestions) {
                    finishQuiz();
                    return;
                }

                currentQIndex = index;
                answered = false;
                phase = 'reveal';
                secondsLeft = 5;
                if (questionTimer) clearInterval(questionTimer);

                const root = qs('#quiz-play-root');

                try {
                    const res = await practiceApi(`/fsrapp/practice-quiz/get-question/${quizId}`, {
                        method: 'POST',
                        body: { index }
                    });
                    const data = unwrap(res) || res || {};

                    if (!data.success || !data.question) {
                        finishQuiz();
                        return;
                    }

                    currentQuestionObj = data.question;
                    currentOptions = data.question.options || [];
                    if (data.lifelines) {
                        lifelines = data.lifelines;
                    }

                    sayAnsPlayed = false;
                    questionShowTime = new Date().toISOString();
                    if (typeof Sound !== 'undefined' && Sound.ensureCtx) Sound.ensureCtx();
                    
                    // 2. Play question_audio (or fallback question_appear_sound) for full 5 seconds
                    playQuestionAudioIfAvailable(currentQuestionObj);

                    renderQuestionUI();
                    start5SecRevealTimer();
                } catch (e) {
                    showToastMsg(practiceText.loadingError, 'error');
                }
            }

            function updateDialUI(totalSec) {
                const fill = qs('#qplay-dial-fill');
                const num = qs('#qplay-dial-number');
                const holdEl = qs('#options-countdown');

                const clampedSec = Math.max(secondsLeft, 0);
                const offset = DIAL_CIRCUMFERENCE * (1 - (clampedSec / totalSec));

                if (fill) {
                    fill.style.strokeDashoffset = String(offset);
                    if (phase === 'active' && clampedSec <= 3 && clampedSec > 0) {
                        fill.setAttribute('stroke', '#f87171');
                    } else if (phase === 'active') {
                        fill.setAttribute('stroke', '#ffd76a');
                    } else {
                        fill.setAttribute('stroke', '#a78bfa');
                    }
                }
                if (num) num.textContent = String(clampedSec);
                if (holdEl) holdEl.textContent = String(clampedSec);
            }

            function start5SecRevealTimer() {
                if (questionTimer) clearInterval(questionTimer);
                questionTimer = setInterval(() => {
                    secondsLeft--;
                    updateDialUI(5);

                    if (secondsLeft <= 0) {
                        clearInterval(questionTimer);
                        const isCustomAudioPlaying = activeQuestionAudio && !activeQuestionAudio.paused && !activeQuestionAudio.ended && getPracticeQuestionAudioUrl(currentQuestionObj);
                        if (!isCustomAudioPlaying) {
                            if (activeQuestionAudio) {
                                try {
                                    activeQuestionAudio.pause();
                                    activeQuestionAudio.currentTime = 0;
                                } catch (e) {}
                                activeQuestionAudio = null;
                            }
                            // 4. answer_appear sound
                            playSoundByKey('answer_appear_sound');
                        }
                        phase = 'active';
                        secondsLeft = 10;
                        renderQuestionUI();
                        start10SecAnswerTimer();
                    }
                }, 1000);
            }

            function start10SecAnswerTimer() {
                if (questionTimer) clearInterval(questionTimer);
                // 5. counter 10 second sound for answering
                playSoundByKey('counter_sound');

                questionTimer = setInterval(() => {
                    secondsLeft--;
                    updateDialUI(10);
                    if (secondsLeft === 3 && typeof Sound !== 'undefined' && Sound.tension) Sound.tension();

                    if (secondsLeft <= 0) {
                        clearInterval(questionTimer);
                        if (!answered) {
                            autoSubmitFirstOption();
                        }
                    }
                }, 1000);
            }


            function renderLifelinesHtml(enabled = false) {
                return '';
            }

            function renderQuestionUI() {
                const q = currentQuestionObj;
                const totalSec = phase === 'reveal' ? 5 : 10;
                const initialOffset = DIAL_CIRCUMFERENCE * (1 - (secondsLeft / totalSec));

                const holdPhase = (phase === 'reveal');
                const phaseClass = holdPhase ? 'hold' : 'answer';
                const phaseText = holdPhase ? practiceText.holdPhase : practiceText.answerNow;
                const subtext = holdPhase 
                    ? `${practiceLabel('optionsAppearing', { seconds: `<span id="options-countdown" class="font-mono font-bold" style="color:#ffe9b0;">${secondsLeft}</span>` })}`
                    : practiceText.tapCorrectOption;
                const strokeColor = holdPhase ? '#a78bfa' : (secondsLeft <= 3 ? '#f87171' : '#ffd76a');

                let dotsHtml = '';
                for (let i = 1; i <= totalQuestions; i++) {
                    const statusClass = (i < (currentQIndex + 1)) ? ' done' : (i === (currentQIndex + 1)) ? ' current' : '';
                    dotsHtml += `<span class="q-dot${statusClass}"></span>`;
                }

                const backBtn = qs('#header-back-btn');
                if (backBtn) backBtn.style.display = 'none';

                const qLabel = qs('#qProgressLabel');
                if (qLabel) qLabel.textContent = practiceLabel('questionProgress', { current: currentQIndex + 1, total: totalQuestions });
                const dotsWrap = qs('#progressDots');
                if (dotsWrap) dotsWrap.innerHTML = dotsHtml;

                const root = qs('#quiz-play-root');
                if (!root) return;

                let optionsHtml = '';
                if (holdPhase) {
                    optionsHtml = `
                        <div id="skeletonOptions" class="grid grid-cols-2 gap-3 mb-6 w-full">
                            <div class="skel-opt"></div>
                            <div class="skel-opt" style="animation-delay:.15s;"></div>
                            <div class="skel-opt" style="animation-delay:.3s;"></div>
                            <div class="skel-opt" style="animation-delay:.45s;"></div>
                        </div>
                    `;
                } else {
                    optionsHtml = `
                        <div id="answerOptions" class="grid grid-cols-2 gap-3 mb-6 w-full">
                            ${currentOptions.map((opt, i) => {
                                const val = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
                                const label = isObj(opt) ? (opt.text ?? opt.value ?? '') : opt;
                                const hindiOpt = (q.hindi_options && Array.isArray(q.hindi_options)) ? q.hindi_options[i] : null;
                                const hindiLabel = hindiOpt ? (isObj(hindiOpt) ? (hindiOpt.text ?? hindiOpt.value ?? hindiOpt) : hindiOpt) : null;
                                
                                const hasHindi = (hindiLabel && String(hindiLabel).trim() !== '' && String(hindiLabel).trim().toLowerCase() !== String(label).trim().toLowerCase());
                                const contentHtml = hasHindi ? `
                                    <div class="flex flex-col items-center justify-center leading-tight w-full">
                                        <span class="font-bold text-white text-xs sm:text-sm break-words">${escapeHtml(hindiLabel)}</span>
                                        <span class="text-amber-200/90 font-medium text-[11px] sm:text-xs mt-0.5 break-words">${escapeHtml(label)}</span>
                                    </div>
                                ` : `
                                    <span class="opt-text flex-1 text-center">${escapeHtml(label)}</span>
                                `;

                                return `
                                    <button class="opt-btn option-btn text-center justify-center relative p-3 min-h-[50px]" data-val="${escapeHtml(val)}" onclick="submitPracticeAns('${escapeHtml(val)}', this)">
                                        ${contentHtml}
                                        <span class="material-symbols-outlined opt-check text-[18px] absolute right-2" style="color:#ffd76a;">check_circle</span>
                                    </button>
                                `;
                            }).join('')}
                        </div>
                    `;
                }

                const hasAudio = !!getPracticeQuestionAudioUrl(q);

                root.innerHTML = `
                    <div class="glow-field">
                        <div class="glow-orb o1"></div>
                        <div class="glow-orb o2"></div>
                    </div>

                    <div class="w-full max-w-md mx-auto flex flex-col items-center pt-2">
                        <div class="flex items-center gap-4 mb-5 w-full">
                            <div class="dial-wrap">
                                <svg width="92" height="92" viewBox="0 0 92 92">
                                    <circle class="dial-track" cx="46" cy="46" r="40"/>
                                    <circle id="qplay-dial-fill" class="dial-fill" cx="46" cy="46" r="40" stroke="${strokeColor}" stroke-dasharray="251.2" stroke-dashoffset="${initialOffset}"/>
                                </svg>
                                <div class="dial-num" id="qplay-dial-number">${secondsLeft}</div>
                            </div>
                            <div class="flex-1 text-left">
                                <span id="phaseChip" class="phase-chip ${phaseClass}">
                                    <span class="w-1.5 h-1.5 rounded-full blink-dot" style="background:currentColor;"></span>
                                    <span id="phaseChipText">${phaseText}</span>
                                </span>
                                <p id="phaseSubtext" class="text-[12.5px] mt-2" style="color:rgba(232,221,255,0.55);">${subtext}</p>
                            </div>
                        </div>

                        <div class="glass-card p-5 mb-5 w-full text-left relative z-10">
                            <div class="flex justify-between items-center mb-3">
                                <span class="inline-block text-[11px] font-bold px-2.5 py-1 rounded-full" style="background:rgba(244,191,50,0.15); color:#ffe9b0; border:1px solid rgba(244,191,50,0.35);">Q${currentQIndex + 1} · ${practiceText.practiceQuiz}</span>
                            </div>
                            <h2 id="questionText" class="font-display text-lg font-extrabold leading-snug" style="color:#f5f0ff;">
                                ${escapeHtml((q.hindi_question && String(q.hindi_question).trim().length > 0) ? q.hindi_question.trim() : q.question_text)}
                            </h2>
                            ${(q.hindi_question && String(q.hindi_question).trim().length > 0) ? `<p id="questionHindi" class="text-[13px] mt-2" style="color:rgba(232,221,255,0.5);">${escapeHtml(q.question_text)}</p>` : ''}
                            ${hasAudio ? `
                            <div class="mt-3 flex justify-start">
                                <button type="button" onclick="replayPracticeQuestionAudio(event)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gradient-to-r from-amber-500/20 via-yellow-400/25 to-amber-500/20 border border-amber-400/60 hover:border-amber-300 text-amber-300 hover:text-white transition-all text-xs font-bold shadow-[0_0_10px_rgba(245,158,11,0.25)] active:scale-95 cursor-pointer group"
                                    title="Play Question Audio">
                                    <span class="material-symbols-outlined text-[17px] group-hover:scale-110 transition-transform">volume_up</span>
                                    <span class="text-[11px] font-bold">${practiceText.playAudio}</span>
                                </button>
                            </div>
                            ` : ''}
                        </div>

                        ${optionsHtml}
                        ${renderLifelinesHtml(phase === 'active' && !answered)}
                    </div>
                `;
            }
            window.submitPracticeAns = async function(val, btnEl) {
                if (answered || phase !== 'active') return;
                answered = true;
                if (questionTimer) clearInterval(questionTimer);

                const timeSpent = 10 - secondsLeft;

                if (typeof Sound !== 'undefined' && Sound.select) Sound.select();

                const lifelineBar = qs('#qplay-lifelines-bar');
                if (lifelineBar) {
                    qsa('button', lifelineBar).forEach(b => {
                        b.disabled = true;
                        b.classList.add('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
                    });
                }

                const allBtns = qsa('.opt-btn');
                allBtns.forEach(b => {
                    b.disabled = true;
                    b.classList.add('opacity-60', 'pointer-events-none');
                });

                if (btnEl) {
                    btnEl.classList.remove('opacity-60');
                    btnEl.classList.add('selected');
                    const glowParent = btnEl.closest('.kbc-opt-glow');
                    if (glowParent) glowParent.classList.add('selected');
                }

                try {
                    const answerSubmitTime = new Date().toISOString();
                    const res = await practiceApi(`/fsrapp/practice-quiz/submit-answer/${quizId}`, {
                        method: 'POST',
                        body: {
                            question_id: currentQuestionObj.id,
                            answer: val,
                            show_time: questionShowTime || new Date().toISOString(),
                            ans_time: answerSubmitTime,
                            total_time: timeSpent
                        }
                    });
                    const data = unwrap(res) || res || {};

                    if (btnEl) {
                        btnEl.classList.add('selected');
                        const glowParent = btnEl.closest('.kbc-opt-glow');
                        if (glowParent) glowParent.classList.add('selected');
                    }

                    setTimeout(() => {
                        if (currentQIndex + 1 >= totalQuestions) {
                            finishQuiz();
                        } else {
                            loadQuestion(currentQIndex + 1);
                        }
                    }, 1200);
                } catch (e) {
                    showToastMsg(practiceText.submitError, 'error');
                    setTimeout(() => {
                        if (currentQIndex + 1 >= totalQuestions) {
                            finishQuiz();
                        } else {
                            loadQuestion(currentQIndex + 1);
                        }
                    }, 1200);
                }
            };

            function autoSubmitFirstOption() {
                const firstBtn = qs('.opt-btn');
                const val = firstBtn ? firstBtn.dataset.val : '';
                submitPracticeAns(val, firstBtn);
            }

            window.usePracticeLifeline = async function(lifeline) {
                if (answered || phase !== 'active') return;
                try {
                    const res = await practiceApi(`/fsrapp/practice-quiz/use-lifeline/${quizId}`, {
                        method: 'POST',
                        body: {
                            lifeline: lifeline,
                            question_id: currentQuestionObj.id
                        }
                    });
                    const data = unwrap(res) || res || {};

                    if (!data.success) {
                        showToastMsg(data.message || practiceText.cannotUseLifeline, 'warning');
                        return;
                    }

                    lifelines.used_lifeline = true;
                    if (lifeline === 'fifty_fifty') {
                        lifelines.used_fifty = true;
                        if (typeof Sound !== 'undefined' && Sound.select) Sound.select();
                        showToastMsg(practiceText.lifelineApplied, 'success');

                        const bar = qs('#qplay-lifelines-bar');
                        if (bar) bar.style.display = 'none';

                        const optWrap = qs('#options-container');
                        if (optWrap) {
                            const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];
                            const remainingVals = (data.remaining_options || []).map(opt => {
                                const v = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
                                return String(v).trim().toLowerCase();
                            });
                            const removedVals = (data.removed_options || []).map(opt => {
                                const v = isObj(opt) ? (opt.value ?? opt.text ?? '') : opt;
                                return String(v).trim().toLowerCase();
                            });

                            const optBtns = qsa('.opt-btn', optWrap);
                            const remainingWrappers = [];
                            const removedWrappers = [];

                            optBtns.forEach((b) => {
                                const val = String(b.dataset.val || '').trim().toLowerCase();
                                const text = String(b.textContent || '').trim().toLowerCase();

                                const isRemoved = (removedVals.length > 0 && (removedVals.includes(val) || removedVals.includes(text))) ||
                                                  (remainingVals.length > 0 && !remainingVals.includes(val));

                                const wrapper = b.closest('.kbc-opt-glow') || b.parentElement;
                                if (isRemoved) {
                                    removedWrappers.push({ btn: b, wrapper });
                                } else {
                                    remainingWrappers.push({ btn: b, wrapper });
                                }
                            });

                            remainingWrappers.forEach((item, idx) => {
                                optWrap.appendChild(item.wrapper);
                                const letterSpan = item.btn.querySelector('.kbc-opt-letter-flat');
                                if (letterSpan) {
                                    letterSpan.textContent = (OPT_LETTERS[idx] || (idx + 1)) + ':';
                                }
                            });

                            removedWrappers.forEach((item) => {
                                optWrap.appendChild(item.wrapper);
                                item.btn.disabled = true;
                                item.btn.classList.add('pointer-events-none');
                                if (item.wrapper) {
                                    item.wrapper.style.transition = 'opacity 0.5s ease, transform 0.5s ease, filter 0.5s ease';
                                    item.wrapper.style.opacity = '0';
                                    item.wrapper.style.transform = 'scale(0.95)';
                                    item.wrapper.style.filter = 'blur(4px)';
                                    item.wrapper.style.pointerEvents = 'none';
                                    setTimeout(() => {
                                        item.wrapper.style.visibility = 'hidden';
                                        item.wrapper.style.display = 'none';
                                    }, 500);
                                }
                            });
                        }

                        currentOptions = data.remaining_options || currentOptions;
                    } else if (lifeline === 'switch_option') {
                        lifelines.used_switch = true;
                        if (data.question) {
                            currentQuestionObj = data.question;
                            currentOptions = data.question.options || [];
                            renderQuestionUI();
                        }
                    }
                } catch (e) {
                    showToastMsg(practiceText.lifelineError, 'error');
                }
            };

            function stopBg() {
                if (window.stopAllQuizAudios) window.stopAllQuizAudios();
                if (typeof Sound !== 'undefined' && Sound.stopBackground) Sound.stopBackground();
            }

            let log = [];
            let summaryStats = {
                total_time: 0,
                correct_answers: 0,
                wrong_answers: 0,
                total_questions: 10
            };
            let winnerComp = null;

            function finishQuiz() {
                if (questionTimer) clearInterval(questionTimer);
                if (warmupTimer) clearInterval(warmupTimer);
                if (activeQuestionAudio) {
                    try {
                        activeQuestionAudio.pause();
                        activeQuestionAudio.currentTime = 0;
                    } catch (e) {}
                    activeQuestionAudio = null;
                }
                if (questionAudioTimer) {
                    clearTimeout(questionAudioTimer);
                    questionAudioTimer = null;
                }
                stopBg();
                renderTallyStage();
            }

            function renderTallyStage() {
                try {
                    window.history.replaceState(null, '', `/fsrapp/practice-quiz/review/${quizId}`);
                } catch(e) {}

                const backBtn = qs('#header-back-btn');
                if (backBtn) backBtn.style.display = 'flex';

                const qLabel = qs('#qProgressLabel');
                if (qLabel) qLabel.textContent = practiceText.tallying;
                const dotsWrap = qs('#progressDots');
                if (dotsWrap) dotsWrap.innerHTML = '';

                const root = qs('#quiz-play-root');
                if (!root) return;

                stopBg();
                // Play 10-second counter sound before answer sheet
                playSoundByKey('counter_sound');

                root.className = 'w-full flex-1 flex flex-col items-center justify-start relative z-[1] py-16';
                root.innerHTML = `
                    <div class="kbc-drumroll-ring"></div>
                    <p class="text-on-surface-variant mt-5 kbc-title-glow font-headline-md">${practiceText.tallyingAnswers}</p>
                    <p class="text-tertiary font-label-caps text-sm mt-2">${practiceText.answersRevealing} <span id="review-tally-counter" class="font-display text-xl text-primary font-bold">10</span>s</p>
                `;

                // Fetch Answer Sheet in background during 10s counter
                const fetchP = practiceApi(`/fsrapp/practice-quiz/answer-sheet/${quizId}`, { method: 'GET' })
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
                            const qText = item.question || item.question_text || practiceText.question;
                            const hindiText = item.hindi_question || item.hindi_question_text || null;
                            const chosenText = item.selected_answer ?? item.your_answer ?? item.user_answer ?? null;
                            const correctText = item.correct_answer ?? null;
                            const totalTime = item.total_time ?? null;
                            const showTime = item.show_time ?? null;
                            const ansTime = item.ans_time ?? null;
                            let isCorrect = item.is_correct;
                            if (isCorrect === undefined && chosenText != null && correctText != null) {
                                isCorrect = String(chosenText).trim().toLowerCase() === String(correctText).trim().toLowerCase();
                            }
                            return {
                                text: qText,
                                hindiText,
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
                        return [];
                    });

                let tallySec = 10;
                const tallyTimer = setInterval(() => {
                    tallySec = Math.max(tallySec - 1, 0);
                    const el = qs('#review-tally-counter');
                    if (el) el.textContent = String(tallySec);

                    if (tallySec <= 0) {
                        clearInterval(tallyTimer);
                        renderAnswerSheet(fetchP);
                    }
                }, 1000);
            }

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

            async function renderAnswerSheet(fetchP) {
                stopBg();
                log = (await fetchP) || [];
                const root = qs('#quiz-play-root');
                if (!root) return;

                stopBg();

                // 6. Answer Sheet Appear sound
                playSoundByKey('answer_sheet_appear_sound');

                // Clear sound session & cache
                clearPracticeSoundCacheAndSession();

                const total = log.length || summaryStats.total_questions || 10;
                const correctCount = log.filter(x => x.isCorrect === true).length;
                const wrongCount = total - correctCount;
                const sumTime = log.reduce((acc, x) => acc + (parseFloat(x.totalTime) || 0), 0);
                const totalTimeFormatted = (summaryStats.total_time > 0 ? summaryStats.total_time : sumTime).toFixed(1);

                root.className = 'relative z-[1] max-w-md mx-auto w-full pt-16';
                root.innerHTML = `
                    <!-- HEADER -->
                    <header class="fixed top-0 w-full z-50 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2" style="background: rgba(13, 6, 32, 0.88); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <div class="flex justify-between items-center px-4 h-full w-full">
                            <div class="flex items-center gap-3">
                                <a href="/fsrapp/home" class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10 text-[#e8ddff] hover:bg-white/10 active:scale-95 transition-all">
                                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                                </a>
                                <h1 class="font-display text-lg font-bold text-[#e8ddff] tracking-wide">${practiceText.answerSheet}</h1>
                            </div>
                        </div>
                    </header>

                    <!-- ===== Summary Stats Cards ===== -->
                    <div class="grid grid-cols-4 gap-2 text-center mb-4">
                        <div class="glass-card rounded-2xl p-2.5 border border-white/10" style="background: rgba(255, 255, 255, 0.02);">
                            <p class="font-label-caps text-[8px] uppercase text-white/50 tracking-wider">${practiceText.totalQuestions}</p>
                            <p class="font-headline-md text-base font-bold text-white mt-1">${total}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-emerald-500/30" style="background: rgba(16, 185, 129, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-emerald-400 tracking-wider">${practiceText.correct}</p>
                            <p class="font-headline-md text-base font-bold text-emerald-400 mt-1">${correctCount}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-red-500/30" style="background: rgba(239, 68, 68, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-red-400 tracking-wider">${practiceText.wrong}</p>
                            <p class="font-headline-md text-base font-bold text-red-400 mt-1">${wrongCount}</p>
                        </div>
                        <div class="glass-card rounded-2xl p-2.5 border border-amber-400/30" style="background: rgba(251, 191, 36, 0.05);">
                            <p class="font-label-caps text-[8px] uppercase text-amber-300 tracking-wider">${practiceText.totalTime}</p>
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
                                            ${practiceText.winnerBenchmark}
                                        </h3>
                                        <p class="text-[11px] text-white/60">${practiceText.winner}: <span class="text-white font-semibold">${escapeHtml(winnerComp.winner_name)}</span></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${winnerComp.is_better ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : (winnerComp.is_equal ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-white/10 text-white/70 border border-white/10')}">
                                    ${winnerComp.is_better ? `🔥 ${practiceText.championBeater}` : (winnerComp.is_equal ? `⭐ ${practiceText.equalToWinner}` : `⚡ ${practiceText.keepTrying}`)}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 text-center mb-3">
                                <!-- Winner Record -->
                                <div class="bg-black/30 rounded-xl p-2.5 border border-white/5">
                                    <p class="text-[10px] font-bold text-white/50 uppercase tracking-wider">${practiceText.rankRecord}</p>
                                    <div class="flex items-center justify-center gap-3 mt-1.5">
                                        <div>
                                            <span class="text-xs text-emerald-400 font-bold block">${winnerComp.winner_correct_ans}</span>
                                            <span class="text-[9px] text-white/40">${practiceText.correct}</span>
                                        </div>
                                        <div class="w-px h-5 bg-white/10"></div>
                                        <div>
                                            <span class="text-xs text-amber-300 font-bold block">${winnerComp.winner_total_time}s</span>
                                            <span class="text-[9px] text-white/40">${@json(__('app.time'))}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Your Stats -->
                                <div class="bg-black/30 rounded-xl p-2.5 border ${winnerComp.is_equal_or_better ? 'border-amber-400/30' : 'border-white/5'}">
                                    <p class="text-[10px] font-bold text-amber-300/80 uppercase tracking-wider">${practiceText.yourPerformance}</p>
                                    <div class="flex items-center justify-center gap-3 mt-1.5">
                                        <div>
                                            <span class="text-xs ${winnerComp.user_correct_ans >= winnerComp.winner_correct_ans ? 'text-emerald-400' : 'text-white'} font-bold block">${winnerComp.user_correct_ans}</span>
                                            <span class="text-[9px] text-white/40">${practiceText.correct}</span>
                                        </div>
                                        <div class="w-px h-5 bg-white/10"></div>
                                        <div>
                                            <span class="text-xs ${(winnerComp.user_correct_ans > winnerComp.winner_correct_ans || (winnerComp.user_correct_ans == winnerComp.winner_correct_ans && winnerComp.user_total_time <= winnerComp.winner_total_time)) ? 'text-amber-300' : 'text-white'} font-bold block">${winnerComp.user_total_time}s</span>
                                            <span class="text-[9px] text-white/40">${@json(__('app.time'))}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[11px] font-semibold text-center ${winnerComp.is_equal_or_better ? 'text-amber-200' : 'text-white/70'}">
                                ${escapeHtml(winnerComp.message)}
                            </p>
                        </div>
                    ` : ''}

                    <!-- ===== Questions Answer Sheet List ===== -->
                    <div class="space-y-3.5 mb-6">
                        ${log.map((item, idx) => {
                            const isCorrect = item.isCorrect === true;
                            const chosenText = item.chosenText || practiceText.notAnswered;
                            const correctText = item.correctText || '-';
                            const totalTimeStr = item.totalTime ? (typeof item.totalTime === 'number' ? item.totalTime.toFixed(1) + 's' : item.totalTime + 's') : '';

                            return `
                                <div class="ans-card ${isCorrect ? 'correct' : 'incorrect'}">
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="status-badge ${isCorrect ? 'text-emerald-400' : 'text-red-400'}">
                                            <span class="material-symbols-outlined text-[17px]" style="font-variation-settings: 'FILL' 1;">${isCorrect ? 'check_circle' : 'cancel'}</span>
                                            Q${idx + 1}. ${isCorrect ? practiceText.correct : practiceText.incorrect}
                                        </span>
                                        ${totalTimeStr ? `<span class="time-badge">${totalTimeStr}</span>` : ''}
                                    </div>

                                    <div class="q-text">
                                        ${(item.hindiText && String(item.hindiText).trim().length > 0) ? `<p class="text-white font-bold">${escapeHtml(item.hindiText)}</p>` : ''}
                                        <p class="${(item.hindiText && String(item.hindiText).trim().length > 0) ? 'q-sub-text' : 'text-white font-bold'}">${escapeHtml(item.text)}</p>
                                    </div>

                                    <div class="ans-info-row">
                                        <p>
                                            <span class="label-correct">${practiceText.correctAnswer}</span>
                                            <span class="val-correct">${escapeHtml(correctText)}</span>
                                        </p>
                                        <p>
                                            <span class="text-white/60 font-semibold">${practiceText.yourAnswer}</span>
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
                            <span>${practiceText.backHome}</span>
                        </button>
                    </div>
                `;

                const backHomeBtn = qs('#btn-back-home');
                if (backHomeBtn) {
                    backHomeBtn.addEventListener('click', () => navigateTo('/fsrapp/home'));
                }
            }
        });
    </script>
@endpush
