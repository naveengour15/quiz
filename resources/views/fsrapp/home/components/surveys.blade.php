<!-- ===== Surveys Component ===== -->
@php
    $uncompletedSurveys = isset($surveys) ? $surveys->reject(function($survey) use ($completedSurveyIds) {
        return in_array($survey->id, $completedSurveyIds ?? []);
    }) : collect();
@endphp

@if ($uncompletedSurveys->count() > 0)
    <div id="fsr-surveys-container" class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.surveys') }}</h2>
            <a href="{{ route('fsrapp.survey.index') }}" class="text-[12px] font-semibold flex items-center gap-1"
                style="color:#e879f9;">
                {{ __('app.see_all') }}
            </a>
        </div>

        <!-- ===== Info Banner: Why Complete Surveys (Animated Text & Icon) ===== -->
        <div class="glass-card p-3 mb-3 rounded-xl relative overflow-hidden fsr-info-banner-card">
            <div class="absolute -left-8 -top-8 w-24 h-24 bg-[#ffd76a]/15 rounded-full blur-xl pointer-events-none">
            </div>

            <div class="flex items-center gap-2.5 relative z-10">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 fsr-info-icon-pulse"
                    style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);">
                    <span class="material-symbols-outlined text-[17px]" style="color:#1a0b3d;">bolt</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display text-[13px] font-extrabold leading-tight fsr-info-shimmer-text">
                        {{ __('app.increase_quiz_limit') }}
                    </h3>
                    <p class="text-[10.5px] mt-0.5 leading-snug truncate" style="color:rgba(232,221,255,0.85);">
                        {{ __('app.increase_quiz_limit_desc') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Horizontal Scroll Row for Surveys (Matching survey/index.blade.php design) -->
        <div class="fsr-survey-scroll-row">
            @foreach ($uncompletedSurveys as $survey)
                @php
                    $isStarted = in_array($survey->id, $startedSurveyIds ?? []);

                    $btnText = __('app.start');
                    $btnClass = 'fsr-survey-btn';

                    if ($isStarted) {
                        $btnText = __('app.resume');
                        $btnClass = 'fsr-survey-btn resume';
                    }

                    $surveyUrl = route('fsrapp.survey.play', $survey->id);
                    $rewardText =
                        ($survey->reward_type ?? '') === 'quiz' || ($survey->quiz && $survey->quiz > 0)
                            ? '+' . ($survey->quiz ?? 1) . ' Quiz'
                            : '+' . ($survey->point ?? 50) . ' pts';

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
                @endphp

                <div class="fsr-survey-card fsr-survey-scroll-card cursor-pointer"
                    data-survey-id="{{ $survey->id }}" data-survey-url="{{ $surveyUrl }}"
                    onclick="window.handleSurveyClick(this)">
                    <div class="fsr-survey-icon"
                        style="background:{{ $bgGrad }}; border: 1px solid rgba(147, 197, 253, 0.25);">
                        <span class="material-symbols-outlined text-[18px]"
                            style="color:{{ $icColor }};">{{ $iconName }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold truncate" style="color:#e8ddff;">
                            {{ $survey->name ?? ($survey->title ?? 'Survey') }}</p>
                        <p class="text-[11px] mt-0.5 truncate" style="color:rgba(232,221,255,0.5);">
                            {{ $rewardText }}
                        </p>
                    </div>
                    <button type="button" class="{{ $btnClass }}">{{ $btnText }}</button>
                </div>
            @endforeach
        </div>
    </div>
@endif

<script>
    if (typeof window.handleSurveyClick === 'undefined') {
        window.handleSurveyClick = function(element) {
            const url = element.getAttribute('data-survey-url');
            if (url) {
                window.location.href = url;
            }
        };
    }
</script>
