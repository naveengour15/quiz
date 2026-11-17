<!-- ===== Quiz History Component ===== -->
<div class="flex items-center justify-between mb-3">
    <h2 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.quiz_history') }}</h2>
    <a href="{{ route('fsrapp.history') }}" class="text-[12px] font-semibold cursor-pointer" style="color:#e879f9;">{{ __('app.see_all') }}</a>
</div>

<!-- ===== Info Banner: Quiz History (Animated Text & Icon) ===== -->
<div class="glass-card p-3 mb-3 rounded-xl relative overflow-hidden fsr-info-banner-card">
    <div class="absolute -left-8 -top-8 w-24 h-24 bg-[#ffd76a]/15 rounded-full blur-xl pointer-events-none"></div>

    <div class="flex items-center gap-2.5 relative z-10">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 fsr-info-icon-pulse"
            style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);">
            <span class="material-symbols-outlined text-[17px]" style="color:#1a0b3d;">history</span>
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="font-display text-[13px] font-extrabold leading-tight fsr-info-shimmer-text">
                {{ __('app.track_scores_answers') }}
            </h3>
            <p class="text-[10.5px] mt-0.5 leading-snug truncate" style="color:rgba(232,221,255,0.85);">
                {{ __('app.review_past_performance') }}
            </p>
        </div>
    </div>
</div>

@php
    $validHistories = collect();
    if (isset($histories_quiz) && is_object($histories_quiz) && !($histories_quiz instanceof \__PHP_Incomplete_Class) && method_exists($histories_quiz, 'filter')) {
        $validHistories = $histories_quiz->filter(function($h) {
            if (!$h || !isset($h->updated_at) || !$h->updated_at) return true;
            return \Carbon\Carbon::parse($h->updated_at)->addMinutes(5)->isPast();
        });
    }
@endphp

@if ($validHistories->count())
    <div class="flex flex-col gap-2.5 mb-4">
        @foreach ($validHistories->take(3) as $h)
            @php
                $playedCount = $h->quiz_users_count ?? 0;
                $userResult = $h->quizUsers->first();
                $percentage = 0;
                if ($userResult && isset($userResult->total_question) && $userResult->total_question > 0) {
                    $percentage = round(($userResult->total_correct_answer / $userResult->total_question) * 100);
                } elseif ($userResult && isset($userResult->total_score)) {
                    $percentage = min(100, max(0, (int) $userResult->total_score));
                }

                // 2. Icon Rotation Logic (Premium Gradients)
                $iconGradients = [
                    'linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08))',
                    'linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.1))',
                    'linear-gradient(160deg, rgba(217,70,239,0.3), rgba(217,70,239,0.08))',
                ];
                $iconColors = ['#ffd76a', '#d8b4fe', '#f0abfc'];
                $bgGrad = $iconGradients[$loop->index % count($iconGradients)];
                $icColor = $iconColors[$loop->index % count($iconColors)];

                // Dynamic icon based on category, fallback to 'quiz'
                $iconName = $h->category->icon ?? 'quiz';
            @endphp

            <div class="fsr-history-card">
                <div class="flex justify-between items-center w-full gap-1">
                    <!-- Left: Icon & Info -->
                    <div class="flex gap-2.5 items-center min-w-0 flex-1">
                        <!-- Box Icon -->
                        <div class="fsr-history-icon flex-shrink-0"
                            style="width:36px; height:36px; background:{{ $bgGrad }}; border:1px solid {{ $icColor }}33;">
                            <span class="material-symbols-outlined text-[18px]" style="color:{{ $icColor }};">history</span>
                        </div>

                        <div class="flex flex-col min-w-0">
                            <h3 class="font-display text-[13px] font-bold truncate" style="color:#e8ddff;">
                                {{ $h->title ?? ($h->name ?? 'Quiz') }}
                            </h3>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] font-medium truncate" style="color:rgba(232,221,255,0.6);">
                                    {{ $h->category->name ?? 'Category' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Score & Actions -->
                    <div class="flex flex-col items-end justify-center gap-1.5 flex-shrink-0 ml-auto">
                        <!-- Players Count Pill -->
                        <div class="px-2 py-0.5 rounded-full text-[9px] font-bold"
                            style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.2);">
                            {{ number_format($playedCount ?? 0) }} {{ __('app.players') }}
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <a href="{{ route('fsrapp.history.leaderboard', $h->id) }}"
                                class="flex items-center justify-center gap-1 py-[3px] px-2 rounded-full font-display text-[9px] font-bold transition-all duration-150"
                                style="background: linear-gradient(90deg, #ffb347, #e052c1); color: #1e1b4b; box-shadow: 0 2px 6px rgba(224, 82, 193, 0.25);">
                                <span class="material-symbols-outlined" style="font-size: 10px !important;">leaderboard</span>
                                {{ __('app.leaderboard') }}
                            </a>
                            <a href="{{ route('fsrapp.history.answersheet', $h->id) }}"
                                class="flex items-center justify-center gap-1 py-[3px] px-2 rounded-full border border-emerald-500/30 hover:border-emerald-500/50 bg-emerald-500/10 hover:bg-emerald-500/15 font-display text-[9px] font-bold text-[#00E5A0] transition-all duration-150">
                                <span class="material-symbols-outlined" style="font-size: 10px !important;">task_alt</span>
                                {{ __('app.answers') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <!-- Premium Empty State -->
    <div class="glass-card p-8 flex flex-col items-center justify-center text-center mt-4 mb-6 rounded-2xl"
        style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05);">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
            style="background:linear-gradient(135deg, rgba(217,70,239,0.2), rgba(124,58,237,0.1)); border:1px solid rgba(217,70,239,0.3);">
            <span class="material-symbols-outlined text-3xl" style="color:#f0abfc;">history</span>
        </div>
        <h3 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.no_quiz_history_title') }}</h3>
        <p class="text-[12px] mt-1.5 max-w-[220px]" style="color:rgba(232,221,255,0.5);">
            {{ __('app.no_quiz_history') }}
        </p>
        <button onclick="navigate('/home')"
            class="fsr-btn-primary px-6 py-2.5 rounded-xl font-display text-xs mt-5 cursor-pointer flex items-center gap-1.5 transition-transform active:scale-95"
            style="background:linear-gradient(135deg, #d946ef, #7c3aed); color:white; box-shadow:0 4px 12px rgba(217,70,239,0.3);">
            <span class="material-symbols-outlined text-[16px]">play_arrow</span>
            {{ __('app.play_quiz_today') }}
        </button>
    </div>
@endif
