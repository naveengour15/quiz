<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8">
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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="{{ asset('fsrapp/css/fonts.css') }}">
    <style>
        :root {
            --surface-glass: rgba(255, 255, 255, 0.06);
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        * {
            -webkit-tap-highlight-color: transparent;
        }

        html {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            position: relative;
            /* makes body the containing block so the absolutely
                                positioned .glow-effect blobs below get clipped by
                                overflow-x:hidden instead of escaping to the
                                viewport and enabling sideways scroll on touch */
            background-color: #160d2f;
            color: #e8ddff;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        input,
        select,
        textarea {
            font-size: 16px;
            /* prevents iOS Safari from auto-zooming into focused fields */
            max-width: 100%;
        }

        img,
        svg {
            max-width: 100%;
        }

        .glass-card {
            background: var(--surface-glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
        }

        .vibrant-gradient {
            background: linear-gradient(135deg, #7c3aed 0%, #ffb1c2 100%);
        }

        .glow-effect {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.15) 0%, rgba(22, 13, 47, 0) 70%);
            z-index: -1;
            filter: blur(40px);
            pointer-events: none;
        }

        ::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
        }

        .spin {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn .25s ease both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .otp-box {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 22px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.6);
        }

        .toast-enter {
            animation: toastIn .25s ease both;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translate(-50%, -10px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        .skeleton {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.05) 25%, rgba(255, 255, 255, 0.12) 37%, rgba(255, 255, 255, 0.05) 63%);
            background-size: 400% 100%;
            animation: shimmer 1.4s ease infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0 50%;
            }
        }

        .progress-bar-fill {
            transition: width .3s linear;
        }

        .option-btn.selected {
            border-color: #d2bbff !important;
            background: rgba(124, 58, 237, 0.25) !important;
        }

        .option-btn.correct {
            border-color: #00E5A0 !important;
            background: rgba(0, 229, 160, 0.15) !important;
        }

        .option-btn.wrong {
            border-color: #ffb4ab !important;
            background: rgba(147, 0, 10, 0.25) !important;
        }

        /* ===================== KBC-STYLE QUIZ THEME ===================== */
        /* Palette matched to the "KBC Style Quiz Play" reference screenshot:
       deep purple-black stage, warm gold for chrome/borders/text, and a
       teal-to-mint gradient reserved specifically for the countdown ring. */
        :root {
            --kbc-gold: #ffd76a;
            --kbc-gold-deep: #d4a537;
            --kbc-navy: #150a28;
            --kbc-navy-2: #1f1038;
            --kbc-teal: #29e0c9;
            --kbc-mint: #7cf29a;
        }

        .kbc-stage {
            position: relative;
            background:
                radial-gradient(120% 70% at 50% -10%, rgba(255, 215, 106, 0.10) 0%, rgba(255, 215, 106, 0) 60%),
                radial-gradient(90% 60% at 50% 110%, rgba(124, 58, 237, 0.22) 0%, rgba(124, 58, 237, 0) 60%),
                linear-gradient(180deg, var(--kbc-navy) 0%, var(--kbc-navy-2) 100%);
            border-radius: 20px;
        }

        .kbc-spotlight {
            position: absolute;
            inset: 0;
            overflow: hidden;
            border-radius: 20px;
            pointer-events: none;
            z-index: 0;
        }

        .kbc-spotlight::before,
        .kbc-spotlight::after {
            content: '';
            position: absolute;
            top: -30%;
            width: 2px;
            height: 160%;
            background: linear-gradient(180deg, rgba(255, 215, 106, 0.0), rgba(255, 215, 106, 0.10), rgba(255, 215, 106, 0.0));
            animation: kbcSweep 5s ease-in-out infinite;
        }

        .kbc-spotlight::before {
            left: 20%;
            animation-delay: 0s;
        }

        .kbc-spotlight::after {
            left: 75%;
            animation-delay: 1.6s;
        }

        @keyframes kbcSweep {

            0%,
            100% {
                transform: rotate(8deg) scaleY(0.9);
                opacity: .4;
            }

            50% {
                transform: rotate(-6deg) scaleY(1.1);
                opacity: 1;
            }
        }

        @keyframes qSlideInLeft {
            from {
                opacity: 0;
                transform: translateX(-80px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes qSlideInRight {
            from {
                opacity: 0;
                transform: translateX(80px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .kbc-q-fade-in,
        .kbc-q-slide-in,
        .kbc-q-slide-left {
            animation: qSlideInLeft 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .kbc-q-slide-right {
            animation: qSlideInRight 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-60px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(60px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .kbc-opt-slide-left {
            animation: slideInLeft 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .kbc-opt-slide-right {
            animation: slideInRight 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* Ambient radar-style rings on the KBC stage background, like the glowing
       concentric circles behind the classic quiz set. Purely decorative. */
        .kbc-stage-rings {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            border-radius: 20px;
        }

        .kbc-stage-rings span {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 215, 106, 0.16);
            box-shadow: 0 0 12px rgba(255, 215, 106, 0.06) inset;
            animation: kbcRingPulse 4.5s ease-out infinite;
        }

        .kbc-stage-rings span:nth-child(1) {
            width: 110px;
            height: 110px;
            animation-delay: 0s;
        }

        .kbc-stage-rings span:nth-child(2) {
            width: 220px;
            height: 220px;
            animation-delay: 1.1s;
        }

        .kbc-stage-rings span:nth-child(3) {
            width: 340px;
            height: 340px;
            animation-delay: 2.2s;
        }

        .kbc-stage-rings span:nth-child(4) {
            width: 460px;
            height: 460px;
            animation-delay: 3.3s;
        }

        @keyframes kbcRingPulse {
            0% {
                transform: scale(0.75);
                opacity: 0;
            }

            20% {
                opacity: .55;
            }

            100% {
                transform: scale(1.12);
                opacity: 0;
            }
        }

        .kbc-card {
            position: relative;
            z-index: 1;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 215, 106, 0.35);
            box-shadow: 0 0 0 1px rgba(255, 215, 106, 0.08) inset, 0 12px 40px rgba(0, 0, 0, 0.45), 0 0 30px rgba(255, 215, 106, 0.08);
        }

        .kbc-title-glow {
            color: var(--kbc-gold);
            text-shadow: 0 0 12px rgba(255, 215, 106, 0.55), 0 0 2px rgba(255, 215, 106, 0.8);
            letter-spacing: .04em;
        }

        .kbc-q-badge {
            border: 1px solid rgba(255, 215, 106, 0.5);
            color: var(--kbc-gold);
            background: rgba(255, 215, 106, 0.08);
        }

        /* Traveling-light gradient border — wraps the question card. Uses a CSS
       mask to paint the conic-gradient ONLY inside the border-thickness ring
       (mask-composite: exclude cuts out the center), so the light is confined
       strictly to the border line and never bleeds into the card's own
       background, no matter how transparent that background is.
       IMPORTANT: the highlight is animated by rotating the `--border-angle`
       custom property that feeds the conic-gradient's `from` angle — NOT by
       applying `transform: rotate()` to the pseudo-element. A transform would
       spin the whole rounded-rect shape (including its corners), which reads
       as the entire box rotating. Animating the angle instead just slides the
       color around a perfectly static ring, so the card itself never moves. */
        @property --border-angle {
            syntax: '<angle>';
            inherits: false;
            initial-value: 0deg;
        }

        .kbc-glow-border {
            position: relative;
            border-radius: 20px;
            padding: 2px;
        }

        .kbc-glow-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 2px;
            background: conic-gradient(from var(--border-angle),
                    transparent 0deg,
                    rgba(255, 215, 106, 0.95) 35deg,
                    transparent 95deg,
                    rgba(124, 58, 237, 0.85) 180deg,
                    transparent 250deg,
                    rgba(255, 215, 106, 0.95) 320deg,
                    transparent 360deg);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            animation: kbcBorderSpin 4.5s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        .kbc-glow-inner {
            position: relative;
            z-index: 1;
            border-radius: inherit;
        }

        @keyframes kbcBorderSpin {
            to {
                --border-angle: 360deg;
            }
        }

        /* Same technique, red palette — used to highlight a wrong answer in the
       review screen. The box itself never moves or resizes; only the ring
       of colour along the border keeps traveling. */
        .kbc-glow-border-red {
            position: relative;
            border-radius: 12px;
            padding: 2px;
        }

        .kbc-glow-border-red::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 2px;
            background: conic-gradient(from var(--border-angle),
                    transparent 0deg,
                    rgba(255, 90, 90, 0.95) 40deg,
                    transparent 100deg,
                    rgba(255, 180, 171, 0.7) 200deg,
                    transparent 260deg,
                    rgba(255, 90, 90, 0.95) 330deg,
                    transparent 360deg);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            animation: kbcBorderSpin 3.2s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        .kbc-glow-inner-red {
            position: relative;
            z-index: 1;
            border-radius: inherit;
            background: rgba(120, 0, 10, 0.34);
        }

        /* Same technique, green palette — used to highlight a correct answer in
       the review screen. Mirrors kbc-glow-border-red exactly, just tinted
       green, so both states read as a matched pair. */
        .kbc-glow-border-green {
            position: relative;
            border-radius: 12px;
            padding: 2px;
        }

        .kbc-glow-border-green::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 2px;
            background: conic-gradient(from var(--border-angle),
                    transparent 0deg,
                    rgba(0, 229, 160, 0.95) 40deg,
                    transparent 100deg,
                    rgba(126, 255, 201, 0.7) 200deg,
                    transparent 260deg,
                    rgba(0, 229, 160, 0.95) 330deg,
                    transparent 360deg);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            animation: kbcBorderSpin 3.2s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        .kbc-glow-inner-green {
            position: relative;
            z-index: 1;
            border-radius: inherit;
            background: rgba(6, 60, 40, 0.34);
        }

        /* Same traveling-light technique again, this time as a per-option wrapper
       on the quiz answer buttons. Colour is driven by CSS custom properties
       so one ruleset covers the idle/selected/correct/wrong states — no
       separate keyframes needed per state, and (as above) nothing ever
       transforms the box itself, only the ring of colour inside the mask. */
        .kbc-opt-glow {
            position: relative;
            border-radius: 14px;
            padding: 2px;
            --opt-glow-a: rgba(255, 215, 106, 0.55);
            --opt-glow-b: rgba(124, 58, 237, 0.45);
        }

        .kbc-opt-glow::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 2px;
            background: conic-gradient(from var(--border-angle),
                    transparent 0deg,
                    var(--opt-glow-a) 35deg,
                    transparent 95deg,
                    var(--opt-glow-b) 180deg,
                    transparent 250deg,
                    var(--opt-glow-a) 320deg,
                    transparent 360deg);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            animation: kbcBorderSpin 4.5s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        .kbc-opt-glow.selected {
            --opt-glow-a: rgba(255, 215, 106, 0.95);
            --opt-glow-b: rgba(255, 215, 106, 0.5);
        }

        .kbc-opt-glow.correct {
            --opt-glow-a: rgba(0, 229, 160, 0.95);
            --opt-glow-b: rgba(0, 229, 160, 0.5);
        }

        .kbc-opt-glow.wrong {
            --opt-glow-a: rgba(255, 90, 90, 0.95);
            --opt-glow-b: rgba(255, 90, 90, 0.5);
        }

        .kbc-opt-glow>.kbc-option {
            position: relative;
            z-index: 1;
            border-radius: inherit;
            width: 100%;
        }

        /* Browsers without @property support fall back to a static conic-gradient
       ring — still a glow, just without the traveling motion, and still
       never rotates the box. */

        /* Countdown badge with a rotating ring sweep + pulsing glow rings when urgent —
       gives the timer a "live" ring effect instead of a static circle. */
        .kbc-timer {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 2px solid rgba(255, 215, 106, 0.55);
            color: var(--kbc-gold);
            font-family: 'JetBrains Mono', monospace;
            box-shadow: 0 0 14px rgba(255, 215, 106, 0.25);
            transition: box-shadow .2s ease, border-color .2s ease, color .2s ease;
        }

        .kbc-timer::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: rgba(255, 215, 106, 0.65);
            animation: spin 2.4s linear infinite;
        }

        .kbc-timer::after {
            content: '';
            position: absolute;
            inset: -12px;
            border-radius: 50%;
            border: 1px solid rgba(255, 215, 106, 0.18);
            animation: kbcRingPulse 2.4s ease-out infinite;
        }

        .kbc-timer.urgent {
            border-color: #ff6a6a;
            color: #ff8a8a;
            box-shadow: 0 0 18px rgba(255, 90, 90, 0.45);
            animation: kbcPulse 1s ease-in-out infinite;
        }

        .kbc-timer.urgent::before {
            border-top-color: rgba(255, 106, 106, 0.85);
            animation-duration: .7s;
        }

        .kbc-timer.urgent::after {
            border-color: rgba(255, 106, 106, 0.35);
            animation-duration: 1s;
        }

        @keyframes kbcPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.08);
            }
        }

        /* ---- KBC option bar: chrome/silver pointed-hexagon OUTLINE only.
       Background stays fully transparent — the stage glow/rings show through.
       Shape is drawn with an inline SVG stroke (see kbcOptionSvg() in JS) so
       the border follows the hexagon precisely, including the pointed tips. ---- */
        .kbc-option {
            position: relative;
            z-index: 1;
            padding: 16px 30px;
            background: transparent;
            color: #f2ecff;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
            --kbc-c1: #fdfdfd;
            --kbc-c2: #9a9aa4;
            --kbc-c3: #f5f5f8;
            --kbc-c4: #6f6f78;
            --kbc-c5: #f2f2f5;
            transition: transform .15s ease, filter .15s ease;
        }

        .kbc-hex-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
            filter: drop-shadow(0 0 6px rgba(255, 215, 106, 0.16));
            transition: filter .2s ease;
        }

        .kbc-option:hover:not(:disabled) {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .kbc-option:hover:not(:disabled) .kbc-hex-svg {
            filter: drop-shadow(0 0 12px rgba(255, 215, 106, 0.35));
        }

        .kbc-option .kbc-opt-letter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            flex: 0 0 auto;
            border: 1px solid rgba(255, 215, 106, 0.6);
            color: var(--kbc-gold);
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            background: transparent;
        }

        .kbc-option.selected {
            --kbc-c1: #fff6d8;
            --kbc-c2: var(--kbc-gold-deep);
            --kbc-c3: #ffe9ad;
            --kbc-c4: var(--kbc-gold-deep);
            --kbc-c5: #fff2c2;
        }

        .kbc-option.selected .kbc-hex-svg {
            filter: drop-shadow(0 0 16px rgba(255, 215, 106, 0.55));
        }

        .kbc-option.correct {
            --kbc-c1: #c8ffe9;
            --kbc-c2: #0a8f66;
            --kbc-c3: #7effc9;
            --kbc-c4: #065c40;
            --kbc-c5: #b6ffdf;
            animation: kbcCorrectPop .4s ease;
        }

        .kbc-option.correct .kbc-hex-svg {
            filter: drop-shadow(0 0 18px rgba(0, 229, 160, 0.55));
        }

        .kbc-option.wrong {
            --kbc-c1: #ffd6d6;
            --kbc-c2: #9c1f1f;
            --kbc-c3: #ff9a9a;
            --kbc-c4: #6e1414;
            --kbc-c5: #ffbcbc;
            animation: kbcShake .35s ease;
        }

        .kbc-option.wrong .kbc-hex-svg {
            filter: drop-shadow(0 0 16px rgba(255, 90, 90, 0.5));
        }

        @keyframes kbcCorrectPop {
            0% {
                transform: scale(1);
            }

            40% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes kbcShake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-4px);
            }

            75% {
                transform: translateX(4px);
            }
        }

        .kbc-progress-track {
            background: rgba(255, 215, 106, 0.12);
        }

        .kbc-progress-fill {
            background: linear-gradient(90deg, var(--kbc-gold-deep), var(--kbc-gold));
            box-shadow: 0 0 10px rgba(255, 215, 106, 0.5);
            transition: width .3s linear;
        }

        .kbc-coin-badge {
            background: linear-gradient(180deg, #ffe9ad, #d4a537);
            color: #3f2e00;
            box-shadow: 0 0 20px rgba(255, 215, 106, 0.45);
        }

        .kbc-reveal-row {
            animation: kbcRevealRow .35s ease both;
        }

        @keyframes kbcRevealRow {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .kbc-drumroll-ring {
            position: relative;
            width: 90px;
            height: 90px;
            border-radius: 999px;
            border: 3px solid rgba(255, 215, 106, 0.25);
            border-top-color: var(--kbc-gold);
            animation: spin 1s linear infinite;
        }

        .kbc-drumroll-ring::before,
        .kbc-drumroll-ring::after {
            content: '';
            position: absolute;
            inset: -14px;
            border-radius: 50%;
            border: 1px solid rgba(255, 215, 106, 0.18);
            animation: kbcRingPulse 2s ease-out infinite;
        }

        .kbc-drumroll-ring::after {
            inset: -30px;
            animation-delay: .6s;
        }

        .kbc-mute-btn {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 215, 106, 0.4);
            color: var(--kbc-gold);
            background: rgba(255, 215, 106, 0.06);
        }

        /* ---- Header icon-badge row (close / mute / etc.) — circular, thin gold
       ring, matches the reference screenshot's top row of round icons. ---- */
        .kbc-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(255, 215, 106, 0.45);
            color: var(--kbc-gold);
            background: rgba(255, 215, 106, 0.05);
            transition: border-color .15s ease, background .15s ease;
        }

        .kbc-icon-badge:hover {
            border-color: rgba(255, 215, 106, 0.8);
            background: rgba(255, 215, 106, 0.1);
        }

        /* ---- Prize pool pill — dark rounded pill, gold border, diamond icons
       flanking the amount. Sits below the header, above the timer dial. ---- */
        .kbc-prize-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1.5px solid rgba(255, 215, 106, 0.5);
            background: rgba(255, 215, 106, 0.06);
            border-radius: 999px;
            padding: 8px 20px;
            color: var(--kbc-gold);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            box-shadow: 0 0 16px rgba(255, 215, 106, 0.12);
        }

        /* ---- Circular countdown dial — teal-to-mint gradient ring with the
       seconds-remaining number glowing in the center. Replaces the small
       text-pill timer with the large dial shown in the reference image. ---- */
        .kbc-dial-wrap {
            position: relative;
            width: 110px;
            height: 110px;
            margin: 4px auto 6px;
        }

        .kbc-dial-wrap svg {
            transform: rotate(-90deg);
            width: 100%;
            height: 100%;
        }

        .kbc-dial-track {
            stroke: rgba(255, 255, 255, 0.08);
        }

        .kbc-dial-fill {
            stroke: url(#kbcDialGradient);
            stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
            filter: drop-shadow(0 0 6px rgba(124, 242, 154, 0.45));
        }

        .kbc-dial-number {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 34px;
            color: var(--kbc-mint);
            text-shadow: 0 0 14px rgba(124, 242, 154, 0.55);
        }

        .kbc-dial-wrap.urgent .kbc-dial-number {
            color: #ff8a8a;
            text-shadow: 0 0 14px rgba(255, 90, 90, 0.6);
        }

        .kbc-dial-wrap.urgent .kbc-dial-fill {
            stroke: #ff6a6a;
            filter: drop-shadow(0 0 8px rgba(255, 90, 90, 0.55));
        }

        .kbc-q-caption {
            text-align: center;
            color: var(--kbc-gold);
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: 20px;
        }

        /* ---- Flat option row — dark card, thin gold top-divider, "A:" style
       gold letter prefix. Matches the reference image's answer-option look
       (kept alongside the existing traveling glow-border wrapper, which
       still provides the selected/correct/wrong ring animation). ---- */
        .kbc-option-flat {
            position: relative;
            z-index: 1;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 16px;
            border-radius: 12px;
            border-top: 2px solid rgba(255, 215, 106, 0.55);
            background: rgba(255, 255, 255, 0.03);
            color: #f2ecff;
            transition: background .15s ease;
        }

        .kbc-option-flat:hover:not(:disabled) {
            background: rgba(255, 215, 106, 0.06);
        }

        .kbc-option-flat .kbc-opt-letter-flat {
            color: var(--kbc-gold);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .kbc-winner-row.top {
            border: 1px solid rgba(255, 215, 106, 0.55) !important;
            box-shadow: 0 0 22px rgba(255, 215, 106, 0.2);
        }

        .kbc-confetti {
            position: absolute;
            top: -10px;
            width: 8px;
            height: 14px;
            opacity: 0.9;
            animation: kbcFall linear forwards;
        }

        @keyframes kbcFall {
            to {
                transform: translateY(420px) rotate(540deg);
                opacity: 0;
            }
        }
    </style>
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
    <div id="app" class="max-w-md mx-auto min-h-screen relative"></div>

    <!-- Toast -->
    <div id="toast" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%] toast-enter"></div>

    <!-- Global loading overlay -->
    <div id="global-loader"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[90] hidden items-center justify-center">
        <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div>
    </div>

    <script>
        /* =========================================================================
                                       FSRQuiz — Full API-driven Quiz App
                                       Single-file SPA. Works when opened directly (file://) or served from any
                                       host. Configure the API base URL from the Settings screen (gear icon on
                                       the login page) — this is stored in localStorage so it works the same
                                       whether you run it locally or deploy it on a server.
                                       ========================================================================= */

        /* ---------------------------- CONFIG / STORAGE --------------------------- */
        /* Pre-configured API base — set as the default so the app works out of the
           box without needing the Settings screen. Users can still change it from
           the gear icon if they want to point at a different server. */
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

        /* ============================================================================
           MOCK API LAYER — DESIGN-ONLY BUILD, NO REAL NETWORK CALLS
           ----------------------------------------------------------------------------
           Every endpoint the app calls is answered below with realistic canned data
           after a short simulated delay, so the entire flow (login → OTP → home →
           quiz → result → wallet → redeem → referral → profile → leaderboard) works
           end-to-end in a browser with zero backend.

           When your tech team wires up the real API, this whole block (down to the
           line marked "END MOCK API LAYER") gets deleted and swapped back for a real
           fetch()-based implementation — the ORIGINAL_API_IMPLEMENTATION reference
           at the very bottom of this comment block shows exactly what that looked
           like, so it's a straight swap, not a rebuild.
           ============================================================================ */

        const MOCK_DELAY_MS = 550; // simulated network latency
        function mockResolve(payload) {
            return new Promise((resolve) => setTimeout(() => resolve(payload), MOCK_DELAY_MS));
        }

        // ---- Static mock data bank ----------------------------------------------
        const MOCK_CATEGORIES = [{
                id: 'cat1',
                name: 'GK'
            },
            {
                id: 'cat2',
                name: 'Current Affairs'
            },
            {
                id: 'cat3',
                name: 'Sports'
            },
            {
                id: 'cat4',
                name: 'Science'
            },
            {
                id: 'cat5',
                name: 'Entertainment'
            },
        ];

        const MOCK_ACTIVE_QUIZZES = [{
                id: 'quiz-101',
                title: 'Current Affairs — 15 Jul',
                category_id: 'cat2',
                prize_pool: '500',
                entry_fee: '0',
                joined_count: '1,204',
                players: '1,204',
                is_private: false
            },
            {
                id: 'quiz-102',
                title: 'Sports Trivia Sprint',
                category_id: 'cat3',
                prize_pool: '300',
                entry_fee: '0',
                joined_count: '842',
                players: '842',
                is_private: false
            },
            {
                id: 'quiz-103',
                title: 'Bollywood Rewind',
                category_id: 'cat5',
                prize_pool: '400',
                entry_fee: '0',
                joined_count: '2,110',
                players: '2,110',
                is_private: false
            },
            {
                id: 'quiz-104',
                title: 'Science & Space GK',
                category_id: 'cat4',
                prize_pool: '350',
                entry_fee: '0',
                joined_count: '615',
                players: '615',
                is_private: false
            },
        ];

        const MOCK_UPCOMING_QUIZZES = [{
                id: 'quiz-201',
                title: 'SSC CGL Mock Test #15',
                start_time: '',
                prize_pool: '600',
                entry_fee: '0'
            },
            {
                id: 'quiz-202',
                title: 'Weekend GK Marathon',
                start_time: '',
                prize_pool: '450',
                entry_fee: '0'
            },
        ];
        // Give upcoming quizzes a real "later today" start_time so their cards show something sensible.
        (function fillUpcomingStartTimes() {
            const now = new Date();
            MOCK_UPCOMING_QUIZZES[0].start_time = new Date(now.getTime() + 30 * 60000).toTimeString().slice(0, 5);
            MOCK_UPCOMING_QUIZZES[1].start_time = new Date(now.getTime() + 3 * 3600000).toTimeString().slice(0, 5);
        })();

        const MOCK_PRACTICE_QUIZZES = [{
                id: 'quiz-301',
                title: 'Practice: Basic GK',
                difficulty: 'Easy',
                question_count: '10'
            },
            {
                id: 'quiz-302',
                title: 'Practice: Reasoning Warm-up',
                difficulty: 'Medium',
                question_count: '12'
            },
            {
                id: 'quiz-303',
                title: 'Practice: Current Affairs Drill',
                difficulty: 'Easy',
                question_count: '8'
            },
        ];

        const MOCK_BANNERS = [{
                title: 'Weekend Double Points — Play Now',
                link: '/quiz/list'
            },
            {
                title: 'Invite Friends, Earn 50 Coins Each',
                link: '/referral'
            },
        ];

        // Question bank shared by every quiz session (id-agnostic — same pool
        // regardless of which quiz was joined, purely for design purposes).
        const MOCK_QUESTIONS = [{
                id: 'q1',
                text: "Which Indian state recently launched India's first fully solar-powered village grid initiative?",
                options: [{
                    id: 'a',
                    text: 'Gujarat'
                }, {
                    id: 'b',
                    text: 'Rajasthan'
                }, {
                    id: 'c',
                    text: 'Kerala'
                }, {
                    id: 'd',
                    text: 'Assam'
                }],
                correct: 'a'
            },
            {
                id: 'q2',
                text: 'Who is widely known as the architect of the Indian Constitution?',
                options: [{
                    id: 'a',
                    text: 'Jawaharlal Nehru'
                }, {
                    id: 'b',
                    text: 'B. R. Ambedkar'
                }, {
                    id: 'c',
                    text: 'Sardar Patel'
                }, {
                    id: 'd',
                    text: 'Rajendra Prasad'
                }],
                correct: 'b'
            },
            {
                id: 'q3',
                text: 'Which planet is known as the Red Planet?',
                options: [{
                    id: 'a',
                    text: 'Venus'
                }, {
                    id: 'b',
                    text: 'Jupiter'
                }, {
                    id: 'c',
                    text: 'Mars'
                }, {
                    id: 'd',
                    text: 'Saturn'
                }],
                correct: 'c'
            },
            {
                id: 'q4',
                text: 'The 2023 Cricket World Cup was hosted by which country?',
                options: [{
                    id: 'a',
                    text: 'Australia'
                }, {
                    id: 'b',
                    text: 'India'
                }, {
                    id: 'c',
                    text: 'England'
                }, {
                    id: 'd',
                    text: 'South Africa'
                }],
                correct: 'b'
            },
            {
                id: 'q5',
                text: 'What is the chemical symbol for Gold?',
                options: [{
                    id: 'a',
                    text: 'Ag'
                }, {
                    id: 'b',
                    text: 'Gd'
                }, {
                    id: 'c',
                    text: 'Au'
                }, {
                    id: 'd',
                    text: 'Go'
                }],
                correct: 'c'
            },
            {
                id: 'q6',
                text: 'Which is the longest river in India?',
                options: [{
                    id: 'a',
                    text: 'Yamuna'
                }, {
                    id: 'b',
                    text: 'Godavari'
                }, {
                    id: 'c',
                    text: 'Brahmaputra'
                }, {
                    id: 'd',
                    text: 'Ganga'
                }],
                correct: 'd'
            },
        ];

        const MOCK_REWARDS = [{
                id: 'rw1',
                name: 'Mobile Recharge ₹50',
                coins: '500'
            },
            {
                id: 'rw2',
                name: 'Amazon Voucher ₹100',
                coins: '900'
            },
            {
                id: 'rw3',
                name: 'Electricity Bill Credit ₹50',
                coins: '520'
            },
            {
                id: 'rw4',
                name: 'FSRQuiz Branded Cap',
                coins: '1800'
            },
        ];

        const MOCK_LEADERBOARD_NAMES = ['Priya Sharma', 'Rahul Kapoor', 'Sana Iyer', 'Dev Malhotra', 'Aarav Mehta (You)',
            'Nikhil Thakur', 'Meera Nair', 'Karan Vora'
        ];

        function findMockQuiz(quizId) {
            return [...MOCK_ACTIVE_QUIZZES, ...MOCK_UPCOMING_QUIZZES, ...MOCK_PRACTICE_QUIZZES].find((q) => q.id ===
                quizId) || {
                id: quizId,
                title: 'Quiz',
                prize_pool: '200',
                entry_fee: '0',
                joined_count: '318'
            };
        }

        // In-memory per-quiz session state — tracks which question we're on and the
        // running answer log, so /submit-answer, /my-result and /answer-sheet all
        // stay consistent with what was actually "played" in this browser session.
        const mockQuizSessions = {};

        function ensureMockSession(quizId) {
            if (!mockQuizSessions[quizId]) mockQuizSessions[quizId] = {
                idx: -1,
                log: []
            };
            return mockQuizSessions[quizId];
        }

        function mockQuestionPayload(idx) {
            const q = MOCK_QUESTIONS[idx];
            if (!q) return {
                data: {}
            }; // no more questions
            return {
                data: {
                    question: {
                        id: q.id,
                        question: q.text
                    },
                    total_questions: MOCK_QUESTIONS.length
                }
            };
        }

        async function api(path, {
            method = 'GET',
            body = null,
            auth = true,
            query = null
        } = {}) {
            console.log(`[MOCK API] ${method} ${path}`, body || query || '');

            // ---- AUTH ---------------------------------------------------------------
            if (path === '/auth/send-otp') return mockResolve({
                data: {
                    token: 'mock-otp-token',
                    is_new_user: false
                }
            });
            if (path === '/auth/resend-otp') return mockResolve({
                message: 'OTP resent'
            });
            if (path === '/auth/verify-otp') {
                return mockResolve({
                    data: {
                        token: 'mock-access-token-demo',
                        is_new_user: false,
                        user: {
                            name: 'Aarav Mehta',
                            mobile: Store.getPendingMobile() || '9876543210',
                            email: ''
                        }
                    }
                });
            }
            if (path === '/signup') {
                return mockResolve({
                    data: {
                        token: 'mock-access-token-demo',
                        user: {
                            name: body && body.name || 'Player',
                            mobile: body && body.mobile || ''
                        }
                    }
                });
            }

            // ---- HOME / CATALOG -------------------------------------------------------
            if (path === '/categories') return mockResolve({
                data: MOCK_CATEGORIES
            });
            if (path === '/home/banners') return mockResolve({
                data: MOCK_BANNERS
            });
            if (path === '/quiz/list') {
                const catId = query && query.category_id;
                const list = catId ? MOCK_ACTIVE_QUIZZES.filter((q) => q.category_id === catId) : MOCK_ACTIVE_QUIZZES;
                return mockResolve({
                    data: list
                });
            }
            if (path === '/quiz/upcoming') return mockResolve({
                data: MOCK_UPCOMING_QUIZZES
            });
            if (path === '/quiz/practice') return mockResolve({
                data: MOCK_PRACTICE_QUIZZES
            });

            // ---- USER / DASHBOARD -----------------------------------------------------
            if (path === '/user/dashboard') {
                return mockResolve({
                    data: {
                        rank: '128',
                        streak: '6',
                        wins: '14',
                        recent_activity: [{
                                quiz_title: 'Current Affairs — 14 Jul',
                                status: 'Completed',
                                coins: '40'
                            },
                            {
                                quiz_title: 'Sports Trivia Sprint',
                                status: 'Completed',
                                coins: '25'
                            },
                        ]
                    }
                });
            }
            if (path === '/user/profile') return mockResolve({
                data: {
                    name: 'Aarav Mehta',
                    mobile: Store.getPendingMobile() || '9876543210',
                    email: '',
                    city: 'Indore'
                }
            });
            if (path === '/user/update-profile') return mockResolve({
                message: 'Profile updated'
            });
            if (path === '/user/delete-account') return mockResolve({
                message: 'Account deleted'
            });
            if (path === '/user/quiz-history') {
                return mockResolve({
                    data: [{
                            quiz_id: 'quiz-101',
                            title: 'Current Affairs — 14 Jul',
                            status: 'Completed',
                            score: '80',
                            coins: '40'
                        },
                        {
                            quiz_id: 'quiz-102',
                            title: 'Sports Trivia Sprint',
                            status: 'Completed',
                            score: '60',
                            coins: '25'
                        },
                    ]
                });
            }

            // ---- WALLET / REWARDS -------------------------------------------------------
            if (path === '/user/wallet') return mockResolve({
                data: {
                    balance: '1,240'
                }
            });
            if (path === '/user/wallet-history') {
                return mockResolve({
                    data: [{
                            description: 'Current Affairs — 14 Jul',
                            amount: '+40',
                            created_at: 'Today'
                        },
                        {
                            description: 'Redeemed: Mobile Recharge ₹50',
                            amount: '-500',
                            created_at: 'Yesterday',
                            type: 'debit'
                        },
                        {
                            description: 'Referral bonus — Priya joined',
                            amount: '+50',
                            created_at: '2 days ago'
                        },
                    ]
                });
            }
            if (path === '/user/rewards') return mockResolve({
                data: MOCK_REWARDS
            });
            if (path === '/user/redeem') return mockResolve({
                message: 'Redemption requested!'
            });
            if (path === '/user/redemptions') {
                return mockResolve({
                    data: [{
                            reward_name: 'Mobile Recharge ₹50',
                            status: 'Fulfilled',
                            coins: '500'
                        },
                        {
                            reward_name: 'Amazon Voucher ₹100',
                            status: 'Pending',
                            coins: '900'
                        },
                    ]
                });
            }

            // ---- REFERRAL ---------------------------------------------------------------
            if (path === '/user/referral-code') return mockResolve({
                data: {
                    referral_code: 'FSRQuiz-AARAV12'
                }
            });
            if (path === '/user/referrals') {
                return mockResolve({
                    data: [{
                            name: 'Priya Sharma',
                            status: 'Joined',
                            reward: '50'
                        },
                        {
                            name: 'Rahul Kapoor',
                            status: 'Joined',
                            reward: '50'
                        },
                    ]
                });
            }
            if (path === '/user/apply-referral') return mockResolve({
                message: 'Referral code applied'
            });

            // ---- LEADERBOARD (global) ----------------------------------------------------
            if (path === '/leaderboard') {
                const rows = MOCK_LEADERBOARD_NAMES.map((name, i) => ({
                    user_name: name,
                    points: String(2400 - i * 180),
                    is_me: name.includes('(You)')
                }));
                return mockResolve({
                    data: rows
                });
            }

            // ---- DYNAMIC /quiz/:id/... ROUTES --------------------------------------------
            let m;

            if ((m = /^\/quiz\/([^/]+)\/join$/.exec(path))) {
                const quizId = m[1];
                // Upcoming quizzes get a short real countdown so the waiting-room screen
                // is actually visible in the demo; everything else starts immediately.
                const isUpcoming = MOCK_UPCOMING_QUIZZES.some((q) => q.id === quizId);
                const startTime = isUpcoming ? new Date(Date.now() + 15000).toTimeString().slice(0, 8) : null;
                mockQuizSessions[quizId] = {
                    idx: -1,
                    log: []
                }; // fresh session on every join
                return mockResolve({
                    data: {
                        start_time: startTime
                    },
                    message: 'Joined quiz!'
                });
            }

            if ((m = /^\/quiz\/([^/]+)\/leave$/.exec(path))) return mockResolve({
                message: 'Left quiz'
            });

            if ((m = /^\/quiz\/([^/]+)\/winners$/.exec(path))) {
                const rows = MOCK_LEADERBOARD_NAMES.slice(0, 6).map((name, i) => ({
                    user_name: name,
                    total_correct_answer: String(6 - i),
                    total_time: String(48 + i * 6),
                    per_ans_average_time: String(8 + i),
                    reward: i < 3 ? String(150 - i * 40) : null,
                    reward_type: i < 3 ? 'Coins' : null,
                }));
                return mockResolve({
                    data: rows
                });
            }

            if ((m = /^\/quiz\/([^/]+)\/start$/.exec(path))) {
                const quizId = m[1];
                const sess = ensureMockSession(quizId);
                sess.idx = 0;
                sess.log = [];
                return mockResolve(mockQuestionPayload(0));
            }

            if ((m = /^\/quiz\/([^/]+)\/get-question$/.exec(path))) {
                const quizId = m[1];
                const sess = ensureMockSession(quizId);
                const lastIdx = MOCK_QUESTIONS.findIndex((q) => q.id === (body && body.last_question_id));
                sess.idx = (lastIdx >= 0 ? lastIdx : sess.idx) + 1;
                return mockResolve(mockQuestionPayload(sess.idx));
            }

            if ((m = /^\/quiz\/([^/]+)\/get-question-options$/.exec(path))) {
                const qId = body && body.question_id;
                const q = MOCK_QUESTIONS.find((x) => x.id === qId);
                return mockResolve({
                    data: q ? q.options : []
                });
            }

            if ((m = /^\/quiz\/([^/]+)\/submit-answer$/.exec(path))) {
                const quizId = m[1];
                const sess = ensureMockSession(quizId);
                const qId = body && body.question_id;
                const answer = body && body.answer;
                const q = MOCK_QUESTIONS.find((x) => x.id === qId);
                const isCorrect = q ? q.correct === answer : null;
                const chosenOpt = q ? q.options.find((o) => o.id === answer) : null;
                const correctOpt = q ? q.options.find((o) => o.id === q.correct) : null;
                sess.log.push({
                    question: q ? q.text : 'Question',
                    your_answer: chosenOpt ? chosenOpt.text : answer,
                    correct_answer: correctOpt ? correctOpt.text : '',
                    is_correct: isCorrect,
                });
                const curIdx = MOCK_QUESTIONS.findIndex((x) => x.id === qId);
                const nextQ = MOCK_QUESTIONS[curIdx + 1];
                return mockResolve({
                    data: {
                        is_correct: isCorrect,
                        correct_answer: q ? q.correct : null,
                        next_question_id: nextQ ? nextQ.id : null
                    }
                });
            }

            if ((m = /^\/quiz\/([^/]+)\/my-result$/.exec(path))) {
                const quizId = m[1];
                const sess = ensureMockSession(quizId);
                const correct = sess.log.filter((r) => r.is_correct).length;
                const total = sess.log.length;
                return mockResolve({
                    data: {
                        score: String(correct * 10),
                        rank: String(Math.max(1, 12 - correct)),
                        coins_earned: String(correct * 10),
                        correct_answers: String(correct),
                        total_questions: String(total)
                    }
                });
            }

            if ((m = /^\/quiz\/([^/]+)\/answer-sheet$/.exec(path))) {
                const quizId = m[1];
                const sess = ensureMockSession(quizId);
                return mockResolve({
                    data: sess.log
                });
            }

            if ((m = /^\/quiz\/([^/]+)$/.exec(path))) {
                return mockResolve({
                    data: findMockQuiz(m[1])
                });
            }

            // ---- FALLBACK -----------------------------------------------------------
            console.warn(`[MOCK API] No mock handler for ${method} ${path} — returning empty response.`);
            return mockResolve({
                data: []
            });
        }
        /* ======================== END MOCK API LAYER ============================= */


        // Unwrap common API envelope shapes: {data:...}, {result:...}, or raw payload
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

        /* =========================================================================
           SOUND ENGINE — original, synthesized quiz-show style sound effects
           (Web Audio API tones only — no external/copyrighted audio files).
           Covers: background suspense music, question-load sting, tick countdown,
           answer-select click, correct/wrong reveal, drumroll and winner fanfare.
           ========================================================================= */
        const Sound = (() => {
            const LS_MUTE = 'qx_sound_muted';
            let ctx = null;
            let master = null; // final output gain
            let sfxBus = null; // percussive/melodic stings route through here (dry + hall-echo send)
            let delay = null,
                feedback = null,
                delayWet = null;
            let bgNodes = null; // { stop() }
            let muted = localStorage.getItem(LS_MUTE) === '1';

            function ensureCtx() {
                if (!ctx) {
                    const AC = window.AudioContext || window.webkitAudioContext;
                    if (!AC) return null;
                    ctx = new AC();
                    master = ctx.createGain();
                    master.gain.value = muted ? 0 : 0.6;
                    master.connect(ctx.destination);

                    // Small hall-style echo bus — gives stings/fanfare/correct-wrong hits a
                    // "live stage" ambience instead of a dry beep. Background pad avoids
                    // this bus so it doesn't smear into mud over a long loop.
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
                return muted;
            }

            function setMuted(v) {
                muted = v;
                localStorage.setItem(LS_MUTE, v ? '1' : '0');
                if (master) master.gain.setTargetAtTime(v ? 0 : 0.6, ctx.currentTime, 0.05);
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

            function noiseBurst({
                dur = 0.25,
                gain = 0.18,
                when = 0,
                filterFreq = 1800,
                bus = null
            }) {
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

            // Warm "mallet/bell" tone — fundamental + a soft octave overtone through a
            // gentle lowpass filter. Sounds much rounder and more pleasant than a bare
            // oscillator beep; used for every short effect sound below.
            function pluck({
                freq = 440,
                dur = 0.4,
                gain = 0.15,
                when = 0,
                bus = null,
                brightness = 2600
            }) {
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

            // Ambient "floating" background pad — plays under the quiz while questions
            // are live. Slowly alternates between two soft chords every few seconds
            // (instead of a single static drone) with a gentle airy shimmer layered on
            // top, for a calmer, more spacious quiz-lounge feel.
            function startBackground() {
                return;
            }

            function stopBackground() {
                if (bgNodes) bgNodes.stop();
            }

            function isBackgroundPlaying() {
                return !!bgNodes;
            }
            // Fully silence output immediately (used when the app is closed/hidden) —
            // separate from stopBackground's fade-out, this is an instant hard-stop.
            function hardStopAll() {
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

            // A soft one-shot swell — used once when the answer countdown enters its
            // final urgent seconds. Gentle sine glide, no harsh edge.
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
                [392.00, 523.25, 659.25].forEach((f, i) => pluck({
                    freq: f,
                    dur: 0.5,
                    gain: 0.11,
                    when: i * 0.11,
                    brightness: 2400
                }));
            }

            function optionsReveal() {
                [587.33, 783.99].forEach((f, i) => pluck({
                    freq: f,
                    dur: 0.3,
                    gain: 0.06,
                    when: i * 0.07,
                    brightness: 2200
                }));
            }

            function tick(urgent = false) {
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
                // gentle chime finale, no cymbal crash
                [523.25, 659.25, 783.99, 1046.5].forEach((f, i) => pluck({
                    freq: f,
                    dur: 0.6,
                    gain: 0.11,
                    when: durationSec + i * 0.08,
                    brightness: 3400
                }));
            }

            function fanfare() {
                const melody = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.5]; // gentle major run
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

            return {
                ensureCtx,
                isMuted,
                setMuted,
                toggleMuted,
                startBackground,
                stopBackground,
                isBackgroundPlaying,
                hardStopAll,
                tension,
                questionLoad,
                optionsReveal,
                tick,
                select,
                correct,
                wrong,
                drumroll,
                fanfare,
                coin
            };
        })();

        /* -------------------------------------------------------------------------
           GLOBAL "APP CLOSED / BACKGROUNDED" SOUND GUARD
           Whenever the tab/app is hidden (user switches away, minimizes, or the
           page is being closed) any playing background quiz music is hard-stopped
           immediately, so it never keeps playing — or resumes on its own — after
           the user comes back in.
           ------------------------------------------------------------------------- */
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) Sound.hardStopAll();
        });
        window.addEventListener('pagehide', () => Sound.hardStopAll());
        window.addEventListener('beforeunload', () => Sound.hardStopAll());

        // Inline SVG "chrome" hexagon outline used behind KBC-style option buttons.
        // Stroke-only (fill="none") so the option's own background stays fully
        // transparent — the stage glow/rings behind it show straight through.
        // Colors come from the --kbc-c1..c5 CSS custom properties set on the button
        // (see .kbc-option[.selected/.correct/.wrong] rules). The gradient itself
        // continuously rotates around the hexagon (SMIL animateTransform) so the
        // border has a genuine spinning-light look, independent of button state.
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
            return `<button id="kbc-mute-toggle" class="kbc-mute-btn" title="Sound on/off">
    <span class="material-symbols-outlined text-[18px]">${Sound.isMuted() ? 'volume_off' : 'volume_up'}</span>
  </button>`;
        }

        function wireMuteBtn(root = document) {
            const btn = qs('#kbc-mute-toggle', root);
            if (!btn) return;
            btn.addEventListener('click', () => {
                Sound.ensureCtx();
                const nowMuted = Sound.toggleMuted();
                const icon = qs('.material-symbols-outlined', btn);
                if (icon) icon.textContent = nowMuted ? 'volume_off' : 'volume_up';
            });
        }

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

            const needsAuth = AUTH_REQUIRED_PREFIXES.some(p => base.startsWith(p));
            if (needsAuth && !Store.getToken()) {
                navigate('/login', true);
                return;
            }
            if (!needsAuth && Store.getToken() && (base === '/login' || base === '/otp')) {
                navigate('/home', true);
                return;
            }
            // New users must finish their profile (POST /signup) before they can reach
            // any other part of the app — force them back to /complete-profile even if
            // they try to jump elsewhere (deep link, browser back button, etc).
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
                                            <div class="w-10 h-10 overflow-hidden flex items-center justify-center">
                                              <img src="https://chaloonline.s3.ap-south-1.amazonaws.com/logo/17872105906a86ab5e9cf983.85145818.webp" alt="Logo" class="w-full h-full object-contain">
                                            </div>`}
        <h1 class="font-display text-headline-md font-bold text-primary truncate max-w-[160px]">${escapeHtml(title)}</h1>
      </div>
      <div class="flex items-center gap-2">
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
                    key: 'profile',
                    label: 'Profile',
                    icon: 'person',
                    path: '/profile'
                },
                {
                    key: 'points',
                    label: 'Points',
                    icon: 'monetization_on',
                    path: '/wallet'
                },
                {
                    key: 'leaderboard',
                    label: 'Leaderboard',
                    icon: 'leaderboard',
                    path: '/leaderboard'
                },
                {
                    key: 'redeem',
                    label: 'Redeem',
                    icon: 'redeem',
                    path: '/redeem'
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

        /* ------------------------------- SETTINGS MODAL ----------------------------- */
        function openSettings(forced = false) {
            const existing = qs('#settings-modal');
            if (existing) existing.remove();
            const modal = el(`
  <div id="settings-modal" class="fixed inset-0 z-[95] bg-black/70 backdrop-blur-sm flex items-end sm:items-center justify-center">
    <div class="w-full max-w-md glass-card bg-surface-container-highest rounded-t-2xl sm:rounded-2xl border border-glass p-6 fade-in">
      <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-5 sm:hidden"></div>
      <h3 class="font-display text-headline-md text-white mb-1">API Connection</h3>
      <p class="text-on-surface-variant text-body-md mb-4">Point the app at your FSRQuiz API. Works the same for a local server (e.g. <span class="font-label-caps text-[11px]">http://localhost:8000/api</span>) or a live domain (e.g. <span class="font-label-caps text-[11px]">https://your-domain.com/api</span>).</p>
      <label class="font-label-caps text-[11px] text-on-surface-variant">API BASE URL</label>
      <input id="settings-api-base" type="text" placeholder="https://your-domain.com/api" value="${escapeHtml(Store.getApiBase())}" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
      <div id="settings-test-result" class="mt-2 text-body-md"></div>
      <div class="flex gap-3 mt-5">
        <button id="settings-test-btn" class="flex-1 py-3 rounded-xl border border-glass text-on-surface font-action active:scale-95 transition-transform">Test Connection</button>
        <button id="settings-save-btn" class="flex-1 py-3 rounded-xl bg-primary text-on-primary font-action active:scale-95 transition-transform">Save</button>
      </div>
      ${!forced ? `<button id="settings-close-btn" class="w-full mt-3 py-2 text-on-surface-variant font-action text-action">Close</button>` : ''}
    </div>
  </div>`);
            document.body.appendChild(modal);
            qs('#settings-test-btn').onclick = async () => {
                const val = qs('#settings-api-base').value.trim().replace(/\/+$/, '');
                const resultBox = qs('#settings-test-result');
                resultBox.textContent = 'Testing...';
                resultBox.className = 'mt-2 text-body-md text-on-surface-variant';
                try {
                    const res = await fetch(val + '/categories', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (res.status < 500) {
                        resultBox.textContent = `Reachable (HTTP ${res.status}).`;
                        resultBox.className = 'mt-2 text-body-md text-mint';
                    } else {
                        resultBox.textContent = `Server responded with error ${res.status}.`;
                        resultBox.className = 'mt-2 text-body-md text-secondary';
                    }
                } catch (e) {
                    resultBox.textContent =
                        'Could not reach this URL. Check the address, CORS settings on the server, and your network.';
                    resultBox.className = 'mt-2 text-body-md text-error';
                }
            };
            qs('#settings-save-btn').onclick = () => {
                const val = qs('#settings-api-base').value.trim();
                if (!val) {
                    toast('Enter an API base URL', 'error');
                    return;
                }
                Store.setApiBase(val);
                toast('API base URL saved', 'success');
                modal.remove();
                router();
            };
            if (qs('#settings-close-btn')) qs('#settings-close-btn').onclick = () => modal.remove();
        }

        /* ==========================================================================
           AUTH VIEWS
           ========================================================================== */
        /* Alphabetically-ordered Indian States + UTs table, id = position (1-based).
           Matches the backend's state_id convention (e.g. Madhya Pradesh = 13). */
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

        route('/login', async (app) => {
            app.innerHTML = `
  <div class="min-h-screen flex flex-col justify-center px-container-margin py-10 fade-in">
    /* <button onclick="openSettings()" class="absolute top-5 right-5 w-10 h-10 rounded-full flex items-center justify-center bg-surface-glass border border-glass active:scale-90">
      <span class="material-symbols-outlined text-on-surface-variant">settings</span>
    </button> */
    <div class="flex flex-col items-center mb-10">
      <div class="w-20 h-20 mb-4 overflow-hidden flex items-center justify-center">
        <img src="https://chaloonline.s3.ap-south-1.amazonaws.com/logo/17872105906a86ab5e9cf983.85145818.webp" alt="Logo" class="w-full h-full object-contain">
      </div>
      <h1 class="font-display text-display text-primary">FSRQuiz</h1>
      <p class="text-on-surface-variant mt-1">Play. Compete. Win real rewards.</p>
    </div>
    <div class="glass-card rounded-2xl p-6">
      <h2 class="font-headline-md text-headline-md mb-1">Login / Sign up</h2>
      <p class="text-on-surface-variant text-body-md mb-5">Enter your mobile number to get an OTP.</p>
      <label class="font-label-caps text-[11px] text-on-surface-variant">MOBILE NUMBER</label>
      <div class="flex items-center gap-2 mt-1 bg-surface-container border border-glass rounded-xl px-4 py-3">
        <span class="text-on-surface-variant">+91</span>
        <input id="login-mobile" type="tel" inputmode="numeric" maxlength="10" placeholder="98765 43210" class="bg-transparent flex-1 min-w-0 text-on-surface placeholder:text-on-surface-variant/60">
      </div>
      <button id="login-send-otp" class="w-full mt-5 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Send OTP</button>
    </div>
    <p class="text-center text-on-surface-variant text-[12px] mt-6">By continuing you agree to our Terms & Privacy Policy.</p>
  </div>`;

            qs('#login-send-otp').onclick = async () => {
                const mobileInput = qs('#login-mobile').value.trim();
                if (!/^\d{10}$/.test(mobileInput)) {
                    toast('Enter a valid 10-digit mobile number', 'error');
                    return;
                }
                await withLoader(async () => {
                    try {
                        const res = await api('/auth/send-otp', {
                            method: 'POST',
                            auth: false,
                            body: {
                                mobile: mobileInput
                            }
                        });
                        const data = unwrap(res);
                        const otpToken = pick(res, ['token']) || pick(data, ['token']);
                        Store.setPendingMobile(mobileInput);
                        if (otpToken) Store.setOtpToken(otpToken);
                        // send-otp tells us upfront whether this number already has an account.
                        const isNew = pick(res, ['is_new_user']) ?? pick(data, [
                            'is_new_user'
                        ]);
                        if (isNew !== undefined && isNew !== null) Store.setIsNewUser(
                            isNew);
                        toast('OTP sent successfully', 'success');
                        navigate('/otp');
                    } catch (e) {
                        toast(e.message, 'error');
                    }
                });
            };
        });

        route('/otp', async (app) => {
            const mobile = Store.getPendingMobile();
            if (!mobile) {
                navigate('/login', true);
                return;
            }
            app.innerHTML = `
  <div class="min-h-screen flex flex-col justify-center px-container-margin py-10 fade-in">
    <div class="flex flex-col items-center mb-8">
      <div class="w-16 h-16 rounded-2xl bg-primary/20 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-primary text-3xl">sms</span>
      </div>
      <h2 class="font-headline-lg text-headline-lg">Verify OTP</h2>
      <p class="text-on-surface-variant mt-1 text-center">We've sent a 4-digit code to <span class="text-on-surface font-bold">+91 ${escapeHtml(mobile)}</span></p>
    </div>
    <div class="glass-card rounded-2xl p-6">
      <div class="flex justify-center gap-3" id="otp-boxes">
        ${[0,1,2,3].map(i => `<input maxlength="1" inputmode="numeric" data-idx="${i}" class="otp-box bg-surface-container border border-glass rounded-xl font-headline-md text-2xl">`).join('')}
      </div>
      <button id="verify-otp-btn" class="w-full mt-6 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Verify & Continue</button>
      <div class="flex justify-between items-center mt-4">
        <button onclick="navigate('/login')" class="text-on-surface-variant font-action text-action">Change number</button>
        <button id="resend-otp-btn" class="text-primary font-action text-action">Resend OTP</button>
      </div>
    </div>
  </div>`;

            const boxes = qsa('.otp-box');
            boxes[0].focus();
            boxes.forEach((b, i) => {
                b.addEventListener('input', () => {
                    b.value = b.value.replace(/\D/g, '');
                    if (b.value && boxes[i + 1]) boxes[i + 1].focus();
                });
                b.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !b.value && boxes[i - 1]) boxes[i - 1].focus();
                });
            });

            qs('#verify-otp-btn').onclick = async () => {
                const otp = boxes.map(b => b.value).join('');
                if (otp.length !== 4) {
                    toast('Enter the 4-digit OTP', 'error');
                    return;
                }
                await withLoader(async () => {
                    try {
                        const res = await api('/auth/verify-otp', {
                            method: 'POST',
                            auth: false,
                            body: {
                                mobile,
                                otp,
                                token: Store.getOtpToken()
                            }
                        });
                        const data = unwrap(res);
                        const accessToken = pick(res, ['access_token']) || pick(data, [
                            'access_token'
                        ]) || pick(res, ['token']) || pick(data, ['token']);
                        if (!accessToken) throw new Error(
                            'Login succeeded but no access token was returned by the API.'
                        );
                        Store.setToken(accessToken);
                        const userObj = data && typeof data === 'object' ? (data.user ||
                            data) : null;
                        if (userObj) Store.setUser(userObj);
                        toast('Logged in successfully', 'success');
                        // is_new_user is normally returned by /auth/send-otp (stored already);
                        // fall back to checking the verify-otp response itself if present there instead.
                        const isNewFromVerify = pick(res, ['is_new_user']) ?? pick(data, [
                            'is_new_user'
                        ]);
                        const isNew = isNewFromVerify !== undefined && isNewFromVerify !==
                            null ? isNewFromVerify : Store.getIsNewUser();
                        navigate(isNew ? '/complete-profile' : '/home', true);
                    } catch (e) {
                        toast(e.message, 'error');
                    }
                });
            };

            qs('#resend-otp-btn').onclick = async () => {
                await withLoader(async () => {
                    try {
                        await api('/auth/resend-otp', {
                            method: 'POST',
                            auth: false,
                            body: {
                                mobile,
                                token: Store.getOtpToken()
                            }
                        });
                        toast('OTP resent', 'success');
                    } catch (e) {
                        toast(e.message, 'error');
                    }
                });
            };
        });

        route('/complete-profile', async (app) => {
            const mobile = Store.getPendingMobile();
            if (!mobile) {
                navigate('/login', true);
                return;
            }
            app.innerHTML = `
  <div class="min-h-screen px-container-margin py-8 sm:py-10 fade-in">
    <div class="mb-6">
      <h2 class="font-headline-lg text-headline-lg">Complete your profile</h2>
      <p class="text-on-surface-variant mt-1">Just a few details to get you started.</p>
    </div>
    <form id="cp-form" class="glass-card rounded-2xl p-4 sm:p-6 flex flex-col gap-4">
      ${formField('name','Full Name','text','Your name', true)}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">STATE *</label>
          <select id="f-state" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
            <option value="">Select state</option>
            ${INDIAN_STATES.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('')}
          </select>
        </div>
        ${formField('city','City','text','e.g. Indore', true)}
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        ${formField('dob','Date of Birth','date','', true)}
        <div>
          <label class="font-label-caps text-[11px] text-on-surface-variant">GENDER *</label>
          <select id="f-gender" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      ${formField('referral','Referral Code (optional)','text','e.g. NAV1A8K2QX')}
      <button type="submit" class="w-full mt-2 bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Save & Continue</button>
    </form>
  </div>`;

            const nameInput = qs('#f-name');
            if (nameInput) {
                nameInput.setAttribute('pattern', '[a-zA-Z\\s]+');
                nameInput.setAttribute('title', 'Only alphabets and spaces are allowed');
                // Real-time restriction: Only alphabets and spaces can be inserted
                nameInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                });
                nameInput.addEventListener('keydown', function(e) {
                    if (e.ctrlKey || e.altKey || e.metaKey || [8, 9, 13, 27, 46, 37, 38, 39, 40].includes(e.keyCode)) {
                        return;
                    }
                    if (!/^[a-zA-Z\s]$/.test(e.key)) {
                        e.preventDefault();
                    }
                });
            }

            qs('#cp-form').onsubmit = async (e) => {
                e.preventDefault();
                const name = qs('#f-name').value.trim();
                const stateId = qs('#f-state').value;
                const city = qs('#f-city').value.trim();
                const dob = qs('#f-dob').value;
                const gender = qs('#f-gender').value;
                const referral = qs('#f-referral').value.trim();

                if (!name) {
                    toast('Enter your full name', 'error');
                    return;
                }
                if (!/^[a-zA-Z\s]+$/.test(name)) {
                    toast('Full name can only contain alphabets and spaces', 'error');
                    return;
                }
                if (!stateId) {
                    toast('Select your state', 'error');
                    return;
                }
                if (!city) {
                    toast('Enter your city', 'error');
                    return;
                }
                if (!dob) {
                    toast('Enter your date of birth', 'error');
                    return;
                }
                if (!gender) {
                    toast('Select your gender', 'error');
                    return;
                }

                const body = {
                    name,
                    mobile: Number(mobile),
                    state_id: Number(stateId),
                    city_name: city,
                    dob,
                    gender,
                };
                if (referral) body.referral_code = referral;

                await withLoader(async () => {
                    try {
                        const res = await api('/signup', {
                            method: 'POST',
                            body
                        });
                        const data = unwrap(res);
                        const accessToken = pick(res, ['access_token']) || pick(data, [
                            'access_token'
                        ]) || pick(res, ['token']) || pick(data, ['token']);
                        if (accessToken) Store.setToken(accessToken);
                        const userObj = data && typeof data === 'object' ? (data.user ||
                            data) : null;
                        if (userObj) Store.setUser(userObj);
                        Store.setIsNewUser(false);
                        toast('Profile completed', 'success');
                        navigate('/home', true);
                    } catch (e) {
                        toast(e.message, 'error');
                    }
                });
            };
        });

        function formField(key, label, type = 'text', placeholder = '', required = false) {
            return `<div>
    <label class="font-label-caps text-[11px] text-on-surface-variant">${label.toUpperCase()}${required ? ' *' : ''}</label>
    <input id="f-${key}" type="${type}" placeholder="${escapeHtml(placeholder)}" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface placeholder:text-on-surface-variant/60">
  </div>`;
        }

        /* ==========================================================================
           HOME
           ========================================================================== */
        route('/home', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'FSRQuiz', coins: '…' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="home-main">
      <section class="mt-stack-md mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg text-on-surface" id="home-welcome">Welcome back!</h2>
        <p class="text-on-surface-variant font-body-md">Ready to dominate today's challenges?</p>
      </section>
      <section class="mb-stack-lg" id="home-banner-section" style="display:none;">
        <div class="relative rounded-2xl overflow-hidden" id="home-banner-track">
          <div class="flex overflow-x-auto scrollbar-hide snap-x snap-mandatory rounded-2xl" id="home-banner-scroller"></div>
          <div class="flex justify-center gap-1.5 mt-2" id="home-banner-dots"></div>
        </div>
      </section>
      <section class="mb-stack-lg">
        <div class="flex justify-between items-center mb-stack-sm">
          <h4 class="font-headline-md text-headline-md">Active Quizzes</h4>
          <button onclick="navigate('/quiz/list')" class="font-action text-primary text-action">View All</button>
        </div>
        <div class="grid grid-cols-2 gap-gutter" id="home-quiz-grid">
          ${skeletonCard('h-40')}${skeletonCard('h-40')}
        </div>
      </section>
      <section class="mb-stack-lg">
        <div class="flex justify-between items-center mb-stack-sm">
          <h4 class="font-headline-md text-headline-md">Upcoming Quizzes</h4>
          <button onclick="navigate('/quiz/list')" class="font-action text-primary text-action">View All</button>
        </div>
        <div class="flex gap-gutter overflow-x-auto scrollbar-hide pb-1" id="home-upcoming-row">
          ${skeletonCard('h-36 w-40 shrink-0')}${skeletonCard('h-36 w-40 shrink-0')}
        </div>
      </section>
      <section class="mb-stack-lg">
        <div class="flex justify-between items-center mb-stack-sm">
          <h4 class="font-headline-md text-headline-md">Practice Quizzes</h4>
          <span class="font-label-caps text-mint text-[10px] bg-mint/10 border border-mint/30 rounded-full px-2 py-1">FREE · NO ENTRY FEE</span>
        </div>
        <div class="flex gap-gutter overflow-x-auto scrollbar-hide pb-1" id="home-practice-row">
          ${skeletonCard('h-28 w-44 shrink-0')}${skeletonCard('h-28 w-44 shrink-0')}
        </div>
      </section>
      <section class="mb-stack-lg">
        <div class="grid grid-cols-2 gap-gutter">
          <div onclick="navigate('/quiz/list')" class="glass-card rounded-xl p-5 flex flex-col items-center justify-center text-center group hover:border-primary/50 transition-all cursor-pointer active:scale-95">
            <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center mb-stack-sm"><span class="material-symbols-outlined text-primary text-3xl">public</span></div>
            <h4 class="font-headline-md text-[18px] text-on-surface">Public Quiz</h4>
            <p class="font-label-caps text-on-surface-variant text-[10px] mt-1">GLOBAL BATTLES</p>
          </div>
          <div onclick="navigate('/history')" class="glass-card rounded-xl p-5 flex flex-col items-center justify-center text-center group hover:border-secondary/50 transition-all cursor-pointer active:scale-95">
            <div class="w-14 h-14 rounded-2xl bg-secondary/20 flex items-center justify-center mb-stack-sm"><span class="material-symbols-outlined text-secondary text-3xl">history</span></div>
            <h4 class="font-headline-md text-[18px] text-on-surface">My History</h4>
            <p class="font-label-caps text-on-surface-variant text-[10px] mt-1">PAST QUIZZES</p>
          </div>
        </div>
      </section>
      <section class="mb-stack-lg">
        <div class="grid grid-cols-6 grid-rows-2 gap-3 h-48" id="home-stats">
          ${skeletonCard('h-full col-span-3 row-span-2')}
          <div class="col-span-3 row-span-1 grid gap-3">${skeletonCard('h-full')}</div>
          <div class="col-span-3 row-span-1 grid gap-3">${skeletonCard('h-full')}</div>
        </div>
      </section>
      <section>
        <div class="flex justify-between items-center mb-stack-sm">
          <h4 class="font-headline-md text-headline-md">Recent Activity</h4>
          <button onclick="navigate('/history')" class="font-action text-primary text-action">View All</button>
        </div>
        <div class="space-y-gutter" id="home-recent">
          ${skeletonCard('h-16')}${skeletonCard('h-16')}
        </div>
      </section>
    </main>
    ${bottomNav('home')}
  `;

            const user = Store.getUser();
            if (user) qs('#home-welcome').textContent = `Welcome back, ${pick(user,['name'],'Champ')}!`;

            // Wallet balance for header
            api('/user/wallet', {
                method: 'GET'
            }).then(res => {
                const d = unwrap(res);
                const bal = pick(d, ['balance', 'coins', 'wallet_balance'], '0');
                const c = qs('#header-coins');
                if (c) c.textContent = bal;
            }).catch(() => {
                const c = qs('#header-coins');
                if (c) c.textContent = '0';
            });

            // Dashboard (stats + recent activity)
            api('/user/dashboard', {
                method: 'GET'
            }).then(res => {
                const d = unwrap(res) || {};
                const rank = pick(d, ['rank', 'current_rank'], '-');
                const streak = pick(d, ['streak', 'streak_days'], '0');
                const wins = pick(d, ['total_wins', 'wins'], '0');
                qs('#home-stats').innerHTML = `
      <div class="col-span-3 row-span-2 glass-card rounded-xl p-4 flex flex-col justify-between">
        <span class="material-symbols-outlined text-mint text-2xl">trending_up</span>
        <div><p class="font-label-caps text-on-surface-variant">RANK</p><p class="font-display text-2xl text-on-surface font-bold">#${escapeHtml(rank)}</p></div>
      </div>
      <div class="col-span-3 row-span-1 glass-card rounded-xl px-4 py-3 flex items-center justify-between">
        <div><p class="font-label-caps text-on-surface-variant">STREAK</p><p class="font-headline-md text-on-surface">${escapeHtml(streak)} Days</p></div>
        <span class="material-symbols-outlined text-secondary">local_fire_department</span>
      </div>
      <div class="col-span-3 row-span-1 glass-card rounded-xl px-4 py-3 flex items-center justify-between">
        <div><p class="font-label-caps text-on-surface-variant">TOTAL</p><p class="font-headline-md text-on-surface">${escapeHtml(wins)} Wins</p></div>
        <span class="material-symbols-outlined text-primary">emoji_events</span>
      </div>`;
                const recent = d.recent_activity || d.recent || d.history || [];
                const recentList = Array.isArray(recent) ? recent : [];
                qs('#home-recent').innerHTML = recentList.length ? recentList.slice(0, 5).map(r => `
      <div class="glass-card rounded-lg p-3 flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant">quiz</span></div>
        <div class="flex-1">
          <p class="font-headline-md text-body-lg text-on-surface">${escapeHtml(pick(r,['quiz_title','title','name'],'Quiz'))}</p>
          <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(r,['status','result'],'Completed'))} • +${escapeHtml(pick(r,['coins','reward'],'0'))} Coins</p>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
      </div>`).join('') : `<p class="text-on-surface-variant text-center py-6">No recent activity yet. Join a quiz to get started!</p>`;
            }).catch(() => {
                qs('#home-stats').innerHTML =
                    `<div class="col-span-6 row-span-2 glass-card rounded-xl p-4 flex items-center justify-center text-on-surface-variant">Stats unavailable</div>`;
                qs('#home-recent').innerHTML =
                    `<p class="text-on-surface-variant text-center py-6">Could not load recent activity.</p>`;
            });

            // Active quizzes
            api('/quiz/list', {
                method: 'GET'
            }).then(res => {
                const list = asList(res);
                const grid = qs('#home-quiz-grid');
                if (!list.length) {
                    grid.innerHTML =
                        `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No active quizzes right now. Check back soon!</div>`;
                    return;
                }
                grid.innerHTML = list.slice(0, 4).map(quizCardHtml).join('');
                attachQuizCardHandlers(grid, list);
            }).catch(() => {
                qs('#home-quiz-grid').innerHTML =
                    `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">Could not load quizzes.</div>`;
            });

            // Banner section — promo/offer banners. Section stays hidden if none returned,
            // rather than showing an empty placeholder block on the home screen.
            api('/home/banners', {
                method: 'GET'
            }).then(res => {
                const banners = asList(res);
                if (!banners.length) return;
                const section = qs('#home-banner-section');
                const scroller = qs('#home-banner-scroller');
                const dots = qs('#home-banner-dots');
                scroller.innerHTML = banners.map(b => {
                    const img = pick(b, ['image', 'banner_image', 'image_url'], '');
                    const title = pick(b, ['title', 'name'], '');
                    const link = pick(b, ['link', 'action_url', 'deep_link'], '');
                    return `
      <div class="banner-slide w-full shrink-0 snap-center cursor-pointer" data-link="${escapeHtml(link)}">
        <div class="relative w-full rounded-2xl overflow-hidden ${img ? '' : 'h-36 vibrant-gradient'}">
          ${img ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(title)}" class="block w-full h-auto"><div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>` : ''}
          ${title ? `<h4 class="absolute bottom-4 left-4 right-4 font-headline-md text-white text-[16px] drop-shadow">${escapeHtml(title)}</h4>` : ''}
        </div>
      </div>`;
                }).join('');
                dots.innerHTML = banners.map((_, i) =>
                    `<span class="w-1.5 h-1.5 rounded-full ${i === 0 ? 'bg-primary' : 'bg-white/25'} banner-dot" data-i="${i}"></span>`
                ).join('');
                section.style.display = '';

                qsa('.banner-slide', scroller).forEach(slide => {
                    slide.addEventListener('click', () => {
                        const link = slide.dataset.link;
                        if (link) {
                            if (link.startsWith('/')) navigate(link);
                            else window.open(link, '_blank');
                        }
                    });
                });
                scroller.addEventListener('scroll', () => {
                    const i = Math.round(scroller.scrollLeft / scroller.clientWidth);
                    qsa('.banner-dot', dots).forEach((d, di) => {
                        d.className =
                            `w-1.5 h-1.5 rounded-full ${di === i ? 'bg-primary' : 'bg-white/25'} banner-dot`;
                    });
                });
            }).catch(() => {
                /* banner section just stays hidden on failure — non-critical */
            });

            // Upcoming quizzes — scheduled to start later; tapping opens the same quiz
            // sheet as an active quiz, which already knows how to route to the waiting
            // screen when a quiz's start time is in the future.
            api('/quiz/upcoming', {
                method: 'GET'
            }).then(res => {
                const list = asList(res);
                const row = qs('#home-upcoming-row');
                if (!list.length) {
                    row.innerHTML =
                        `<div class="glass-card rounded-xl p-4 text-center text-on-surface-variant text-[13px] w-full">No upcoming quizzes scheduled right now.</div>`;
                    return;
                }
                row.innerHTML = list.map(upcomingQuizCardHtml).join('');
                qsa('.qx-upcoming-card', row).forEach(card => {
                    card.addEventListener('click', () => openQuizSheet(card.dataset.quizId));
                });
            }).catch(() => {
                qs('#home-upcoming-row').innerHTML =
                    `<div class="glass-card rounded-xl p-4 text-center text-on-surface-variant text-[13px] w-full">Could not load upcoming quizzes.</div>`;
            });

            // Practice quizzes — free, no entry fee, no prize pool; purely for
            // skill-building. Kept visually distinct from the paid/prize quiz cards.
            api('/quiz/practice', {
                method: 'GET'
            }).then(res => {
                const list = asList(res);
                const row = qs('#home-practice-row');
                if (!list.length) {
                    row.innerHTML =
                        `<div class="glass-card rounded-xl p-4 text-center text-on-surface-variant text-[13px] w-full">No practice quizzes available right now.</div>`;
                    return;
                }
                row.innerHTML = list.map(practiceQuizCardHtml).join('');
                qsa('.qx-practice-card', row).forEach(card => {
                    card.addEventListener('click', () => navigate(
                        `/practice-quiz/play/${card.dataset.quizId}`));
                });
            }).catch(() => {
                qs('#home-practice-row').innerHTML =
                    `<div class="glass-card rounded-xl p-4 text-center text-on-surface-variant text-[13px] w-full">Could not load practice quizzes.</div>`;
            });
        });

        function upcomingQuizCardHtml(q) {
            const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
            const startTime = pick(q, ['start_time', 'start_at'], '');
            const img = pick(q, ['image', 'banner', 'thumbnail'], '');
            return `
  <div class="qx-upcoming-card w-40 shrink-0 bg-surface-glass rounded-xl border border-border-glass overflow-hidden cursor-pointer active:scale-95 transition-transform" data-quiz-id="${escapeHtml(pick(q,['id','quiz_id']))}">
    <div class="h-20 bg-surface-container flex items-center justify-center relative">
      ${img ? `<div class="absolute inset-0 bg-cover bg-center opacity-80" style="background-image:url('${escapeHtml(img)}')"></div>` : `<span class="material-symbols-outlined text-3xl text-secondary/60">event_upcoming</span>`}
      <span class="absolute top-1.5 left-1.5 bg-secondary text-on-secondary text-[9px] font-bold px-1.5 py-0.5 rounded-full">UPCOMING</span>
    </div>
    <div class="p-2.5">
      <h5 class="font-headline-md text-[12.5px] leading-tight text-on-surface line-clamp-2 mb-1">${title}</h5>
      ${startTime ? `<p class="font-label-caps text-[10px] text-secondary flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">schedule</span>${escapeHtml(startTime)}</p>` : ''}
    </div>
  </div>`;
        }

        function practiceQuizCardHtml(q) {
            const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
            const difficulty = pick(q, ['difficulty', 'level'], '');
            const questionCount = pick(q, ['question_count', 'total_questions'], '');
            return `
  <div class="qx-practice-card w-44 shrink-0 glass-card rounded-xl p-3.5 cursor-pointer active:scale-95 transition-transform hover:border-mint/50" data-quiz-id="${escapeHtml(pick(q,['id','quiz_id']))}">
    <div class="flex items-center justify-between mb-2">
      <div class="w-9 h-9 rounded-lg bg-mint/15 flex items-center justify-center"><span class="material-symbols-outlined text-mint text-[18px]">school</span></div>
      <span class="font-label-caps text-[9px] text-mint bg-mint/10 border border-mint/25 rounded-full px-1.5 py-0.5">FREE</span>
    </div>
    <h5 class="font-headline-md text-[12.5px] leading-tight text-on-surface line-clamp-2 mb-1">${title}</h5>
    <p class="font-body-md text-[10.5px] text-on-surface-variant">${[difficulty, questionCount ? `${escapeHtml(questionCount)} Qs` : ''].filter(Boolean).map(escapeHtml).join(' · ')}</p>
  </div>`;
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

        function parseQuizStartTime(timeStr) {
            if (!timeStr) return null;
            const str = String(timeStr).trim();
            const parsedDate = new Date(str.includes(' ') && !str.includes('T') ? str.replace(' ', 'T') : str);
            if (!isNaN(parsedDate.getTime())) return parsedDate;

            const m = /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/.exec(str);
            if (!m) return null;
            const now = new Date();
            return new Date(now.getFullYear(), now.getMonth(), now.getDate(), Number(m[1]), Number(m[2]), Number(m[3] || 0),
                0);
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

        /* After a successful join, check pending_time and set counter exactly according to pending_time */
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

        /* ------------------------------ QUIZ DETAIL SHEET --------------------------- */
        async function openQuizSheet(quizId) {
            const existing = qs('#quiz-sheet-root');
            if (existing) existing.remove();
            const root = el(`
  <div id="quiz-sheet-root">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50" id="qsheet-overlay"></div>
    <div class="fixed bottom-0 w-full z-50 bg-surface-container-highest rounded-t-2xl border-t border-glass max-w-md mx-auto left-1/2 -translate-x-1/2" id="qsheet">
      <div class="p-container-margin pb-10">
        <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-6"></div>
        <div id="qsheet-body" class="min-h-[200px] flex items-center justify-center"><div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full spin"></div></div>
      </div>
    </div>
  </div>`);
            document.body.appendChild(root);
            qs('#qsheet-overlay').onclick = () => root.remove();

            try {
                const res = await api(`/quiz/${quizId}`, {
                    method: 'GET'
                });
                const q = unwrap(res) || {};
                const title = escapeHtml(pick(q, ['title', 'name', 'quiz_title'], 'Quiz'));
                const img = pick(q, ['image', 'banner', 'thumbnail'], '');
                const prize = pick(q, ['prize_pool', 'prize', 'total_prize'], '0');
                const fee = pick(q, ['entry_fee', 'fee'], '0');
                const joined = pick(q, ['joined_count', 'players', 'participants_count'], '0');
                const desc = pick(q, ['description', 'rules'], '');
                const needsPassword = !!pick(q, ['is_private', 'private']);
                const alreadyPlayed = isAlreadyPlayedFlag(q);
                const isJoined = !!pick(q, ['is_joined', 'joined', 'user_joined']) || !!q.is_joined;
                const startTimeStr = pick(q, ['start_time', 'availability_start', 'available_start']);
                const pendingTimeStr = pick(res, ['pending_time']) || pick(q, ['pending_time']);

                if (!alreadyPlayed && isJoined) {
                    root.remove();
                    goToQuizOrWait(quizId, startTimeStr, pendingTimeStr);
                    return;
                }

                try {
                    sessionStorage.setItem('qx_prize_' + quizId, String(prize));
                } catch (e) {}
                qs('#qsheet-body').outerHTML = `
      <div id="qsheet-body">
        <div class="w-full h-48 rounded-xl overflow-hidden mb-4 border border-glass bg-surface-container flex items-center justify-center relative">
          ${img ? `<img alt="Quiz" class="w-full h-full object-cover" src="${escapeHtml(img)}">` : `<span class="material-symbols-outlined text-6xl text-primary/40">quiz</span>`}
          ${alreadyPlayed ? `<span class="absolute top-2 left-2 bg-mint text-surface-container-lowest text-[10px] font-bold px-2 py-0.5 rounded-full">ALREADY PLAYED</span>` : isJoined ? `<span class="absolute top-2 left-2 bg-primary text-on-primary text-[10px] font-bold px-2 py-0.5 rounded-full">JOINED</span>` : ''}
        </div>
        <div class="mb-6">
          <h3 class="font-display text-headline-lg text-white mb-2">${title}</h3>
          ${desc ? `<p class="text-on-surface-variant text-body-md mb-3">${escapeHtml(desc)}</p>` : ''}
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-surface-glass p-3 rounded-xl border border-glass text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">PRIZE POOL</p><p class="text-tertiary font-bold">${escapeHtml(prize)}</p></div>
            <div class="bg-surface-glass p-3 rounded-xl border border-glass text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">ENTRY FEE</p><p class="text-mint font-bold">${escapeHtml(fee)}</p></div>
            <div class="bg-surface-glass p-3 rounded-xl border border-glass text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">JOINED</p><p class="text-primary font-bold">${escapeHtml(joined)}</p></div>
          </div>
          ${(!alreadyPlayed && !isJoined && needsPassword) ? `<input id="qsheet-password" placeholder="Enter quiz password" class="mt-4 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">` : ''}
        </div>
        ${alreadyPlayed
          ? `<button id="qsheet-leaderboard-btn" class="w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">View Leaderboard</button>`
          : `<button id="qsheet-join-btn" class="w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform">Join Quiz Now</button>`}
        <button id="qsheet-cancel-btn" class="w-full mt-3 py-2 text-on-surface-variant font-action text-action">Cancel</button>
      </div>`;
                qs('#qsheet-cancel-btn').onclick = () => root.remove();

                if (alreadyPlayed) {
                    qs('#qsheet-leaderboard-btn').onclick = () => {
                        root.remove();
                        navigate(`/quiz/winners/${quizId}`);
                    };
                    return;
                }

                qs('#qsheet-join-btn').onclick = async () => {
                    const password = needsPassword ? (qs('#qsheet-password')?.value || null) : null;
                    await withLoader(async () => {
                        try {
                            const joinRes = await api(`/quiz/${quizId}/join`, {
                                method: 'POST',
                                body: {
                                    password
                                }
                            });
                            const joinData = unwrap(joinRes);
                            const startTimeStr = pick(joinRes, ['start_time']) || pick(joinData, [
                                'start_time'
                            ]);
                            const pendingTimeStr = pick(joinRes, ['pending_time']) || pick(joinData,
                                [
                                    'pending_time'
                                ]);
                            if (joinRes && joinRes.success === false) {
                                if ((joinRes.message || '').toLowerCase().includes('limit') || (joinRes.message || '').toLowerCase().includes('player')) {
                                    toast(joinRes.message || 'Player limit reached for this quiz! 👥', 'warning');
                                    return;
                                }
                                toast(joinRes.message || 'Failed to join quiz', 'error');
                                return;
                            }
                            toast(pick(joinRes, ['message']) || pick(joinData, ['message']) ||
                                'Joined quiz!', 'success');
                            root.remove();
                            goToQuizOrWait(quizId, startTimeStr, pendingTimeStr);
                        } catch (e) {
                            if (isAlreadyPlayedError(e)) {
                                toast('You have already played this quiz. Showing the leaderboard…',
                                    'info');
                                root.remove();
                                navigate(`/quiz/winners/${quizId}`);
                            } else {
                                toast(e.message, 'error');
                            }
                        }
                    });
                };
            } catch (e) {
                qs('#qsheet-body').innerHTML = `<p class="text-error text-center py-8">${escapeHtml(e.message)}</p>`;
            }
        }

        /* ==========================================================================
           QUIZ WAITING ROOM — shown between joining and the quiz's actual start_time
           ========================================================================== */
        route('/quiz/waiting/:id', async (app, params) => {
            const quizId = params.id;
            const startKey = `qx_quiz_start_${quizId}`;
            const warmupKey = `qx_quiz_warmup_${quizId}`;

            let scheduledTarget = localStorage.getItem(startKey) ? Number(localStorage.getItem(startKey)) :
                null;
            let warmupTarget = localStorage.getItem(warmupKey) ? Number(localStorage.getItem(warmupKey)) : null;

            if (!warmupTarget) {
                try {
                    const res = await api(`/quiz/${quizId}`, {
                        method: 'GET'
                    });
                    const q = unwrap(res) || {};
                    const pt = pick(res, ['pending_time']) || pick(q, ['pending_time']);
                    const st = pick(res, ['start_time']) || pick(q, ['start_time', 'availability_start',
                        'available_start'
                    ]);
                    const pendingSec = parsePendingTimeSeconds(pt);
                    const now = Date.now();
                    if (pendingSec > 0) {
                        scheduledTarget = now + (pendingSec * 1000);
                        warmupTarget = scheduledTarget + (20 * 1000);
                    } else {
                        const parsed = parseQuizStartTime(st);
                        if (parsed && parsed.getTime() > now) {
                            warmupTarget = parsed.getTime();
                            scheduledTarget = Math.max(now, warmupTarget - (20 * 1000));
                        }
                    }
                    if (warmupTarget > now) {
                        localStorage.setItem(startKey, String(scheduledTarget));
                        localStorage.setItem(warmupKey, String(warmupTarget));
                    }
                } catch (e) {}
            }

            if (!warmupTarget || warmupTarget <= Date.now()) {
                localStorage.removeItem(startKey);
                localStorage.removeItem(warmupKey);
                navigate(`/quiz/play/${quizId}`, true);
                return;
            }

            app.innerHTML = `
    <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col items-center justify-center text-center kbc-stage">
      <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
      <div class="relative z-[1] flex flex-col items-center w-full">
        <div class="w-20 h-20 rounded-3xl bg-primary/20 flex items-center justify-center mb-6">
          <span class="material-symbols-outlined text-primary text-4xl" id="waiting-icon">hourglass_top</span>
        </div>
        <h2 class="font-headline-lg text-headline-lg mb-2" id="waiting-title">Get Ready!</h2>
        <p class="text-on-surface-variant mb-8 px-4" id="waiting-subtitle">You're in. This quiz will start automatically in</p>
        <div id="waiting-countdown" class="font-display text-display text-primary tabular-nums mb-10" style="font-variant-numeric: tabular-nums;">--:--:--</div>
        <button onclick="navigate('/quiz/list')" class="text-on-surface-variant font-action text-action">Back to Quizzes</button>
      </div>
    </main>`;

            function fmt2(n) {
                return String(n).padStart(2, '0');
            }

            function render() {
                const now = Date.now();
                const schedMs = scheduledTarget ? scheduledTarget - now : 0;

                if (schedMs > 0) {
                    const titleEl = qs('#waiting-title');
                    const subEl = qs('#waiting-subtitle');
                    const iconEl = qs('#waiting-icon');
                    if (titleEl) titleEl.textContent = 'Get Ready!';
                    if (subEl) subEl.textContent = "You're in. This quiz will start automatically in";
                    if (iconEl) iconEl.textContent = 'hourglass_top';

                    const totalSec = Math.floor(schedMs / 1000);
                    const h = Math.floor(totalSec / 3600);
                    const m = Math.floor((totalSec % 3600) / 60);
                    const s = totalSec % 60;
                    const box = qs('#waiting-countdown');
                    if (box) box.textContent = h > 0 ? `${fmt2(h)}:${fmt2(m)}:${fmt2(s)}` :
                        `${fmt2(m)}:${fmt2(s)}`;
                    return;
                }

                const warmupMs = warmupTarget ? warmupTarget - now : 0;
                if (warmupMs > 0) {
                    const titleEl = qs('#waiting-title');
                    const subEl = qs('#waiting-subtitle');
                    const iconEl = qs('#waiting-icon');
                    if (titleEl) titleEl.textContent = 'Quiz Starting Soon!';
                    if (subEl) subEl.textContent = 'Get ready! The first question will appear in';
                    if (iconEl) iconEl.textContent = 'bolt';

                    const totalSec = Math.floor(warmupMs / 1000);
                    const m = Math.floor(totalSec / 60);
                    const s = totalSec % 60;
                    const box = qs('#waiting-countdown');
                    if (box) box.textContent = `${fmt2(m)}:${fmt2(s)}`;
                    return;
                }

                clearInterval(timer);
                window.removeEventListener('hashchange', stopOnNav);
                localStorage.removeItem(startKey);
                localStorage.removeItem(warmupKey);
                navigate(`/quiz/play/${quizId}`, true);
            }
            render();
            const timer = setInterval(render, 1000);

            function stopOnNav() {
                if (currentPath() !== `/quiz/waiting/${quizId}`) {
                    clearInterval(timer);
                    window.removeEventListener('hashchange', stopOnNav);
                }
            }
            window.addEventListener('hashchange', stopOnNav);
        });

        /* ==========================================================================
           QUIZ LIST (with category filter)
           ========================================================================== */
        route('/quiz/list', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Quizzes', showBack: true })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="flex gap-2 overflow-x-auto scrollbar-hide mb-stack-lg pb-1" id="quiz-cat-filters">
        <button data-cat="" class="cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-primary text-on-primary">All</button>
      </div>
      <div class="grid grid-cols-2 gap-gutter" id="quiz-list-grid">
        ${skeletonCard('h-40')}${skeletonCard('h-40')}${skeletonCard('h-40')}${skeletonCard('h-40')}
      </div>
    </main>
    ${bottomNav('')}
  `;

            let allQuizzes = [];
            async function loadQuizzes(categoryId) {
                const grid = qs('#quiz-list-grid');
                grid.innerHTML = skeletonCard('h-40') + skeletonCard('h-40') + skeletonCard('h-40') +
                    skeletonCard('h-40');
                try {
                    const res = await api('/quiz/list', {
                        method: 'GET',
                        query: {
                            category_id: categoryId || undefined
                        }
                    });
                    const list = asList(res);
                    allQuizzes = list;
                    grid.innerHTML = list.length ? list.map(quizCardHtml).join('') :
                        `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No quizzes found in this category.</div>`;
                    attachQuizCardHandlers(grid, list);
                } catch (e) {
                    grid.innerHTML =
                        `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-error">${escapeHtml(e.message)}</div>`;
                }
            }

            api('/categories', {
                method: 'GET'
            }).then(res => {
                const cats = asList(res);
                const wrap = qs('#quiz-cat-filters');
                cats.forEach(c => {
                    const btn = el(
                        `<button data-cat="${escapeHtml(pick(c,['id','category_id']))}" class="cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-surface-container-high text-on-surface-variant border border-glass">${escapeHtml(pick(c,['name','title'],'Category'))}</button>`
                    );
                    wrap.appendChild(btn);
                });
                qsa('.cat-chip', wrap).forEach(btn => {
                    btn.addEventListener('click', () => {
                        qsa('.cat-chip', wrap).forEach(b => b.className =
                            'cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-surface-container-high text-on-surface-variant border border-glass'
                        );
                        btn.className =
                            'cat-chip whitespace-nowrap px-4 py-2 rounded-full font-action text-action bg-primary text-on-primary';
                        loadQuizzes(btn.dataset.cat);
                    });
                });
            }).catch(() => {});

            loadQuizzes('');
        });

        /* ==========================================================================
           QUIZ PLAY
           ========================================================================== */
        route('/quiz/play/:id', async (app, params) => {
            const quizId = params.id;
            app.innerHTML = `
    <main class="min-h-screen px-container-margin pt-8 pb-8 flex flex-col kbc-stage">
      <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
      <div id="quiz-play-root" class="flex-1 flex flex-col items-center justify-center relative z-[1]">
        <div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div>
        <p class="text-on-surface-variant mt-4">Loading quiz...</p>
      </div>
    </main>`;

            Sound.ensureCtx();
            let bgStopped = false;

            function stopBg() {
                if (!bgStopped) {
                    bgStopped = true;
                    Sound.stopBackground();
                }
            }

            // Tracks whether this route instance is still the one on screen. Without
            // this, pressing back mid-quiz doesn't stop the question/answer timers —
            // they keep running in the background, silently auto-submitting answers
            // and advancing questions, so the quiz looks like it's "playing itself"
            // (or already finished) if the user comes back to it.
            let routeActive = true;

            function onLeaveQuizRoute() {
                if (!currentPath().startsWith('/quiz/play/')) {
                    routeActive = false;
                    clearAnswerTimer();
                    stopBg();
                    window.removeEventListener('hashchange', onLeaveQuizRoute);
                }
            }
            window.addEventListener('hashchange', onLeaveQuizRoute);

            // Local review log — collected as the user answers, used by the review screen.
            const reviewLog = [];

            let questionNumber = 0;
            let totalQuestions = null; // filled in if the API tells us
            let showTime = null; // Date when question+options were shown to the user
            let lifelineState = null;
            let currentQuestionObj = null;

            function renderLifelinesBarHtml() {
                if (!lifelineState || lifelineState.has_used_any) return '';
                const show5050 = lifelineState.can_use_fifty_fifty;
                const showSwitch = lifelineState.can_use_switch_option;
                if (!show5050 && !showSwitch) return '';

                return `
    <div id="qplay-lifelines-bar" class="flex items-center justify-center gap-3 mb-4">
      ${show5050 ? `
                                        <button id="btn-lifeline-5050" class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-amber-500 to-yellow-400 text-black shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer" onclick="triggerLifeline('fifty_fifty')">
                                          <span>⚡</span>
                                          <span>50:50</span>
                                        </button>
                                      ` : ''}
      ${showSwitch ? `
                                        <button id="btn-lifeline-switch" class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-gradient-to-r from-purple-600 to-indigo-500 text-white shadow-lg hover:scale-105 active:scale-95 flex items-center gap-1.5 cursor-pointer" onclick="triggerLifeline('switch_option')">
                                          <span class="material-symbols-outlined text-[16px]">sync</span>
                                          <span>Switch Q</span>
                                        </button>
                                      ` : ''}
    </div>`;
            }

            window.triggerLifeline = async (type) => {
                if (!currentQuestionObj || !currentQuestionObj.id || (lifelineState && lifelineState
                        .has_used_any)) return;

                const btn = qs(`#btn-lifeline-${type === 'fifty_fifty' ? '5050' : 'switch'}`);
                if (btn) btn.disabled = true;

                try {
                    const res = await api(`/quiz/${quizId}/use-lifeline`, {
                        method: 'POST',
                        body: {
                            lifeline: type,
                            question_id: currentQuestionObj.id
                        }
                    });

                    const data = unwrap(res) || res || {};

                    if (!data.success) {
                        if (typeof toast === 'function') toast(data.message || 'Could not use lifeline',
                            'error');
                        else alert(data.message || 'Could not use lifeline');
                        if (btn) btn.disabled = false;
                        return;
                    }

                    if (data.lifelines) lifelineState = data.lifelines;

                    const bar = qs('#qplay-lifelines-bar');
                    if (bar) bar.style.display = 'none';

                    if (type === 'fifty_fifty') {
                        Sound.select();
                        if (typeof toast === 'function') toast('50:50 Lifeline applied!', 'success');
                        const removed = (data.removed_options || []).map(x => String(x).trim().toLowerCase());
                        const removedIdx = (data.removed_indices || []).map(Number);

                        const optBtns = qsa('.option-btn', qs('#qplay-options'));
                        optBtns.forEach((b, i) => {
                            const val = String(b.dataset.val || '').trim().toLowerCase();
                            const text = String(b.dataset.text || '').trim().toLowerCase();
                            const isValMatch = removed.length > 0 && (removed.includes(val) || removed.includes(text));
                            const isIdxMatch = removedIdx.includes(i);

                            if (isValMatch || isIdxMatch) {
                                b.classList.add('opacity-0', 'pointer-events-none');
                                b.style.visibility = 'hidden';
                                const wrapper = b.closest('.kbc-opt-glow') || b.parentElement;
                                if (wrapper) {
                                    wrapper.style.visibility = 'hidden';
                                    wrapper.style.opacity = '0';
                                    wrapper.classList.add('pointer-events-none');
                                }
                            }
                        });
                    } else if (type === 'switch_option') {
                        Sound.select();
                        if (typeof toast === 'function') toast('Switch Question Lifeline applied!',
                            'success');
                        if (data.new_question) {
                            const nq = data.new_question;
                            currentQuestionObj = {
                                id: nq.id || nq.question_id,
                                text: nq.question_text || nq.text || 'Question'
                            };

                            const qTitleEl = qs('#quiz-play-root h3');
                            if (qTitleEl) qTitleEl.textContent = currentQuestionObj.text;

                            if (typeof renderOptions === 'function' && nq.options) {
                                renderOptions(nq.options);
                            }
                        }
                    }
                } catch (err) {
                    if (typeof toast === 'function') toast(err.message || 'Lifeline error', 'error');
                    else alert(err.message || 'Lifeline error');
                    if (btn) btn.disabled = false;
                }
            };

            function fmtTime(d) {
                const pad = (n) => String(n).padStart(2, '0');
                return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            }

            // Pull a question_id / question text out of whatever shape the API returns
            function extractQuestion(raw) {
                const d = unwrap(raw);
                if (!d) return null;
                const q = d.question && typeof d.question === 'object' ? d.question : d;
                const qId = pick(q, ['id', 'question_id']);
                if (!qId) return null; // no question => quiz is finished
                if (pick(d, ['finished', 'completed', 'is_completed', 'quiz_completed'])) return null;
                totalQuestions = pick(d, ['total_questions', 'total'], totalQuestions) || totalQuestions;
                return {
                    id: qId,
                    text: pick(q, ['question', 'question_text', 'title'], 'Question')
                };
            }

            function extractOptions(raw) {
                const d = unwrap(raw);
                if (Array.isArray(d)) return d;
                if (d && Array.isArray(d.options)) return d.options;
                if (d && d.question && Array.isArray(d.question.options)) return d.question.options;
                return asList(raw);
            }

            async function loadFirstQuestion() {
                const res = await api(`/quiz/${quizId}/start`, {
                    method: 'GET'
                });
                const unwrapped = unwrap(res) || res || {};
                if (unwrapped.lifelines) lifelineState = unwrapped.lifelines;
                return extractQuestion(res);
            }

            async function loadNextQuestion(nextQuestionId) {
                const res = await api(`/quiz/${quizId}/get-question`, {
                    method: 'POST',
                    body: {
                        last_question_id: String(nextQuestionId)
                    }
                });
                const unwrapped = unwrap(res) || res || {};
                if (unwrapped.lifelines) lifelineState = unwrapped.lifelines;
                return extractQuestion(res);
            }

            function sleep(ms) {
                return new Promise((r) => setTimeout(r, ms));
            }

            async function loadOptions(questionId) {
                const res = await api(`/quiz/${quizId}/get-question-options`, {
                    method: 'POST',
                    body: {
                        question_id: questionId
                    }
                });
                const unwrapped = unwrap(res) || res || {};
                if (unwrapped.lifelines) lifelineState = unwrapped.lifelines;
                return extractOptions(res);
            }

            async function submitAnswer(questionId, answer) {
                const ansTime = new Date();
                const res = await api(`/quiz/${quizId}/submit-answer`, {
                    method: 'POST',
                    body: {
                        question_id: questionId,
                        answer,
                        show_time: showTime ? fmtTime(showTime) : fmtTime(ansTime),
                        ans_time: fmtTime(ansTime),
                    },
                });
                console.log('[submit-answer] raw response:', res);
                return res;
            }

            const LOAD_SECONDS = 5; // question shown / options fetched during this window
            const ANSWER_SECONDS = 10; // time allowed to pick an answer once options appear
            let answerTimerHandle = null;

            function clearAnswerTimer() {
                if (answerTimerHandle) {
                    clearInterval(answerTimerHandle);
                    answerTimerHandle = null;
                }
            }

            async function renderQuestion(question) {
                if (!routeActive) return;
                currentQuestionObj = question;
                questionNumber++;
                clearAnswerTimer();

                // Question reveal sound — plays as soon as the new question hits the screen
                Sound.questionLoad();

                // Step 1 — show the question right away; options area shows a loader for ~5s
                let sessionPrize = '0';
                try {
                    sessionPrize = sessionStorage.getItem('qx_prize_' + quizId) || '0';
                } catch (e) {}

                qs('#quiz-play-root').outerHTML = `
    <div id="quiz-play-root" class="w-full kbc-card rounded-2xl p-4 fade-in">
      <div class="flex items-center justify-between mb-4">
        <button onclick="if(confirm('Leave this quiz? Your progress may be lost.')) { window.__quizLeave && window.__quizLeave(); }" class="kbc-icon-badge"><span class="material-symbols-outlined text-[18px]">close</span></button>
        <span class="font-label-caps kbc-title-glow text-[12px] tracking-wide">FSRQuiz</span>
        <div id="qplay-mute-slot" class="kbc-icon-badge"></div>
      </div>
      ${Number(String(sessionPrize).replace(/[^0-9.]/g,'')) > 0 ? `
                                          <div class="flex justify-center mb-5">
                                            <div class="kbc-prize-pill"><span>💎</span><span>₹ ${escapeHtml(sessionPrize)}</span><span>💎</span></div>
                                          </div>` : ''}
      <div class="kbc-dial-wrap" id="qplay-dial-wrap">
        <svg viewBox="0 0 100 100">
          <defs>
            <linearGradient id="kbcDialGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="var(--kbc-teal)"/>
              <stop offset="100%" stop-color="var(--kbc-mint)"/>
            </linearGradient>
          </defs>
          <circle class="kbc-dial-track" cx="50" cy="50" r="44" fill="none" stroke-width="7"/>
          <circle class="kbc-dial-fill" id="qplay-dial-fill" cx="50" cy="50" r="44" fill="none" stroke-width="7" stroke-dasharray="276.5" stroke-dashoffset="0"/>
        </svg>
        <div class="kbc-dial-number" id="qplay-dial-number">–</div>
      </div>
      <div class="kbc-q-caption">Question ${questionNumber}${totalQuestions ? ' / ' + totalQuestions : ''}</div>
      ${renderLifelinesBarHtml()}
      <div class="kbc-glow-border mb-6">
        <div class="glass-card kbc-card kbc-glow-inner rounded-2xl p-6">
          <h3 class="font-headline-lg text-headline-lg kbc-title-glow">${escapeHtml(question.text)}</h3>
        </div>
      </div>
      <div class="flex flex-col gap-3" id="qplay-options">
        <div class="flex flex-col items-center py-8 text-on-surface-variant">
          <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full spin mb-3"></div>
          <p class="font-body-sm">Loading options...</p>
        </div>
      </div>
    </div>`;
                qs('#qplay-mute-slot').outerHTML = kbcMuteBtnHtml().replace('kbc-mute-btn',
                    'kbc-icon-badge');
                wireMuteBtn();

                // Step 2 — fetch options, but hold the question on screen for at least LOAD_SECONDS
                let optionsErrorMsg = '';
                const fetchP = loadOptions(question.id).catch((e) => {
                    optionsErrorMsg = e.message || 'Unknown error while loading options.';
                    toast('Could not load options: ' + optionsErrorMsg, 'error');
                    return [];
                });
                const [options] = await Promise.all([fetchP, sleep(LOAD_SECONDS * 1000)]);
                if (!options.length && !optionsErrorMsg) {
                    optionsErrorMsg =
                        'Server returned no options for this question (empty or unrecognized response shape).';
                }

                // Bail out if the user already navigated away while we were waiting
                if (!routeActive || !qs('#qplay-options')) return;

                const optWrap = qs('#qplay-options');
                if (!options.length) {
                    optWrap.innerHTML = `<div class="glass-card rounded-xl p-4 text-center text-on-surface-variant border border-error/40">
        <p>Could not load options for this question.</p>
        <p class="mt-2 text-[12px] text-error font-mono break-all">${escapeHtml(optionsErrorMsg || 'Unknown error')}</p>
        <button id="qplay-retry-options" class="mt-3 text-primary font-action text-action">Retry</button>
      </div>`;
                    qs('#qplay-retry-options').onclick = () => renderQuestion(question);
                    return; // no options => no timer, nothing to auto-submit
                }

                // Options reveal sound
                Sound.optionsReveal();

                const OPT_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];
                optWrap.innerHTML = options.map((opt, i) => {
                    const optText = typeof opt === 'string' ? opt : pick(opt, ['text', 'option',
                        'option_text', 'label'
                    ], `Option ${i+1}`);
                    const optVal = typeof opt === 'string' ? opt : pick(opt, ['id', 'option_id',
                        'value'
                    ], optText);
                    return `<div class="kbc-opt-glow">
        <button data-val="${escapeHtml(optVal)}" data-text="${escapeHtml(optText)}" class="option-btn kbc-option kbc-option-flat active:scale-[0.98]">
          <span class="kbc-opt-letter-flat">${OPT_LETTERS[i] || i+1}:</span>
          <span class="font-body-lg text-on-surface">${escapeHtml(optText)}</span>
        </button>
      </div>`;
                }).join('');

                showTime = new Date(); // answer clock starts now that options are visible

                let answered = false;

                async function handleAnswer(val, btnEl) {
                    if (!routeActive || answered) return;
                    answered = true;
                    clearAnswerTimer();
                    Sound.select();
                    const dialNum = qs('#qplay-dial-number');
                    if (dialNum) dialNum.textContent = '✓';
                    const dialWrap = qs('#qplay-dial-wrap');
                    if (dialWrap) dialWrap.classList.remove('urgent');
                    qsa('.option-btn', optWrap).forEach((b) => {
                        b.disabled = true;
                        b.classList.add('opacity-60', 'pointer-events-none');
                    });
                    if (btnEl) btnEl.classList.add('selected');
                    try {
                        const submitRes = await submitAnswer(question.id, val);
                        if (!routeActive) return; // navigated away while the request was in flight
                        const submitData = unwrap(submitRes) || submitRes || {};

                        // Best-effort: if the API tells us whether the answer was correct
                        // (and/or what the correct answer was), reveal it KBC-style with sound.
                        const rawIsCorrect = pick(submitData, ['is_correct', 'isCorrect', 'correct',
                            'answer_correct'
                        ], null);
                        const correctVal = pick(submitData, ['correct_answer', 'correct_option',
                            'correct_option_id', 'correctAnswer', 'answer'
                        ], null);
                        let isCorrect = null;
                        if (rawIsCorrect !== null) isCorrect = (rawIsCorrect === true ||
                            rawIsCorrect === 1 || rawIsCorrect === '1' || rawIsCorrect === 'true');

                        if (isCorrect !== null && optWrap) {
                            if (btnEl) btnEl.classList.add('selected');
                        }

                        // Log this question for the end-of-quiz review screen.
                        reviewLog.push({
                            text: question.text,
                            chosenText: btnEl ? btnEl.dataset.text : val,
                            isCorrect,
                            correctText: (() => {
                                if (!correctVal) return null;
                                const cb = qsa('.option-btn', optWrap).find(b => b
                                    .dataset.val === String(correctVal) || b.dataset
                                    .text === String(correctVal));
                                return cb ? cb.dataset.text : String(correctVal);
                            })(),
                        });

                        // Brief KBC-style pause on the reveal before moving on, only when we
                        // actually have something to show; otherwise proceed immediately.
                        if (isCorrect !== null) await sleep(900);
                        if (!routeActive) return;

                        const nextQuestionId = pick(submitData, ['next_question_id', 'nextQuestionId',
                            'next_id'
                        ], null);
                        if (!nextQuestionId) {
                            finishQuiz();
                            return;
                        }
                        const next = await loadNextQuestion(nextQuestionId);
                        if (!routeActive) return;
                        if (next) {
                            renderQuestion(next);
                        } else {
                            finishQuiz();
                        }
                    } catch (e) {
                        toast(e.message, 'error');
                        answered = false;
                        qsa('.option-btn', optWrap).forEach((b) => {
                            b.disabled = false;
                            b.classList.remove('opacity-60', 'pointer-events-none');
                        });
                    }
                }

                qsa('.option-btn', optWrap).forEach((btn) => {
                    btn.addEventListener('click', () => handleAnswer(btn.dataset.val, btn));
                });

                // Step 3 — answer countdown: auto-pick the first option if time runs out.
                // Drives the circular dial: ring empties (stroke-dashoffset grows) as
                // secondsLeft counts down, number in the center mirrors it.
                const DIAL_CIRCUMFERENCE = 276.5; // 2 * PI * r(44), matches the SVG circle above
                let secondsLeft = ANSWER_SECONDS;

                function paintDial(s) {
                    const fill = qs('#qplay-dial-fill');
                    const num = qs('#qplay-dial-number');
                    const wrap = qs('#qplay-dial-wrap');
                    if (fill) fill.style.strokeDashoffset = String(DIAL_CIRCUMFERENCE * (1 - Math.max(s,
                        0) / ANSWER_SECONDS));
                    if (num) num.textContent = String(Math.max(s, 0));
                    if (wrap) wrap.classList.toggle('urgent', s <= 5 && s > 0);
                }
                paintDial(secondsLeft);
                answerTimerHandle = setInterval(() => {
                    secondsLeft--;
                    paintDial(secondsLeft);
                    if (secondsLeft === 5) Sound.tension();
                    if (secondsLeft > 0) Sound.tick(secondsLeft <= 5);
                    if (secondsLeft <= 0) {
                        clearAnswerTimer();
                        if (!answered) {
                            const firstBtn = qs('.option-btn', optWrap);
                            if (firstBtn) handleAnswer(firstBtn.dataset.val, firstBtn);
                        }
                    }
                }, 1000);
            }

            function finishQuiz() {
                stopBg();
                window.removeEventListener('hashchange', stopBgOnNav);
                qs('#quiz-play-root').innerHTML =
                    `<div class="flex flex-col items-center justify-center py-24"><div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div><p class="text-on-surface-variant mt-4">Finishing up...</p></div>`;
                toast('Quiz completed!', 'success');
                try {
                    sessionStorage.setItem('qx_review_' + quizId, JSON.stringify(reviewLog));
                } catch (e) {}
                navigate(`/quiz/review/${quizId}`, true);
            }

            window.__quizLeave = async () => {
                clearAnswerTimer();
                stopBg();
                window.removeEventListener('hashchange', stopBgOnNav);
                try {
                    await api(`/quiz/${quizId}/leave`, {
                        method: 'POST'
                    });
                } catch (e) {}
                navigate('/home', true);
            };

            try {
                // Right at start_time there can be a few seconds of clock drift between
                // this device and the server, so the very first /start call may come back
                // with no question yet. Retry with short pauses before giving up — same
                // /start API, just a short grace window instead of a hard failure.
                let firstQuestion = null;
                let lastError = null;
                const maxAttempts = 6;
                for (let attempt = 1; attempt <= maxAttempts && !firstQuestion; attempt++) {
                    try {
                        firstQuestion = await loadFirstQuestion();
                    } catch (err) {
                        lastError = err;
                        if (isAlreadyPlayedError(err)) throw err; // no point retrying this one
                    }
                    if (!firstQuestion && attempt < maxAttempts) {
                        const msgEl = qs('#quiz-play-root p');
                        if (msgEl) msgEl.textContent = 'Starting the quiz…';
                        await sleep(2000);
                    }
                }
                if (!firstQuestion) throw (lastError || new Error('No questions returned for this quiz.'));
                await renderQuestion(firstQuestion);
            } catch (e) {
                stopBg();
                window.removeEventListener('hashchange', stopBgOnNav);
                if (isAlreadyPlayedError(e)) {
                    toast('You have already played this quiz. Showing the leaderboard…', 'info');
                    navigate(`/quiz/winners/${quizId}`, true);
                    return;
                }
                qs('#quiz-play-root').innerHTML = `
      <span class="material-symbols-outlined text-error text-5xl mb-3">error</span>
      <p class="text-error text-center mb-4">${escapeHtml(e.message)}</p>
      <button onclick="navigate('/quiz/list')" class="text-primary font-action">Back to Quizzes</button>`;
            }
        });

        /* ==========================================================================
           QUIZ REVIEW — "kitne sahi, kitne galat, kya correct tha" KBC-style recap
           Primary source: GET /quiz/{quiz_id}/answer-sheet (shows the user's own
           answer for every question, correct answer, and right/wrong per question).
           Falls back to the local reviewLog (collected during /quiz/play) only if
           the API call fails or returns nothing usable.
           ========================================================================== */
        route('/quiz/review/:id', async (app, params) => {
            const quizId = params.id;

            app.innerHTML = `
    <main class="min-h-screen px-container-margin pt-8 pb-10 kbc-stage">
      <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
      <div class="relative z-[1] flex justify-end mb-2">${kbcMuteBtnHtml()}</div>
      <div id="review-root" class="relative z-[1] flex flex-col items-center py-16">
        <div class="kbc-drumroll-ring"></div>
        <p class="text-on-surface-variant mt-5 kbc-title-glow font-headline-md">Tallying your answers...</p>
      </div>
    </main>`;
            wireMuteBtn();

            Sound.stopBackground();

            // Fetch the answer sheet from the API in parallel with the drumroll sound
            let log = [];
            let apiFailed = false;
            const fetchP = api(`/quiz/${quizId}/answer-sheet`, {
                    method: 'GET'
                })
                .then(res => {
                    const raw = unwrap(res) || res || {};
                    const list = Array.isArray(raw) ? raw : (raw.answers || asList(res));
                    return list.map(item => {
                        const qText = pick(item, ['question', 'question_text', 'title'],
                            'Question');
                        const chosenText = pick(item, ['your_answer', 'user_answer',
                            'selected_answer', 'chosen_answer', 'user_option',
                            'selected_option', 'given_answer', 'answer'
                        ], null);
                        const correctText = pick(item, ['correct_answer', 'correct_option',
                            'correctAnswer', 'right_answer'
                        ], null);
                        const rawIsCorrect = pick(item, ['is_correct', 'isCorrect', 'correct',
                            'answer_correct'
                        ], null);
                        let isCorrect = null;
                        if (rawIsCorrect !== null && rawIsCorrect !== '') {
                            isCorrect = (rawIsCorrect === true || rawIsCorrect === 1 ||
                                rawIsCorrect === '1' || String(rawIsCorrect).toLowerCase() ===
                                'true');
                        } else if (chosenText != null && correctText != null) {
                            isCorrect = String(chosenText).trim().toLowerCase() === String(
                                correctText).trim().toLowerCase();
                        }
                        return {
                            text: qText,
                            chosenText,
                            correctText,
                            isCorrect
                        };
                    });
                })
                .catch(e => {
                    apiFailed = true;
                    console.error('[answer-sheet] failed:', e);
                    return [];
                });

            const [apiLog] = await Promise.all([fetchP, new Promise(r => setTimeout(r, 1700))]);
            log = apiLog;

            // Fall back to the locally-collected log only if the API gave us nothing
            if (!log.length) {
                try {
                    log = JSON.parse(sessionStorage.getItem('qx_review_' + quizId) || '[]');
                } catch (e) {
                    log = [];
                }
            }



            const total = log.length;
            const correctCount = log.filter(r => r.isCorrect === true).length;
            const wrongCount = log.filter(r => r.isCorrect === false).length;

            const summaryHtml = total ? `
    <div class="grid grid-cols-3 gap-3 w-full mb-8">
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CORRECT</p>
        <p class="font-display text-2xl text-mint">${correctCount}</p>
      </div>
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">WRONG</p>
        <p class="font-display text-2xl" style="color:#ff8a8a">${wrongCount}</p>
      </div>
      <div class="kbc-card rounded-xl p-4 text-center">
        <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL</p>
        <p class="font-display text-2xl kbc-title-glow">${total}</p>
      </div>
    </div>
    <div class="w-full flex flex-col gap-3 mb-8">
      ${log.map((r, i) => {
        const isWrong = r.isCorrect === false;
        if (isWrong) {
          // Wrong answer: fixed-size box, only the border ring rotates.
          // Solid dark-red inner background + white badge text for clear contrast.
          return `
                                              <div class="kbc-glow-border-red kbc-reveal-row" style="animation-delay:${i * 70}ms">
                                                <div class="kbc-glow-inner-red rounded-xl p-4">
                                                  <p class="font-body-sm text-on-surface mb-2">Question ${i + 1}. ${escapeHtml(r.text)}</p>
                                                  <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-[12px] px-2 py-1 rounded-full font-bold" style="background:#c62828; color:#ffffff;">
                                                      <span class="material-symbols-outlined text-[13px] align-middle">cancel</span>
                                                      Your answer: ${escapeHtml(r.chosenText ?? '-')}
                                                    </span>
                                                    ${r.correctText ? `<span class="text-[12px] px-2 py-1 rounded-full bg-mint/15 text-mint">Correct: ${escapeHtml(r.correctText)}</span>` : ''}
                                                  </div>
                                                </div>
                                              </div>`;
        }
        return ` <
                div class = "kbc-card kbc-reveal-row rounded-xl p-4"
            style = "animation-delay:${i * 70}ms" >
                <
                p class = "font-body-sm text-on-surface-variant mb-2" > Question $ {
                    i + 1
                }.$ {
                    escapeHtml(r.text)
                } < /p> <
            div class = "flex items-center gap-2 flex-wrap" >
            <
            span class =
            "text-[12px] px-2 py-1 rounded-full ${r.isCorrect === true ? 'bg-mint/15 text-mint' : 'bg-surface-container-high text-on-surface-variant'}" >
            <
            span class = "material-symbols-outlined text-[13px] align-middle" > $ {
                r.isCorrect === true ? 'check_circle' : 'help'
            } < /span>
            Your answer: $ {
                    escapeHtml(r.chosenText ?? '-')
                } <
                /span> < /
            div > <
                /div>`;
    }).join('')
    } <
    /div>` : ` <
    div class = "kbc-card rounded-xl p-6 text-center mb-8 w-full" >
    <
    p class = "text-on-surface-variant" > $ {
        apiFailed ? "Couldn't load the answer sheet from the server." :
            "Detailed answer summary isn't available for this session."
    } < /p> < /
    div > `;

                                        qs('#review-root').outerHTML = ` <
        div id = "review-root"
    class = "relative z-[1] flex flex-col items-center py-4 fade-in w-full" >
    <
    div class =
    "w-20 h-20 rounded-full vibrant-gradient flex items-center justify-center mb-4 shadow-[0_0_40px_rgba(255,215,106,0.4)]" >
    <
    span class = "material-symbols-outlined text-white text-4xl" > fact_check < /span> < /
    div > <
        h2 class = "font-display text-headline-lg kbc-title-glow mb-1" > Answer Recap < /h2> <
    p class = "text-on-surface-variant mb-6" > Here 's how every question went</p>
    $ {
        summaryHtml
    } <
    button onclick = "navigate('/quiz/result/${quizId}', true)"
    class =
    "w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform" >
    See My Score < /button> < /
    div > `;
                                    });

                                    route('/quiz/result/:id', async (app, params) => {
                                        const quizId = params.id;
                                        app.innerHTML = `
    $ {
        topHeader({
            title: 'Result',
            showBack: true,
            backTo: '/home'
        })
    } <
    main class = "pt-20 pb-24 px-container-margin min-h-screen"
    id = "result-main" >
        <
        div class = "flex flex-col items-center py-12" >
        <
        div class = "w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin" > < /div> < /
    div > <
        /main>`;
        try {
            const res = await api(`/quiz/${quizId}/my-result`, {
                method: 'GET'
            });
            const d = unwrap(res) || {};
            const score = pick(d, ['score', 'total_score'], '0');
            const rank = pick(d, ['rank', 'my_rank'], '-');
            const coins = pick(d, ['coins_earned', 'reward', 'coins'], '0');
            const correct = pick(d, ['correct_answers', 'correct'], '-');
            const total = pick(d, ['total_questions', 'total'], '-');
            Sound.ensureCtx();
            Sound.coin();
            qs('#result-main').innerHTML = `
    <div class="flex flex-col items-center text-center py-6 kbc-stage p-4">
      <div class="kbc-spotlight"></div><div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
      <div class="relative z-[1] w-full flex justify-end mb-2">${kbcMuteBtnHtml()}</div>
      <div class="relative z-[1] w-24 h-24 rounded-full vibrant-gradient flex items-center justify-center mb-4 shadow-[0_0_40px_rgba(255,215,106,0.45)]">
        <span class="material-symbols-outlined text-white text-5xl">emoji_events</span>
      </div>
      <h2 class="relative z-[1] font-display text-headline-lg kbc-title-glow mb-1">Quiz Completed!</h2>
      <p class="relative z-[1] text-on-surface-variant mb-8">Here's how you did</p>
      <div class="relative z-[1] grid grid-cols-2 gap-3 w-full mb-6">
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">SCORE</p><p class="font-display text-2xl text-primary">${escapeHtml(score)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">RANK</p><p class="font-display text-2xl text-on-surface">#${escapeHtml(rank)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">COINS EARNED</p><p class="font-display text-2xl kbc-title-glow">+${escapeHtml(coins)}</p></div>
        <div class="kbc-card rounded-xl p-4 text-center"><p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CORRECT</p><p class="font-display text-2xl text-mint">${escapeHtml(correct)}/${escapeHtml(total)}</p></div>
      </div>
      <button onclick="navigate('/quiz/winners/${quizId}')" class="relative z-[1] w-full bg-primary text-on-primary py-4 rounded-xl font-action text-lg shadow-[0_0_20px_rgba(210,187,255,0.3)] active:scale-95 transition-transform mb-3">View Leaderboard</button>
      <button onclick="navigate('/home', true)" class="relative z-[1] w-full py-3 text-on-surface-variant font-action text-action">Back to Home</button>
    </div>`;
            wireMuteBtn();
        } catch (e) {
            qs('#result-main').innerHTML =
                `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p><button onclick="navigate('/home',true)" class="w-full text-primary font-action text-center mt-4">Back to Home</button>`;
        }
        });

        route('/quiz/winners/:id', async (app, params) => {
            const quizId = params.id;
            app.innerHTML = `
    ${topHeader({ title: 'Leaderboard', showBack: true })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="winners-main">
      <div class="space-y-gutter">${skeletonCard('h-16')}${skeletonCard('h-16')}${skeletonCard('h-16')}</div>
    </main>`;
            try {
                const res = await api(`/fsrapp/quiz/get-winners/${quizId}`, {
                    method: 'GET'
                });
                const list = asList(res);
                if (list.length) {
                    Sound.ensureCtx();
                    Sound.fanfare();
                }
                qs('#winners-main').innerHTML = list.length ? `
      <div class="relative z-[1] flex justify-end mb-3">${kbcMuteBtnHtml()}</div>
      <div class="space-y-gutter">${list.map((w,i) => {
        const name = pick(w, ['user_name','name'], 'Player');
        const correct = pick(w, ['total_correct_answer','correct_answers','score'], '-');
        const totalTime = pick(w, ['total_time'], null);
        const avgTime = pick(w, ['per_ans_average_time'], null);
        const reward = pick(w, ['reward'], null);
        const rewardType = pick(w, ['reward_type'], null);
        const hasReward = reward !== null && reward !== '' && reward !== undefined;
        return `
                                          <div class="kbc-card kbc-winner-row ${i<3 ? 'top' : ''} kbc-reveal-row rounded-lg p-3 flex items-center gap-3 sm:gap-4 flex-wrap sm:flex-nowrap" style="animation-delay:${i*60}ms">
                                            <div class="w-10 h-10 shrink-0 rounded-full ${i===0?'kbc-coin-badge':i===1?'bg-outline text-surface':i===2?'bg-secondary text-on-secondary':'bg-surface-container text-on-surface-variant'} flex items-center justify-center font-bold">${i===0?'<span class="material-symbols-outlined text-[18px]">emoji_events</span>':i+1}</div>
                                            <div class="flex-1 min-w-0">
                                              <p class="font-headline-md text-body-lg text-on-surface truncate">${escapeHtml(name)}</p>
                                              <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                                <p class="font-body-md text-on-surface-variant text-[12px]">Correct: <span class="text-mint font-bold">${escapeHtml(correct)}</span></p>
                                                ${totalTime !== null ? `<p class="font-body-md text-on-surface-variant text-[12px]">Time: ${escapeHtml(totalTime)}s</p>` : ''}
                                                ${avgTime !== null ? `<p class="font-body-md text-on-surface-variant text-[12px]">Avg/Q: ${escapeHtml(avgTime)}s</p>` : ''}
                                              </div>
                                            </div>
                                            <div class="flex flex-col items-end shrink-0">
                                              <span class="text-tertiary font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">monetization_on</span>${hasReward ? escapeHtml(reward) : '0'}</span>
                                              ${hasReward && rewardType ? `<span class="text-on-surface-variant text-[10px] uppercase tracking-wide mt-0.5">${escapeHtml(rewardType)}</span>` : ''}
                                            </div>
                                          </div>`;}).join('')}</div>` :
                    `<p class="text-on-surface-variant text-center py-10">No results yet.</p>`;
                wireMuteBtn();
            } catch (e) {
                qs('#winners-main').innerHTML =
                    `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
            }
        });

        /* ==========================================================================
           LEADERBOARD (global — footer nav destination)
           ========================================================================== */
        route('/leaderboard', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Leaderboard' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="leaderboard-main">
      <div class="flex gap-2 mb-stack-lg" id="leaderboard-period-tabs">
        <button data-period="weekly" class="lb-tab flex-1 py-2 rounded-xl font-action text-action bg-primary text-on-primary">This Week</button>
        <button data-period="monthly" class="lb-tab flex-1 py-2 rounded-xl font-action text-action bg-surface-container-high text-on-surface-variant border border-glass">This Month</button>
        <button data-period="alltime" class="lb-tab flex-1 py-2 rounded-xl font-action text-action bg-surface-container-high text-on-surface-variant border border-glass">All Time</button>
      </div>
      <div class="space-y-gutter" id="leaderboard-list">${skeletonCard('h-16')}${skeletonCard('h-16')}${skeletonCard('h-16')}${skeletonCard('h-16')}</div>
    </main>
    ${bottomNav('leaderboard')}`;

            async function loadLeaderboard(period) {
                const list = qs('#leaderboard-list');
                list.innerHTML = skeletonCard('h-16') + skeletonCard('h-16') + skeletonCard('h-16') +
                    skeletonCard('h-16');
                try {
                    const res = await api('/leaderboard', {
                        method: 'GET',
                        query: {
                            period
                        }
                    });
                    const rows = asList(res);
                    list.innerHTML = rows.length ? rows.map((w, i) => {
                            const name = pick(w, ['user_name', 'name'], 'Player');
                            const points = pick(w, ['points', 'score', 'total_points'], '0');
                            const isMe = !!pick(w, ['is_me', 'is_current_user']);
                            return `
        <div class="glass-card rounded-lg p-3 flex items-center gap-3 sm:gap-4 ${isMe ? 'border-primary/60' : ''}">
          <div class="w-10 h-10 shrink-0 rounded-full ${i === 0 ? 'bg-tertiary text-surface-container-lowest' : i === 1 ? 'bg-outline text-surface' : i === 2 ? 'bg-secondary text-on-secondary' : 'bg-surface-container text-on-surface-variant'} flex items-center justify-center font-bold">${i < 3 ? `<span class="material-symbols-outlined text-[18px]">emoji_events</span>` : i + 1}</div>
          <div class="flex-1 min-w-0">
            <p class="font-headline-md text-body-lg text-on-surface truncate">${escapeHtml(name)}${isMe ? ' (You)' : ''}</p>
          </div>
          <span class="text-tertiary font-bold flex items-center gap-1"><span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">monetization_on</span>${escapeHtml(points)}</span>
        </div>`;
                        }).join('') :
                        `<p class="text-on-surface-variant text-center py-10">No rankings yet — play a quiz to get on the board!</p>`;
                } catch (e) {
                    list.innerHTML = `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
                }
            }

            qsa('.lb-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    qsa('.lb-tab').forEach(b => b.className =
                        'lb-tab flex-1 py-2 rounded-xl font-action text-action bg-surface-container-high text-on-surface-variant border border-glass'
                    );
                    btn.className =
                        'lb-tab flex-1 py-2 rounded-xl font-action text-action bg-primary text-on-primary';
                    loadLeaderboard(btn.dataset.period);
                });
            });

            loadLeaderboard('weekly');
        });

        /* ==========================================================================
           HISTORY
           ========================================================================== */
        route('/history', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'My History', showBack: true })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="history-main">
      <div class="space-y-gutter">${skeletonCard('h-16')}${skeletonCard('h-16')}${skeletonCard('h-16')}</div>
    </main>
    ${bottomNav('')}`;
            try {
                const res = await api('/user/quiz-history', {
                    method: 'GET'
                });
                const list = asList(res);
                qs('#history-main').innerHTML = list.length ? `<div class="space-y-gutter">${list.map(h => `
                                          <div class="glass-card rounded-lg p-3 flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant">quiz</span></div>
                                            <div class="flex-1">
                                              <p class="font-headline-md text-body-lg text-on-surface">${escapeHtml(pick(h,['title','quiz_title','name'],'Quiz'))}</p>
                                              <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(h,['status'],'Completed'))} • Score ${escapeHtml(pick(h,['score'],'-'))} • +${escapeHtml(pick(h,['coins','reward'],'0'))} Coins</p>
                                            </div>
                                            <button onclick="navigate('/quiz/result/${escapeHtml(pick(h,['quiz_id','id']))}')" class="material-symbols-outlined text-on-surface-variant">chevron_right</button>
                                          </div>`).join('')}</div>` :
                    `<p class="text-on-surface-variant text-center py-10">You haven't played any quizzes yet.</p>`;
            } catch (e) {
                qs('#history-main').innerHTML =
                    `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
            }
        });

        /* ==========================================================================
           WALLET
           ========================================================================== */
        route('/wallet', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Wallet', showBack: true, backTo: '/home' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="glass-card rounded-2xl p-6 mb-stack-lg text-center vibrant-gradient" id="wallet-balance-card">
        <p class="font-label-caps text-[11px] text-white/80">TOTAL BALANCE</p>
        <p class="font-display text-4xl text-white mt-1" id="wallet-balance">...</p>
      </div>
      <div class="grid grid-cols-2 gap-gutter mb-stack-lg">
        <button onclick="navigate('/redeem')" class="glass-card rounded-xl p-4 flex flex-col items-center gap-2 active:scale-95 transition-transform"><span class="material-symbols-outlined text-primary text-3xl">redeem</span><span class="font-action text-action">Redeem</span></button>
        <button onclick="navigate('/referral')" class="glass-card rounded-xl p-4 flex flex-col items-center gap-2 active:scale-95 transition-transform"><span class="material-symbols-outlined text-secondary text-3xl">share</span><span class="font-action text-action">Refer & Earn</span></button>
      </div>
      <h4 class="font-headline-md text-headline-md mb-stack-sm">Transaction History</h4>
      <div class="space-y-gutter" id="wallet-history">${skeletonCard('h-14')}${skeletonCard('h-14')}${skeletonCard('h-14')}</div>
    </main>
    ${bottomNav('points')}`;

            api('/user/wallet', {
                method: 'GET'
            }).then(res => {
                const d = unwrap(res) || {};
                qs('#wallet-balance').textContent = pick(d, ['balance', 'coins', 'wallet_balance'],
                    '0');
            }).catch(() => {
                qs('#wallet-balance').textContent = '0';
            });

            api('/user/wallet-history', {
                method: 'GET'
            }).then(res => {
                const list = asList(res);
                qs('#wallet-history').innerHTML = list.length ? list.map(t => {
                        const amt = pick(t, ['amount', 'coins'], '0');
                        const isCredit = !String(amt).startsWith('-') && (pick(t, ['type'], '') ||
                            '').toLowerCase() !== 'debit';
                        return `<div class="glass-card rounded-lg p-3 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full ${isCredit ? 'bg-mint/20 text-mint' : 'bg-error/20 text-error'} flex items-center justify-center"><span class="material-symbols-outlined text-[20px]">${isCredit ? 'call_received' : 'call_made'}</span></div>
        <div class="flex-1">
          <p class="font-body-lg text-on-surface">${escapeHtml(pick(t,['description','reason','title'],'Transaction'))}</p>
          <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(t,['created_at','date'],''))}</p>
        </div>
        <span class="font-bold ${isCredit ? 'text-mint' : 'text-error'}">${isCredit ? '+' : ''}${escapeHtml(amt)}</span>
      </div>`;
                    }).join('') :
                    `<p class="text-on-surface-variant text-center py-6">No transactions yet.</p>`;
            }).catch(() => {
                qs('#wallet-history').innerHTML =
                    `<p class="text-on-surface-variant text-center py-6">Could not load history.</p>`;
            });
        });

        /* ==========================================================================
           REDEEM
           ========================================================================== */
        route('/redeem', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Redeem Rewards', showBack: true, backTo: '/wallet' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="flex justify-between items-center mb-stack-sm">
        <h4 class="font-headline-md text-headline-md">Rewards Catalog</h4>
        <button onclick="navigate('/redeem/history')" class="font-action text-primary text-action">My Redemptions</button>
      </div>
      <div class="grid grid-cols-2 gap-gutter" id="redeem-grid">${skeletonCard('h-36')}${skeletonCard('h-36')}</div>
    </main>
    ${bottomNav('redeem')}`;
            try {
                const res = await api('/user/rewards', {
                    method: 'GET'
                });
                const list = asList(res);
                qs('#redeem-grid').innerHTML = list.length ? list.map(r => `
      <div class="glass-card rounded-xl p-4 flex flex-col items-center text-center redeem-card" data-id="${escapeHtml(pick(r,['id','reward_id']))}" data-coins="${escapeHtml(pick(r,['coins','cost','points'],'0'))}" data-name="${escapeHtml(pick(r,['name','title'],'Reward'))}">
        <div class="w-14 h-14 rounded-2xl bg-tertiary/20 flex items-center justify-center mb-2"><span class="material-symbols-outlined text-tertiary text-2xl">card_giftcard</span></div>
        <p class="font-headline-md text-[14px] text-on-surface line-clamp-2">${escapeHtml(pick(r,['name','title'],'Reward'))}</p>
        <p class="text-mint font-bold mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1;">monetization_on</span>${escapeHtml(pick(r,['coins','cost','points'],'0'))}</p>
        <button class="redeem-btn mt-3 w-full py-1.5 rounded-lg bg-surface-container-high text-primary font-action text-action border border-primary/20 active:scale-95 transition-transform">Redeem</button>
      </div>`).join('') :
                    `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-on-surface-variant">No rewards available right now.</div>`;

                qsa('.redeem-card').forEach(card => {
                    qs('.redeem-btn', card).addEventListener('click', async () => {
                        const id = card.dataset.id,
                            coins = card.dataset.coins,
                            name = card.dataset.name;
                        if (!confirm(`Redeem "${name}" for ${coins} coins?`)) return;
                        await withLoader(async () => {
                            try {
                                await api('/user/redeem', {
                                    method: 'POST',
                                    body: {
                                        reward_id: id,
                                        coins: Number(coins)
                                    }
                                });
                                toast('Redemption requested!', 'success');
                            } catch (e) {
                                toast(e.message, 'error');
                            }
                        });
                    });
                });
            } catch (e) {
                qs('#redeem-grid').innerHTML =
                    `<div class="col-span-2 glass-card rounded-xl p-6 text-center text-error">${escapeHtml(e.message)}</div>`;
            }
        });

        route('/redeem/history', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'My Redemptions', showBack: true, backTo: '/redeem' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="redeem-history-main">
      <div class="space-y-gutter">${skeletonCard('h-16')}${skeletonCard('h-16')}</div>
    </main>`;
            try {
                const res = await api('/user/redemptions', {
                    method: 'GET'
                });
                const list = asList(res);
                qs('#redeem-history-main').innerHTML = list.length ? `<div class="space-y-gutter">${list.map(r => `
                                          <div class="glass-card rounded-lg p-3 flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center"><span class="material-symbols-outlined text-on-surface-variant">card_giftcard</span></div>
                                            <div class="flex-1">
                                              <p class="font-headline-md text-body-lg text-on-surface">${escapeHtml(pick(r,['reward_name','name'],'Reward'))}</p>
                                              <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(r,['status'],'Pending'))} • ${escapeHtml(pick(r,['coins'],'-'))} coins</p>
                                            </div>
                                          </div>`).join('')}</div>` :
                    `<p class="text-on-surface-variant text-center py-10">No redemption requests yet.</p>`;
            } catch (e) {
                qs('#redeem-history-main').innerHTML =
                    `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
            }
        });

        /* ==========================================================================
           REFERRAL
           ========================================================================== */
        route('/referral', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Refer & Earn', showBack: true, backTo: '/wallet' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen">
      <div class="glass-card rounded-2xl p-6 text-center mb-stack-lg">
        <span class="material-symbols-outlined text-primary text-4xl mb-2">card_giftcard</span>
        <h3 class="font-headline-lg text-headline-lg mb-1">Invite friends, earn coins</h3>
        <p class="text-on-surface-variant mb-4">Share your code1 — you both get rewarded when they join.</p>
        <div class="flex items-center justify-between bg-surface-container-high rounded-xl px-4 py-3 border border-glass" id="referral-code-box">
          <span class="font-label-caps text-headline-md" id="referral-code">...</span>
          <button id="copy-referral-btn" class="text-primary font-action text-action">Copy</button>
        </div>
      </div>
      <div class="glass-card rounded-2xl p-6 mb-stack-lg">
        <h4 class="font-headline-md text-headline-md mb-3">Have a code?</h4>
        <div class="flex gap-2">
          <input id="apply-referral-input" placeholder="Enter referral code" class="flex-1 bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface">
          <button id="apply-referral-btn" class="bg-primary text-on-primary px-5 rounded-xl font-action">Apply</button>
        </div>
      </div>
      <h4 class="font-headline-md text-headline-md mb-stack-sm">My Referrals</h4>
      <div class="space-y-gutter" id="referral-list">${skeletonCard('h-14')}</div>
    </main>
    ${bottomNav('')}`;

            api('/user/referral-code', {
                method: 'GET'
            }).then(res => {
                const d = unwrap(res) || {};
                qs('#referral-code').textContent = pick(d, ['referral_code', 'code'], '—');
            }).catch(() => {
                qs('#referral-code').textContent = '—';
            });

            qs('#copy-referral-btn').onclick = () => {
                const code = qs('#referral-code').textContent;
                navigator.clipboard?.writeText(code).then(() => toast('Referral code copied', 'success'))
                    .catch(() => toast('Could not copy', 'error'));
            };

            qs('#apply-referral-btn').onclick = async () => {
                const code = qs('#apply-referral-input').value.trim();
                if (!code) {
                    toast('Enter a referral code', 'error');
                    return;
                }
                await withLoader(async () => {
                    try {
                        await api('/user/apply-referral', {
                            method: 'POST',
                            body: {
                                referral_code: code
                            }
                        });
                        toast('Referral code applied', 'success');
                    } catch (e) {
                        toast(e.message, 'error');
                    }
                });
            };

            api('/user/referrals', {
                method: 'GET'
            }).then(res => {
                const list = asList(res);
                qs('#referral-list').innerHTML = list.length ? list.map(r => `
      <div class="glass-card rounded-lg p-3 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center"><span class="material-symbols-outlined text-primary text-[20px]">person</span></div>
        <div class="flex-1">
          <p class="font-body-lg text-on-surface">${escapeHtml(pick(r,['name','user_name'],'Friend'))}</p>
          <p class="font-body-md text-on-surface-variant text-[12px]">${escapeHtml(pick(r,['status'],'Joined'))}</p>
        </div>
        <span class="text-mint font-bold">+${escapeHtml(pick(r,['reward','coins'],'0'))}</span>
      </div>`).join('') : `<p class="text-on-surface-variant text-center py-6">No referrals yet. Share your code!</p>`;
            }).catch(() => {
                qs('#referral-list').innerHTML =
                    `<p class="text-on-surface-variant text-center py-6">Could not load referrals.</p>`;
            });
        });

        /* ==========================================================================
           PROFILE
           ========================================================================== */
        route('/profile', async (app) => {
            app.innerHTML = `
    ${topHeader({ title: 'Profile', showBack: true, backTo: '/home' })}
    <main class="pt-20 pb-24 px-container-margin min-h-screen" id="profile-main">
      <div class="flex flex-col items-center py-10"><div class="w-10 h-10 border-4 border-primary/30 border-t-primary rounded-full spin"></div></div>
    </main>
    ${bottomNav('profile')}`;

            try {
                const res = await api('/user/profile', {
                    method: 'GET'
                });
                const d = unwrap(res) || {};
                Store.setUser(d);
                qs('#profile-main').innerHTML = `
      <div class="glass-card rounded-2xl p-6 mb-stack-lg flex flex-col items-center text-center">
        <div class="w-20 h-20 rounded-full vibrant-gradient flex items-center justify-center mb-3"><span class="material-symbols-outlined text-white text-4xl">person</span></div>
        <h3 class="font-headline-lg text-headline-lg">${escapeHtml(pick(d,['name'],'Player'))}</h3>
        <p class="text-on-surface-variant">${escapeHtml(pick(d,['mobile','phone'],''))}</p>
        ${pick(d,['email']) ? `<p class="text-on-surface-variant text-[13px]">${escapeHtml(pick(d,['email']))}</p>` : ''}
      </div>
      <form id="profile-edit-form" class="glass-card rounded-2xl p-6 flex flex-col gap-4 mb-stack-lg">
        <h4 class="font-headline-md text-headline-md mb-1">Edit Profile</h4>
        <div><label class="font-label-caps text-[11px] text-on-surface-variant">NAME</label><input id="pf-name" value="${escapeHtml(pick(d,['name']))}" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface"></div>
        <div><label class="font-label-caps text-[11px] text-on-surface-variant">CITY</label><input id="pf-city" value="${escapeHtml(pick(d,['city']))}" class="mt-1 w-full bg-surface-container border border-glass rounded-xl px-4 py-3 text-on-surface"></div>
        <button type="submit" class="w-full mt-1 bg-primary text-on-primary py-3 rounded-xl font-action active:scale-95 transition-transform">Save Changes</button>
      </form>
      <div class="glass-card rounded-2xl divide-y divide-white/10 mb-stack-lg overflow-hidden">
        <button onclick="navigate('/history')" class="w-full flex items-center justify-between px-5 py-4"><span class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">history</span>Quiz History</span><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
        <button onclick="navigate('/wallet')" class="w-full flex items-center justify-between px-5 py-4"><span class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">account_balance_wallet</span>Wallet</span><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
        <button onclick="navigate('/referral')" class="w-full flex items-center justify-between px-5 py-4"><span class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">share</span>Refer & Earn</span><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
        <button onclick="openSettings()" class="w-full flex items-center justify-between px-5 py-4"><span class="flex items-center gap-3"><span class="material-symbols-outlined text-primary">settings</span>API Settings</span><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
      </div> 
      <button id="logout-btn" class="w-full py-3 rounded-xl border border-error text-error font-action mb-3 active:scale-95 transition-transform">Logout</button>
      <button id="delete-account-btn" class="w-full py-3 text-on-surface-variant font-action text-action">Delete Account</button>
    `;

                qs('#profile-edit-form').onsubmit = async (e) => {
                    e.preventDefault();
                    await withLoader(async () => {
                        try {
                            await api('/user/update-profile', {
                                method: 'POST',
                                body: {
                                    name: qs('#pf-name').value.trim(),
                                    city: qs('#pf-city').value.trim()
                                }
                            });
                            toast('Profile updated', 'success');
                        } catch (e) {
                            toast(e.message, 'error');
                        }
                    });
                };
                qs('#logout-btn').onclick = () => {
                    Store.clearToken();
                    Store.clearUser();
                    toast('Logged out', 'success');
                    navigate('/login', true);
                };
                qs('#delete-account-btn').onclick = async () => {
                    if (!confirm('This will permanently delete your account. Continue?')) return;
                    await withLoader(async () => {
                        try {
                            await api('/user/delete-account', {
                                method: 'DELETE'
                            });
                            Store.clearToken();
                            Store.clearUser();
                            toast('Account deleted', 'success');
                            navigate('/login', true);
                        } catch (e) {
                            toast(e.message, 'error');
                        }
                    });
                };
            } catch (e) {
                qs('#profile-main').innerHTML =
                    `<p class="text-error text-center py-10">${escapeHtml(e.message)}</p>`;
            }
        });
    </script>
</body>

</html>
