@php
    $isQuizScreen = (request()->is('*quizes/*') 
        || request()->is('*quiz/*') 
        || request()->is('*fff/*') 
        || request()->is('*fff*')
        || request()->routeIs('*.fff.*')
        || request()->routeIs('fff.*')
        || request()->is('*practice-quiz/play*') 
        || request()->is('*practice-quiz/review*') 
        || request()->is('*practice-quiz/result*') 
        || (request()->is('*survey/*') && !request()->is('*survey'))
        || request()->routeIs('quizes.*')
        || request()->routeIs('*.quizes.*')
        || request()->routeIs('quiz.*')
        || request()->routeIs('*.quiz.*')
        || request()->routeIs('practice-quiz.play*')
        || request()->routeIs('practice-quiz.review*')
        || request()->routeIs('practice-quiz.result*')
        || request()->routeIs('*.practice-quiz.play*')
        || request()->routeIs('*.practice-quiz.review*')
        || request()->routeIs('*.practice-quiz.result*')
        || request()->routeIs('survey.play*')
        || request()->routeIs('*.survey.play*'))
        && !request()->is('*answer-sheet*')
        && !request()->is('*leaderboard*')
        && !request()->is('*winners*')
        && !request()->is('*result*')
        && !request()->routeIs('*.answer-sheet')
        && !request()->routeIs('quizes.answer-sheet')
        && !request()->routeIs('*.fff.answer-sheet')
        && !request()->routeIs('*.fff.leaderboard')
        && !request()->routeIs('*.survey.play*');
@endphp
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FSRQuiz">
    <meta name="theme-color" content="#0d0620">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>FSRQuiz - Play, Win, Earn</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preload" href="{{ asset('fsrapp/fonts/plus-jakarta-sans.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fsrapp/fonts/material-symbols-outlined.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('fsrapp/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('fsrapp/css/style.css') }}">
    @stack('styles')
    <style>
        /* Disable text selection globally for app security without blocking touch drag/scroll */
        *, *::before, *::after {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
            -webkit-touch-callout: none !important;
        }
        /* Allow selection only on inputs and textareas so users can write */
        input, textarea, [contenteditable="true"], input *, textarea * {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }
    </style>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-secondary-fixed-variant": "#8f003f",
                        "primary-fixed": "#eaddff",
                        "secondary-fixed-dim": "#ffb1c2",
                        "on-primary-fixed-variant": "#5a00c6",
                        "surface-container-lowest": "#11072a",
                        "on-primary": "#3f008e",
                        "on-tertiary": "#3f2e00",
                        "on-tertiary-fixed": "#251a00",
                        "secondary-fixed": "#ffd9df",
                        "error": "#ffb4ab",
                        "inverse-surface": "#e8ddff",
                        "on-background": "#e8ddff",
                        "surface-container": "#221a3c",
                        "on-primary-container": "#ede0ff",
                        "primary-fixed-dim": "#d2bbff",
                        "surface-bright": "#3d3457",
                        "mint": "#00E5A0",
                        "on-secondary-container": "#ffdbe1",
                        "border-glass": "rgba(255, 255, 255, 0.1)",
                        "secondary-container": "#c9005b",
                        "primary-container": "#7c3aed",
                        "error-container": "#93000a",
                        "surface": "#160d2f",
                        "surface-tint": "#d2bbff",
                        "paper": "#F6F3FF",
                        "surface-variant": "#382f53",
                        "on-error": "#690005",
                        "surface-dim": "#160d2f",
                        "surface-container-highest": "#382f53",
                        "on-error-container": "#ffdad6",
                        "on-secondary-fixed": "#3f0018",
                        "tertiary-container": "#826200",
                        "inverse-on-surface": "#342b4e",
                        "surface-container-low": "#1e1638",
                        "inverse-primary": "#732ee4",
                        "outline-variant": "#4a4455",
                        "on-surface-variant": "#ccc3d8",
                        "outline": "#958da1",
                        "paper-dim": "#B7ACDA",
                        "on-secondary": "#66002b",
                        "primary": "#d2bbff",
                        "tertiary": "#f4bf32",
                        "on-surface": "#e8ddff",
                        "on-primary-fixed": "#25005a",
                        "surface-glass": "rgba(255, 255, 255, 0.06)",
                        "surface-container-high": "#2d2447",
                        "on-tertiary-container": "#ffe3a9",
                        "background": "#160d2f",
                        "on-tertiary-fixed-variant": "#5a4300",
                        "secondary": "#ffb1c2",
                        "violet-soft": "#A78BFA",
                        "tertiary-fixed": "#ffdf9a",
                        "tertiary-fixed-dim": "#f4bf32"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-lg": "1.5rem",
                        "stack-sm": "0.5rem",
                        "gutter": "0.75rem",
                        "stack-md": "1rem",
                        "container-margin": "1rem"
                    },
                    "fontFamily": {
                        "action": ["Source Sans 3"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "display": ["Plus Jakarta Sans"],
                        "body-md": ["Source Sans 3"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "label-caps": ["JetBrains Mono"],
                        "body-lg": ["Source Sans 3"]
                    },
                    "fontSize": {
                        "action": ["13px", {
                            "lineHeight": "18px",
                            "fontWeight": "600"
                        }],
                        "headline-md": ["20px", {
                            "lineHeight": "28px",
                            "fontWeight": "600"
                        }],
                        "display": ["32px", {
                            "lineHeight": "40px",
                            "fontWeight": "700"
                        }],
                        "body-md": ["14px", {
                            "lineHeight": "20px",
                            "fontWeight": "400"
                        }],
                        "headline-lg": ["24px", {
                            "lineHeight": "32px",
                            "fontWeight": "700"
                        }],
                        "label-caps": ["11px", {
                            "lineHeight": "16px",
                            "letterSpacing": "1px",
                            "fontWeight": "500"
                        }],
                        "body-lg": ["16px", {
                            "lineHeight": "24px",
                            "fontWeight": "400"
                        }]
                    }
                },
            },
        }
    </script>
</head>

<body class="bg-background font-body-md text-on-surface"
    style="background-color: #160d2f; background-image: radial-gradient(circle at 50% 0%, rgba(124, 58, 237, 0.25) 0%, transparent 60%), radial-gradient(circle at 100% 100%, rgba(0, 229, 160, 0.1) 0%, transparent 50%), radial-gradient(circle at 0% 100%, rgba(244, 191, 50, 0.1) 0%, transparent 50%); background-attachment: fixed;">

    <div class="glow-effect top-[-50px] left-[-100px]"></div>
    <div class="glow-effect bottom-[100px] right-[-100px]"
        style="background: radial-gradient(circle, rgba(255, 177, 194, 0.1) 0%, rgba(22, 13, 47, 0) 70%);"></div>

    <!-- App root -->
    <div id="app" class="max-w-md mx-auto min-h-screen relative">
        @yield('content')
    </div>

    <!-- Global Response Alert Popup Modal (Closes on manual user interaction) -->
    <div id="global-app-alert-modal" style="display: none;" class="fixed inset-0 z-[999999] items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-all duration-300 opacity-0 pointer-events-auto">
        <div id="global-app-alert-box" class="relative w-full max-w-sm rounded-3xl bg-[#1c0f3d] border border-[#f4bf32]/40 p-6 text-center shadow-[0_25px_60px_-10px_rgba(0,0,0,0.9),0_0_28px_rgba(217,70,239,0.35)] space-y-4 scale-95 transition-all duration-300 transform">
            <!-- Close button (top right) -->
            <button type="button" onclick="closeGlobalAppAlertModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer" title="Close">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>

            <!-- Warning/Alert Icon with animated halo -->
            <div id="global-app-alert-icon-wrap" class="w-16 h-16 mx-auto rounded-full bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center text-amber-300 shadow-lg shadow-amber-500/20">
                <span id="global-app-alert-icon" class="material-symbols-outlined text-3xl animate-pulse">warning</span>
            </div>

            <div class="space-y-1.5">
                <h3 id="global-app-alert-title" class="font-display text-lg font-bold text-white tracking-wide">Notice</h3>
                <p id="global-app-alert-message" class="text-xs text-[#e8ddff] leading-relaxed px-2 font-medium">
                    Alert message
                </p>
            </div>

            <div class="pt-2 flex flex-col gap-2">
                <button type="button" onclick="closeGlobalAppAlertModal()" class="w-full py-3 rounded-xl bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] font-action font-bold text-sm shadow-[0_8px_22px_rgba(217,70,239,0.35)] active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%] toast-enter"></div>

    <!-- Global Floating Enable Quiz Sound Banner (Shown only on waiting page if browser blocks autoplay) -->
    <div id="global-quiz-audio-banner" class="fixed top-4 left-1/2 -translate-x-1/2 z-[99999] w-[92%] max-w-sm pointer-events-auto transition-all duration-300 transform -translate-y-24 opacity-0 hidden" onclick="handleGlobalAudioBannerClick(event)">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#1c0f3d]/95 via-[#2b1055]/95 to-[#1c0f3d]/95 backdrop-blur-md border border-[#f4bf32]/60 p-3 shadow-[0_10px_30px_rgba(0,0,0,0.8),0_0_20px_rgba(244,191,50,0.3)] flex items-center justify-between gap-3 cursor-pointer select-none">
            <div class="absolute -right-6 -top-6 w-20 h-20 bg-[#f4bf32]/20 rounded-full blur-xl pointer-events-none"></div>
            <div class="absolute -left-6 -bottom-6 w-20 h-20 bg-[#d946ef]/20 rounded-full blur-xl pointer-events-none"></div>

            <div class="flex items-center gap-2.5 relative z-10">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#f4bf32] to-[#d946ef] p-0.5 shadow-md flex items-center justify-center shrink-0 animate-bounce">
                    <div class="w-full h-full bg-[#1c0f3d] rounded-[10px] flex items-center justify-center text-[#f4bf32]">
                        <span class="material-symbols-outlined text-[18px]">volume_up</span>
                    </div>
                </div>
                <div class="text-left">
                    <p class="font-display font-bold text-xs text-white tracking-wide flex items-center gap-1.5">
                        <span>Enable Quiz Sound</span>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                    </p>
                    <p class="text-[10px] text-[#ffe9b0] font-medium leading-tight mt-0.5">
                        Tap anywhere to play quiz audio
                    </p>
                </div>
            </div>

            <button type="button" class="relative z-10 px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#160d2f] font-action font-black text-xs shadow-[0_4px_12px_rgba(244,191,50,0.35)] active:scale-95 transition-transform flex items-center gap-1 shrink-0">
                <span>Play</span>
                <span class="material-symbols-outlined text-[13px]">play_arrow</span>
            </button>
        </div>
    </div>

    <!-- Global Loading Overlay -->
    <div id="global-loader"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[90] hidden items-center justify-center">
        <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div>
    </div>

    <!-- Global Utility Functions -->
    <script>
        let toastTimer = null;
        let globalAlertRedirectUrl = null;

        function showAppAlertModal(message, type = 'warning', redirectUrl = null, title = null) {
            if (!message) return;
            globalAlertRedirectUrl = redirectUrl;

            const modal = document.getElementById('global-app-alert-modal');
            const box = document.getElementById('global-app-alert-box');
            const msgEl = document.getElementById('global-app-alert-message');
            const titleEl = document.getElementById('global-app-alert-title');
            const iconEl = document.getElementById('global-app-alert-icon');
            const iconWrap = document.getElementById('global-app-alert-icon-wrap');

            let displayTitle = title;
            if (!displayTitle) {
                if (message.includes('Screen Minimize')) {
                    displayTitle = 'Screen Minimize';
                } else if (message.includes('Internet Off')) {
                    displayTitle = 'Internet Off';
                } else if (message.includes('Screen Refresh')) {
                    displayTitle = 'Screen Refresh';
                } else if (message.includes('Back Button')) {
                    displayTitle = 'Back Button';
                } else if (message.includes('Quiz Time Expired') || message.includes('time has ended') || message.includes('availability period has expired')) {
                    displayTitle = 'Quiz Time Expired';
                } else {
                    displayTitle = (type === 'error' ? 'Quiz Se Out' : 'Notice');
                }
            }

            if (msgEl) msgEl.innerText = message;
            if (titleEl) titleEl.innerText = displayTitle;

            if (iconEl && iconWrap) {
                if (type === 'error') {
                    iconEl.innerText = 'error';
                    iconWrap.className = 'w-16 h-16 mx-auto rounded-full bg-rose-500/20 border-2 border-rose-400 flex items-center justify-center text-rose-300 shadow-lg shadow-rose-500/20';
                } else if (type === 'success') {
                    iconEl.innerText = 'check_circle';
                    iconWrap.className = 'w-16 h-16 mx-auto rounded-full bg-emerald-500/20 border-2 border-emerald-400 flex items-center justify-center text-emerald-300 shadow-lg shadow-emerald-500/20';
                } else {
                    iconEl.innerText = 'warning';
                    iconWrap.className = 'w-16 h-16 mx-auto rounded-full bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center text-amber-300 shadow-lg shadow-amber-500/20';
                }
            }

            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    if (box) {
                        box.classList.remove('scale-95');
                        box.classList.add('scale-100');
                    }
                }, 10);
            }
        }

        function closeGlobalAppAlertModal() {
            const modal = document.getElementById('global-app-alert-modal');
            const box = document.getElementById('global-app-alert-box');
            if (box) {
                box.classList.remove('scale-100');
                box.classList.add('scale-95');
            }
            if (modal) {
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    const targetUrl = globalAlertRedirectUrl;
                    globalAlertRedirectUrl = null;
                    if (targetUrl) {
                        window.location.replace(targetUrl);
                    }
                }, 200);
            } else if (globalAlertRedirectUrl) {
                const targetUrl = globalAlertRedirectUrl;
                globalAlertRedirectUrl = null;
                window.location.replace(targetUrl);
            }
        }

        window.showAppAlertModal = showAppAlertModal;
        window.closeGlobalAppAlertModal = closeGlobalAppAlertModal;

        document.addEventListener('DOMContentLoaded', () => {
            @if (session('warning'))
                showAppAlertModal(@json(session('warning')), 'warning');
            @elseif (session('error'))
                showAppAlertModal(@json(session('error')), 'error');
            @endif
        });

        function toast(msg, type = 'info') {
            const box = document.querySelector('#toast');
            if (!box) return;
            const colors = {
                info: 'bg-surface-container-high border-glass text-on-surface',
                error: 'bg-error-container border-error text-on-error-container',
                success: 'bg-mint/20 border-mint text-mint'
            };
            box.className =
                `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg font-body-md text-body-md ${colors[type] || colors.info}`;
            box.textContent = msg;
            box.classList.remove('hidden');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => box.classList.add('hidden'), 3200);
        }

        function showLoader() {
            const loader = document.querySelector('#global-loader');
            if (loader) {
                loader.classList.remove('hidden');
                loader.classList.add('flex');
            }
        }

        function hideLoader() {
            const loader = document.querySelector('#global-loader');
            if (loader) {
                loader.classList.add('hidden');
                loader.classList.remove('flex');
            }
        }

        async function withLoader(fn) {
            showLoader();
            try {
                return await fn();
            } finally {
                hideLoader();
            }
        }
    </script>

    @if (request()->is('home') ||
            request()->is('fsrapp') ||
            request()->is('fsrapp/home') ||
            request()->is('*quiz*') ||
            request()->is('*practice*'))
        <script src="{{ asset('assets/js/quiz.js') }}"></script>
    @endif

    <!-- Global Sound Manager & Speaker Toggle -->
    <script>
        window.activeQuizAudios = window.activeQuizAudios || [];
        window._preloadedQuestionAudios = window._preloadedQuestionAudios || {};
        window._preloadedUrls = window._preloadedUrls || {};
        window._lastMuteToggleTime = 0;
        window._globalAudioCtx = null;
        window._iosAudioUnlocked = false;
        window._hasUserInteracted = false;

        // Dedicated reusable Audio channels for iOS WebKit & Mobile browsers
        window._bgChannel = new Audio();
        window._sfxChannel = new Audio();
        window._questionChannel = new Audio();
        window._extraChannel = new Audio();

        [window._bgChannel, window._sfxChannel, window._questionChannel, window._extraChannel].forEach(audio => {
            try {
                audio.preload = 'auto';
                audio.setAttribute('playsinline', 'true');
                audio.setAttribute('webkit-playsinline', 'true');
            } catch(e) {}
        });

        // Global Audio Unlock Banner Management
        let _globalBannerVisible = false;
        let _globalBannerShownTime = 0;
        let _globalDocUnlockBound = false;

        window.markAudioUnlocked = function() {
            try {
                sessionStorage.setItem('fsr_audio_unlocked', '1');
                sessionStorage.setItem('fsr_joined_from_home', '1');
                localStorage.setItem('fsr_audio_unlocked', '1');
            } catch(e) {}
            window._hasUserInteracted = true;
            window._audioAutoplayBlocked = false;
        };

        window.isAudioUnlocked = function() {
            if (window._audioAutoplayBlocked) return false;
            return !!(window._hasUserInteracted || window._audioPlayedSuccessfully);
        };

        function isQuizWaitingStage() {
            if (window.QuizSpa && typeof window.QuizSpa.currentStage !== 'undefined') {
                return window.QuizSpa.currentStage === 'waiting';
            }
            if (window.FffSpa && typeof window.FffSpa.currentStage !== 'undefined') {
                return window.FffSpa.currentStage === 'waiting';
            }
            const path = window.location.pathname || '';
            return path.includes('/quizes/waiting') || path.includes('/fff/waiting') || path.endsWith('/waiting') || path.endsWith('/waiting/');
        }

        window.showGlobalAudioUnlockBanner = function() {
            // Strict check: Only show sound permission banner on WAITING page/stage (for both Quiz and FFF Quiz)
            if (!isQuizWaitingStage()) {
                if (_globalBannerVisible) {
                    window.hideGlobalAudioUnlockBanner();
                }
                return;
            }

            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;

            const bg = window._bgChannel;
            const sfx = window._sfxChannel;
            const qChannel = window._questionChannel;
            const isAnyPlaying = (bg && !bg.paused && bg.currentTime > 0) ||
                                 (sfx && !sfx.paused && sfx.currentTime > 0) ||
                                 (qChannel && !qChannel.paused && qChannel.currentTime > 0);
            if (isAnyPlaying) {
                window._audioPlayedSuccessfully = true;
                window._audioAutoplayBlocked = false;
                window.hideGlobalAudioUnlockBanner();
                return;
            }

            const banner = document.getElementById('global-quiz-audio-banner');
            if (!banner) return;

            banner.classList.remove('hidden');
            _globalBannerVisible = true;
            _globalBannerShownTime = Date.now();

            requestAnimationFrame(() => {
                banner.classList.remove('-translate-y-24', 'opacity-0');
                banner.classList.add('translate-y-0', 'opacity-100');
            });

            setTimeout(() => {
                if (_globalBannerVisible) {
                    bindGlobalAudioDocUnlockListener();
                }
            }, 200);
        };

        window.hideGlobalAudioUnlockBanner = function() {
            _globalBannerVisible = false;
            unbindGlobalAudioDocUnlockListener();
            const banner = document.getElementById('global-quiz-audio-banner');
            if (!banner) return;
            banner.classList.remove('translate-y-0', 'opacity-100');
            banner.classList.add('-translate-y-24', 'opacity-0');
            setTimeout(() => {
                if (!_globalBannerVisible) {
                    banner.classList.add('hidden');
                }
            }, 300);
        };

        window.handleGlobalAudioBannerClick = function(e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }
            unlockAndResumeGlobalQuizAudio();
        };

        let _isUnlockingGlobalAudio = false;
        function unlockAndResumeGlobalQuizAudio() {
            if (_isUnlockingGlobalAudio) return;
            _isUnlockingGlobalAudio = true;
            setTimeout(() => { _isUnlockingGlobalAudio = false; }, 300);

            window._hasUserInteracted = true;
            window._audioAutoplayBlocked = false;
            window._audioPlayedSuccessfully = true;

            try {
                sessionStorage.setItem('fsr_audio_unlocked', '1');
                sessionStorage.setItem('fsr_joined_from_home', '1');
                localStorage.setItem('fsr_audio_unlocked', '1');
            } catch(e) {}

            if (window._globalAudioCtx && window._globalAudioCtx.state === 'suspended') {
                try { window._globalAudioCtx.resume(); } catch(e) {}
            }

            try {
                sessionStorage.setItem('qx_sound_muted_manual', 'false');
                localStorage.setItem('qx_sound_muted', 'false');
                localStorage.setItem('qx_mute', '0');
            } catch(e) {}

            window.hideGlobalAudioUnlockBanner();

            if (typeof window.resumeCurrentStageAudio === 'function') {
                window.resumeCurrentStageAudio();
            }

            if (window.updateSpeakerIcon) window.updateSpeakerIcon();
        }

        function onGlobalDocUnlockInteraction(e) {
            if (!_globalBannerVisible) return;
            if (Date.now() - _globalBannerShownTime < 250) return;
            const target = e.target;
            if (target && target.closest('#kbc-mute-toggle, .kbc-mute-btn, button[onclick*="toggleGlobalMute"]')) {
                window.hideGlobalAudioUnlockBanner();
                return;
            }
            if (target && target.closest('a')) {
                return;
            }
            unlockAndResumeGlobalQuizAudio();
        }

        function bindGlobalAudioDocUnlockListener() {
            if (_globalDocUnlockBound) return;
            _globalDocUnlockBound = true;
            document.addEventListener('click', onGlobalDocUnlockInteraction, true);
            document.addEventListener('touchend', onGlobalDocUnlockInteraction, true);
        }

        function unbindGlobalAudioDocUnlockListener() {
            if (!_globalDocUnlockBound) return;
            _globalDocUnlockBound = false;
            document.removeEventListener('click', onGlobalDocUnlockInteraction, true);
            document.removeEventListener('touchend', onGlobalDocUnlockInteraction, true);
        }

        // Backward compatibility aliases
        window.showWaitingAudioBanner = window.showGlobalAudioUnlockBanner;
        window.hideWaitingAudioBanner = window.hideGlobalAudioUnlockBanner;
        window.handleWaitingAudioBannerClick = window.handleGlobalAudioBannerClick;
        window.showFffWaitingAudioBanner = window.showGlobalAudioUnlockBanner;
        window.hideFffWaitingAudioBanner = window.hideGlobalAudioUnlockBanner;
        window.handleFffWaitingAudioBannerClick = window.handleGlobalAudioBannerClick;

        let _audioUnlockEvents = ['pointerdown', 'touchstart', 'touchend', 'mousedown', 'click', 'keydown'];
        let _globalAudioUnlocked = false;
        const unlockGlobalAudio = (e) => {
            window._hasUserInteracted = true;
            window._audioAutoplayBlocked = false;

            // 1. Web Audio Context Unlock
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    if (!window._globalAudioCtx) {
                        window._globalAudioCtx = new AudioCtx();
                    }
                    if (window._globalAudioCtx.state === 'suspended') {
                        window._globalAudioCtx.resume();
                    }
                }
            } catch(e) {}

            // 2. Hide global unlock banner
            if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                window.hideGlobalAudioUnlockBanner();
            }

            // Remove unlock event listeners once unlocked to prevent repeated execution on every click/touch
            if (!_globalAudioUnlocked) {
                _globalAudioUnlocked = true;
                _audioUnlockEvents.forEach(evt => {
                    window.removeEventListener(evt, unlockGlobalAudio, { capture: true });
                    document.removeEventListener(evt, unlockGlobalAudio, { capture: true });
                });

                // 3. Only resume waiting stage background audio if user was on waiting stage and audio was blocked
                const isMuteBtnClick = e && e.target && typeof e.target.closest === 'function' && e.target.closest('#kbc-mute-toggle, .kbc-mute-btn, button[onclick*="toggleGlobalMute"]');
                if (!isMuteBtnClick && typeof window.isGlobalMuted === 'function' && !window.isGlobalMuted()) {
                    const stage = (window.FffSpa && window.FffSpa.currentStage) ? window.FffSpa.currentStage : ((window.QuizSpa && window.QuizSpa.currentStage) ? window.QuizSpa.currentStage : null);
                    if (stage === 'waiting') {
                        if (typeof window.resumeCurrentStageAudio === 'function') {
                            window.resumeCurrentStageAudio();
                        }
                    }
                }
            }

            if (typeof Sound !== 'undefined' && Sound.ensureCtx) {
                try { Sound.ensureCtx(); } catch (e) {}
            }
        };

        _audioUnlockEvents.forEach(evt => {
            window.addEventListener(evt, unlockGlobalAudio, { passive: true, capture: true });
            document.addEventListener(evt, unlockGlobalAudio, { passive: true, capture: true });
        });

        @php
            $appGlobalSoundSettings = \Illuminate\Support\Facades\Cache::remember('global_quiz_sound_settings', 3600, function() {
                return \App\Models\QuizSetting::first()?->getSoundUrls() ?? [];
            });
        @endphp

        window.SERVER_SOUND_URLS = @json($appGlobalSoundSettings);
        window.DEFAULT_SOUND_URLS = Object.assign({
            background_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/background_sound_1789460372.mp3",
            one_min_before: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/one_min_before_1789466274.mp3",
            question_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/question_appear_sound_1787231022.mp3",
            start_quiz_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/start_quiz_sound_1787214208.mp3",
            say_ans_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/say_ans_appear_sound_1787840303.mp3",
            ans_select_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/say_ans_appear_sound_1787840303.mp3",
            answer_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/counter_sound_1787214366.mp3",
            answer_sheet_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/answer_sheet_appear_sound_1787214584.mp3",
            leaderboard_appear_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/leaderboard_appear_sound_1787214598.mp3",
            counter_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/counter_sound_1789460529.mp3",
            quiz_end_sound: "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/answer_sheet_appear_sound_1787214584.mp3"
        }, window.SERVER_SOUND_URLS || {});

        window.preloadAllQuizSounds = function(soundsObj) {
            const sounds = (soundsObj && typeof soundsObj === 'object' && Object.keys(soundsObj).length > 0)
                ? soundsObj
                : window.DEFAULT_SOUND_URLS;
            if (!sounds || typeof sounds !== 'object') return;
            try {
                const soundStr = JSON.stringify(sounds);
                sessionStorage.setItem('qx_sound_settings', soundStr);
                sessionStorage.setItem('qx_cached_sound_urls', soundStr);
                localStorage.setItem('qx_sound_settings', soundStr);
                localStorage.setItem('qx_cached_sound_urls', soundStr);
            } catch(e) {}

            Object.values(sounds).forEach(url => {
                if (url && typeof url === 'string') {
                    if (window.preloadAudioUrl) {
                        window.preloadAudioUrl(url);
                    } else {
                        try {
                            const a = new Audio();
                            a.preload = 'auto';
                            a.setAttribute('playsinline', 'true');
                            a.src = url;
                            a.load();
                            if (window._preloadedQuestionAudios) window._preloadedQuestionAudios[url] = a;
                            if (window._preloadedUrls) window._preloadedUrls[url] = true;
                        } catch(e) {}
                    }
                }
            });
        };

        // Preload sound URLs immediately on page parse
        try {
            window.preloadAllQuizSounds(window.DEFAULT_SOUND_URLS);
        } catch(e) {}

        // Immediately preload all quiz sounds on page render (Login, Home, Waiting, etc.)
        try {
            window.preloadAllQuizSounds();
        } catch(e) {}

        window.getSoundUrl = function(key) {
            try {
                const cached = sessionStorage.getItem('qx_sound_settings') || 
                               sessionStorage.getItem('qx_cached_sound_urls') || 
                               localStorage.getItem('qx_sound_settings') || 
                               localStorage.getItem('qx_cached_sound_urls');
                if (cached) {
                    const sounds = JSON.parse(cached);
                    if (sounds && sounds[key]) return sounds[key];
                }
            } catch(e) {}
            return (window.DEFAULT_SOUND_URLS && window.DEFAULT_SOUND_URLS[key]) || null;
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

        window.hasPlayedOneMinSoundForQuiz = function(quizId) {
            if (!quizId) return false;
            try {
                if (sessionStorage.getItem('qx_one_min_played_' + quizId) === 'true' ||
                    localStorage.getItem('qx_one_min_played_' + quizId) === 'true') {
                    return true;
                }
            } catch(e) {}
            return !!(window._oneMinAlertPlayed && window._oneMinAlertPlayed[quizId]);
        };

        window.markOneMinSoundPlayedForQuiz = function(quizId) {
            if (!quizId) return;
            window._oneMinAlertPlayed = window._oneMinAlertPlayed || {};
            window._oneMinAlertPlayed[quizId] = true;
            try {
                sessionStorage.setItem('qx_one_min_played_' + quizId, 'true');
                localStorage.setItem('qx_one_min_played_' + quizId, 'true');
            } catch(e) {}
        };

        window.playOneMinBeforeSound = function(quizId = null, volume = 0.85) {
            if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return null;
            if (quizId && window.hasPlayedOneMinSoundForQuiz(quizId)) {
                return null;
            }
            if (quizId) {
                window.markOneMinSoundPlayedForQuiz(quizId);
            }
            const url = window.getSoundUrl('one_min_before');
            if (!url) return null;
            if (window._globalAudioCtx && window._globalAudioCtx.state === 'suspended') {
                try { window._globalAudioCtx.resume(); } catch(e) {}
            }

            const resumeWaitingBg = () => {
                if (typeof window.isGlobalMuted === 'function' && window.isGlobalMuted()) return;
                if (window.__isQuizCompleted || window.__isExitingQuiz || window.__isFffCompleted) return;

                const isWaiting = (typeof isQuizWaitingStage === 'function' && isQuizWaitingStage()) ||
                                  (window.QuizSpa && window.QuizSpa.currentStage === 'waiting') ||
                                  (window.FffSpa && window.FffSpa.currentStage === 'waiting');
                if (isWaiting) {
                    if (typeof window.playWaitingSound === 'function') {
                        window.playWaitingSound();
                    } else if (typeof window.playCachedBackgroundSound === 'function') {
                        window.playCachedBackgroundSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('background_sound', 0.5, true, 'bg');
                    } else {
                        const bgUrl = (typeof window.getSoundUrl === 'function' ? window.getSoundUrl('background_sound') : null) ||
                                      "https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/background_sound_1789460372.mp3";
                        if (bgUrl && window.playQuizAudio) {
                            window.playQuizAudio(bgUrl, 0.5, true, 'bg');
                        }
                    }
                }
            };

            if (window.playQuizAudio) {
                const audio = window.playQuizAudio(url, volume, false, 'bg');
                if (audio) {
                    audio.onended = function() {
                        audio.onended = null;
                        resumeWaitingBg();
                    };
                    audio.onerror = function() {
                        audio.onerror = null;
                        resumeWaitingBg();
                    };
                }
                return audio;
            }
            return null;
        };

        window.preloadOneMinBeforeSound = function() {
            const url = window.getSoundUrl('one_min_before');
            if (url && window.preloadAudioUrl) {
                return window.preloadAudioUrl(url);
            }
            return null;
        };

        // Global tracker for 1-min-before alerts to ensure it triggers once per quiz
        window._oneMinAlertPlayed = window._oneMinAlertPlayed || {};
        window._monitoredUpcomingQuizzes = window._monitoredUpcomingQuizzes || {};

        window.registerUpcomingQuizForOneMinAlert = function(quizId, startTimeSecs) {
            if (!quizId || !startTimeSecs) return;
            const nowSecs = Math.floor(Date.now() / 1000) + (window._globalServerTimeOffset || 0);
            const initialDiff = parseInt(startTimeSecs) - nowSecs;
            // If quiz is already less than 58s away on initial registration, mark it as already passed so it won't play late
            if (initialDiff < 58) {
                window.markOneMinSoundPlayedForQuiz(quizId);
            }
            window._monitoredUpcomingQuizzes[quizId] = parseInt(startTimeSecs);
        };

        let _isGlobalSyncing = false;
        window.syncGlobalUpcomingQuizzes = async function() {
            if (document.hidden || _isGlobalSyncing) return;
            // Skip sync on home page (which has its own sync) and inside active quiz/waiting screens
            if (window.location.pathname.endsWith('/home') || window.location.pathname.endsWith('/home/') || window.QuizSpa || window.FffSpa || window.location.pathname.includes('/quizes/') || window.location.pathname.includes('/fff/') || window.location.pathname.includes('/practice-quiz/')) {
                return;
            }
            _isGlobalSyncing = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('{{ route("fsrapp.home.quiz-times") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ _token: csrf })
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.success && json.data) {
                        if (json.server_time) {
                            const clientNowSecs = Math.floor(Date.now() / 1000);
                            window._globalServerTimeOffset = json.server_time - clientNowSecs;
                        }
                        Object.keys(json.data).forEach(qId => {
                            const item = json.data[qId];
                            if (item && item.start_time && item.status !== 'completed' && item.complete_status !== 'completed') {
                                window.registerUpcomingQuizForOneMinAlert(item.id || qId, item.start_time);
                            }
                        });
                    }
                }
            } catch(e) {
            } finally {
                _isGlobalSyncing = false;
            }
        };

        // Global ticker for upcoming quiz auto-play
        if (!window._oneMinTickerStarted) {
            window._oneMinTickerStarted = true;
            setInterval(() => {
                const nowSecs = Math.floor(Date.now() / 1000) + (window._globalServerTimeOffset || 0);
                Object.keys(window._monitoredUpcomingQuizzes).forEach(qId => {
                    const startSec = window._monitoredUpcomingQuizzes[qId];
                    if (!startSec) return;
                    const diff = startSec - nowSecs;
                    // Trigger strictly at the 1-minute window (between 58s and 61s)
                    if (diff >= 58 && diff <= 61) {
                        if (!window.hasPlayedOneMinSoundForQuiz(qId)) {
                            window.playOneMinBeforeSound(qId);
                        }
                    } else if (diff < 58) {
                        // If already less than 58 seconds, mark as passed so it never plays late
                        window.markOneMinSoundPlayedForQuiz(qId);
                    }
                });
            }, 1000);
        }

        window.resolveMediaUrl = function(rawMedia) {
            if (!rawMedia || typeof rawMedia !== 'string' || rawMedia.trim().length === 0) return null;
            let mediaUrl = rawMedia.trim();
            if (mediaUrl.startsWith('http://') || mediaUrl.startsWith('https://')) {
                return mediaUrl;
            }
            const awsBaseUrl = "{{ rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') }}";
            const cleanPath = mediaUrl.replace(/^\/+/, '');
            return awsBaseUrl + '/' + cleanPath;
        };

        window.resolveAudioUrl = function(rawAudio) {
            if (!rawAudio || typeof rawAudio !== 'string' || rawAudio.trim().length === 0) return null;
            let audioUrl = rawAudio.trim();
            if (/\.(jpe?g|png|webp|svg|gif)$/i.test(audioUrl)) return null;
            if (audioUrl.startsWith('http://') || audioUrl.startsWith('https://')) {
                return audioUrl;
            }
            const awsBaseUrl = "{{ rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') }}";
            const cleanPath = audioUrl.replace(/^\/+/, '');
            return awsBaseUrl + '/' + cleanPath;
        };

        window.preloadAudioUrl = function(url) {
            if (!url) return null;
            if (window._preloadedQuestionAudios[url]) {
                return window._preloadedQuestionAudios[url];
            }
            try {
                const audio = new Audio();
                audio.preload = 'auto';
                audio.setAttribute('playsinline', 'true');
                audio.setAttribute('webkit-playsinline', 'true');
                audio.src = url;
                audio.load();
                window._preloadedQuestionAudios[url] = audio;
                window._preloadedUrls[url] = true;
                return audio;
            } catch (e) {
                return null;
            }
        };

        window.preloadAllQuestionAudios = function(questions) {
            if (!Array.isArray(questions)) return;
            questions.forEach(qObj => {
                let rawAudio = qObj ? (
                    qObj.question_audio ||
                    qObj.audio_file ||
                    (typeof qObj.question === 'object' && qObj.question ? (qObj.question.question_audio || qObj.question.audio_file) : null)
                ) : null;
                const fullUrl = window.resolveAudioUrl(rawAudio);
                if (fullUrl) {
                    window.preloadAudioUrl(fullUrl);
                }
            });
        };

        window.preloadAllQuestionMedia = function(questions) {
            if (!Array.isArray(questions)) return;
            questions.forEach(qObj => {
                let rawMedia = qObj ? (
                    qObj.media_url ||
                    qObj.media ||
                    qObj.image_file ||
                    (typeof qObj.question === 'object' && qObj.question ? (qObj.question.media_url || qObj.question.media || qObj.question.image_file) : null)
                ) : null;
                if (rawMedia && typeof rawMedia === 'string' && !/\.(mp3|wav|ogg|m4a|aac|flac)$/i.test(rawMedia.trim())) {
                    const fullUrl = window.resolveMediaUrl ? window.resolveMediaUrl(rawMedia) : rawMedia.trim();
                    if (fullUrl) {
                        const img = new Image();
                        img.src = fullUrl;
                    }
                }
            });
        };

        window.isGlobalMuted = function() {
            try {
                const manual = sessionStorage.getItem('qx_sound_muted_manual');
                if (manual !== null) {
                    return manual === 'true' || manual === '1';
                }
            } catch(e) {}
            return false;
        };

        window.playQuizAudio = function(url, volume = 0.7, loop = false, channelType = 'sfx') {
            if (window.isGlobalMuted && window.isGlobalMuted()) return null;
            if (!url) return null;

            // Ensure AudioContext is active
            if (window._globalAudioCtx && window._globalAudioCtx.state === 'suspended') {
                try { window._globalAudioCtx.resume(); } catch(e) {}
            }

            let audio = null;
            if (loop || channelType === 'bg') {
                audio = window._bgChannel;
            } else if (channelType === 'question') {
                audio = window._questionChannel;
            } else {
                audio = window._sfxChannel;
            }

            if (!audio || audio.error) {
                audio = new Audio();
                if (loop || channelType === 'bg') window._bgChannel = audio;
                else if (channelType === 'question') window._questionChannel = audio;
                else window._sfxChannel = audio;
                try {
                    audio.setAttribute('playsinline', 'true');
                    audio.setAttribute('webkit-playsinline', 'true');
                } catch(e) {}
            }

            try {
                const isBg = (loop || channelType === 'bg');
                const isSameUrl = (audio.src === url || (audio.currentSrc && audio.currentSrc === url));

                // If already actively playing this exact track, just ensure volume and return without restarting
                if (isBg && isSameUrl && !audio.paused && audio.currentTime > 0) {
                    audio.volume = Math.max(0, Math.min(1, volume));
                    return audio;
                }

                audio.loop = !!loop;
                audio.volume = Math.max(0, Math.min(1, volume));

                // If changing tracks, reset src; if ended or seeking, reset currentTime to 0
                if (!isSameUrl) {
                    try { audio.pause(); } catch(e) {}
                    audio.src = url;
                } else if (audio.ended || audio.currentTime > 0) {
                    try { audio.currentTime = 0; } catch(e) {}
                }

                if (window.activeQuizAudios && !window.activeQuizAudios.includes(audio)) {
                    window.activeQuizAudios.push(audio);
                }
                window.activeQuizAudio = audio;

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        window._audioAutoplayBlocked = false;
                        window._audioPlayedSuccessfully = true;
                        if (typeof window.hideGlobalAudioUnlockBanner === 'function') {
                            window.hideGlobalAudioUnlockBanner();
                        }
                        if (typeof window.onQuizAudioPlaySuccess === 'function') {
                            try { window.onQuizAudioPlaySuccess(); } catch(e){}
                        }
                    }).catch(err => {
                        if (err && (err.name === 'AbortError' || err.name === 'IndexSizeError')) {
                            return;
                        }
                        window._audioAutoplayBlocked = true;
                        console.warn('[QuizAudio] Play blocked by autoplay policy:', err);
                        if (typeof window.showGlobalAudioUnlockBanner === 'function') {
                            window.showGlobalAudioUnlockBanner();
                        }
                        if (typeof window.onQuizAudioPlayBlocked === 'function') {
                            try { window.onQuizAudioPlayBlocked(); } catch(e){}
                        }
                    });
                }
                return audio;
            } catch (e) {
                console.warn('[QuizAudio] Error:', e);
                return null;
            }
        };

        window.stopAllQuizAudios = function() {
            [window._bgChannel, window._sfxChannel, window._questionChannel, window._extraChannel].forEach(audio => {
                if (audio) {
                    try {
                        audio.pause();
                        audio.currentTime = 0;
                    } catch(e) {}
                }
            });
            if (window.activeQuizAudios && window.activeQuizAudios.length > 0) {
                window.activeQuizAudios.forEach(a => {
                    try {
                        a.pause();
                        a.currentTime = 0;
                    } catch (e) {}
                });
                window.activeQuizAudios = [];
            }
            if (window.activeQuizAudio) {
                try {
                    window.activeQuizAudio.pause();
                    window.activeQuizAudio.currentTime = 0;
                } catch (e) {}
                window.activeQuizAudio = null;
            }
            try {
                document.querySelectorAll('audio').forEach(a => {
                    try {
                        a.pause();
                        a.currentTime = 0;
                    } catch(e) {}
                });
            } catch(e) {}
            if (window._preloadedQuestionAudios) {
                Object.values(window._preloadedQuestionAudios).forEach(a => {
                    try {
                        if (a && typeof a.pause === 'function') {
                            a.pause();
                            a.currentTime = 0;
                        }
                    } catch(e) {}
                });
            }
            if (typeof Sound !== 'undefined') {
                try { if (typeof Sound.stopBackground === 'function') Sound.stopBackground(); } catch(e) {}
                try { if (typeof Sound.stopBgTrack === 'function') Sound.stopBgTrack(); } catch(e) {}
                try { if (typeof Sound.hardStopAll === 'function') Sound.hardStopAll(); } catch(e) {}
            }
        };

        window.toggleGlobalMute = function() {
            const now = Date.now();
            if (now - window._lastMuteToggleTime < 150) {
                return window.isGlobalMuted();
            }
            window._lastMuteToggleTime = now;

            // Ensure audio context is unlocked
            try {
                if (window._globalAudioCtx && window._globalAudioCtx.state === 'suspended') {
                    window._globalAudioCtx.resume();
                }
            } catch(e) {}

            const isCurrentlyPlaying = window.activeQuizAudio && !window.activeQuizAudio.paused;
            const manualMute = sessionStorage.getItem('qx_sound_muted_manual');
            let next;

            // If audio was blocked by autoplay upon direct link entry (and user has not explicitly muted),
            // this first speaker button click means user wants to PLAY/UNMUTE sound!
            if (manualMute === null && !isCurrentlyPlaying && (window._audioAutoplayBlocked || !window._audioPlayedSuccessfully)) {
                next = false;
                window._audioAutoplayBlocked = false;
            } else {
                next = !window.isGlobalMuted();
            }

            try {
                sessionStorage.setItem('qx_sound_muted_manual', next ? 'true' : 'false');
                localStorage.setItem('qx_sound_muted', next ? 'true' : 'false');
                localStorage.setItem('qx_mute', next ? '1' : '0');
            } catch(e) {}

            if (next) {
                // Turning sound OFF (Mute)
                window.stopAllQuizAudios();
                if (typeof Sound !== 'undefined' && typeof Sound.setMuted === 'function') {
                    try { Sound.setMuted(true); } catch (e) {}
                }
            } else {
                // Turning sound ON (Unmute)
                if (typeof Sound !== 'undefined' && typeof Sound.setMuted === 'function') {
                    try { Sound.setMuted(false); } catch (e) {}
                }
                if (typeof window.resumeCurrentStageAudio === 'function') {
                    window.resumeCurrentStageAudio();
                }
            }

            window.updateSpeakerIcon();
            return next;
        };

        window.resumeCurrentStageAudio = function() {
            if (window.isGlobalMuted && window.isGlobalMuted()) return;

            const stage = (window.FffSpa && window.FffSpa.currentStage) ? window.FffSpa.currentStage : ((window.QuizSpa && window.QuizSpa.currentStage) ? window.QuizSpa.currentStage : null);

            // Strict check: ONLY resume sound if user is actively in a quiz/FFF stage!
            if (!stage) {
                return;
            }

            try {
                if (stage === 'question') {
                    if (typeof window.playCurrentQuestionAudio === 'function') {
                        window.playCurrentQuestionAudio();
                    }
                } else if (stage === 'waiting') {
                    if (typeof window.playWaitingSound === 'function') {
                        window.playWaitingSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('background_sound', 0.5, true, 'bg');
                    } else if (typeof window.playCachedBackgroundSound === 'function') {
                        window.playCachedBackgroundSound();
                    }
                } else if (stage === 'start-counter') {
                    if (typeof window.playStartCounterSound === 'function') {
                        window.playStartCounterSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('start_quiz_sound', 0.8, false, 'sfx');
                    } else if (typeof window.playCachedStartQuizSound === 'function') {
                        window.playCachedStartQuizSound();
                    }
                } else if (stage === 'review-counter') {
                    if (typeof window.playReviewCounterSound === 'function') {
                        window.playReviewCounterSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('counter_sound', 0.75, false, 'sfx');
                    } else if (typeof window.playCachedCounterSound === 'function') {
                        window.playCachedCounterSound();
                    }
                } else if (stage === 'final-counter') {
                    if (typeof window.playFinalCounterSound === 'function') {
                        window.playFinalCounterSound();
                    } else if (typeof window.playCachedBackgroundSound === 'function') {
                        window.playCachedBackgroundSound();
                    }
                } else if (stage === 'leaderboard') {
                    if (typeof window.playLeaderboardSound === 'function') {
                        window.playLeaderboardSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('leaderboard_appear_sound', 0.6, true, 'bg');
                    } else if (typeof window.playCachedLeaderboardSound === 'function') {
                        window.playCachedLeaderboardSound();
                    }
                } else if (stage === 'answer-sheet') {
                    if (typeof window.playAnswerSheetSound === 'function') {
                        window.playAnswerSheetSound();
                    } else if (typeof window.playNamedSound === 'function') {
                        window.playNamedSound('answer_sheet_appear_sound', 0.7, false, 'sfx');
                    }
                }
            } catch (e) {
                console.warn('Resume stage audio error:', e);
            }
        };

        window.updateSpeakerIcon = function() {
            const buttons = document.querySelectorAll('#kbc-mute-toggle, .kbc-mute-btn, button[onclick*="toggleGlobalMute"]');
            if (!buttons || buttons.length === 0) return;
            const muted = window.isGlobalMuted();

            buttons.forEach(btn => {
                const icon = btn.querySelector('.material-symbols-outlined') || btn;
                if (muted) {
                    if (icon) icon.textContent = 'volume_off';
                    btn.classList.add('text-red-400', 'opacity-70');
                    btn.classList.remove('text-primary', 'text-tertiary', 'text-white');
                    btn.title = "Unmute Sound";
                } else {
                    if (icon) icon.textContent = 'volume_up';
                    btn.classList.remove('text-red-400', 'opacity-70');
                    btn.classList.add('text-white');
                    btn.title = "Mute Sound";
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.updateSpeakerIcon();
        });

        // ── Safe Web Storage Helpers (iOS Safari Quota / Private Mode Safe) ──
        window.safeStorageGet = function(key) {
            try {
                const sVal = window.sessionStorage ? window.sessionStorage.getItem(key) : null;
                if (sVal !== null && sVal !== undefined && sVal !== 'null' && sVal !== 'undefined') return sVal;
            } catch (e) {}
            try {
                const lVal = window.localStorage ? window.localStorage.getItem(key) : null;
                if (lVal !== null && lVal !== undefined && lVal !== 'null' && lVal !== 'undefined') return lVal;
            } catch (e) {}
            return null;
        };

        window.cleanupOldQuizStorage = function(currentQuizId) {
            const prefixes = ['qx_enc_questions_', 'qx_cached_questions_', 'qx_cached_switch_questions_', 'qx_anssheet_', 'qx_device_q_order_', 'qx_quiz_settings_'];
            const suffix = currentQuizId ? String(currentQuizId) : '';
            [window.localStorage, window.sessionStorage].forEach(function(store) {
                if (!store) return;
                try {
                    const keysToRemove = [];
                    for (let i = 0; i < store.length; i++) {
                        const k = store.key(i);
                        if (!k) continue;
                        if (prefixes.some(function(p) { return k.startsWith(p); }) && (!suffix || !k.endsWith('_' + suffix))) {
                            keysToRemove.push(k);
                        }
                    }
                    keysToRemove.forEach(function(k) {
                        try { store.removeItem(k); } catch (e) {}
                    });
                } catch (e) {}
            });
        };

        window.safeStorageSet = function(key, value, currentQuizId) {
            if (value === null || value === undefined) return;
            const strVal = String(value);
            [window.sessionStorage, window.localStorage].forEach(function(store) {
                if (!store) return;
                try {
                    store.setItem(key, strVal);
                } catch (e) {
                    try {
                        window.cleanupOldQuizStorage(currentQuizId);
                        store.setItem(key, strVal);
                    } catch (e2) {}
                }
            });
        };

        // ── Quiz Payload Encryption Engine ────────────────────────────────
        window.QuizCipher = {
            getKey: function(quizId) {
                return 'FSRQ_' + quizId + '_' + (location.host || 'quiz').replace(/[^a-zA-Z0-9]/g, '');
            },
            encrypt: function(data, key) {
                try {
                    const str = typeof data === 'string' ? data : JSON.stringify(data);
                    const encoded = encodeURIComponent(str);
                    let result = '';
                    for (let i = 0; i < encoded.length; i++) {
                        const charCode = encoded.charCodeAt(i) ^ key.charCodeAt(i % key.length);
                        result += String.fromCharCode(charCode);
                    }
                    return 'U2:' + btoa(result);
                } catch (e) {
                    return null;
                }
            },
            decrypt: function(encryptedStr, key) {
                try {
                    if (!encryptedStr || typeof encryptedStr !== 'string' || encryptedStr === 'null') return null;
                    if (encryptedStr.startsWith('U2:')) {
                        const bin = atob(encryptedStr.slice(3));
                        let encoded = '';
                        for (let i = 0; i < bin.length; i++) {
                            const charCode = bin.charCodeAt(i) ^ key.charCodeAt(i % key.length);
                            encoded += String.fromCharCode(charCode);
                        }
                        return JSON.parse(decodeURIComponent(encoded));
                    }
                    const raw = decodeURIComponent(atob(encryptedStr));
                    let result = '';
                    for (let i = 0; i < raw.length; i++) {
                        const charCode = raw.charCodeAt(i) ^ key.charCodeAt(i % key.length);
                        result += String.fromCharCode(charCode);
                    }
                    return JSON.parse(result);
                } catch (e) {
                    return null;
                }
            }
        };

        // ── Quiz Question Randomization & Order Manager ──────────────────
        window.QuizQuestionManager = {
            getOrderKey: function(quizId) {
                return 'qx_device_q_order_' + quizId;
            },
            prepareQuestionsForDevice: function(quizId, rawQuestions, rawSwitchQuestions) {
                if (!Array.isArray(rawQuestions) || rawQuestions.length === 0) {
                    return { questions: [], switch_questions: [] };
                }

                const seenIds = new Set();
                const regularQuestions = [];
                const switchQuestions = [];

                // 1. Collect and deduplicate switch questions
                (Array.isArray(rawSwitchQuestions) ? rawSwitchQuestions : []).forEach(sq => {
                    if (!sq) return;
                    const id = sq.id !== undefined && sq.id !== null ? String(sq.id) : (sq.question_id !== undefined && sq.question_id !== null ? String(sq.question_id) : null);
                    if (id && !seenIds.has(id)) {
                        seenIds.add(id);
                        const cloned = Object.assign({}, sq);
                        cloned.is_switch = true;
                        switchQuestions.push(cloned);
                    }
                });

                // 2. Collect and deduplicate regular questions
                rawQuestions.forEach(q => {
                    if (!q) return;
                    const id = q.id !== undefined && q.id !== null ? String(q.id) : (q.question_id !== undefined && q.question_id !== null ? String(q.question_id) : null);
                    if (!id || seenIds.has(id)) return;
                    seenIds.add(id);

                    const isSwitch = q.is_switch || q.is_switch_question == 1 || q.is_switch_question === true;
                    if (isSwitch) {
                        const cloned = Object.assign({}, q);
                        cloned.is_switch = true;
                        switchQuestions.push(cloned);
                    } else {
                        regularQuestions.push(Object.assign({}, q));
                    }
                });

                if (regularQuestions.length === 0) {
                    return {
                        questions: rawQuestions,
                        switch_questions: switchQuestions
                    };
                }

                // 3. Check for locked random order saved for this device
                const orderKey = this.getOrderKey(quizId);
                let savedOrderIds = null;
                try {
                    const raw = sessionStorage.getItem(orderKey) || localStorage.getItem(orderKey);
                    if (raw) savedOrderIds = JSON.parse(raw);
                } catch (e) {}

                const regularMap = new Map();
                regularQuestions.forEach(q => {
                    const id = String(q.id !== undefined && q.id !== null ? q.id : q.question_id);
                    regularMap.set(id, q);
                });

                let finalRegular = [];
                const isSavedOrderValid = Array.isArray(savedOrderIds) &&
                    savedOrderIds.length === regularQuestions.length &&
                    savedOrderIds.every(id => regularMap.has(String(id)));

                if (isSavedOrderValid) {
                    savedOrderIds.forEach(id => {
                        const strId = String(id);
                        if (regularMap.has(strId)) {
                            finalRegular.push(regularMap.get(strId));
                            regularMap.delete(strId);
                        }
                    });
                    regularMap.forEach(q => finalRegular.push(q));
                } else {
                    // Fresh random permutation for this device
                    finalRegular = [...regularQuestions];
                    for (let i = finalRegular.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        const temp = finalRegular[i];
                        finalRegular[i] = finalRegular[j];
                        finalRegular[j] = temp;
                    }

                    try {
                        const orderIds = finalRegular.map(q => String(q.id !== undefined && q.id !== null ? q.id : q.question_id));
                        sessionStorage.setItem(orderKey, JSON.stringify(orderIds));
                        localStorage.setItem(orderKey, JSON.stringify(orderIds));
                    } catch (e) {}
                }

                // 4. Handle switch question if lifeline was activated in current session
                const wasSwitchUsed = (window.safeStorageGet && window.safeStorageGet('qx_used_switch_' + quizId) === 'true');
                if (wasSwitchUsed && switchQuestions.length > 0 && !finalRegular.some(q => q.is_switch)) {
                    const sq = switchQuestions[0];
                    sq.is_switch = true;
                    finalRegular.push(sq);
                }

                return {
                    questions: finalRegular,
                    switch_questions: switchQuestions
                };
            }
        };

        window._soundCachePromise = null;

        window.ensureSoundCache = async function() {
            try {
                let cached = sessionStorage.getItem('qx_sound_settings') || 
                             sessionStorage.getItem('qx_cached_sound_urls') || 
                             localStorage.getItem('qx_sound_settings') || 
                             localStorage.getItem('qx_cached_sound_urls');

                if (cached) {
                    sessionStorage.setItem('qx_sound_settings', cached);
                    sessionStorage.setItem('qx_cached_sound_urls', cached);
                    localStorage.setItem('qx_sound_settings', cached);
                    localStorage.setItem('qx_cached_sound_urls', cached);
                    try {
                        const parsed = JSON.parse(cached);
                        if (parsed && typeof parsed === 'object') {
                            if (window.preloadAllQuizSounds) {
                                window.preloadAllQuizSounds(parsed);
                            }
                            return parsed;
                        }
                    } catch (e) { return null; }
                }

                if (window._soundCachePromise) {
                    return await window._soundCachePromise;
                }

                window._soundCachePromise = (async () => {
                    try {
                        const res = await fetch('/fsrapp/quizes/api/sounds', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (res.ok) {
                            const json = await res.json();
                            if (json && (json.data || json.success)) {
                                const soundData = json.data || json;
                                const soundStr = JSON.stringify(soundData);
                                sessionStorage.setItem('qx_sound_settings', soundStr);
                                sessionStorage.setItem('qx_cached_sound_urls', soundStr);
                                localStorage.setItem('qx_sound_settings', soundStr);
                                localStorage.setItem('qx_cached_sound_urls', soundStr);
                                if (window.preloadAllQuizSounds) {
                                    window.preloadAllQuizSounds(soundData);
                                }
                                return soundData;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to pre-fetch sound settings:', e);
                    }
                    if (window.DEFAULT_SOUND_URLS && window.preloadAllQuizSounds) {
                        window.preloadAllQuizSounds(window.DEFAULT_SOUND_URLS);
                        return window.DEFAULT_SOUND_URLS;
                    }
                    return null;
                })();

                const data = await window._soundCachePromise;
                window._soundCachePromise = null;
                if (data && window.preloadAllQuizSounds) {
                    window.preloadAllQuizSounds(data);
                }
                return data;
            } catch (e) {
                window._soundCachePromise = null;
                console.error('Failed to pre-fetch sound settings:', e);
            }
        };

        // ── Global App Safety and Copy/Inspect Protection ────────────────────────────
        // Always disables right-click, copy, cut, paste, dragging, developer keys (F12, inspect element, view-source)
        // and enforces browser back button & reload blocking on quiz screens.
        window._quizProtectionActive = {{ ($isQuizScreen ?? false) ? 'true' : 'false' }};

        const lockQuizHistory = () => {
            if (!window._quizProtectionActive) return;
            if (window.QuizSpa && (window.QuizSpa.currentStage === 'answer-sheet' || window.QuizSpa.currentStage === 'leaderboard')) return;
            try {
                history.pushState({ quizProtected: true }, document.title, location.href);
            } catch (err) {}
        };
        window._lockQuizHistory = lockQuizHistory;

        window.enforceQuizProtection = function() {
            window._quizProtectionActive = true;
            try {
                lockQuizHistory();
            } catch (e) {}
        };

        window.releaseQuizProtection = function() {
            window._quizProtectionActive = false;
            window.onbeforeunload = null;
        };

        (function initGlobalSafetyAndProtectionListeners() {
            // 1. Disable text copying, cutting, and pasting
            document.addEventListener('copy', function(e) { e.preventDefault(); }, true);
            document.addEventListener('cut', function(e) { e.preventDefault(); }, true);
            document.addEventListener('paste', function(e) { e.preventDefault(); }, true);

            // 2. Disable right-click context menu (prevents Inspect element, View Source, and Reload options)
            document.addEventListener('contextmenu', function (e) {
                e.preventDefault();
                return false;
            }, true);

            // 3. Disable dragging of images/links/selected text
            document.addEventListener('dragstart', function(e) { e.preventDefault(); }, true);

            // 4. Disable DevTools keys & keyboard shortcuts (Always active)
            document.addEventListener('keydown', function(e) {
                // Disable F12
                if (e.keyCode === 123) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+Shift+I (DevTools Inspect)
                if (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.key === 'i' || e.key === 'I')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+Shift+J (DevTools Console)
                if (e.ctrlKey && e.shiftKey && (e.keyCode === 74 || e.key === 'j' || e.key === 'J')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+Shift+C (DevTools Element selector)
                if (e.ctrlKey && e.shiftKey && (e.keyCode === 67 || e.key === 'c' || e.key === 'C')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+U (View Source)
                if (e.ctrlKey && (e.keyCode === 85 || e.key === 'u' || e.key === 'U')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+S (Save Page)
                if (e.ctrlKey && (e.keyCode === 83 || e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Disable Ctrl+A (Select All)
                if (e.ctrlKey && (e.keyCode === 65 || e.key === 'a' || e.key === 'A')) {
                    const tag = e.target.tagName.toLowerCase();
                    if (tag !== 'input' && tag !== 'textarea' && !e.target.isContentEditable) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }

                // ── Quiz Screen Specific Keyboard Traps ──────────────────────
                if (window._quizProtectionActive) {
                    // Intercept Refresh Keys (F5, Ctrl+R, Cmd+R, Ctrl+F5, Shift+F5, Ctrl+Shift+R)
                    if (
                        e.key === 'F5' ||
                        e.keyCode === 116 ||
                        ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R' || e.keyCode === 82)) ||
                        (e.ctrlKey && (e.key === 'F5' || e.keyCode === 116)) ||
                        (e.shiftKey && (e.key === 'F5' || e.keyCode === 116))
                    ) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    // Intercept Browser Navigation Keys (Alt + Left/Right Arrow, Cmd + [/])
                    if (e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.keyCode === 37 || e.keyCode === 39)) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                    if (e.metaKey && (e.key === '[' || e.key === ']')) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }

                    // Intercept Backspace outside inputs (prevents legacy browser back navigation)
                    if ((e.key === 'Backspace' || e.keyCode === 8) &&
                        !['INPUT', 'TEXTAREA'].includes(e.target.tagName) &&
                        !e.target.isContentEditable) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            }, true);

            // 5. Intercept browser back button & mobile gestures (Android hardware/swipe back, iOS swipe back)
            if (window._quizProtectionActive) {
                try {
                    history.pushState({ quizProtected: true, init: true }, document.title, location.href);
                    lockQuizHistory();
                } catch (err) {}
            }

            // Popstate event handler for trapping back navigation on web & mobile without exiting
            window.addEventListener('popstate', function (e) {
                if (window.__isExitingQuiz) return;
                if (window.QuizSpa && window.QuizSpa.currentStage === 'answer-sheet') {
                    try {
                        history.pushState({ quizProtected: true, stage: 'answer-sheet' }, document.title, location.href);
                    } catch (err) {}
                    return;
                }
                if (window.QuizSpa && window.QuizSpa.currentStage === 'leaderboard') {
                    window.location.replace("{{ route('fsrapp.home') }}");
                    return;
                }
                if (window._quizProtectionActive) {
                    try {
                        history.pushState({ quizProtected: true }, document.title, location.href);
                    } catch (err) {}
                }
            });

            // 7. Warn / Intercept page reload & tab close when quiz is active
            window.addEventListener('beforeunload', function (e) {
                if (window.__isExitingQuiz || !window._quizProtectionActive) {
                    return;
                }
                if (window._quizProtectionActive) {
                    e.preventDefault();
                    e.returnValue = 'Quiz is currently active. Are you sure you want to leave? Your quiz progress may be lost.';
                    return e.returnValue;
                }
            });
        })();

        document.addEventListener('DOMContentLoaded', () => {
            window.updateSpeakerIcon();
            window.ensureSoundCache();
            window.preloadOneMinBeforeSound();
            if (typeof window.syncGlobalUpcomingQuizzes === 'function') {
                window.syncGlobalUpcomingQuizzes();
                setInterval(window.syncGlobalUpcomingQuizzes, 30000);
            }
            if (!window._quizProtectionActive && !(window.QuizSpa && window.QuizSpa.currentStage) && !(window.FffSpa && window.FffSpa.currentStage)) {
                if (typeof window.stopAllQuizAudios === 'function') {
                    window.stopAllQuizAudios();
                }
            }
        });
    </script>

    <!-- Firebase Cloud Messaging Web SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js"></script>
    <script>
        (function() {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                return;
            }

            const firebaseConfig = {
                apiKey: "{{ config('firebase.app_key', env('FIREBASE_APP_KEY', 'AIzaSyCuPnEty4CdFAA6ueQSqsTKpn8GYfmGr0c')) }}",
                authDomain: "fsrquiz.firebaseapp.com",
                projectId: "{{ config('firebase.project_id', env('FIREBASE_PROJECT_ID', 'fsrquiz')) }}",
                storageBucket: "fsrquiz.firebasestorage.app",
                messagingSenderId: "{{ config('firebase.messaging_sender_id', env('FIREBASE_MESSAGING_SENDER_ID', '145585273968')) }}",
                appId: "{{ config('firebase.app_id', env('FIREBASE_APP_ID', '1:145585273968:android:a71813c8159d0c8f2508b5')) }}"
            };

            let messagingInstance = null;

            try {
                if (!firebase.apps.length) {
                    firebase.initializeApp(firebaseConfig);
                }
                messagingInstance = firebase.messaging();
            } catch (e) {
                console.warn('[FCM] Firebase init error:', e);
                return;
            }

            function sendTokenToServer(token) {
                if (!token) return;
                const currentTopic = "{{ config('firebase.topic', env('FIREBASE_TOPIC', 'quiz_updates')) }}";
                const lastToken = localStorage.getItem('fsr_fcm_token');
                const lastTopic = localStorage.getItem('fsr_fcm_topic');
                const lastSynced = localStorage.getItem('fsr_fcm_synced');

                if (lastToken === token && lastTopic === currentTopic && lastSynced === '1') {
                    return;
                }

                fetch('/api/device-token/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        token: token,
                        platform: 'web',
                        topic: currentTopic,
                        device_name: navigator.userAgent ? navigator.userAgent.substring(0, 100) : 'Web Browser',
                        user_id: @json(auth()->id() ?: (session('fsr_user_id') ?: 0))
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        localStorage.setItem('fsr_fcm_token', token);
                        localStorage.setItem('fsr_fcm_topic', currentTopic);
                        localStorage.setItem('fsr_fcm_synced', '1');
                        console.log('[FCM] Device token registered and subscribed to topic (' + currentTopic + ') successfully.');
                    }
                })
                .catch(err => {
                    console.warn('[FCM] Token sync to server failed:', err);
                });
            }

            window.initFSRFirebaseMessaging = function() {
                if (!navigator.serviceWorker || !messagingInstance) return;

                navigator.serviceWorker.register('/firebase-messaging-sw.js')
                    .then(function(registration) {
                        const vapidKey = "{{ config('firebase.vapid_key', env('FIREBASE_VAPID_KEY', '')) }}";
                        const tokenOptions = {
                            serviceWorkerRegistration: registration
                        };
                        if (vapidKey) {
                            tokenOptions.vapidKey = vapidKey;
                        }

                        return messagingInstance.getToken(tokenOptions);
                    })
                    .then(function(currentToken) {
                        if (currentToken) {
                            sendTokenToServer(currentToken);
                        }
                    })
                    .catch(function(err) {
                        console.warn('[FCM] ServiceWorker / token registration notice:', err.message || err);
                    });
            };

            window.requestFSRNotificationPermission = function() {
                if (!('Notification' in window)) return;
                Notification.requestPermission().then(function(perm) {
                    if (perm === 'granted') {
                        window.initFSRFirebaseMessaging();
                    }
                });
            };

            // Auto-check on page load if permission already granted
            if (Notification.permission === 'granted') {
                window.initFSRFirebaseMessaging();
            } else if (Notification.permission === 'default') {
                const autoPromptOnUserAction = function() {
                    document.removeEventListener('click', autoPromptOnUserAction);
                    document.removeEventListener('touchstart', autoPromptOnUserAction);
                    window.requestFSRNotificationPermission();
                };
                document.addEventListener('click', autoPromptOnUserAction, { once: true });
                document.addEventListener('touchstart', autoPromptOnUserAction, { once: true });
            }

            // Listen for foreground push notifications
            if (messagingInstance) {
                messagingInstance.onMessage(function(payload) {
                    console.log('[FCM Foreground] Push received:', payload);
                    const title = payload.notification?.title || payload.data?.title || '⚡ FSR Quiz Alert';
                    const body = payload.notification?.body || payload.data?.body || 'Quiz शुरू होने वाला है!';
                    const actionUrl = payload.data?.action_url || payload.data?.click_action || '/fsrapp/home';

                    if (Notification.permission === 'granted') {
                        try {
                            const n = new Notification(title, {
                                body: body,
                                icon: '/assets/images/logo.png',
                                badge: '/assets/images/logo.png',
                                data: { action_url: actionUrl },
                                actions: [
                                    {
                                        action: 'join_now',
                                        title: '🚀 Join Now'
                                    }
                                ]
                            });
                            n.onclick = function() {
                                window.focus();
                                window.location.href = actionUrl;
                            };
                        } catch (e) {
                            console.warn('[FCM] In-app notification creation notice:', e);
                        }
                    }
                });
            }
        })();
    </script>
    @stack('scripts')
</body>

</html>
