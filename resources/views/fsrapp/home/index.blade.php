@extends('fsrapp.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('fsrapp/css/home.css') }}">
@endpush

@section('content')
    @include('fsrapp.layouts.header')

    <main class="max-w-md mx-auto px-4 pt-20 pb-24 min-h-screen">


       
        <!-- ===== Msg Alert: Quiz Limit Alert Component ===== -->
        @include('fsrapp.home.components.limit-alert')

        

        <!-- ===== Whatsup: user whatsup subscribe Component ===== -->
        @include('fsrapp.home.components.user-whatsapp-subscribe')

        <!-- ===== WhatsApp Channel Subscription Popup Modal ===== -->
        @include('fsrapp.home.components.whatsapp-subscribe-modal')

        <!-- ===== Last Quiz Leaderboard Component ===== -->
        @include('fsrapp.home.components.last-quiz-leaderboard')

        <!-- ===== Hero: Today's Quiz Component ===== -->
        @include('fsrapp.home.components.today-quiz')

        <!-- Latest Bullet News Slider/Alert -->
         @include('fsrapp.home.components.bullet-news-slider') 

        <!-- ===== Hero: joinpopup Component ===== -->
        @include('fsrapp.home.components.joinpopup', ['upcomingQuizzes' => $todayQuizzes ?? []])
        <!-- ===== WhatsApp Notification Component ===== -->
        {{-- @include('fsrapp.home.components.whatsapp-notify') --}}

         <!-- ===== Application : apk download card ===== -->
        @include('fsrapp.home.components.apk-download-card')
        <!-- ===== How FSRQuiz Works ===== -->
        @include('fsrapp.home.components.how-fsrquiz-works')


        <!-- ===== Announcement Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-announce">
            @include('fsrapp.home.components.announcement')
        </div>

        <!-- ===== Referral : WhatsApp code Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-referral">
            @include('fsrapp.home.components.referral-code-whatsapp')
        </div>

        <!-- ===== Surveys Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-surveys">
            @include('fsrapp.home.components.surveys')
        </div>

        <!-- ===== Feedback : user feedback Quiz Component ===== -->
        @include('fsrapp.home.components.quiz-user-feedback-modal')

        <!-- ===== Practice Quiz Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-practice">
            @include('fsrapp.home.components.practice-quiz')
        </div>

        <!-- ===== profile update : quiz_users Component ===== -->
        @include('fsrapp.home.components.quiz-users-profile-update')

        <!-- ===== User Account Stats Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-stats">
            @include('fsrapp.home.components.users_account_info')
        </div>

        <!-- ===== Banners Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-banners">
            @include('fsrapp.home.components.banners', ['banners' => $banners, 'pageType' => 'home'])
        </div>

        <!-- ===== Quiz History Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-history">
            @include('fsrapp.home.components.quiz-history')
        </div>

        <!-- ===== Last Quiz History Reward Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-winners">
            @include('fsrapp.home.components.last-quiz-history-reward')
        </div>

        <!-- ===== Top Quiz Players Component (Lazy) ===== -->
        <div class="fsr-lazy-section lazy-top-players">
            @include('fsrapp.home.components.top-quiz-players')
        </div>

        <!-- ===== Welcome & Banner Component ===== -->

    </main>

    @include('fsrapp.layouts.bottommenu')

    @push('scripts')
        <script>
            window.userQuizLimit = {{ (int) (\Illuminate\Support\Facades\Auth::user()?->available_quiz_limit ?? 0) }};

            document.addEventListener('DOMContentLoaded', function() {
                // Prevent browser back-button from reviving previous quiz screen
                try {
                    history.pushState(null, document.title, location.href);
                    window.addEventListener('popstate', function(e) {
                        history.pushState(null, document.title, location.href);
                    });
                } catch (e) {}

                // Flush any pending mark-out requests from offline exit to server
                (function syncPendingQuizMarkouts() {
                    try {
                        const pendingKeys = [];
                        for (let i = 0; i < localStorage.length; i++) {
                            const k = localStorage.key(i);
                            if (k && k.startsWith('fsr_pending_mark_out_')) {
                                pendingKeys.push(k);
                            }
                        }
                        pendingKeys.forEach(k => {
                            const data = localStorage.getItem(k);
                            if (data) {
                                const parsed = JSON.parse(data);
                                const currentUserId = '{{ Auth::id() ?? (session('fsr_user_id') ?? 0) }}';
                                fetch(`/fsrapp/quizes/api/mark-out/${parsed.quiz_id}`, {
                                    method: 'POST',
                                    keepalive: true,
                                    credentials: 'same-origin',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken || '',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        reason: parsed.reason || 'Quiz Exit',
                                        quiz_id: parsed.quiz_id,
                                        user_id: currentUserId,
                                        _token: csrfToken
                                    })
                                }).then(() => {
                                    localStorage.removeItem(k);
                                }).catch(() => {});
                            }
                        });
                    } catch (e) {}
                })();



                // Global Server-Synced Timers (Cached DOM elements for 0ms interaction overhead)
                let serverTimeSecs = {{ time() }};
                let clientInitTimeSecs = Math.floor(Date.now() / 1000);
                let timeOffset = serverTimeSecs - clientInitTimeSecs;

                const quizCardItems = Array.from(document.querySelectorAll('.quiz-card-item')).map(box => {
                    const quizId = box.getAttribute('data-quiz-id');
                    return {
                        box,
                        quizId,
                        startTime: parseInt(box.getAttribute('data-time')) || 0,
                        timer: document.getElementById('timer-val-' + quizId),
                        playBtn: document.getElementById('play-btn-' + quizId),
                        playBtnText: document.getElementById('play-btn-text-' + quizId),
                        heroBox: document.getElementById('hero-quiz-' + quizId),
                        statusText: document.getElementById('status-text-' + quizId),
                        statusDot: document.getElementById('status-dot-' + quizId),
                        countdownRing: document.getElementById('timer-badge-' + quizId) || box.querySelector(
                            '.fsr-countdown-ring')
                    };
                });

                function updateTimers() {
                    if (document.hidden) return;
                    const now = Math.floor(Date.now() / 1000) + timeOffset;

                    quizCardItems.forEach(item => {
                        if (!item.startTime) return;
                        const diff = item.startTime - now;

                        // Register with global upcoming monitor
                        if (window.registerUpcomingQuizForOneMinAlert) {
                            window.registerUpcomingQuizForOneMinAlert(item.quizId, item.startTime);
                        }

                        // Auto-play 1 Minute Before Quiz Start Alert (Strictly when crossing 1-min mark: 58s-61s)
                        if (diff >= 58 && diff <= 61) {
                            if (!window.hasPlayedOneMinSoundForQuiz || !window.hasPlayedOneMinSoundForQuiz(item
                                    .quizId)) {
                                item._hasPlayedOneMinBefore = true;
                                if (typeof window.playOneMinBeforeSound === 'function') {
                                    window.playOneMinBeforeSound(item.quizId);
                                }
                            }
                        } else if (diff < 58) {
                            // If time is already less than 1 minute, mark as passed so it won't play late
                            if (window.markOneMinSoundPlayedForQuiz) {
                                window.markOneMinSoundPlayedForQuiz(item.quizId);
                            }
                        }

                        // Timer text update
                        if (diff <= 0) {
                            if (item.timer) {
                                if (item.countdownRing) {
                                    item.countdownRing.style.visibility = 'hidden';
                                }
                                item.timer.innerText = "00:00";
                            }
                        } else {
                            if (item.timer) {
                                let days = Math.floor(diff / 86400);
                                let hours = Math.floor((diff % 86400) / 3600);
                                let mins = Math.floor((diff % 3600) / 60);
                                let secs = diff % 60;

                                let timeStr = "";
                                if (days > 0) timeStr += days + "d ";
                                if (days > 0 || hours > 0) timeStr += (hours < 10 ? "0" + hours : hours) + ":";
                                timeStr += (mins < 10 ? "0" + mins : mins) + ":" + (secs < 10 ? "0" + secs :
                                    secs);

                                if (item.timer.innerText !== timeStr) {
                                    item.timer.innerText = timeStr;
                                }
                            }
                        }

                        // Dynamic state update logic based on time
                        if (diff <= 0) {
                            const isJoined = item.playBtn && item.playBtn.getAttribute('data-joined') ===
                                'true';

                            if (isJoined) {
                                // User has joined: Keep "Play Quiz" link active
                                if (item.playBtn && item.playBtnText && !['Play Quiz', 'Open Quiz'].includes(
                                        item.playBtnText.innerText)) {
                                    item.playBtnText.innerText = "Play Quiz";
                                    const icon = item.playBtn.querySelector('.material-symbols-outlined');
                                    if (icon) {
                                        icon.innerText = "play_arrow";
                                        icon.className = "material-symbols-outlined text-[16px] play-icon-" +
                                            item.quizId;
                                    }
                                }
                            } else {
                                // User has NOT joined: Show "Processing..."
                                if (item.playBtn && item.playBtnText && item.playBtnText.innerText !==
                                    "Processing...") {
                                    if (item.playBtn.tagName === 'A') {
                                        item.playBtn.removeAttribute('href');
                                        item.playBtn.style.pointerEvents = 'none';
                                    }
                                    item.playBtn.disabled = true;
                                    item.playBtn.className =
                                        "fsr-play-btn flex-1 py-2 rounded-full font-display font-bold text-[13px] flex items-center justify-center gap-1.5 opacity-50 cursor-not-allowed";
                                    item.playBtnText.innerText = "Processing...";
                                    const icon = item.playBtn.querySelector('.material-symbols-outlined');
                                    if (icon) {
                                        icon.innerText = "sync";
                                        icon.className = "material-symbols-outlined text-[16px] animate-spin";
                                    }
                                    if (item.heroBox) item.heroBox.onclick = null;
                                }
                            }

                            if (item.statusText && item.statusText.innerText !== "STARTED") {
                                item.statusText.innerText = "STARTED";
                                item.statusText.style.color = "#fca5a5";
                            }
                            if (item.statusDot) {
                                item.statusDot.style.background = "#ef4444";
                                item.statusDot.style.boxShadow = "0 0 8px #ef4444";
                            }
                        } else if (diff <= 900) {
                            // Play Now / Join window (<= 15 mins)
                            const notifyContainer = document.getElementById('notify-container-' + item.quizId);
                            const playContainer = document.getElementById('play-container-' + item.quizId);
                            if (notifyContainer && playContainer) {
                                if (!notifyContainer.classList.contains('hidden')) {
                                    notifyContainer.classList.add('hidden');
                                }
                                if (playContainer.classList.contains('hidden')) {
                                    playContainer.classList.remove('hidden');
                                    // Update cached element references
                                    item.playBtn = document.getElementById('play-btn-' + item.quizId);
                                    item.playBtnText = document.getElementById('play-btn-text-' + item.quizId);
                                }
                            }
                        }
                    });
                }

                if (quizCardItems.length > 0) {
                    updateTimers();
                    setInterval(updateTimers, 1000);

                    let isSyncing = false;
                    async function syncQuizTimes() {
                        if (document.hidden || isSyncing || quizCardItems.length === 0) return;
                        isSyncing = true;
                        try {
                            const ids = quizCardItems.map(item => item.quizId);
                            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                            const res = await fetch('/fsrapp/home/quiz-times', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf || '',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    quiz_ids: ids,
                                    _token: csrf
                                })
                            });
                            const result = await res.json();
                            if (result && result.success && result.data) {
                                if (result.server_time) {
                                    serverTimeSecs = result.server_time;
                                    clientInitTimeSecs = Math.floor(Date.now() / 1000);
                                    timeOffset = serverTimeSecs - clientInitTimeSecs;
                                }

                                quizCardItems.forEach(item => {
                                    const newData = result.data[item.quizId];
                                    if (newData && newData.start_time) {
                                        if (item.startTime !== newData.start_time) {
                                            item.startTime = newData.start_time;
                                            item.box.setAttribute('data-time', newData.start_time);

                                            const timeSpan = document.getElementById('quiz-start-time-' +
                                                item.quizId);
                                            if (timeSpan) {
                                                const dt = new Date(newData.start_time * 1000);
                                                let h = dt.getHours();
                                                const ampm = h >= 12 ? 'PM' : 'AM';
                                                h = h % 12;
                                                h = h ? h : 12; // hour '0' should be '12'
                                                const m = dt.getMinutes().toString().padStart(2, '0');
                                                timeSpan.innerText = ' | ' + h.toString().padStart(2, '0') +
                                                    ':' + m + ' ' + ampm;
                                            }
                                        }

                                        if (newData.complete_status === 'completed' || newData.status ===
                                            'completed') {
                                            if (item.statusText && item.statusText.innerText !==
                                                "COMPLETED") {
                                                item.statusText.innerText = "COMPLETED";
                                                item.statusText.style.color = "#9ca3af";
                                            }
                                            if (item.statusDot) {
                                                item.statusDot.style.background = "#6b7280";
                                                item.statusDot.style.boxShadow = "none";
                                            }
                                        }
                                    }
                                });
                                updateTimers();
                            }
                        } catch (e) {} finally {
                            isSyncing = false;
                        }
                    }

                    setInterval(syncQuizTimes, 60000); // Sync with backend every 60 seconds (optimized for 50k concurrent users)
                }

                window.addEventListener("pageshow", function(event) {
                    if (event.persisted) {
                        window.location.reload();
                    }
                });
            });
        </script>
    @endpush
@endsection
