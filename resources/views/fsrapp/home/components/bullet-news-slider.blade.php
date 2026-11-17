@php
    $newsDisplaySeconds = 10;
    $breakingDisplaySeconds = 4.5;
    $breakingNewsText = 'BREAKING NEWS';
    $breakingNewsWords = preg_split('/\s+/', trim($breakingNewsText), 2) ?: [];
    $breakingNewsFirstWord = $breakingNewsWords[0] ?? $breakingNewsText;
    $breakingNewsSecondWord = $breakingNewsWords[1] ?? '';

    $newsItems = \App\Models\News::where('tenant_id', getSuperAdminTenantId())
        ->where('status', 'active')
        ->whereDate('created_at', today())
        ->latest('updated_at')
        ->limit(20)
        ->get();
@endphp

@if($newsItems->isNotEmpty())
    <section
        class="news-reader"
        id="news-reader"
        aria-label="ताज़ा खबरें"
        data-news-interval="{{ $newsDisplaySeconds * 1000 }}"
        data-breaking-duration="{{ $breakingDisplaySeconds * 1000 }}"
    >
        <header class="news-reader__header">
            <div class="news-reader__brand">
                <span class="news-reader__live-dot" aria-hidden="true"></span>
                <div>
                    <h2 class="news-reader__section-title">आज की खबरें</h2>
                </div>
            </div>
            @if($newsItems->count() > 1)
                <nav class="news-reader__navigation" aria-label="खबर चुनें">
                    @foreach($newsItems as $index => $news)
                        <button
                            class="news-reader__indicator"
                            type="button"
                            data-news-indicator="{{ $index }}"
                            aria-label="खबर {{ $index + 1 }}"
                            aria-current="false"
                        ></button>
                    @endforeach
                </nav>
            @endif
            <div class="news-reader__audio-controls">
                <button
                    class="news-reader__sound"
                    id="news-reader-speech-toggle"
                    type="button"
                    aria-label="खबर पढ़कर सुनाएँ"
                    aria-pressed="false"
                >
                    <span id="news-reader-speech-icon" aria-hidden="true">🔊</span>
                </button>
                <button
                    class="news-reader__sound"
                    id="news-reader-sound-toggle"
                    type="button"
                    aria-label="बैकग्राउंड साउंड बंद करें"
                    aria-pressed="true"
                >
                    <span id="news-reader-sound-icon" aria-hidden="true">🔔</span>
                </button>
            </div>
        </header>

        <div class="news-reader__stage" id="news-reader-stage">
            @foreach($newsItems as $index => $news)
                <article
                    class="news-reader__article{{ filled($news->image) ? ' news-reader__article--with-image' : '' }}"
                    data-news-article
                    hidden
                    aria-hidden="true"
                >
                    <div class="news-reader__story">
                        @if(filled($news->image))
                            <img
                                class="news-reader__image"
                                src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($news->image) }}"
                                alt=""
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            >
                        @endif
                        <div class="news-reader__content">
                            <h3 class="news-reader__headline">{{ $news->title ?: 'ताज़ा खबर' }}</h3>

                            @if(filled($news->description))
                                <p class="news-reader__description">{{ $news->description }}</p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
            @if($newsItems->isNotEmpty())
                <div class="news-reader__breaking is-active" data-news-breaking aria-live="polite" role="status">
                    <div class="news-reader__breaking-inner">
                        <span class="news-reader__breaking-tag">FSRQUIZ</span>
                        <div class="news-reader__breaking-content">
                            <span class="news-reader__breaking-line" aria-hidden="true"></span>
                            <span class="news-reader__breaking-words">
                                <span class="news-reader__breaking-word news-reader__breaking-word--first">{{ $breakingNewsFirstWord }}</span>
                                @if($breakingNewsSecondWord !== '')
                                    <span class="news-reader__breaking-word news-reader__breaking-word--second">{{ $breakingNewsSecondWord }}</span>
                                @endif
                            </span>
                            <span class="news-reader__breaking-line" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </section>

    <audio id="news-reader-sound" loop preload="auto" autoplay playsinline>
        <source src="https://chaloonline.s3.ap-south-1.amazonaws.com/sounds/quiz_end_sound_1792157311.mp3" type="audio/mpeg">
    </audio>

    <style>
        .news-reader {
            --news-red: #bd1725;
            --news-ink: #171717;
            margin: 0 0 1.5rem;
            overflow: hidden;
            border: 1px solid #e7e1dc;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 12px 32px rgb(36 23 20 / 10%);
        }

        .news-reader__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1rem;
            color: #fff;
            background: linear-gradient(110deg, #9e101b, var(--news-red) 65%, #d22b31);
        }

        .news-reader__brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .news-reader__live-dot {
            width: 0.65rem;
            height: 0.65rem;
            flex: 0 0 auto;
            border-radius: 50%;
            background: #ffd34e;
            box-shadow: 0 0 0 4px rgb(255 211 78 / 18%);
        }

        .news-reader__section-title {
            margin: 0;
            color: #fff;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .news-reader__navigation {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: flex-end;
            min-width: 0;
            gap: 0.3rem;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .news-reader__navigation::-webkit-scrollbar {
            display: none;
        }

        .news-reader__indicator {
            width: 0.48rem;
            height: 0.48rem;
            flex: 0 0 auto;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: rgb(255 255 255 / 48%);
            cursor: pointer;
            transition: width 180ms ease, background-color 180ms ease;
        }

        .news-reader__indicator.is-active {
            width: 1.15rem;
            background: #ffd34e;
        }

        .news-reader__indicator:focus-visible,
        .news-reader__sound:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 3px;
        }

        .news-reader__sound {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid rgb(255 255 255 / 40%);
            border-radius: 50%;
            background: rgb(0 0 0 / 12%);
            color: #fff;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .news-reader__sound[aria-pressed="false"] {
            opacity: 0.7;
        }

        .news-reader__audio-controls {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 0.35rem;
        }

        .news-reader__stage {
            padding: 0.45rem;
            background: #a20e19;
        }

        .news-reader__article.is-active {
            animation: news-reader-enter 380ms ease-out both;
        }

        .news-reader__article[hidden] {
            display: none;
        }

        .news-reader__article {
            overflow: hidden;
            border: 1px solid rgb(255 255 255 / 72%);
            background:
                linear-gradient(135deg, transparent 68%, rgb(255 255 255 / 8%) 68% 71%, transparent 71%),
                linear-gradient(110deg, #8f101a, #d52630 58%, #9c101b);
            box-shadow: 0 3px 12px rgb(0 0 0 / 18%);
        }

        .news-reader__story {
            display: grid;
            min-height: 8rem;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
        }

        .news-reader__article:not(.news-reader__article--with-image) .news-reader__story {
            grid-template-columns: minmax(0, 1fr);
        }

        .news-reader__image {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 8rem;
            background: #240609;
            object-fit: cover;
        }

        .news-reader__article--with-image .news-reader__content {
            border-left: 2px solid rgb(255 255 255 / 80%);
        }

        .news-reader__content {
            display: flex;
            min-width: 0;
            flex-direction: column;
            justify-content: center;
            padding: 0.8rem;
            color: #fff;
            background:
                linear-gradient(135deg, transparent 72%, rgb(255 255 255 / 9%) 72% 76%, transparent 76%),
                linear-gradient(145deg, #ee3540, #bc101e 70%);
        }

        .news-reader__headline {
            margin: 0;
            color: #fff;
            font-size: clamp(0.9rem, 4.5vw, 1.2rem);
            font-weight: 900;
            line-height: 1.35;
            overflow-wrap: anywhere;
            text-shadow: 0 1px 3px rgb(0 0 0 / 18%);
        }

        .news-reader__description {
            display: -webkit-box;
            margin: 0.35rem 0 0;
            color: rgb(255 255 255 / 92%);
            overflow: hidden;
            font-size: clamp(0.68rem, 3vw, 0.8rem);
            line-height: 1.4;
            overflow-wrap: anywhere;
            white-space: pre-line;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .news-reader__breaking {
            display: flex;
            min-height: 8rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0.5rem;
            border-radius: 0.65rem;
            background:
                radial-gradient(ellipse at 50% 50%, rgb(255 255 255 / 13%), transparent 58%),
                repeating-linear-gradient(145deg, transparent 0 28px, rgb(255 255 255 / 5%) 29px 31px),
                linear-gradient(110deg, #700811, #dc1726 50%, #760913);
            color: #fff;
        }

        .news-reader__breaking[hidden] {
            display: none;
        }

        .news-reader__breaking-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            width: 100%;
        }

        .news-reader__breaking-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.16rem 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 999px;
            background: linear-gradient(135deg, #ffd34e, #f59e0b);
            color: #700811;
            font-size: clamp(0.62rem, 2.4vw, 0.75rem);
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            box-shadow: 0 2px 10px rgb(0 0 0 / 25%), 0 0 12px rgba(255, 211, 78, 0.45);
            opacity: 0;
            transform: translateY(-8px) scale(0.92);
        }

        .news-reader__breaking.is-active .news-reader__breaking-tag {
            animation: news-breaking-tag-enter 450ms cubic-bezier(0.2, 0.8, 0.2, 1) 60ms forwards;
        }

        .news-reader__breaking-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(0.2rem, 2vw, 0.65rem);
            width: 100%;
        }

        .news-reader__breaking-words {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            justify-content: center;
            gap: clamp(0.15rem, 1vw, 0.35rem);
            font-size: clamp(0.82rem, 5.5vw, 2rem);
            font-weight: 900;
            line-height: 1.2;
            text-shadow: 0 3px 0 #7f0911, 0 5px 12px rgb(0 0 0 / 40%);
            white-space: nowrap;
        }

        .news-reader__breaking-word {
            flex: 0 1 auto;
            min-width: 0;
            opacity: 0;
        }

        .news-reader__breaking-word--first {
            text-align: right;
        }

        .news-reader__breaking-word--second {
            text-align: left;
        }

        .news-reader__breaking.is-active .news-reader__breaking-word--first {
            animation: news-breaking-from-left 650ms cubic-bezier(0.2, 0.8, 0.2, 1) 100ms forwards;
        }

        .news-reader__breaking.is-active .news-reader__breaking-word--second {
            animation: news-breaking-from-right 650ms cubic-bezier(0.2, 0.8, 0.2, 1) 800ms forwards;
        }

        .news-reader__breaking-line {
            width: clamp(0.35rem, 4vw, 2.5rem);
            min-width: 0.35rem;
            height: 2px;
            flex: 0 1 auto;
            background: #ffd34e;
            transform: scaleX(0);
            animation: news-breaking-line 700ms ease-out 200ms forwards;
        }

        .news-reader__breaking-line:last-child {
            animation-name: news-breaking-line-reverse;
        }

        @keyframes news-reader-enter {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes news-breaking-tag-enter {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes news-breaking-from-left {
            from {
                opacity: 0;
                transform: translateX(-2.5rem);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes news-breaking-from-right {
            from {
                opacity: 0;
                transform: translateX(2.5rem);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes news-breaking-line {
            to {
                transform: scaleX(1);
                transform-origin: left;
            }
        }

        @keyframes news-breaking-line-reverse {
            to {
                transform: scaleX(1);
                transform-origin: right;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .news-reader__article.is-active {
                animation: none;
            }

            .news-reader__breaking-tag,
            .news-reader__breaking-word,
            .news-reader__breaking-line {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>

    <script>
        (() => {
            // =========================================================================
            // बैकग्राउंड न्यूज़ साउंड एवं आवाज़ सेटिंग्स (रवीश कुमार स्टाइल) / CONFIG
            // =========================================================================
            // बैकग्राउंड न्यूज़ साउंड वॉल्यूम (0.0 से 1.0) - 0.75 यानी मोबाइल/सिस्टम वॉल्यूम का 75%
            const BACKGROUND_NEWS_SOUND_VOLUME = 0.75;

            // न्यूज़ एंकर की स्वाभाविक, सजीव और स्पष्ट गति (Rate) - 1.0 वास्तविक इंसान जैसी गति
            const NEWS_ANCHOR_RATE = 1.0;

            // न्यूज़ एंकर की स्वाभाविक ह्यूमन पिच (Pitch) - 1.0 (रोबोटिक इफेक्ट से मुक्त नेचुरल पिच)
            const NEWS_ANCHOR_PITCH = 1.0;

            const initializeNewsReader = () => {
                const reader = document.getElementById('news-reader');
                const articles = reader ? Array.from(reader.querySelectorAll('[data-news-article]')) : [];
                if (!reader || articles.length === 0) return;

                const sound = document.getElementById('news-reader-sound');
                const soundToggle = reader.querySelector('#news-reader-sound-toggle');
                const soundIcon = reader.querySelector('#news-reader-sound-icon');
                const speechToggle = reader.querySelector('#news-reader-speech-toggle');
                const speechIcon = reader.querySelector('#news-reader-speech-icon');
                const indicators = Array.from(reader.querySelectorAll('[data-news-indicator]'));
                const breaking = reader.querySelector('[data-news-breaking]');
                const intervalTime = Number(reader.dataset.newsInterval);
                const breakingTime = Number(reader.dataset.breakingDuration);
                const speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;

                let currentIndex = -1;
                let timer;
                let soundEnabled = true; // डिफ़ॉल्ट बैकग्राउंड साउंड चालू रहेगा
                let speechEnabled = false;
                let pointerInside = false;
                let transitionTimer;
                let availableVoices = [];

                if (sound) {
                    sound.volume = BACKGROUND_NEWS_SOUND_VOLUME;
                    sound.loop = true;
                }

                // बैकग्राउंड साउंड आइकन और स्टेट को ऑडियो की वास्तविक स्थिति के साथ 100% सिंक रखना
                const syncSoundUI = () => {
                    if (!soundToggle || !soundIcon || !sound) return;
                    const isPlaying = !sound.paused && !sound.ended;
                    soundToggle.setAttribute('aria-pressed', String(isPlaying));
                    soundToggle.setAttribute('aria-label', isPlaying ? 'बैकग्राउंड साउंड बंद करें' : 'बैकग्राउंड साउंड चालू करें');
                    soundIcon.textContent = isPlaying ? '🔔' : '🔕';
                };

                if (sound) {
                    sound.addEventListener('play', syncSoundUI);
                    sound.addEventListener('playing', syncSoundUI);
                    sound.addEventListener('pause', syncSoundUI);
                    sound.addEventListener('ended', syncSoundUI);
                }

                // ब्राउज़र में उपलब्ध आवाज़ें (Voices) लोड करें
                const loadVoices = () => {
                    if (!speechSupported) return;
                    const voices = window.speechSynthesis.getVoices();
                    if (voices && voices.length) {
                        availableVoices = voices;
                    }
                };

                loadVoices();
                if (speechSupported && window.speechSynthesis.onvoiceschanged !== undefined) {
                    window.speechSynthesis.onvoiceschanged = loadVoices;
                }

                // सबसे स्वाभाविक, सजीव और स्पष्ट ह्यूमन न्यूज़ एंकर आवाज़ (Natural / Neural Voice) का चयन
                const getNewsAnchorVoice = () => {
                    if (!speechSupported) return null;
                    const voices = availableVoices.length ? availableVoices : (window.speechSynthesis.getVoices() || []);
                    if (!voices || voices.length === 0) return null;

                    // 1. हिंदी आवाज़ें फ़िल्टर करें
                    const hindiVoices = voices.filter(v => {
                        const lang = (v.lang || '').toLowerCase().replace(/_/g, '-');
                        return lang.startsWith('hi') || lang === 'hin';
                    });

                    if (hindiVoices.length > 0) {
                        // प्राथमिकता 1: नेचुरल / न्यूरल हिंदी आवाज़ें (जैसे Microsoft Madhur / Swara Online Natural जो बिल्कुल असली इंसान जैसी लगती हैं)
                        const naturalHindi = hindiVoices.find(v => {
                            const name = (v.name || '').toLowerCase();
                            return name.includes('natural') || name.includes('neural') || name.includes('online');
                        });
                        if (naturalHindi) return naturalHindi;

                        // प्राथमिकता 2: Google हिन्दी (Chrome/Android की हाई-क्वालिटी नेचुरल आवाज़)
                        const googleHindi = hindiVoices.find(v => (v.name || '').toLowerCase().includes('google'));
                        if (googleHindi) return googleHindi;

                        // प्राथमिकता 3: प्रीमियम / एन्हांस्ड हिंदी आवाज़
                        const premiumHindi = hindiVoices.find(v => {
                            const name = (v.name || '').toLowerCase();
                            return name.includes('premium') || name.includes('enhanced');
                        });
                        if (premiumHindi) return premiumHindi;

                        // प्राथमिकता 4: Madhur, Swara या Hemant
                        const anchorVoice = hindiVoices.find(v => {
                            const name = (v.name || '').toLowerCase();
                            return name.includes('madhur') || name.includes('swara') || name.includes('hemant');
                        });
                        if (anchorVoice) return anchorVoice;

                        // प्राथमिकता 5: पहली उपलब्ध हिंदी आवाज़
                        return hindiVoices[0];
                    }

                    // फ़ालबैक: यदि हिंदी न हो, तो नेचुरल इंडियन आवाज़
                    const indianNatural = voices.find(v => {
                        const lang = (v.lang || '').toLowerCase().replace(/_/g, '-');
                        const name = (v.name || '').toLowerCase();
                        return (lang.includes('en-in') || name.includes('india')) &&
                               (name.includes('natural') || name.includes('neural') || name.includes('online') || name.includes('google'));
                    });
                    if (indianNatural) return indianNatural;

                    return null;
                };

                const isPaused = () => pointerInside;

                // बैकग्राउंड साउंड चालू करना
                const playBackgroundSound = () => {
                    if (!soundEnabled || !sound) return;
                    sound.volume = BACKGROUND_NEWS_SOUND_VOLUME;
                    sound.loop = true;
                    const playPromise = sound.play();
                    if (playPromise !== undefined) {
                        playPromise.then(() => {
                            syncSoundUI();
                        }).catch(() => {
                            syncSoundUI();
                        });
                    }
                };

                // बैकग्राउंड साउंड धीमा करना (जब न्यूज़ वाचन चल रहा हो, ताकि आवाज़ एकदम साफ़ और स्टूडियो जैसी सुनाई दे)
                const duckBackgroundSound = () => {
                    if (sound && soundEnabled) {
                        sound.volume = 0.22;
                    }
                };

                // बैकग्राउंड साउंड सामान्य करना
                const restoreBackgroundSound = () => {
                    if (sound && soundEnabled) {
                        sound.volume = BACKGROUND_NEWS_SOUND_VOLUME;
                    }
                };

                // बैकग्राउंड साउंड बंद करना
                const stopBackgroundSound = () => {
                    if (!sound) return;
                    sound.pause();
                    syncSoundUI();
                };

                // सुनिश्चित करें कि डिफ़ॉल्ट रूप से साउंड बज रहा हो
                const ensureAudioPlaying = () => {
                    if (soundEnabled && sound && sound.paused) {
                        playBackgroundSound();
                    }
                };

                // ब्राउज़र ऑटो-प्ले पॉलिसी अनलॉक: किसी भी यूज़र इंटरैक्शन पर डिफ़ॉल्ट बैकग्राउंड साउंड तुरंत प्ले
                const unlockEvents = ['click', 'touchstart', 'touchend', 'pointerdown', 'scroll', 'keydown'];
                const unlockAudioOnGesture = () => {
                    if (soundEnabled && sound && sound.paused) {
                        playBackgroundSound();
                    }
                    if (sound && !sound.paused) {
                        unlockEvents.forEach((evt) => {
                            window.removeEventListener(evt, unlockAudioOnGesture, { capture: true });
                            document.removeEventListener(evt, unlockAudioOnGesture, { capture: true });
                        });
                    }
                };
                unlockEvents.forEach((evt) => {
                    window.addEventListener(evt, unlockAudioOnGesture, { capture: true, passive: true });
                    document.addEventListener(evt, unlockAudioOnGesture, { capture: true, passive: true });
                });

                // ब्रेकिंग न्यूज़ का ऐलान (नेचुरल एंकर स्टाइल में)
                const speakBreakingTransition = (onDone) => {
                    if (!speechSupported || !speechEnabled) {
                        if (onDone) onDone();
                        return;
                    }

                    window.speechSynthesis.cancel();

                    const breakingPhrase = 'नमस्कार, आप सुन रहे हैं एफएसआर क्विज़ ब्रेकिंग न्यूज़।';
                    const utterance = new SpeechSynthesisUtterance(breakingPhrase);
                    utterance.volume = 1.0;
                    utterance.rate = NEWS_ANCHOR_RATE;
                    utterance.pitch = NEWS_ANCHOR_PITCH;

                    const anchorVoice = getNewsAnchorVoice();
                    if (anchorVoice) {
                        utterance.voice = anchorVoice;
                        utterance.lang = anchorVoice.lang || 'hi-IN';
                    } else {
                        utterance.lang = 'hi-IN';
                    }

                    utterance.onstart = () => {
                        duckBackgroundSound();
                    };

                    let finished = false;
                    const triggerDone = () => {
                        if (!finished) {
                            finished = true;
                            restoreBackgroundSound();
                            if (onDone) onDone();
                        }
                    };

                    utterance.onend = triggerDone;
                    utterance.onerror = triggerDone;
                    window.speechSynthesis.speak(utterance);

                    // सेफ़्टी टाइमआउट
                    window.setTimeout(triggerDone, 2600);
                };

                // मुख्य न्यूज़ का वाचन (नेचुरल ह्यूमन एंकर स्टाइल में)
                const speakCurrentArticle = () => {
                    if (!speechSupported || !speechEnabled) return;

                    window.speechSynthesis.cancel();

                    const headline = articles[currentIndex].querySelector('.news-reader__headline')?.textContent?.trim() || '';
                    const description = articles[currentIndex].querySelector('.news-reader__description')?.textContent?.trim() || '';

                    const cleanHeadline = headline.replace(/\s+/g, ' ').trim();
                    const cleanDescription = description.replace(/\s+/g, ' ').trim();

                    // नेचुरल ह्यूमन न्यूज़ एंकर स्टाइल में उचित विराम चिह्नों के साथ वाचन
                    let textToSpeak = cleanHeadline;
                    if (cleanDescription) {
                        const endsWithPunctuation = /[।?!.]$/.test(cleanHeadline);
                        textToSpeak += (endsWithPunctuation ? ' ' : '। ') + cleanDescription;
                    }
                    if (!textToSpeak) return;

                    const utterance = new SpeechSynthesisUtterance(textToSpeak);
                    utterance.volume = 1.0;
                    utterance.rate = NEWS_ANCHOR_RATE;
                    utterance.pitch = NEWS_ANCHOR_PITCH;

                    const anchorVoice = getNewsAnchorVoice();
                    if (anchorVoice) {
                        utterance.voice = anchorVoice;
                        utterance.lang = anchorVoice.lang || 'hi-IN';
                    } else {
                        utterance.lang = 'hi-IN';
                    }

                    utterance.onstart = () => {
                        duckBackgroundSound();
                    };

                    const handleSpeechEnd = () => {
                        restoreBackgroundSound();
                        if (speechEnabled && !pointerInside && articles.length > 1) {
                            timer = window.setTimeout(() => showArticle(currentIndex + 1), 1200);
                        }
                    };

                    utterance.onend = handleSpeechEnd;
                    utterance.onerror = handleSpeechEnd;

                    window.speechSynthesis.speak(utterance);
                };

                const startTimer = () => {
                    window.clearTimeout(timer);
                    if (isPaused() || speechEnabled || articles.length < 2) return;
                    timer = window.setTimeout(() => showArticle(currentIndex + 1), intervalTime);
                };

                const showArticle = (index, isInitial = false) => {
                    const nextIndex = (index + articles.length) % articles.length;
                    if (nextIndex === currentIndex || !breaking) return;

                    window.clearTimeout(timer);
                    window.clearTimeout(transitionTimer);
                    window.speechSynthesis?.cancel();

                    if (currentIndex >= 0 && articles[currentIndex]) {
                        articles[currentIndex].hidden = true;
                        articles[currentIndex].classList.remove('is-active');
                    }
                    breaking.hidden = false;

                    // पहली बार लोड होने पर एनीमेशन रीसेट (रिफ्लो) न करें, ताकि स्क्रीन ब्लिंक न करे
                    if (!isInitial) {
                        breaking.classList.remove('is-active');
                        void breaking.offsetWidth;
                        breaking.classList.add('is-active');
                    } else if (!breaking.classList.contains('is-active')) {
                        breaking.classList.add('is-active');
                    }

                    let transitionFinished = false;
                    let breakingSpeechFinished = !speechEnabled;

                    // दोनों (एनिमेशन और ब्रेकिंग आवाज़) पूरे होने पर अगली न्यूज़ दिखाना
                    const proceedToNextArticle = () => {
                        if (!transitionFinished || !breakingSpeechFinished) return;

                        breaking.hidden = true;
                        breaking.classList.remove('is-active');
                        currentIndex = nextIndex;
                        articles.forEach((article, articleIndex) => {
                            const isCurrent = (articleIndex === currentIndex);
                            article.hidden = !isCurrent;
                            article.setAttribute('aria-hidden', String(!isCurrent));
                            article.classList.toggle('is-active', isCurrent);
                        });
                        indicators.forEach((indicator, indicatorIndex) => {
                            const isActive = indicatorIndex === currentIndex;
                            indicator.classList.toggle('is-active', isActive);
                            indicator.setAttribute('aria-current', String(isActive));
                        });

                        // बैकग्राउंड म्यूजिक लगातार बजता रहेगा
                        ensureAudioPlaying();

                        // अगली खबर पढ़कर सुनाना
                        if (speechEnabled) {
                            speakCurrentArticle();
                        }
                        startTimer();
                    };

                    // यदि खबर पढ़कर सुनाना ऑन है, तो ब्रेकिंग न्यूज़ का ऐलान करें
                    if (speechEnabled) {
                        speakBreakingTransition(() => {
                            breakingSpeechFinished = true;
                            proceedToNextArticle();
                        });
                    }

                    transitionTimer = window.setTimeout(() => {
                        transitionFinished = true;
                        proceedToNextArticle();
                    }, breakingTime);
                };

                indicators.forEach((indicator) => {
                    indicator.addEventListener('click', () => {
                        showArticle(Number(indicator.dataset.newsIndicator));
                    });
                });

                speechToggle.addEventListener('click', () => {
                    if (!speechSupported) {
                        speechToggle.setAttribute('aria-label', 'इस ब्राउज़र में खबर पढ़ने की सुविधा उपलब्ध नहीं है');
                        return;
                    }

                    speechEnabled = !speechEnabled;
                    speechToggle.setAttribute('aria-pressed', String(speechEnabled));
                    speechToggle.setAttribute('aria-label', speechEnabled ? 'पढ़ना बंद करें' : 'खबर पढ़कर सुनाएँ');
                    speechIcon.textContent = speechEnabled ? '⏹' : '🔊';

                    if (speechEnabled) {
                        ensureAudioPlaying();
                        speakCurrentArticle();
                    } else {
                        window.speechSynthesis.cancel();
                        restoreBackgroundSound();
                        startTimer();
                    }
                });

                // बैकग्राउंड साउंड बटन क्लिक हैंडलर: सीधे वास्तविक प्ले/पॉज़ से नियंत्रित
                if (soundToggle) {
                    soundToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (!sound) return;

                        if (sound.paused) {
                            soundEnabled = true;
                            sound.volume = BACKGROUND_NEWS_SOUND_VOLUME;
                            sound.play().then(() => syncSoundUI()).catch(() => {});
                        } else {
                            soundEnabled = false;
                            sound.pause();
                            syncSoundUI();
                        }
                    });
                }

                if (!speechSupported) {
                    speechToggle.disabled = true;
                    speechToggle.setAttribute('aria-label', 'इस ब्राउज़र में खबर पढ़ने की सुविधा उपलब्ध नहीं है');
                }

                // बैकग्राउंड टैब होने पर साउंड म्यूट और वापस आने पर चालू करना
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        stopBackgroundSound();
                        window.speechSynthesis?.cancel();
                    } else {
                        restoreBackgroundSound();
                        if (soundEnabled && sound) {
                            playBackgroundSound();
                        }
                        if (speechEnabled) {
                            speakCurrentArticle();
                        }
                    }
                });

                reader.addEventListener('pointerenter', () => {
                    pointerInside = true;
                    window.clearInterval(timer);
                });
                reader.addEventListener('pointerleave', () => {
                    pointerInside = false;
                    if (speechEnabled && !window.speechSynthesis.speaking && articles.length > 1) {
                        timer = window.setTimeout(() => showArticle(currentIndex + 1), 1000);
                    } else {
                        startTimer();
                    }
                });

                // शुरुआत में बैकग्राउंड साउंड डिफ़ॉल्ट रूप से प्ले करने का प्रयास
                playBackgroundSound();

                // सबसे पहले 4 सेकंड का ब्रेकिंग न्यूज़ एनीमेशन चलेगा, फिर पहली न्यूज़ आएगी
                if (articles.length > 0) {
                    showArticle(0, true);
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeNewsReader, { once: true });
            } else {
                initializeNewsReader();
            }
        })();
    </script>
@endif
