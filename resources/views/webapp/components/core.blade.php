<script>
    /* =========================================================================
   FSRQuiziz Core Engine — API, Storage, Router, Sound Engine & Layout Helpers
   ========================================================================= */

    const DEFAULT_API_BASE = 'https://fsrquiz.chaloonline.in/api';

    const LS = {
        apiBase: 'qx_api_base',
        token: 'qx_access_token',
        user: 'qx_user',
        mobile: 'qx_pending_mobile',
        otpToken: 'qx_otp_token',
        isNewUser: 'qx_is_new_user',
    };

    const Store = {
        getApiBase() {
            return localStorage.getItem(LS.apiBase) || DEFAULT_API_BASE;
        },
        setApiBase(v) {
            localStorage.setItem(LS.apiBase, v.trim().replace(/\/+$/, ''));
        },
        getToken() {
            return localStorage.getItem(LS.token) || '';
        },
        setToken(v) {
            localStorage.setItem(LS.token, v || '');
        },
        clearToken() {
            localStorage.removeItem(LS.token);
        },
        getUser() {
            try {
                return JSON.parse(localStorage.getItem(LS.user) || 'null');
            } catch (e) {
                return null;
            }
        },
        setUser(u) {
            localStorage.setItem(LS.user, JSON.stringify(u || null));
        },
        clearUser() {
            localStorage.removeItem(LS.user);
        },
        getPendingMobile() {
            return localStorage.getItem(LS.mobile) || '';
        },
        setPendingMobile(v) {
            localStorage.setItem(LS.mobile, v || '');
        },
        getOtpToken() {
            return localStorage.getItem(LS.otpToken) || '';
        },
        setOtpToken(v) {
            localStorage.setItem(LS.otpToken, v || '');
        },
        getIsNewUser() {
            return localStorage.getItem(LS.isNewUser) === '1';
        },
        setIsNewUser(v) {
            localStorage.setItem(LS.isNewUser, v ? '1' : '0');
        },
    };

    /* ------------------------------- API LAYER -------------------------------- */
    class ApiError extends Error {
        constructor(message, status, payload) {
            super(message);
            this.status = status;
            this.payload = payload;
        }
    }

    async function api(path, {
        method = 'GET',
        body = null,
        auth = true,
        query = null
    } = {}) {
        const base = Store.getApiBase();
        if (!base) {
            openSettings(true);
            throw new ApiError('Please set the API base URL first.', 0, null);
        }
        let url = (path.startsWith('/api') || path.startsWith('/fsrapp')) ? (window.location.origin + path) : (
            base + path);
        if (query) {
            const qs = Object.entries(query).filter(([, v]) => v !== undefined && v !== null && v !== '').map(([k,
                v
            ]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
            if (qs) url += (url.includes('?') ? '&' : '?') + qs;
        }
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        const token = Store.getToken();
        if (auth && token) headers['Authorization'] = 'Bearer ' + token;

        let res;
        try {
            res = await fetch(url, {
                method,
                headers,
                body: body ? JSON.stringify(body) : undefined
            });
        } catch (e) {
            throw new ApiError(
                'Network error — could not reach the API. Check the API base URL in Settings and your connection.',
                0, null);
        }

        let data = null;
        const text = await res.text();
        try {
            data = text ? JSON.parse(text) : null;
        } catch (e) {
            data = {
                raw: text
            };
        }

        if (res.status === 401) {
            Store.clearToken();
            Store.clearUser();
            if (location.hash !== '#/login') {
                toast('Session expired. Please login again.', 'error');
                navigate('/login');
            }
            throw new ApiError('Unauthorized', 401, data);
        }
        if (!res.ok) {
            console.error(`[API ERROR] ${method} ${url} → ${res.status}`, data);
            const msg = (data && (data.message || data.error || data.msg)) || `Request failed (${res.status})`;
            throw new ApiError(msg, res.status, data);
        }
        return data;
    }

    function unwrap(json) {
        if (json == null) return json;
        if (json.data !== undefined) return json.data;
        if (json.result !== undefined) return json.result;
        return json;
    }

    function asList(json) {
        const d = unwrap(json);
        if (Array.isArray(d)) return d;
        if (d && Array.isArray(d.answers)) return d.answers;
        if (d && Array.isArray(d.items)) return d.items;
        if (d && Array.isArray(d.list)) return d.list;
        if (d && Array.isArray(d.rows)) return d.rows;
        for (const k in (d || {})) {
            if (Array.isArray(d[k])) return d[k];
        }
        return [];
    }

    const pick = (obj, keys, fallback = '') => {
        for (const k of keys) {
            if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k];
        }
        return fallback;
    };

    /* -------------------------------- HELPERS --------------------------------- */
    function el(html) {
        const t = document.createElement('template');
        t.innerHTML = html.trim();
        return t.content.firstElementChild;
    }

    function qs(sel, root = document) {
        return root.querySelector(sel);
    }

    function qsa(sel, root = document) {
        return [...root.querySelectorAll(sel)];
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

    let toastTimer = null;

    function toast(msg, type = 'info') {
        const box = qs('#toast');
        const colors = {
            info: 'bg-surface-container-high border-glass text-on-surface',
            error: 'bg-error-container border-error text-on-error-container',
            success: 'bg-mint/20 border-mint text-mint'
        };
        box.className =
            `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg font-body-md text-body-md ${colors[type]||colors.info}`;
        box.textContent = msg;
        box.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => box.classList.add('hidden'), 3200);
    }

    function showLoader() {
        qs('#global-loader').classList.remove('hidden');
        qs('#global-loader').classList.add('flex');
    }

    function hideLoader() {
        qs('#global-loader').classList.add('hidden');
        qs('#global-loader').classList.remove('flex');
    }
    async function withLoader(fn) {
        showLoader();
        try {
            return await fn();
        } finally {
            hideLoader();
        }
    }

    /* ----------------------------- SOUND ENGINE ------------------------------- */
    const Sound = (() => {
        const LS_MUTE = 'qx_sound_muted';
        let ctx = null;
        let master = null;
        let sfxBus = null;
        let delay = null,
            feedback = null,
            delayWet = null;
        let bgNodes = null;
        let muted = false;
        const pendingSfx = [];
        let contextResumePending = false;
        try {
            muted = localStorage.getItem(LS_MUTE) === '1';
        } catch (e) {}

        function flushPendingSfx() {
            if (!ctx || ctx.state !== 'running' || pendingSfx.length === 0) return;
            if (isSoundMuted()) {
                pendingSfx.length = 0;
                return;
            }
            const queued = pendingSfx.splice(0);
            queued.forEach(play => {
                try {
                    play();
                } catch (e) {
                    console.warn('[Webapp Sound] Deferred effect failed:', e);
                }
            });
        }

        function requestContextResume() {
            if (!ctx || ctx.state === 'running' || ctx.state === 'closed' || contextResumePending) return;
            contextResumePending = true;
            try {
                Promise.resolve(ctx.resume()).then(() => {
                    contextResumePending = false;
                    flushPendingSfx();
                }).catch(() => {
                    contextResumePending = false;
                });
            } catch (e) {
                contextResumePending = false;
            }
        }

        function ensureCtx() {
            if (ctx && ctx.state === 'closed') pendingSfx.length = 0;
            if (!ctx || ctx.state === 'closed') {
                const AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return null;
                ctx = new AC();
                master = ctx.createGain();
                master.gain.value = muted ? 0 : 0.6;
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
            requestContextResume();
            return ctx;
        }

        function isSoundMuted() {
            return muted || (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted());
        }

        function scheduleSfx(play) {
            if (isSoundMuted()) return;
            const c = ensureCtx();
            if (!c) return;
            if (c.state === 'running') {
                play();
                return;
            }
            if (pendingSfx.length >= 32) pendingSfx.shift();
            pendingSfx.push(play);
        }

        function isMuted() {
            return muted;
        }

        function setMuted(v) {
            muted = v;
            try {
                localStorage.setItem(LS_MUTE, v ? '1' : '0');
            } catch (e) {}
            if (v) pendingSfx.length = 0;
            if (master && ctx) {
                try {
                    master.gain.setTargetAtTime(v ? 0 : 0.6, ctx.currentTime, 0.05);
                } catch (e) {}
            }
            if (v) {
                stopBgTrack();
                if (bgNodes) bgNodes.stop();
            } else if (bgAudio) {
                bgAudio.muted = false;
                bgAudio.volume = 0.6;
                bgAudio.play().catch(() => {});
            }
        }

        function toggleMuted() {
            setMuted(!muted);
            return muted;
        }

        function tone({
            freq = 440,
            dur = 0.2,
            type = 'sine',
            gain = 0.2,
            when = 0,
            glideTo = null,
            attack = 0.01,
            release = 0.08,
            bus = null
        }) {
            scheduleSfx(() => {
                const c = ctx;
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
            });
        }

        function noiseBurst({
            dur = 0.25,
            gain = 0.18,
            when = 0,
            filterFreq = 1800,
            bus = null
        }) {
            scheduleSfx(() => {
                const c = ctx;
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
            });
        }

        function pluck({
            freq = 440,
            dur = 0.4,
            gain = 0.15,
            when = 0,
            bus = null,
            brightness = 2600
        }) {
            scheduleSfx(() => {
                const c = ctx;
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
            });
        }

        function startBackground() {
            return;
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

        const audioCache = {};

        function preloadAudioUrl(url) {
            if (!url || audioCache[url]) return;
            try {
                const a = new Audio();
                a.preload = 'auto';
                a.setAttribute('playsinline', 'true');
                a.setAttribute('webkit-playsinline', 'true');
                a.src = url;
                audioCache[url] = a;
            } catch (e) {}
        }

        function preloadAllSounds() {
            Object.values(customSounds).forEach(url => {
                if (url && typeof url === 'string') {
                    preloadAudioUrl(url);
                }
            });
        }

        function setCustomSounds(soundsObj) {
            if (!soundsObj || typeof soundsObj !== 'object') return;
            Object.keys(customSounds).forEach(key => {
                if (soundsObj[key]) {
                    customSounds[key] = soundsObj[key];
                    preloadAudioUrl(soundsObj[key]);
                }
            });
            try {
                localStorage.setItem('qx_cached_sound_urls', JSON.stringify(customSounds));
            } catch (e) {}
        }

        // 1. Instantly boot from localStorage cache (Zero network delay)
        try {
            const cached = localStorage.getItem('qx_cached_sound_urls') || sessionStorage.getItem('qx_cached_sound_urls');
            if (cached) {
                setCustomSounds(JSON.parse(cached));
            }
        } catch (e) {}

        function playCustomAudio(url, onFailure) {
            if (!url || isSoundMuted()) return false;
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return false;
            let failureHandled = false;
            const handleFailure = error => {
                if (failureHandled) return;
                failureHandled = true;
                if (typeof onFailure === 'function') {
                    onFailure(error);
                } else {
                    console.warn('[Webapp Sound] Audio playback failed:', error || url);
                }
            };
            if (typeof window.playQuizAudio === 'function') {
                try {
                    const played = window.playQuizAudio(url, 0.7);
                    if (!played) {
                        handleFailure();
                        return typeof onFailure === 'function';
                    }
                    return true;
                } catch (e) {
                    handleFailure(e);
                    return typeof onFailure === 'function';
                }
            }
            try {
                let a = audioCache[url];
                if (!a) {
                    a = new Audio(url);
                    a.preload = 'auto';
                    a.setAttribute('playsinline', 'true');
                    a.setAttribute('webkit-playsinline', 'true');
                    audioCache[url] = a;
                }
                a.onerror = handleFailure;
                a.muted = muted;
                a.volume = muted ? 0 : 0.7;
                a.currentTime = 0;
                if (muted) return false;
                const p = a.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(e => {
                        if (e && e.name === 'AbortError') return;
                        handleFailure(e);
                    });
                }
                return true;
            } catch (e) {
                handleFailure(e);
                return typeof onFailure === 'function';
            }
        }

        (async function initSoundSettings() {
            try {
                const res = await fetch('/fsrapp/quiz/sound-settings');
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.sound_settings) {
                        setCustomSounds(data.sound_settings);
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
                const a = bgAudio;
                bgAudio = null;
                currentBgKey = null;
                try {
                    a.pause();
                    a.currentTime = 0;
                } catch (e) {}
                if (a._playPromise) {
                    a._playPromise.then(() => {
                        try {
                            a.pause();
                            a.currentTime = 0;
                        } catch (e) {}
                    }).catch(() => {});
                }
            }
            currentBgKey = null;
        }

        function startBackground(trackKey = 'default') {
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
                try {
                    bgNodes.stop();
                } catch (e) {}
            }
            if (master && ctx) {
                try {
                    master.gain.cancelScheduledValues(ctx.currentTime);
                    master.gain.setValueAtTime(muted ? 0 : 0.6, ctx.currentTime);
                } catch (e) {}
            }
            if (ctx && ctx.state === 'running') {
                try {
                    ctx.suspend();
                } catch (e) {}
            }
        }

        function tension() {
            tone({
                freq: 260,
                dur: 0.8,
                type: 'sine',
                gain: 0.05,
                glideTo: 400,
                attack: 0.08,
                release: 0.35,
                bus: master
            });
        }

        function questionLoad() {
            const fallback = () => [392.00, 523.25, 659.25].forEach((f, i) => pluck({
                freq: f,
                dur: 0.5,
                gain: 0.11,
                when: i * 0.11,
                brightness: 2400
            }));
            if (customSounds.question_appear_sound &&
                playCustomAudio(customSounds.question_appear_sound, fallback)) {
                return;
            }
            fallback();
        }

        function sayAnsAppear() {
            // Disabled
        }

        function optionsReveal() {
            stopBgTrack();
            const url = customSounds.answer_appear_sound;
            const fallback = () => [587.33, 783.99].forEach((f, i) => pluck({
                freq: f,
                dur: 0.3,
                gain: 0.06,
                when: i * 0.07,
                brightness: 2200
            }));
            if (url && playCustomAudio(url, fallback)) {
                return;
            }
            fallback();
        }

        function tick(urgent = false) {
            const fallback = () => {
                if (urgent) {
                    pluck({
                        freq: 1046.5,
                        dur: 0.09,
                        gain: 0.07,
                        brightness: 3200,
                        bus: master
                    });
                } else {
                    pluck({
                        freq: 698.46,
                        dur: 0.09,
                        gain: 0.035,
                        brightness: 2000,
                        bus: master
                    });
                }
            };
            if (customSounds.counter_sound && playCustomAudio(customSounds.counter_sound, fallback)) {
                return;
            }
            fallback();
        }

        function select() {
            pluck({
                freq: 587.33,
                dur: 0.14,
                gain: 0.07,
                brightness: 2600
            });
        }

        function correct() {
            [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => pluck({
                freq: f,
                dur: 0.35,
                gain: 0.1,
                when: i * 0.09,
                brightness: 3200
            }));
        }

        function wrong() {
            [392.00, 349.23].forEach((f, i) => pluck({
                freq: f,
                dur: 0.4,
                gain: 0.08,
                when: i * 0.13,
                brightness: 1500
            }));
        }

        function drumroll(durationSec = 1.6) {
            const c = ensureCtx();
            if (!c) return;
            const notes = [392.00, 440.00, 392.00, 440.00];
            const steps = Math.floor(durationSec / 0.14);
            for (let i = 0; i < steps; i++) {
                pluck({
                    freq: notes[i % notes.length],
                    dur: 0.16,
                    gain: 0.035 + (i / steps) * 0.05,
                    when: i * 0.14,
                    brightness: 2200
                });
            }
            [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => pluck({
                freq: f,
                dur: 0.6,
                gain: 0.11,
                when: durationSec + i * 0.08,
                brightness: 3400
            }));
        }

        function fanfare() {
            const fallback = () => {
                const melody = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.5];
                melody.forEach((f, i) => {
                    const dur = i === melody.length - 1 ? 0.55 : 0.18;
                    pluck({
                        freq: f,
                        dur,
                        gain: 0.1,
                        when: i * 0.15,
                        brightness: 3000
                    });
                });
            };
            if (customSounds.quiz_end_sound && playCustomAudio(customSounds.quiz_end_sound, fallback)) {
                return;
            }
            fallback();
        }

        function answerSheet() {
            if (customSounds.answer_sheet_appear_sound &&
                playCustomAudio(customSounds.answer_sheet_appear_sound, optionsReveal)) {
                return;
            }
            optionsReveal();
        }

        function leaderboard() {
            if (customSounds.leaderboard_appear_sound &&
                playCustomAudio(customSounds.leaderboard_appear_sound, fanfare)) {
                return;
            }
            fanfare();
        }

        function startQuizSound() {
            stopBgTrack();
            const fallback = () => {
                tone({
                    freq: 392,
                    glideTo: 784,
                    dur: 0.65,
                    gain: 0.12,
                    attack: 0.02,
                    release: 0.12
                });
                pluck({
                    freq: 659.25,
                    dur: 0.45,
                    gain: 0.09,
                    when: 0.18,
                    brightness: 3000
                });
            };
            if (customSounds.start_quiz_sound && playCustomAudio(customSounds.start_quiz_sound, fallback)) {
                return;
            }
            fallback();
        }

        function coin() {
            [1046.5, 1318.5].forEach((f, i) => pluck({
                freq: f,
                dur: 0.16,
                gain: 0.07,
                when: i * 0.07,
                brightness: 3600
            }));
        }

        function zoom(count = 4) {
            const total = Math.min(Math.max(count, 1), 6);
            for (let i = 0; i < total; i++) {
                const delaySec = i * 0.18;
                tone({
                    freq: 180 + (i * 60),
                    glideTo: 650 + (i * 90),
                    dur: 0.22,
                    type: 'sine',
                    gain: 0.14,
                    when: delaySec,
                    attack: 0.005,
                    release: 0.04
                });
                pluck({
                    freq: 523.25 + (i * 110),
                    dur: 0.28,
                    gain: 0.11,
                    when: delaySec + 0.04,
                    brightness: 3200
                });
            }
        }

        return {
            ensureCtx,
            isMuted,
            setMuted,
            toggleMuted,
            setCustomSounds,
            playBgTrack,
            resetBgTrackHistory,
            stopBgTrack,
            startBackground,
            stopBackground,
            isBackgroundPlaying,
            hardStopAll,
            tension,
            questionLoad,
            sayAnsAppear,
            optionsReveal,
            zoom,
            tick,
            select,
            correct,
            wrong,
            drumroll,
            fanfare,
            answerSheet,
            leaderboard,
            startQuizSound,
            preloadAllSounds,
            audioCache
        };
    })();

    window.QuizSoundEngine = Sound;

    ['pointerdown', 'touchstart', 'click', 'keydown'].forEach(eventName => {
        document.addEventListener(eventName, () => {
            Sound.ensureCtx();
        }, { capture: true, passive: true });
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) Sound.hardStopAll();
    });
    window.addEventListener('pagehide', () => Sound.hardStopAll());
    window.addEventListener('beforeunload', () => Sound.hardStopAll());

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
    let globalWebappMuteWired = false;

    function wireMuteBtn(root = document) {
        if (globalWebappMuteWired) return;
        globalWebappMuteWired = true;
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#kbc-mute-toggle, .kbc-mute-btn');
            if (btn) {
                Sound.ensureCtx();
                const nowMuted = Sound.toggleMuted();
                document.querySelectorAll('#kbc-mute-toggle, .kbc-mute-btn').forEach(b => {
                    const icon = b.querySelector('.material-symbols-outlined');
                    if (icon) icon.textContent = nowMuted ? 'volume_off' : 'volume_up';
                    b.setAttribute('title', nowMuted ? 'Sound Off (Click to turn ON)' :
                        'Sound On (Click to turn OFF)');
                });
            }
        });
    }
    document.addEventListener('DOMContentLoaded', () => wireMuteBtn());

    /* --------------------------------- ROUTER ---------------------------------- */
    const routes = {};

    function route(path, handler) {
        routes[path] = handler;
    }

    function navigate(path, replace = false) {
        if (replace) location.replace('#' + path);
        else location.hash = '#' + path;
    }

    function currentPath() {
        const h = location.hash.replace(/^#/, '');
        return h || '/login';
    }

    function parsePath(path) {
        const [base, qstr] = path.split('?');
        const params = {};
        if (qstr) qstr.split('&').forEach(p => {
            const [k, v] = p.split('=');
            if (k) params[decodeURIComponent(k)] = decodeURIComponent(v || '');
        });
        return {
            base,
            params
        };
    }

    const AUTH_REQUIRED_PREFIXES = ['/home', '/quiz', '/wallet', '/redeem', '/referral', '/profile', '/history',
        '/complete-profile', '/survey'
    ];

    async function router() {
        const path = currentPath();
        const {
            base,
            params
        } = parsePath(path);

        // Dynamic Quiz Navigation Protection
        const isProtectedQuizRoute = base.startsWith('/quiz/play/') ||
            base.startsWith('/quiz/start/') ||
            base.startsWith('/quiz/review/') ||
            base.startsWith('/quiz/result/') ||
            base.startsWith('/quiz/winners/') ||
            base.startsWith('/practice-quiz/play/') ||
            base.startsWith('/practice-quiz/review/') ||
            base.startsWith('/practice-quiz/result/');

        if (isProtectedQuizRoute) {
            if (window.enforceQuizProtection) window.enforceQuizProtection();
        } else {
            if (window.releaseQuizProtection) window.releaseQuizProtection();
        }

        const needsAuth = AUTH_REQUIRED_PREFIXES.some(p => base.startsWith(p));
        if (needsAuth && !Store.getToken()) {
            navigate('/login', true);
            return;
        }
        if (!needsAuth && Store.getToken() && (base === '/login' || base === '/otp')) {
            navigate('/home', true);
            return;
        }
        if (Store.getToken() && Store.getIsNewUser() && base !== '/complete-profile') {
            navigate('/complete-profile', true);
            return;
        }

        const matched = Object.keys(routes).find(r => {
            if (r === base) return true;
            const rp = r.split('/');
            const bp = base.split('/');
            if (rp.length !== bp.length) return false;
            return rp.every((seg, i) => seg.startsWith(':') || seg === bp[i]);
        });

        const app = qs('#app');
        if (!matched) {
            app.innerHTML = notFoundView();
            return;
        }

        const rp = matched.split('/');
        const bp = base.split('/');
        rp.forEach((seg, i) => {
            if (seg.startsWith(':')) params[seg.slice(1)] = bp[i];
        });

        try {
            await routes[matched](app, params);
            window.scrollTo(0, 0);
        } catch (e) {
            console.error(e);
            if (!(e instanceof ApiError && e.status === 401)) {
                toast(e.message || 'Something went wrong', 'error');
            }
        }
    }
    window.addEventListener('hashchange', router);
    window.addEventListener('DOMContentLoaded', () => {
        router();
    });

    function notFoundView() {
        return `<div class="p-8 text-center pt-24"><p class="text-on-surface-variant">Page not found.</p><button onclick="navigate('/home')" class="mt-4 text-primary font-action">Go Home</button></div>`;
    }

    /* ------------------------------- LAYOUT CHROME ------------------------------ */
    function topHeader({
        title = 'FSRQuiz',
        showBack = false,
        backTo = '/home',
        coins = null
    } = {}) {
        return `
  <header class="fixed top-0 w-full z-40 bg-surface-glass backdrop-blur-xl border-b border-glass h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
    <div class="flex justify-between items-center px-container-margin h-full w-full">
      <div class="flex items-center gap-3">
        ${showBack ? `
        <button onclick="navigate('${backTo}')" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors active:scale-90">
          <span class="material-symbols-outlined text-on-surface">arrow_back</span>
        </button>` : `
        <div class="w-10 h-10 rounded-full border-2 border-primary-container overflow-hidden bg-primary-container/40 flex items-center justify-center">
          <span class="material-symbols-outlined text-primary">bolt</span>
        </div>`}
        <h1 class="font-display text-headline-md font-bold text-primary truncate max-w-[160px]">${escapeHtml(title)}</h1>
      </div>
      <div class="flex items-center gap-2">
        ${kbcMuteBtnHtml()}
        ${coins !== null ? `
        <div class="flex items-center bg-surface-container-high rounded-full px-3 py-1 gap-1.5 border border-glass">
          <span class="material-symbols-outlined text-tertiary text-lg" style="font-variation-settings: 'FILL' 1;">monetization_on</span>
          <span class="font-action text-action text-on-surface" id="header-coins">${coins}</span>
        </div>` : ''}
      </div>
    </div>
  </header>`;
    }

    function bottomNav(active) {
        const items = [{
                key: 'home',
                label: 'Home',
                icon: 'home',
                path: '/home'
            },
            {
                key: 'wallet',
                label: 'Wallet',
                icon: 'account_balance_wallet',
                path: '/wallet'
            },
            {
                key: 'rank',
                label: 'Rank',
                icon: 'leaderboard',
                path: '/quiz/list'
            },
            {
                key: 'redeem',
                label: 'Redeem',
                icon: 'redeem',
                path: '/redeem'
            },
            {
                key: 'profile',
                label: 'Profile',
                icon: 'person',
                path: '/profile'
            },
        ];
        return `
  <nav class="fixed bottom-0 w-full z-40 bg-surface-glass backdrop-blur-xl border-t border-glass px-2 pb-6 pt-2 max-w-md mx-auto left-1/2 -translate-x-1/2">
    <div class="flex justify-around items-center w-full">
      ${items.map(it => it.key === active ? `
      <button onclick="navigate('${it.path}')" class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-5 py-1.5 transition-transform active:scale-90">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">${it.icon}</span>
        <span class="font-label-caps text-label-caps">${it.label}</span>
      </button>` : `
      <button onclick="navigate('${it.path}')" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary transition-all">
        <span class="material-symbols-outlined text-2xl">${it.icon}</span>
        <span class="font-label-caps text-label-caps">${it.label}</span>
      </button>`).join('')}
    </div>
  </nav>`;
    }

    function skeletonCard(h = 'h-28') {
        return `<div class="glass-card rounded-xl ${h} skeleton"></div>`;
    }

    /* ------------------------------- INDIAN STATES DATA ------------------------- */
    const INDIAN_STATES = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat',
        'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand',
        'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh',
        'Lakshadweep', 'Puducherry'
    ].map((name, i) => ({
        id: i + 1,
        name
    }));

    /* ------------------------------- QUIZ HELPERS ------------------------------- */
    function parseQuizStartTime(timeStr) {
        if (!timeStr) return null;
        const str = String(timeStr).trim();
        if (!str) return null;

        if (str.includes('-') || str.includes('/')) {
            const formatted = str.replace(' ', 'T');
            const d = new Date(formatted);
            if (!isNaN(d.getTime())) return d;
        }

        const m = /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/.exec(str);
        if (m) {
            const now = new Date();
            return new Date(now.getFullYear(), now.getMonth(), now.getDate(), Number(m[1]), Number(m[2]), Number(m[3] ||
                0), 0);
        }

        const d = new Date(str);
        if (!isNaN(d.getTime())) return d;

        return null;
    }

    function parsePendingTimeSeconds(pendingTimeStr) {
        if (!pendingTimeStr) return 0;
        if (typeof pendingTimeStr === 'number') return pendingTimeStr;
        const str = String(pendingTimeStr).trim();
        const parts = str.split(':').map(Number);
        if (parts.length === 3) {
            return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
        } else if (parts.length === 2) {
            return (parts[0] * 60) + parts[1];
        } else if (!isNaN(Number(str))) {
            return Number(str);
        }
        return 0;
    }

    function goToQuizOrWait(quizId, startTimeStr, pendingTimeStr) {
        window.location.href = `/fsrapp/quizes/join/${quizId}`;
    }

    function isAlreadyPlayedFlag(obj) {
        return !!pick(obj, ['is_played', 'already_played', 'has_played', 'is_attempted', 'attempted', 'is_completed',
            'played'
        ]);
    }

    function isAlreadyPlayedError(e) {
        const msg = (e && e.message || '').toLowerCase();
        return msg.includes('already') && (msg.includes('play') || msg.includes('submit') || msg.includes('complet') ||
            msg.includes('attempt') || msg.includes('particip'));
    }

    function isAlreadyJoinedError(e) {
        const msg = (e && e.message || '').toLowerCase();
        return msg.includes('already') && (msg.includes('join') || msg.includes('enroll') || msg.includes(
            'participat'));
    }

    function quizCardHtml(q) {
        const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
        const prize = pick(q, ['prize_pool', 'prize', 'total_prize'], '0');
        const players = pick(q, ['joined_count', 'players', 'participants_count'], '0');
        const img = pick(q, ['image', 'banner', 'thumbnail'], '');
        const alreadyPlayed = isAlreadyPlayedFlag(q);
        return `
  <div class="qx-quiz-card bg-surface-glass rounded-xl border border-border-glass p-1 relative overflow-hidden group cursor-pointer hover:border-primary/50 transition-colors shadow-[0_4px_20px_rgba(0,0,0,0.3)]" data-quiz-id="${escapeHtml(pick(q,['id','quiz_id']))}">
    <div class="rounded-lg overflow-hidden relative h-28 mb-2 bg-surface-container flex items-center justify-center">
      ${img ? `<div class="bg-cover bg-center w-full h-full opacity-80 group-hover:scale-110 duration-500" style="background-image:url('${escapeHtml(img)}')"></div>`
            : `<span class="material-symbols-outlined text-4xl text-primary/50">quiz</span>`}
      ${alreadyPlayed ? `<span class="absolute top-1.5 left-1.5 bg-mint text-surface-container-lowest text-[9px] font-bold px-1.5 py-0.5 rounded-full">PLAYED</span>` : ''}
    </div>
    <div class="px-2 pb-2 flex flex-col gap-1">
      <h3 class="font-headline-md text-[14px] leading-tight text-on-surface line-clamp-2">${title}</h3>
      <div class="flex justify-between items-end mt-1">
        <div class="flex items-center gap-0.5 text-tertiary">
          <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">monetization_on</span>
          <span class="font-headline-md text-[14px]">${escapeHtml(prize)}</span>
        </div>
        <div class="flex items-center gap-0.5 text-on-surface-variant text-[12px]"><span class="material-symbols-outlined text-[12px]">group</span> ${escapeHtml(players)}</div>
      </div>
    </div>
  </div>`;
    }

    function attachQuizCardHandlers(root, list) {
        qsa('.qx-quiz-card', root).forEach(card => {
            card.addEventListener('click', () => {
                const id = card.dataset.quizId;
                openQuizSheet(id);
            });
        });
    }

    function surveyCardHtml(s) {
        const sId = pick(s, ['id', 'survey_id']);
        const title = escapeHtml(pick(s, ['title', 'name', 'survey_name'], 'Survey'));
        const reward = pick(s, ['reward_points', 'point', 'coins', 'reward'], '10');
        const img = pick(s, ['image', 'banner', 'thumbnail'], '');
        const isJoined = pick(s, ['is_joined', 'completed'], false);
        return `
  <div onclick="navigate('/survey/play/${sId}')" class="qx-survey-card bg-surface-glass rounded-xl border border-border-glass p-1 relative overflow-hidden group cursor-pointer hover:border-tertiary/50 transition-colors shadow-[0_4px_20px_rgba(0,0,0,0.3)]">
    <div class="rounded-lg overflow-hidden relative h-28 mb-2 bg-surface-container flex items-center justify-center">
      ${img ? `<div class="bg-cover bg-center w-full h-full opacity-80 group-hover:scale-110 duration-500" style="background-image:url('${escapeHtml(img)}')"></div>`
            : `<span class="material-symbols-outlined text-4xl text-tertiary/50">poll</span>`}
      ${isJoined ? `<span class="absolute top-1.5 left-1.5 bg-mint text-surface-container-lowest text-[9px] font-bold px-1.5 py-0.5 rounded-full">COMPLETED</span>`
                 : `<span class="absolute top-1.5 left-1.5 bg-tertiary text-surface-container-lowest text-[9px] font-bold px-1.5 py-0.5 rounded-full">SURVEY</span>`}
    </div>
    <div class="px-2 pb-2 flex flex-col gap-1">
      <h3 class="font-headline-md text-[14px] leading-tight text-on-surface line-clamp-2">${title}</h3>
      <div class="flex justify-between items-end mt-1">
        <div class="flex items-center gap-0.5 text-tertiary">
          <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">monetization_on</span>
          <span class="font-headline-md text-[14px]">${escapeHtml(reward)} Pts</span>
        </div>
        <div class="flex items-center gap-0.5 text-on-surface-variant text-[12px]"><span class="material-symbols-outlined text-[12px]">rule</span> Yes/No</div>
      </div>
    </div>
  </div>`;
    }
</script>
