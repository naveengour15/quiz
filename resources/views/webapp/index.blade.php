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
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Source+Sans+3:wght@400;600&family=JetBrains+Mono:wght@500&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
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
        :root {
            --kbc-gold: #ffd76a;
            --kbc-gold-deep: #d4a537;
            --kbc-navy: #060b26;
            --kbc-navy-2: #0c1640;
        }

        .kbc-stage {
            position: relative;
            background:
                radial-gradient(120% 70% at 50% -10%, rgba(255, 215, 106, 0.14) 0%, rgba(255, 215, 106, 0) 60%),
                radial-gradient(90% 60% at 50% 110%, rgba(124, 58, 237, 0.20) 0%, rgba(124, 58, 237, 0) 60%),
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

    {{-- Include Core & UI Component Modules --}}
    @include('webapp.components.core')
    @include('webapp.components.settings')
    @include('webapp.components.login')
    @include('webapp.components.otp')
    @include('webapp.components.complete-profile')
    @include('webapp.components.home')
    @include('webapp.components.quiz-list')
    @include('webapp.components.quiz-sheet')
    @include('webapp.components.quiz-waiting')
    @include('webapp.components.quiz-play')
    @include('webapp.components.practice-quiz-play')
    @include('webapp.components.practice-quiz-review')
    @include('webapp.components.quiz-test')
    @include('webapp.components.quiz-result')
    @include('webapp.components.quiz-review')
    @include('webapp.components.quiz-winners')
    @include('webapp.components.survey-list')
    @include('webapp.components.survey-play')
    @include('webapp.components.wallet')
    @include('webapp.components.redeem')
    @include('webapp.components.redeem-history')
    @include('webapp.components.referral')
    @include('webapp.components.history')
    @include('webapp.components.profile')

</body>

</html>
