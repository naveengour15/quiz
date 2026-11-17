@extends('fsrapp.layouts.app')

@section('content')
    <!-- Ambient Glow Field -->
    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
    </div>

    <!-- ===================== HEADER ===================== -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.85); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-lg font-extrabold tracking-tight leading-none" style="color:#e8ddff;">Quiz
                        History</h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">Your past quiz
                        performance</p>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold font-display"
                style="background:rgba(192,132,252,0.15); color:#d8b4fe; border:1px solid rgba(192,132,252,0.3);">
                <span class="material-symbols-outlined text-[15px]">history_edu</span>
                History
            </div>
        </div>
    </header>


    <!-- ===================== MAIN ===================== -->
    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen">

        <!-- ===== Hero Summary Banner ===== -->
        <div class="fsr-hero p-5 mb-5 relative overflow-hidden">
            <div class="fsr-ribbon">🏆 COMPLETED</div>
            <span class="fsr-sparkle" style="top:10px; left:34%;">✨</span>
            <span class="fsr-sparkle" style="bottom:16px; left:8%; animation-delay:.8s;">✨</span>

            <div class="flex items-center gap-1.5 mb-2 relative z-10">
                <span class="w-1.5 h-1.5 rounded-full" style="background:#4ade80; box-shadow:0 0 6px #4ade80;"></span>
                <span class="text-[11px] font-bold tracking-wide" style="color:#86efac;">QUIZ ACHIEVEMENTS</span>
            </div>

            <div class="flex items-center justify-between gap-3 relative z-10">
                <div>
                    <h2 class="font-display text-xl font-extrabold leading-tight" style="color:#e8ddff;">
                        Your Quiz<br><span style="color:#ffd76a;">History & Ranks</span>
                    </h2>
                    <p class="text-[12px] mt-1.5" style="color:rgba(232,221,255,0.6);">
                        Total <strong style="color:#ffd76a;">{{ number_format($totalCount ?? $histories->count()) }}</strong> quizzes
                        completed
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                    style="background:linear-gradient(135deg, rgba(244,191,50,0.3), rgba(217,70,239,0.15)); border:1px solid rgba(244,191,50,0.3);">
                    <span class="material-symbols-outlined text-2xl" style="color:#ffd76a;">emoji_events</span>
                </div>
            </div>
        </div>
        @include('fsrapp.home.components.banners', ['banners' => $banners, 'pageType' => 'quiz_history_list'])
        <!-- ===== Quiz History List ===== -->
        @php
            $validHistories = $histories;
        @endphp

        @if ($validHistories->count())
            <div id="history-anchor" class="flex flex-col gap-2.5">
                @foreach ($validHistories as $loopIndex => $h)
                    @php
                        $iconGradients = [
                            'linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08))',
                            'linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.1))',
                            'linear-gradient(160deg, rgba(217,70,239,0.3), rgba(217,70,239,0.08))',
                        ];
                        $iconColors = ['#ffd76a', '#d8b4fe', '#f0abfc'];
                        $bgGrad = $iconGradients[$loopIndex % count($iconGradients)];
                        $icColor = $iconColors[$loopIndex % count($iconColors)];
                        $iconName = $h->category->icon ?? 'quiz';
                    @endphp

                    <div class="fsr-history-card history-card-item">
                        <div class="flex justify-between items-center w-full gap-1">
                            <!-- Left: Icon & Info -->
                            <div class="flex gap-2.5 items-center min-w-0 flex-1">
                                <!-- Box Icon -->
                                <div class="fsr-history-icon flex-shrink-0"
                                    style="width:36px; height:36px; background:{{ $bgGrad }}; border:1px solid {{ $icColor }}33;">
                                    <span class="material-symbols-outlined text-[18px]"
                                        style="color:{{ $icColor }};">{{ $iconName }}</span>
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

                            <!-- Right: Players Count & Actions -->
                            <div class="flex flex-col items-end justify-center gap-1.5 flex-shrink-0 ml-auto">
                                <!-- Players Count Pill -->
                                <div class="px-2 py-0.5 rounded-full text-[9px] font-bold"
                                    style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.2);">
                                    {{ number_format($h->quiz_users_count ?? 0) }} Players
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('fsrapp.history.leaderboard', $h->id) }}"
                                        class="flex items-center justify-center gap-1 py-[3px] px-2 rounded-full font-display text-[9px] font-bold transition-all duration-150"
                                        style="background: linear-gradient(90deg, #ffb347, #e052c1); color: #1e1b4b; box-shadow: 0 2px 6px rgba(224, 82, 193, 0.25);">
                                        <span class="material-symbols-outlined" style="font-size: 10px !important;">leaderboard</span>
                                        Leaderboard
                                    </a>
                                    <a href="{{ route('fsrapp.history.answersheet', $h->id) }}"
                                        class="flex items-center justify-center gap-1 py-[3px] px-2 rounded-full border border-emerald-500/30 hover:border-emerald-500/50 bg-emerald-500/10 hover:bg-emerald-500/15 font-display text-[9px] font-bold text-[#00E5A0] transition-all duration-150">
                                        <span class="material-symbols-outlined" style="font-size: 10px !important;">task_alt</span>
                                        Answers
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Load More Button -->
            @if ($totalCount > $limit)
                <div class="mt-5 text-center" id="load-more-container">
                    <a href="{{ request()->fullUrlWithQuery(['limit' => $limit + 10]) }}#load-more-container"
                        class="fsr-load-more-btn">
                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                        Load More ({{ $totalCount - $limit }} remaining)
                    </a>
                </div>
            @endif
        @else
            <!-- Empty state -->
            <div class="glass-card p-8 flex flex-col items-center justify-center text-center mt-6">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                    style="background:linear-gradient(135deg, rgba(217,70,239,0.2), rgba(124,58,237,0.1)); border:1px solid rgba(217,70,239,0.3);">
                    <span class="material-symbols-outlined text-3xl" style="color:#f0abfc;">history</span>
                </div>
                <h3 class="font-display text-base font-bold" style="color:#e8ddff;">No Quiz History Yet</h3>
                <p class="text-[12px] mt-1.5 max-w-[220px]" style="color:rgba(232,221,255,0.5);">
                    You haven't completed any quizzes yet. Play today's quiz to start earning rewards!
                </p>
                <a href="{{ route('fsrapp.home') }}"
                    class="fsr-btn-primary px-6 py-2.5 rounded-xl font-display text-xs mt-5 cursor-pointer flex items-center gap-1.5 text-center">
                    <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                    Play Today's Quiz
                </a>
            </div>
        @endif

    </main>




@endsection
