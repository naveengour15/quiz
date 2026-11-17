/* =========================================================================
   FSRQuiz — Quiz Engine JavaScript
   Contains: Sound Engine, QuizMasterClock, KBC UI SVG/Mute helpers,
   Quiz Detail Sheet & Navigation helpers.
   Loaded exclusively on Quiz pages.
   ========================================================================= */

/* =========================================================================
   SOUND ENGINE — original, synthesized quiz-show style sound effects
   (Web Audio API tones only — no external/copyrighted audio files).
   ========================================================================= */
const Sound = (() => {
    const LS_MUTE = 'qx_sound_muted';
    let ctx = null;
    let master = null;
    let sfxBus = null;
    let delay = null, feedback = null, delayWet = null;
    let bgNodes = null;
    let muted = typeof window.isGlobalMuted === 'function'
        ? window.isGlobalMuted()
        : (localStorage.getItem(LS_MUTE) === 'true' || localStorage.getItem(LS_MUTE) === '1' || localStorage.getItem('qx_mute') === '1');

    function ensureCtx() {
        if (!ctx) {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return null;
            ctx = new AC();
            master = ctx.createGain();
            master.gain.value = isMuted() ? 0 : 0.6;
            master.connect(ctx.destination);

            delay = ctx.createDelay(1.0);
            delay.delayTime.value = 0.24;
            feedback = ctx.createGain();
            feedback.gain.value = 0.3;
            delay.connect(feedback);
            feedback.connect(delay);
            delayWet = ctx.createGain();
            delayWet.gain.value = 0.28;
            delay.connect(delayWet);
            delayWet.connect(master);

            sfxBus = ctx.createGain();
            sfxBus.gain.value = 1;
            sfxBus.connect(master);
            sfxBus.connect(delay);
        }
        if (ctx.state === 'suspended') ctx.resume().catch(() => {});
        return ctx;
    }

    function isMuted() {
        if (typeof window.isGlobalMuted === 'function') {
            return window.isGlobalMuted();
        }
        return muted;
    }

    function setMuted(v) {
        muted = !!v;
        localStorage.setItem(LS_MUTE, muted ? 'true' : 'false');
        localStorage.setItem('qx_mute', muted ? '1' : '0');
        if (master && ctx) {
            try {
                master.gain.setTargetAtTime(muted ? 0 : 0.6, ctx.currentTime, 0.05);
            } catch (e) {}
        }
        if (muted) {
            stopBgTrack();
            if (bgNodes) bgNodes.stop();
        } else if (typeof bgAudio !== 'undefined' && bgAudio) {
            bgAudio.muted = false;
            bgAudio.volume = 0.6;
            bgAudio.play().catch(() => {});
        }
        if (typeof window.updateSpeakerIcon === 'function') {
            window.updateSpeakerIcon();
        }
    }

    function toggleMuted() {
        if (typeof window.toggleGlobalMute === 'function') {
            return window.toggleGlobalMute();
        }
        setMuted(!muted);
        return muted;
    }

    function tone({ freq = 440, dur = 0.2, type = 'sine', gain = 0.2, when = 0, glideTo = null, attack = 0.01, release = 0.08, bus = null }) {
        if (muted) return;
        const c = ensureCtx();
        if (!c) return;
        const t0 = c.currentTime + when;
        const osc = c.createOscillator();
        const g = c.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, t0);
        if (glideTo) osc.frequency.exponentialRampToValueAtTime(Math.max(glideTo, 1), t0 + dur);
        g.gain.setValueAtTime(0, t0);
        g.gain.linearRampToValueAtTime(gain, t0 + attack);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur + release);
        osc.connect(g);
        g.connect(bus || sfxBus || master);
        osc.start(t0);
        osc.stop(t0 + dur + release + 0.02);
    }

    function noiseBurst({ dur = 0.25, gain = 0.18, when = 0, filterFreq = 1800, bus = null }) {
        if (muted) return;
        const c = ensureCtx();
        if (!c) return;
        const t0 = c.currentTime + when;
        const bufferSize = Math.floor(c.sampleRate * dur);
        const buffer = c.createBuffer(1, bufferSize, c.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) data[i] = (Math.random() * 2 - 1) * (1 - i / bufferSize);
        const src = c.createBufferSource();
        src.buffer = buffer;
        const filt = c.createBiquadFilter();
        filt.type = 'bandpass';
        filt.frequency.value = filterFreq;
        const g = c.createGain();
        g.gain.setValueAtTime(gain, t0);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
        src.connect(filt);
        filt.connect(g);
        g.connect(bus || sfxBus || master);
        src.start(t0);
    }

    function pluck({ freq = 440, dur = 0.4, gain = 0.15, when = 0, bus = null, brightness = 2600 }) {
        if (muted) return;
        const c = ensureCtx();
        if (!c) return;
        const t0 = c.currentTime + when;
        const osc1 = c.createOscillator();
        osc1.type = 'sine';
        osc1.frequency.value = freq;
        const osc2 = c.createOscillator();
        osc2.type = 'sine';
        osc2.frequency.value = freq * 2.005;
        const g1 = c.createGain();
        g1.gain.value = 1;
        const g2 = c.createGain();
        g2.gain.value = 0.22;
        const filt = c.createBiquadFilter();
        filt.type = 'lowpass';
        filt.frequency.value = brightness;
        filt.Q.value = 0.4;
        const amp = c.createGain();
        amp.gain.setValueAtTime(0, t0);
        amp.gain.linearRampToValueAtTime(gain, t0 + 0.008);
        amp.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
        osc1.connect(g1);
        osc2.connect(g2);
        g1.connect(filt);
        g2.connect(filt);
        filt.connect(amp);
        amp.connect(bus || sfxBus || master);
        osc1.start(t0);
        osc2.start(t0);
        osc1.stop(t0 + dur + 0.05);
        osc2.stop(t0 + dur + 0.05);
    }


    let bgAudio = null;
    let currentBgKey = null;

    let customSounds = {
        background_sound: null,
        one_min_before: null,
        question_appear_sound: null,
        start_quiz_sound: null,
        say_ans_appear_sound: null,
        answer_appear_sound: null,
        quiz_end_sound: null,
        answer_sheet_appear_sound: null,
        leaderboard_appear_sound: null,
        counter_sound: null,
    };

    function setCustomSounds(soundsObj) {
        if (!soundsObj || typeof soundsObj !== 'object') return;
        Object.keys(customSounds).forEach(key => {
            if (soundsObj[key]) {
                customSounds[key] = soundsObj[key];
            }
        });
    }

    function playCustomAudio(url) {
        if (!url) return false;
        if (muted) return false;
        try {
            const a = new Audio(url);
            a.volume = muted ? 0 : 0.7;
            const p = a.play();
            if (p !== undefined) {
                p.catch(() => {});
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    (async function initSoundSettings() {
        try {
            const cached = sessionStorage.getItem('qx_sound_settings');
            if (cached) {
                const parsed = JSON.parse(cached);
                if (parsed) {
                    setCustomSounds(parsed);
                    return;
                }
            }
            const res = await fetch('/fsrapp/quiz/sound-settings');
            if (res.ok) {
                const data = await res.json();
                if (data && data.sound_settings) {
                    setCustomSounds(data.sound_settings);
                    sessionStorage.setItem('qx_sound_settings', JSON.stringify(data.sound_settings));
                }
            }
        } catch (e) {}
    })();

    const AUDIO_TRACKS = {
        start: ['/assets/audio/quiz_start.mpeg', '/assets/audio/quiz_start'],
        question: ['/assets/audio/question_appears.mpeg', '/assets/audio/question_appears'],
        options: ['/assets/audio/option_appears_and_for_timer.mpeg', '/assets/audio/option_appears_and_for_timer'],
        leaderboard: ['/assets/audio/leaderboard.mpeg', '/assets/audio/leaderboard'],
        default: ['/assets/audio/bgm.mpeg', '/assets/audio/bgm']
    };

    let bgAudioPlayedKeys = {};

    function resetBgTrackHistory() {
        bgAudioPlayedKeys = {};
    }

    function getBgSourceUrl(trackKey) {
        if ((trackKey === 'default' || trackKey === 'background') && customSounds.background_sound) {
            return customSounds.background_sound;
        }
        if (trackKey === 'one_min_before' && customSounds.one_min_before) {
            return customSounds.one_min_before;
        }
        if (trackKey === 'start' && customSounds.start_quiz_sound) {
            return customSounds.start_quiz_sound;
        }
        if (trackKey === 'question' && customSounds.question_appear_sound) {
            return customSounds.question_appear_sound;
        }
        if (trackKey === 'options' && customSounds.answer_appear_sound) {
            return customSounds.answer_appear_sound;
        }
        if (trackKey === 'leaderboard' && customSounds.leaderboard_appear_sound) {
            return customSounds.leaderboard_appear_sound;
        }
        return null;
    }

    function playBgTrack(trackKey = 'default', force = false) {
        stopBgTrack();
        return;
    }

    function stopBgTrack() {
        if (bgAudio) {
            try {
                bgAudio.pause();
                bgAudio.currentTime = 0;
            } catch (e) {}
            bgAudio = null;
        }
        currentBgKey = null;
    }

    function startBackground(trackKey = 'default', force = false) {
        return;
    }

    function stopBackground() {
        stopBgTrack();
        if (bgNodes) bgNodes.stop();
    }

    function isBackgroundPlaying() {
        return (bgAudio && !bgAudio.paused) || !!bgNodes;
    }

    function hardStopAll() {
        stopBgTrack();
        if (bgNodes) {
            try { bgNodes.stop(); } catch (e) {}
        }
        if (master && ctx) {
            try {
                master.gain.cancelScheduledValues(ctx.currentTime);
                master.gain.setValueAtTime(muted ? 0 : 0.6, ctx.currentTime);
            } catch (e) {}
        }
        if (ctx && ctx.state === 'running') {
            try { ctx.suspend(); } catch (e) {}
        }
    }

    function tension() {
        pluck({ freq: 110, dur: 0.8, gain: 0.12, when: 0, brightness: 1200 });
    }

    function questionLoad() {
        if (customSounds.question_appear_sound) {
            playCustomAudio(customSounds.question_appear_sound);
            return;
        }
        pluck({ freq: 587.33, dur: 0.35, gain: 0.15, when: 0, brightness: 3200 });
    }

    let lastOptionsRevealTime = 0;

    function sayAnsAppear() {
        // Disabled
    }

    function optionsReveal() {
        stopBgTrack();
        lastOptionsRevealTime = Date.now();
        const url = customSounds.answer_appear_sound;
        if (url) {
            playCustomAudio(url);
            return;
        }
        [523.25, 659.25, 783.99].forEach((f, i) => pluck({
            freq: f, dur: 0.25, gain: 0.08, when: i * 0.06, brightness: 2800
        }));
    }

    function tick(isUrgent = false) {
        if (customSounds.counter_sound) {
            playCustomAudio(customSounds.counter_sound);
            return;
        }
        pluck({
            freq: isUrgent ? 880 : 660, dur: 0.06, gain: isUrgent ? 0.12 : 0.06, when: 0, brightness: isUrgent ? 4000 : 2400
        });
    }

    function select() {
        pluck({ freq: 783.99, dur: 0.12, gain: 0.1, when: 0, brightness: 3000 });
    }

    function correct() {
        [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => pluck({
            freq: f, dur: 0.45, gain: 0.1, when: i * 0.08, brightness: 3500
        }));
    }

    function wrong() {
        [311.13, 293.66, 277.18].forEach((f, i) => pluck({
            freq: f, dur: 0.3, gain: 0.12, when: i * 0.1, brightness: 1400
        }));
    }

    function drumroll(durationSec = 2.0) {
        const steps = Math.floor(durationSec / 0.14);
        for (let i = 0; i < steps; i++) {
            pluck({
                freq: 130 + (i / steps) * 40, dur: 0.12, gain: 0.035 + (i / steps) * 0.05, when: i * 0.14, brightness: 2200
            });
        }
        [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => pluck({
            freq: f, dur: 0.6, gain: 0.11, when: durationSec + i * 0.08, brightness: 3400
        }));
    }

    function fanfare() {
        if (customSounds.quiz_end_sound) {
            playCustomAudio(customSounds.quiz_end_sound);
            return;
        }
        const melody = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.5];
        melody.forEach((f, i) => {
            const dur = i === melody.length - 1 ? 0.55 : 0.18;
            pluck({ freq: f, dur, gain: 0.1, when: i * 0.15, brightness: 3000 });
        });
    }

    function answerSheet() {
        if (customSounds.answer_sheet_appear_sound) {
            playCustomAudio(customSounds.answer_sheet_appear_sound);
            return;
        }
        optionsReveal();
    }

    function leaderboard() {
        if (customSounds.leaderboard_appear_sound) {
            playCustomAudio(customSounds.leaderboard_appear_sound);
            return;
        }
        fanfare();
    }

    function startQuizSound() {
        stopBgTrack();
        if (customSounds.start_quiz_sound) {
            playCustomAudio(customSounds.start_quiz_sound);
            return;
        }
    }

    function coin() {
        [1046.5, 1318.5].forEach((f, i) => pluck({
            freq: f, dur: 0.16, gain: 0.07, when: i * 0.07, brightness: 3600
        }));
    }

    function zoom(count = 4) {
        const total = Math.min(Math.max(count, 1), 6);
        for (let i = 0; i < total; i++) {
            const delaySec = i * 0.18;
            tone({
                freq: 180 + (i * 60), glideTo: 650 + (i * 90), dur: 0.22, type: 'sine', gain: 0.14, when: delaySec, attack: 0.005, release: 0.04
            });
            pluck({
                freq: 523.25 + (i * 110), dur: 0.28, gain: 0.11, when: delaySec + 0.04, brightness: 3200
            });
        }
    }

    return {
        ensureCtx, isMuted, setMuted, toggleMuted, setCustomSounds, playBgTrack, resetBgTrackHistory, stopBgTrack,
        startBackground, stopBackground, isBackgroundPlaying, hardStopAll, tension, questionLoad,
        sayAnsAppear, optionsReveal, zoom, tick, select, correct, wrong, drumroll, fanfare, coin, answerSheet, leaderboard, startQuizSound
    };
})();

/* Sound Visibility Listeners */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) Sound.hardStopAll();
});
window.addEventListener('pagehide', () => Sound.hardStopAll());
window.addEventListener('beforeunload', () => Sound.hardStopAll());

/* =========================================================================
   QUIZ MASTER CLOCK — Timeline & Server Sync Manager
   ========================================================================= */
const QuizMasterClock = (() => {
    let serverOffsetMs = 0;
    let quizConfigs = {};

    function updateServerTime(server_now_ms) {
        if (server_now_ms) {
            serverOffsetMs = Number(server_now_ms) - Date.now();
        }
    }

    function getServerNowMs() {
        return Date.now() + serverOffsetMs;
    }

    function parseTimeMs(v) {
        if (!v) return 0;
        if (typeof v === 'number') return v;
        const clean = String(v).trim().replace(' ', 'T');
        const ms = Date.parse(clean);
        return isNaN(ms) ? 0 : ms;
    }

    function setQuizConfig(quizId, config) {
        if (!quizId || !config) return;

        let start_time_ms = config.start_time_ms ? Number(config.start_time_ms) : null;
        if (!start_time_ms) {
            const rawStart = config.start_time || config.availability_start || config.available_start;
            if (rawStart) {
                start_time_ms = parseTimeMs(rawStart);
            }
        }

        const existing = quizConfigs[quizId] || {};
        if (!start_time_ms) {
            start_time_ms = existing.start_time_ms || getServerNowMs();
        }

        quizConfigs[quizId] = Object.assign({}, {
            warmup_seconds: 10,
            per_question_seconds: 15,
            hold_seconds: 5,
            answer_seconds: 10,
            anssheet_seconds: 10,
            leaderboard_wait_seconds: 30,
            total_questions: 10
        }, existing, config, {
            start_time_ms: start_time_ms
        });
    }

    function calculateState(quizId) {
        const cfg = quizConfigs[quizId];
        if (!cfg || !cfg.start_time_ms) {
            return {
                phase: 'UNKNOWN',
                remainingSec: 0
            };
        }

        const nowMs = getServerNowMs();
        const startMs = Number(cfg.start_time_ms);
        const warmupMs = (cfg.warmup_seconds || 10) * 1000;
        const perQMs = (cfg.per_question_seconds || 15) * 1000;
        const holdMs = (cfg.hold_seconds || 5) * 1000;
        const totalQs = Number(cfg.total_questions || 10);
        const questionsDurationMs = totalQs * perQMs;
        const anssheetMs = (cfg.anssheet_seconds || 10) * 1000;
        const lbWaitMs = (cfg.leaderboard_wait_seconds || 30) * 1000;

        const warmupEndMs = startMs + warmupMs;
        const questionsEndMs = warmupEndMs + questionsDurationMs;
        const anssheetEndMs = questionsEndMs + anssheetMs;
        const lbWaitEndMs = anssheetEndMs + lbWaitMs;

        if (nowMs < startMs) {
            return {
                phase: 'PENDING_WAIT',
                remainingMs: startMs - nowMs,
                remainingSec: Math.ceil((startMs - nowMs) / 1000),
                startMs,
                warmupEndMs
            };
        }

        if (nowMs < warmupEndMs) {
            return {
                phase: 'WARMUP_WAIT',
                remainingMs: warmupEndMs - nowMs,
                remainingSec: Math.ceil((warmupEndMs - nowMs) / 1000),
                startMs,
                warmupEndMs
            };
        }

        if (nowMs < questionsEndMs) {
            const elapsedQsMs = nowMs - warmupEndMs;
            const qIndex = Math.floor(elapsedQsMs / perQMs);
            const qNumber = qIndex + 1;
            const msInQ = elapsedQsMs % perQMs;

            if (msInQ < holdMs) {
                return {
                    phase: 'QUESTION_HOLD',
                    questionIndex: qIndex,
                    questionNumber: qNumber,
                    totalQuestions: totalQs,
                    msInQ,
                    remainingHoldMs: holdMs - msInQ,
                    remainingHoldSec: Math.ceil((holdMs - msInQ) / 1000),
                    remainingQMs: perQMs - msInQ,
                    remainingQSec: Math.ceil((perQMs - msInQ) / 1000),
                    remainingSec: Math.ceil((holdMs - msInQ) / 1000)
                };
            } else {
                return {
                    phase: 'OPTIONS_ANSWER',
                    questionIndex: qIndex,
                    questionNumber: qNumber,
                    totalQuestions: totalQs,
                    msInQ,
                    remainingAnswerMs: perQMs - msInQ,
                    remainingAnswerSec: Math.ceil((perQMs - msInQ) / 1000),
                    remainingQMs: perQMs - msInQ,
                    remainingQSec: Math.ceil((perQMs - msInQ) / 1000),
                    remainingSec: Math.ceil((perQMs - msInQ) / 1000)
                };
            }
        }

        if (nowMs < anssheetEndMs) {
            return {
                phase: 'ANSWERSHEET_REVIEW',
                remainingMs: anssheetEndMs - nowMs,
                remainingSec: Math.ceil((anssheetEndMs - nowMs) / 1000)
            };
        }

        if (nowMs < lbWaitEndMs) {
            return {
                phase: 'LEADERBOARD_WAIT',
                remainingMs: lbWaitEndMs - nowMs,
                remainingSec: Math.ceil((lbWaitEndMs - nowMs) / 1000)
            };
        }

        return {
            phase: 'FINAL_WINNERS',
            remainingSec: 0
        };
    }

    async function syncStatus(quizId) {
        if (!quizId) return;
        try {
            const resp = await fetch(`/fsrapp/quiz/status/${quizId}`);
            if (resp.ok) {
                const data = await resp.json();
                if (data) {
                    if (data.sound_settings) {
                        Sound.setCustomSounds(data.sound_settings);
                    }
                    updateServerTime(data.server_now_ms);
                    if (data.start_time_ms) {
                        setQuizConfig(quizId, {
                            start_time_ms: data.start_time_ms,
                            total_questions: data.total_questions,
                            warmup_seconds: data.warmup_seconds,
                            per_question_seconds: data.per_question_seconds,
                            hold_seconds: data.hold_seconds,
                            answer_seconds: data.answer_seconds,
                            anssheet_seconds: data.anssheet_seconds,
                            leaderboard_wait_seconds: data.leaderboard_wait_seconds
                        });
                        return;
                    }
                }
            }
        } catch (e) {}

        try {
            const res = await api(`/quiz/${quizId}`, { method: 'GET' });
            const data = unwrap(res) || {};
            if (res && res.server_now_ms) updateServerTime(res.server_now_ms);
            setQuizConfig(quizId, data);
        } catch (e) {}
    }

    return {
        updateServerTime,
        getServerNowMs,
        nowMs: getServerNowMs,
        setQuizConfig,
        calculateState,
        syncStatus
    };
})();

/* =========================================================================
   KBC STYLED HELPERS
   ========================================================================= */
let __kbcHexSvgId = 0;
function kbcOptionSvg() {
    const id = 'kbcHexGrad' + (__kbcHexSvgId++);
    return `<svg class="kbc-hex-svg" viewBox="0 0 300 74" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="${id}" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color:var(--kbc-c1)"/>
        <stop offset="22%" style="stop-color:var(--kbc-c2)"/>
        <stop offset="45%" style="stop-color:var(--kbc-c3)"/>
        <stop offset="68%" style="stop-color:var(--kbc-c4)"/>
        <stop offset="100%" style="stop-color:var(--kbc-c5)"/>
        <animateTransform attributeName="gradientTransform" type="rotate" from="0 0.5 0.5" to="360 0.5 0.5" dur="3.5s" repeatCount="indefinite"/>
      </linearGradient>
    </defs>
    <polygon points="26,3 274,3 297,37 274,71 26,71 3,37" fill="none" stroke="url(#${id})" stroke-width="3.5" stroke-linejoin="round"/>
  </svg>`;
}

function kbcMuteBtnHtml() {
    const isMuted = Sound.isMuted();
    return `<button id="kbc-mute-toggle" class="kbc-mute-btn kbc-icon-badge" title="Sound ${isMuted ? 'Off (Click to turn ON)' : 'On (Click to turn OFF)'}">
    <span class="material-symbols-outlined text-[18px]">${isMuted ? 'volume_off' : 'volume_up'}</span>
  </button>`;
}

let globalMuteWired = false;
function wireMuteBtn(root = document) {
    if (globalMuteWired) return;
    globalMuteWired = true;
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#kbc-mute-toggle, .kbc-mute-btn');
        if (btn) {
            Sound.ensureCtx();
            const nowMuted = Sound.toggleMuted();
            if (typeof window.updateSpeakerIcon === 'function') {
                window.updateSpeakerIcon();
            }
        }
    });
}
document.addEventListener('DOMContentLoaded', () => wireMuteBtn());

/* =========================================================================
   QUIZ NAVIGATION & SHEET HELPERS
   ========================================================================= */
async function goToQuizOrWait(quizId, startTimeStr = null, pendingTimeStr = null) {
    if (!quizId) return;

    if (!startTimeStr) {
        try {
            const res = await api(`/quiz/${quizId}`, { method: 'GET' });
            const data = unwrap(res) || {};
            startTimeStr = pick(data, ['start_time', 'availability_start', 'available_start']);
            pendingTimeStr = pick(data, ['pending_time']);
        } catch (e) {}
    }

    if (startTimeStr || pendingTimeStr) {
        const startMs = Date.parse((startTimeStr || '').replace(' ', 'T')) || 0;
        const pendingMs = Date.parse((pendingTimeStr || '').replace(' ', 'T')) || 0;
        const now = Date.now();

        if ((pendingMs > 0 && now < pendingMs) || (startMs > 0 && now < startMs)) {
            navigate(`/quiz/waiting/${quizId}`);
            return;
        }
    }

    await QuizMasterClock.syncStatus(quizId);
    const state = QuizMasterClock.calculateState(quizId);

    if (state.phase === 'PENDING_WAIT' || state.phase === 'WARMUP_WAIT') {
        navigate(`/quiz/waiting/${quizId}`);
        return;
    }

    if (state.phase === 'ANSWERSHEET_REVIEW') {
        navigate(`/quiz/review/${quizId}`);
        return;
    }

    if (state.phase === 'LEADERBOARD_WAIT') {
        localStorage.setItem(`qx_wait_mode_${quizId}`, 'winners');
        navigate(`/quiz/waiting/${quizId}`);
        return;
    }

    if (state.phase === 'FINAL_WINNERS') {
        navigate(`/quiz/winners/${quizId}`);
        return;
    }

    navigate(`/quiz/play/${quizId}`);
}

function isAlreadyPlayedFlag(obj) {
    return !!pick(obj, ['is_played', 'already_played', 'has_played', 'is_attempted', 'attempted', 'is_completed', 'played']);
}

function isAlreadyPlayedError(e) {
    const msg = (e && e.message || '').toLowerCase();
    return msg.includes('already') && (msg.includes('play') || msg.includes('submit') || msg.includes('complet') || msg.includes('attempt') || msg.includes('particip'));
}

async function openQuizSheet(quizId, maxPoints = null) {
    const existing = qs('#quiz-sheet-root');
    if (existing) existing.remove();

    const root = el(`
  <div id="quiz-sheet-root">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50" id="qsheet-overlay"></div>
    <div class="fixed bottom-0 w-full z-50 bg-[#1a0b3d] rounded-t-3xl border-t border-purple-500/30 shadow-[0_-10px_40px_rgba(168,85,247,0.15)] max-w-md mx-auto left-1/2 -translate-x-1/2 transform transition-transform duration-300" id="qsheet">
      <div class="px-5 pb-8 pt-3">
        <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-5"></div>
        <div id="qsheet-body" class="min-h-[200px] flex items-center justify-center">
            <div class="w-8 h-8 border-4 border-[#e879f9]/30 border-t-[#e879f9] rounded-full spin"></div>
        </div>
      </div>
    </div>
  </div>`);
    document.body.appendChild(root);
    qs('#qsheet-overlay').onclick = () => root.remove();

    try {
        const res = await api(`/quiz/${quizId}`, { method: 'GET' });
        const q = unwrap(res) || {};
        const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
        const prize = pick(q, ['prize_pool', 'prize', 'total_prize'], '0');
        const fee = pick(q, ['entry_fee', 'fee'], '0');
        const joined = pick(q, ['joined_count', 'players', 'participants_count'], '0');
        const desc = pick(q, ['description', 'rules'], '');
        const needsPassword = !!pick(q, ['is_private', 'private']);
        const alreadyPlayed = isAlreadyPlayedFlag(q);
        const isJoined = !!pick(q, ['is_joined', 'joined', 'user_joined']) || !!q.is_joined;
        const startTimeStr = pick(q, ['start_time', 'availability_start', 'available_start']);

        let displayPrize = escapeHtml(prize);
        if (maxPoints && maxPoints !== '0') {
            displayPrize = 'Upto ' + escapeHtml(maxPoints) + ' pts';
        }

        try {
            sessionStorage.setItem('qx_prize_' + quizId, String(prize));
        } catch (e) {}

        let buttonHtml =
            `<button id="qsheet-join-btn" class="w-full mt-2 bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-[#1a0b3d] font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[19px]">play_circle</span> Join Quiz Now
            </button>`;

        if (alreadyPlayed) {
            buttonHtml =
                `<button id="qsheet-leaderboard-btn" class="w-full mt-2 bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[19px]">leaderboard</span> View Leaderboard
                </button>`;
        } else if (isJoined) {
            buttonHtml =
                `<button id="qsheet-open-btn" class="w-full mt-2 bg-gradient-to-r from-[#86efac] to-[#3b82f6] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(59,130,246,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[19px]">play_arrow</span> Open Quiz
                </button>`;
        }

        qs('#qsheet-body').outerHTML = `
      <div id="qsheet-body">
        <div class="mb-5 text-center">
          ${alreadyPlayed ? `<span class="inline-block mb-3 bg-[#86efac] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">ALREADY PLAYED</span>` : isJoined ? `<span class="inline-block mb-3 bg-[#e879f9] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">YOU HAVE JOINED</span>` : ''}
          <h3 class="font-display text-2xl font-extrabold text-[#ffe9b0] mb-2 leading-tight">${title}</h3>
          ${desc ? `<p class="text-[13px] text-[#e8ddff]/70 mb-4 line-clamp-3">${escapeHtml(desc)}</p>` : ''}
          
          <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#ffd76a] mb-1">emoji_events</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">PRIZE POOL</p>
                <p class="text-[#ffe9b0] font-mono font-bold text-[13px]">${displayPrize}</p>
            </div>
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#86efac] mb-1">confirmation_number</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">ENTRY FEE</p>
                <p class="text-[#86efac] font-mono font-bold text-[13px]">${fee == 0 || fee == 'Free' ? 'Free' : escapeHtml(fee)}</p>
            </div>
            <div class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                <span class="material-symbols-outlined text-[18px] text-[#e879f9] mb-1">groups</span>
                <p class="text-[10px] font-bold text-white/50 mb-0.5">JOINED</p>
                <p class="text-[#f0abfc] font-mono font-bold text-[13px]">${escapeHtml(joined)}</p>
            </div>
          </div>
          ${(!alreadyPlayed && !isJoined && needsPassword) ? `<input id="qsheet-password" placeholder="Enter quiz password" class="mt-4 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-[#e8ddff] placeholder-white/30 focus:outline-none focus:border-[#e879f9]/50 focus:ring-1 focus:ring-[#e879f9]/50 transition-all">` : ''}
        </div>
        ${buttonHtml}
        <button id="qsheet-cancel-btn" class="w-full mt-3 py-2.5 text-[#e8ddff]/60 font-bold text-[14px] hover:text-[#e8ddff] transition-colors rounded-xl hover:bg-white/5">Cancel</button>
      </div>`;
        qs('#qsheet-cancel-btn').onclick = () => root.remove();

        if (alreadyPlayed) {
            qs('#qsheet-leaderboard-btn').onclick = () => {
                root.remove();
                navigate(`/quiz/winners/${quizId}`);
            };
        } else if (isJoined) {
            qs('#qsheet-open-btn').onclick = () => {
                root.remove();
                goToQuizOrWait(quizId, startTimeStr);
            };
        } else {
            qs('#qsheet-join-btn').onclick = async () => {
                const password = needsPassword ? (qs('#qsheet-password')?.value || null) : null;
                await withLoader(async () => {
                    try {
                        const joinRes = await api(`/quiz/${quizId}/join`, {
                            method: 'POST',
                            body: { password }
                        });
                        const joinData = unwrap(joinRes);
                        const startTimeStr = pick(joinRes, ['start_time']) || pick(joinData, ['start_time']);
                        const pt = pick(joinRes, ['pending_time']) || pick(joinData, ['pending_time']);

                        if (joinRes && joinRes.success === false) {
                            if ((joinRes.message || '').toLowerCase().includes('already')) {
                                toast(joinRes.message || 'You have already joined this quiz', 'info');
                                root.remove();
                                goToQuizOrWait(quizId, startTimeStr, pt);
                                return;
                            }
                            toast(joinRes.message || 'Failed to join quiz', 'error');
                            return;
                        }

                        toast(pick(joinRes, ['message']) || pick(joinData, ['message']) || 'Joined quiz!', 'success');
                        root.remove();
                        goToQuizOrWait(quizId, startTimeStr, pt);
                    } catch (e) {
                        const payload = e.payload || {};
                        const st = pick(payload, ['start_time']) || pick(e, ['start_time']) || startTimeStr;
                        const pt = pick(payload, ['pending_time']) || pick(e, ['pending_time']);

                        if (isAlreadyPlayedError(e)) {
                            toast('You have already played this quiz. Showing the leaderboard…', 'info');
                            root.remove();
                            navigate(`/quiz/winners/${quizId}`);
                        } else if ((e.message || '').toLowerCase().includes('already')) {
                            toast('You have already joined this quiz!', 'info');
                            root.remove();
                            goToQuizOrWait(quizId, st, pt);
                        } else {
                            toast(e.message, 'error');
                        }
                    }
                });
            };
        }
    } catch (e) {
        qs('#qsheet-body').innerHTML = `<p class="text-red-400 text-center py-8">${escapeHtml(e.message)}</p>`;
    }
}
