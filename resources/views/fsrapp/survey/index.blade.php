@extends('fsrapp.layouts.app')

@section('content')
    <style>
        header.fsr-header {
            background: rgba(13, 6, 32, 0.75) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border-glass, rgba(255, 255, 255, 0.1));
        }
    </style>

    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
        <div class="glow-orb o4"></div>
    </div>

    <!-- ===================== HEADER ===================== -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-lg font-extrabold tracking-tight leading-none" style="color:#e8ddff;">
                        {{ __('app.surveys_title') }}</h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">{{ __('app.survey_subtitle') }}</p>
                </div>
            </div>
            {{-- <div class="fsr-wallet-pill">
                <div class="fsr-wallet-icon kbc-hex"><span class="material-symbols-outlined">monetization_on</span></div>
                <span class="fsr-wallet-points">{{ number_format($user->wallet_balance ?? 0) }}</span>
            </div> --}}
        </div>
    </header>

    <!-- ===================== MAIN ===================== -->
    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen">

        <!-- ===== Play Quiz CTA banner ===== -->
        <div class="fsr-hero p-5 mb-5">
            <div class="fsr-ribbon">🎁 EARN MORE</div>
            <div class="fsr-coin-field">
                <span class="fsr-coin" style="left:12%; animation-delay:0s;">🪙</span>
                <span class="fsr-coin" style="left:40%; animation-delay:1.6s; font-size:12px;">🪙</span>
                <span class="fsr-coin" style="left:68%; animation-delay:3.2s;">🪙</span>
            </div>
            <span class="fsr-sparkle" style="top:10px; left:34%;">✨</span>
            <span class="fsr-sparkle" style="bottom:16px; left:8%; animation-delay:.8s;">✨</span>
            <div class="flex items-center gap-1.5 mb-3 relative z-10">
                <span class="w-1.5 h-1.5 rounded-full" style="background:#4ade80; box-shadow:0 0 6px #4ade80;"></span>
                <span class="text-[11px] font-bold tracking-wide" style="color:#86efac;">{{ strtoupper(__('app.surveys_title')) }} + {{ strtoupper(__('app.play_quiz')) }} = DOUBLE
                    REWARDS</span>
            </div>
            <div class="flex items-center justify-between gap-3 relative z-10">
                <div>
                    <h2 class="font-display text-xl font-extrabold leading-tight" style="color:#e8ddff;">{{ __('app.survey_banner_title') }}</h2>
                    <p class="text-[12px] mt-1.5" style="color:rgba(232,221,255,0.6);">{{ __('app.survey_banner_desc') }}</p>
                </div>
            </div>
            <a href="{{ route('fsrapp.home') }}"
                class="fsr-play-btn w-full mt-4 py-3 rounded-xl font-display font-bold text-[15px] flex items-center justify-center gap-2 relative z-10 cursor-pointer">
                <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                {{ __('app.play_quiz') }}
            </a>
        </div>

        <!-- ===== Stats ===== -->
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="fsr-survey-stat">
                <p class="font-display text-lg font-extrabold leading-none" style="color:#e8ddff;">
                    {{ number_format($surveysDone ?? 0) }}</p>
                <p class="text-[10px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.surveys_done') }}</p>
            </div>
            <div class="fsr-survey-stat">
                <p class="font-display text-lg font-extrabold leading-none" style="color:#ffe9b0;">
                    {{ number_format($surveyPoints ?? 0) }}</p>
                <p class="text-[10px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.points_earned') }}</p>
            </div>
            <div class="fsr-survey-stat">
                <p class="font-display text-lg font-extrabold leading-none" style="color:#e8ddff;">
                    {{ number_format($quizzesPlayed ?? 0) }}</p>
                <p class="text-[10px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.quizzes_earned') }}</p>
            </div>
        </div>

        <!-- ===== Filter tabs ===== -->
        <div class="fsr-tab-row mb-5">
            <div class="fsr-tab active" onclick="filterSurveys('all', this)">{{ __('app.all') }}</div>
            <div class="fsr-tab" onclick="filterSurveys('new', this)">{{ __('app.new') }}</div>
            <div class="fsr-tab" onclick="filterSurveys('in_progress', this)">{{ __('app.in_progress') }}</div>
            <div class="fsr-tab" onclick="filterSurveys('completed', this)">{{ __('app.completed') }}</div>
        </div>

        <!-- ===== Survey list ===== -->
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.available_surveys') }}</h2>
            <span class="text-[12px] font-semibold" id="survey-count-text"
                style="color:#ffd76a;">{{ $surveys ? $surveys->count() : 0 }} {{ strtolower(__('app.surveys_title')) }}</span>
        </div>
        
        <div class="flex flex-col gap-2.5 mb-6" id="surveys-list">
            @if ($surveys && $surveys->count() > 0)
                @foreach ($surveys as $survey)
                    @php
                        $isCompleted = in_array($survey->id, $completedSurveyIds ?? []);
                        $isStarted = in_array($survey->id, $startedSurveyIds ?? []);

                        $filterTag = 'new';
                        $btnText = __('app.start');
                        $btnClass = 'fsr-survey-btn';

                        if ($isCompleted) {
                            $filterTag = 'completed';
                            $btnText = __('app.completed');
                        } elseif ($isStarted) {
                            $filterTag = 'in_progress';
                            $btnText = __('app.resume');
                        }

                        $surveyUrl = route('fsrapp.survey.play', $survey->id);

                        $iconGradients = [
                            'linear-gradient(160deg, rgba(96,165,250,0.3), rgba(96,165,250,0.08))',
                            'linear-gradient(160deg, rgba(74,222,128,0.3), rgba(74,222,128,0.08))',
                            'linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08))',
                            'linear-gradient(160deg, rgba(217,70,239,0.3), rgba(217,70,239,0.08))',
                            'linear-gradient(160deg, rgba(168,85,247,0.3), rgba(168,85,247,0.08))',
                            'linear-gradient(160deg, rgba(56,189,248,0.3), rgba(56,189,248,0.08))',
                        ];
                        $iconColors = ['#93c5fd', '#86efac', '#ffd76a', '#f0abfc', '#d8b4fe', '#7dd3fc'];
                        $icons = ['fact_check', 'poll', 'movie_filter', 'restaurant', 'smartphone', 'travel_explore'];

                        $bgGrad = $iconGradients[$loop->index % count($iconGradients)];
                        $icColor = $iconColors[$loop->index % count($iconColors)];
                        $iconName = $icons[$loop->index % count($icons)];

                        $rewardText = ($survey->reward_type ?? '') === 'quiz' || ($survey->quiz && $survey->quiz > 0)
                            ? '+' . ($survey->quiz ?? 1) . ' Quiz'
                            : '+' . ($survey->point ?? 50) . ' pts';
                    @endphp

                    <div class="fsr-survey-card survey-item-card {{ !$isCompleted ? 'cursor-pointer' : '' }}"
                        data-status="{{ $filterTag }}" data-survey-url="{{ $surveyUrl }}"
                        style="{{ $isCompleted ? 'opacity:0.6;' : '' }}" {!! !$isCompleted ? "onclick=\"window.handleSurveyClick(this)\"" : '' !!}>
                        <div class="fsr-survey-icon" style="background:{{ $bgGrad }};">
                            <span class="material-symbols-outlined text-[18px]"
                                style="color:{{ $icColor }};">{{ $iconName }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold truncate" style="color:#e8ddff;">
                                {{ $survey->name ?? ($survey->title ?? 'Survey') }}</p>
                            <p class="text-[11px] mt-0.5" style="color:rgba(232,221,255,0.5);">
                                @if($isCompleted) Completed · @endif {{ $rewardText }}
                            </p>
                        </div>
                        @if ($isCompleted)
                            <span class="material-symbols-outlined text-[20px]" style="color:#4ade80;">check_circle</span>
                        @else
                            <button type="button" class="fsr-survey-btn">{{ $btnText }}</button>
                        @endif
                    </div>
                @endforeach
            @endif

            <div id="no-surveys-found" class="{{ ($surveys && $surveys->count() > 0) ? 'hidden' : '' }} text-center py-8 text-white/50 text-sm font-semibold glass-card">
                {{ __('app.no_surveys_found') }}
            </div>
        </div>

        <!-- ===== Bottom note ===== -->
        <div class="glass-card p-4 flex items-center gap-3 mb-4">
            <span class="material-symbols-outlined text-[22px]" style="color:#ffd76a;">emoji_events</span>
            <p class="text-[11.5px] leading-snug" style="color:rgba(232,221,255,0.7);">{{ __('app.bonus_unlock') }}</p>
        </div>

    </main>

    <script>
        if (typeof window.handleSurveyClick === 'undefined') {
            window.handleSurveyClick = function(element) {
                const url = element.getAttribute('data-survey-url');
                if (url) {
                    window.location.href = url;
                }
            };
        }

        function filterSurveys(filter, tabEl) {
            document.querySelectorAll('.fsr-tab').forEach(t => t.classList.remove('active'));
            if (tabEl) tabEl.classList.add('active');

            const cards = document.querySelectorAll('.survey-item-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const countText = document.getElementById('survey-count-text');
            if (countText) {
                countText.innerText = visibleCount + ' survey' + (visibleCount === 1 ? '' : 's');
            }

            const noSurveysEl = document.getElementById('no-surveys-found');
            if (noSurveysEl) {
                if (visibleCount === 0) {
                    noSurveysEl.classList.remove('hidden');
                } else {
                    noSurveysEl.classList.add('hidden');
                }
            }
        }
    </script>
@endsection
