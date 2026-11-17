<!-- ===== Msg Alert: Quiz Limit & Activity Alert Component ===== -->
@php
    $authUser = \Illuminate\Support\Facades\Auth::user();
    $userId = $authUser?->id;
    $userLimit = (int) ($authUser?->available_quiz_limit ?? 0);
    $targetUrl = Route::has('fsrapp.survey.index') ? route('fsrapp.survey.index') : 'javascript:void(0);';

    // Fetch Last Played Quiz Reward
    $lastQuizPts = 0;
    if ($userId) {
        $lastWinner = \App\Models\QuizWinner::where('user_id', $userId)->latest('id')->first();
        if ($lastWinner && $lastWinner->reward > 0) {
            $lastQuizPts = (int) $lastWinner->reward;
        }
    }

    // Fetch Last Completed Survey Rewards
    $lastSurveyPts = 0;
    $lastSurveyQuizLimit = 0;
    if ($userId) {
        $lastReward = \Illuminate\Support\Facades\DB::table('survey_rewards')
            ->where('user_id', $userId)
            ->latest('id')
            ->first();
        if ($lastReward) {
            $lastSurveyPts = (int) ($lastReward->point ?? 0);
            $lastSurveyQuizLimit = (int) ($lastReward->quiz ?? 0);
        }
    }
@endphp

<a id="fsr-limit-alert-card" href="{{ $targetUrl }}"
    class="fsr-animated-alert-bg block mb-3.5 p-3.5 rounded-2xl border-0 relative overflow-hidden transition-all cursor-pointer shadow-lg group min-h-[66px]">
    
    <!-- Ambient background highlight light -->
    <div class="absolute -right-8 -top-8 w-28 h-28 bg-[#f43f5e]/20 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-[#10b981]/20 rounded-full blur-2xl pointer-events-none"></div>

    <div class="flex items-center gap-3.5 relative z-10">
        <!-- Left Notification Icon with vibrant multi-tint glow -->
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#ffd76a]/30 via-[#34d399]/25 to-[#f43f5e]/30 border border-[#ffd76a]/60 flex items-center justify-center shrink-0 shadow-[0_0_12px_rgba(255,215,106,0.35)] group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-[20px] text-[#ffe9b0] animate-pulse drop-shadow-[0_0_8px_rgba(255,215,106,0.8)]">notifications_active</span>
        </div>

        <!-- Animated Text Slider Content -->
        <div class="flex-1 min-w-0 relative overflow-hidden" style="height: 36px;">
            <div id="fsr-alert-text-container" class="fsr-alert-slide-text anim-bounce-in absolute inset-0 flex flex-col justify-center">
                <p id="fsr-alert-title" class="text-[13px] font-extrabold text-[#ffe9b0] truncate drop-shadow-[0_0_8px_rgba(255,233,176,0.3)]">
                    @if ($userLimit <= 0)
                        {{ __('app.daily_limit_reached') }}
                    @elseif ($userLimit <= 2)
                        {{ __('app.low_quiz_limit') }}
                    @elseif ($lastQuizPts > 0)
                        {{ __('app.last_quiz_reward') }}
                    @else
                        {{ __('app.earn_extra_rewards') }}
                    @endif
                </p>
                <p id="fsr-alert-desc" class="text-[11.5px] font-semibold text-white/90 truncate">
                    @if ($userLimit <= 0)
                        {{ __('app.daily_limit_reached_desc') }}
                    @elseif ($userLimit <= 2)
                        {{ __('app.low_quiz_limit_desc', ['limit' => $userLimit]) }}
                    @elseif ($lastQuizPts > 0)
                        {{ __('app.last_quiz_reward_desc', ['points' => $lastQuizPts]) }}
                    @else
                        {{ __('app.earn_extra_rewards_desc') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</a>

@if ($userLimit <= 2)
    <div class="mb-3.5 text-center leading-tight">
        <a href="{{ $targetUrl }}" class="inline-block text-[#ef4444] text-[13px] font-bold animate-pulse hover:no-underline">
            @if ($userLimit <= 0)
                ⚠️ Daily Quiz Limit Reached!<br>
                <span class="text-[11.5px] text-[#fca5a5] font-semibold underline decoration-[#fca5a5] underline-offset-2">Complete surveys to earn more plays.</span>
            @else
                ⚠️ Low Quiz Limit: Only {{ $userLimit }} play(s) left!<br>
                <span class="text-[11.5px] text-[#fca5a5] font-semibold underline decoration-[#fca5a5] underline-offset-2">Click to earn more.</span>
            @endif
        </a>
    </div>
@endif

<script>
    (function() {
        const messages = [];

        // Message 1: Limit Status Alert (ONLY included in slider if limit <= 2)
        @if ($userLimit <= 0)
            messages.push({
                title: "Daily Quiz Limit Reached 🎯",
                desc: "Complete surveys to earn more quiz plays.",
                url: "{{ $targetUrl }}"
            });
        @elseif ($userLimit <= 2)
            messages.push({
                title: "Low Quiz Play Limit ⚠️",
                desc: "Only {{ $userLimit }} quiz play(s) left! Complete surveys to add more.",
                url: "{{ $targetUrl }}"
            });
        @endif

        // Message 2: Last Quiz Played Rewards
        @if ($lastQuizPts > 0)
            messages.push({
                title: "Last Quiz Played Reward 🏆",
                desc: "You won {{ $lastQuizPts }} PTS in your last quiz!",
                url: "{{ $targetUrl }}"
            });
        @endif

        // Message 3: Last Survey Filled Rewards
        @if ($lastSurveyQuizLimit > 0 && $lastSurveyPts > 0)
            messages.push({
                title: "Last Survey Reward 🌟",
                desc: "You earned +{{ $lastSurveyQuizLimit }} Quiz Limit & +{{ $lastSurveyPts }} PTS!",
                url: "{{ $targetUrl }}"
            });
        @elseif ($lastSurveyQuizLimit > 0)
            messages.push({
                title: "Last Survey Reward ⚡",
                desc: "You unlocked +{{ $lastSurveyQuizLimit }} Quiz Play Limit in your last survey!",
                url: "{{ $targetUrl }}"
            });
        @elseif ($lastSurveyPts > 0)
            messages.push({
                title: "Last Survey Reward 🪙",
                desc: "You earned +{{ $lastSurveyPts }} PTS in your last survey!",
                url: "{{ $targetUrl }}"
            });
        @else
            messages.push({
                title: "Earn Extra Rewards 🎁",
                desc: "Fill quick surveys to earn bonus quiz plays & points!",
                url: "{{ $targetUrl }}"
            });
        @endif

        let currentIndex = 0;
        let animIndex = 0;

        const animationPresets = [
            { out: 'anim-bounce-out', prep: 'anim-bounce-prep', in: 'anim-bounce-in' },
            { out: 'anim-flip-out', prep: 'anim-flip-prep', in: 'anim-flip-in' },
            { out: 'anim-slide-out', prep: 'anim-slide-prep', in: 'anim-slide-in' },
            { out: 'anim-zoom-out', prep: 'anim-zoom-prep', in: 'anim-zoom-in' }
        ];

        const container = document.getElementById('fsr-alert-text-container');
        const titleElem = document.getElementById('fsr-alert-title');
        const descElem = document.getElementById('fsr-alert-desc');
        const cardElem = document.getElementById('fsr-limit-alert-card');

        const baseClass = 'fsr-alert-slide-text absolute inset-0 flex flex-col justify-center ';

        if (container && titleElem && descElem && messages.length > 1) {
            setInterval(() => {
                // Select next animation style preset
                const anim = animationPresets[animIndex % animationPresets.length];
                animIndex++;

                // Phase 1: Exit Animation
                container.className = baseClass + anim.out;

                setTimeout(() => {
                    // Update text content
                    currentIndex = (currentIndex + 1) % messages.length;
                    const msg = messages[currentIndex];
                    titleElem.textContent = msg.title;
                    descElem.textContent = msg.desc;
                    if (cardElem && msg.url) {
                        cardElem.setAttribute('href', msg.url);
                    }

                    // Phase 2: Instant Prep
                    container.className = baseClass + anim.prep;

                    // Phase 3: Entrance Animation
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            container.className = baseClass + anim.in;
                        });
                    });
                }, 350);
            }, 3800);
        }
    })();
</script>
