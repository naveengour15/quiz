@extends('fsrapp.layouts.app')

@section('content')
    <!-- ===================== HEADER ===================== -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.85); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                {{-- <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('fsrapp.home') }}" --}}
                <a href="{{ $backUrl }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors shrink-0"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-base font-extrabold tracking-tight truncate" style="color:#e8ddff;">
                        {{ $quiz->title ?? __('app.quiz_leaderboard') }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        {{ __('app.quiz_leaderboard') }} & {{ __('app.rewards') }}
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold font-display shrink-0 ml-2"
                style="background:rgba(244,191,50,0.15); color:#ffe9b0; border:1px solid rgba(244,191,50,0.35);">
                <span class="material-symbols-outlined text-[15px]"
                    style="font-variation-settings:'FILL' 1;">emoji_events</span>
                {{ __('app.leaderboard_title') }}
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen relative z-10">

        @if (isset($quiz->complete_status) && $quiz->complete_status === 'processing')
            <div class="flex flex-col items-center justify-center min-h-[50vh] text-center">
                <span class="material-symbols-outlined text-[60px] text-[#ffd76a] animate-spin mb-4">sync</span>
                <h3 class="font-display text-2xl font-extrabold text-white mb-2">{{ __('app.results_processing') }}</h3>
                <p class="text-[13px] text-white/60 mb-8 max-w-[280px]">{{ __('app.check_back_soon') }}</p>
            </div>
            <script>
                (function() {
                    let pollAttempt = 0;
                    let timeoutId = null;

                    const getNextInterval = () => {
                        pollAttempt++;
                        return Math.min(10000, 4000 + (pollAttempt * 1500));
                    };

                    const checkStatus = () => {
                        if (document.hidden) return;

                        fetch('{{ route('fsrapp.home.last-quiz-status') }}?quiz_id={{ $quiz->id }}&_t=' + new Date().getTime(), {
                                method: 'GET',
                                cache: 'no-store',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.id == {{ $quiz->id }}) {
                                    if (data.complete_status === 'completed') {
                                        window.location.reload();
                                    } else {
                                        timeoutId = setTimeout(checkStatus, getNextInterval());
                                    }
                                } else {
                                    setTimeout(() => window.location.reload(), 4000);
                                }
                            })
                            .catch(err => {
                                timeoutId = setTimeout(checkStatus, getNextInterval());
                            });
                    };

                    document.addEventListener('visibilitychange', function() {
                        if (!document.hidden) {
                            if (timeoutId) clearTimeout(timeoutId);
                            checkStatus();
                        }
                    });

                    timeoutId = setTimeout(checkStatus, 4000);
                })();
            </script>
        @else
            <!-- ===== Hero Leaderboard Card ===== -->
            <div
                class="relative overflow-hidden rounded-3xl pt-6 px-4 pb-0 mb-5 text-center shadow-2xl bg-gradient-to-br from-[#0c5935] to-[#042e18]">

                <!-- Wavy Pattern overlay -->
                <div class="absolute inset-0 opacity-[0.1]"
                    style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, #ffffff 10px, #ffffff 11px);">
                </div>

                <!-- Big Trophy Image Right Side -->
                <div class="absolute -right-2 top-4 w-[220px] h-[220px] pointer-events-none z-0">
                    <img src="{{ asset('fsrapp/images/winner.png') }}"
                        class="w-full h-full object-contain animate-subtle-float drop-shadow-[0_10px_20px_rgba(0,0,0,0.3)]"
                        alt="Trophy">
                </div>

                <!-- Header Info (Left Aligned) -->
                <div class="relative z-10 flex flex-col items-start mb-10 w-[60%]">
                    <div class="flex items-center gap-3 mb-1">
                        <div
                            class="w-10 h-10 rounded-xl border border-amber-500/30 flex items-center justify-center bg-amber-500/10 backdrop-blur-sm">
                            <span class="material-symbols-outlined text-[#ffd76a] text-xl"
                                style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                        </div>
                        <h3 class="font-display text-xl font-extrabold text-white">
                            {{ mb_strlen($quiz->title ?? '') > 18 ? mb_substr($quiz->title, 0, 18) . '...' : $quiz->title ?? 'Quiz Leaderboard' }}
                        </h3>
                    </div>
                    <p class="text-[11px] text-[#ffd76a] font-bold ml-1 mb-4">
                        {{ count($leaderboard) }} <span class="text-white/60 font-normal">{{ __('app.matches_competed') }}</span>
                    </p>

                    <div
                        class="inline-flex items-center gap-2 bg-[#0c5935]/40 border border-[#00b06b]/40 backdrop-blur-sm rounded-full px-4 py-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-[#ffd76a] text-[16px]"
                            style="font-variation-settings: 'FILL' 1;">crown</span>
                        <span class="font-display text-[10px] font-bold text-[#ffd76a] uppercase tracking-wider">{{ __('app.top_3_champions_short') }}</span>
                    </div>
                </div>

                @php
                    $getValidPhoto = function ($photo) {
                        if (empty($photo)) {
                            return null;
                        }
                        if (\Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])) {
                            return $photo;
                        }
                        $path = ltrim($photo, '/');
                        if (file_exists(public_path('storage/' . $path))) {
                            return asset('storage/' . $path);
                        }
                        if (file_exists(public_path($path))) {
                            return asset($path);
                        }
                        return null;
                    };

                    $totalQuestions =
                        ($quiz->switch_option ?? 0) == 1
                            ? ($quiz->questions_count ?? 10) - 1
                            : $quiz->questions_count ?? 10;

                    $top1 = $leaderboard->firstWhere('rank', 1) ?? $leaderboard->first();
                    $top2 = $leaderboard->firstWhere('rank', 2) ?? $leaderboard->skip(1)->first();
                    $top3rd = $leaderboard->firstWhere('rank', 3) ?? $leaderboard->skip(2)->first();

                    $photo1 = $top1 ? $getValidPhoto($top1->profile_photo ?? null) : null;
                    $photo2 = $top2 ? $getValidPhoto($top2->profile_photo ?? null) : null;
                    $photo3 = $top3rd ? $getValidPhoto($top3rd->profile_photo ?? null) : null;
                @endphp

                <!-- ===== Creative 3D Glass Podium (Flush to Bottom Baseline) ===== -->
                <div class="relative z-10 grid grid-cols-3 gap-1.5 items-end pt-2 px-1">

                    <!-- 2nd Place (Silver) -->
                    <div class="flex flex-col items-center">
                        <!-- Avatar & Badge -->
                        <div class="relative mb-3 flex flex-col items-center">
                            <div
                                class="w-12 h-12 rounded-full border-[2px] border-[#a0aec0] bg-[#1a202c] flex items-center justify-center overflow-hidden shadow-[0_0_15px_rgba(160,174,192,0.3)] relative z-10">
                                @if ($photo2)
                                    <img src="{{ $photo2 }}" alt="{{ $top2->name }}"
                                        class="w-full h-full object-cover" loading="lazy" decoding="async">
                                @else
                                    <span
                                        class="font-bold text-[#a0aec0] text-sm">{{ strtoupper(mb_substr(trim($top2->name ?? 'N'), 0, 1)) }}</span>
                                @endif
                            </div>
                            <div
                                class="w-5 h-5 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 text-slate-900 font-black text-[10px] flex items-center justify-center absolute -bottom-2 z-20 shadow-md">
                                2</div>
                        </div>

                        <!-- Name & Score -->
                        <p class="font-display text-[11px] font-bold text-white truncate w-[80px] mb-1">
                            {{ $top2->name ?? '—' }}</p>
                        <span
                            class="font-mono text-[9px] font-bold text-slate-300 bg-white/5 px-2 py-0.5 rounded-full border border-white/10 mb-1">
                            @if ($quiz['visibility'] === 'leaderboard')
                                {{ $top2->reward ?? 0 }} X Marks
                            @elseif ($quiz['visibility'] === 'fastest_finger_first')
                                {{ number_format($top2->reward ?? 0) }} Marks
                            @else
                                {{ number_format($top2->reward ?? 0) }} Pts
                            @endif
                        </span>
                         {{-- ✅ Top 2 Point History --}}
                         
                         {{-- ✅ Top 1 Point History (Center Aligned) --}}
                        @if($quiz->visibility === 'leaderboard' && isset($top2->point_history))
                            @php
                                $oldPt2 = $top2->point_history->old_point ?? 0;
                                $newPt2 = $top2->point_history->new_point ?? 0;
                                $fmtOld2 = rtrim(rtrim(sprintf('%.2f', $oldPt2), '0'), '.') ?: '0';
                                $fmtNew2 = rtrim(rtrim(sprintf('%.2f', $newPt2), '0'), '.') ?: '0';
                                
                                if ($newPt2 > $oldPt2) {
                                    $tc2 = '#00E5A0'; $ti2 = 'trending_up';
                                } elseif ($newPt2 < $oldPt2) {
                                    $tc2 = '#ef4444'; $ti2 = 'trending_down';
                                } else {
                                    $tc2 = '#94a3b8'; $ti2 = 'trending_flat';
                                }
                            @endphp

                            <div class="flex flex-col items-center gap-1 mt-1.5 pt-1.5 border-t border-white/10 w-full">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold text-amber-200/60 uppercase tracking-wider">Last</span>
                                    <span class="text-[11px] font-mono font-bold text-amber-400">{{ $fmtOld2 }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold uppercase tracking-wider flex items-center gap-0.5" style="color: {{ $tc2 }}99;">
                                        <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">{{ $ti2 }}</span> 
                                        Current
                                    </span>
                                    <span class="text-[11px] font-mono font-bold drop-shadow-sm" style="color: {{ $tc2 }};">{{ $fmtNew2 }}</span>
                                </div>
                            </div>
                        @endif
                        @if ($top2)
                            <span class="text-[8px] text-white/40 font-mono mb-2">
                                {{ $top2->correct }} / {{ $totalQuestions }} Ans
                            </span>
                        @else
                            <span class="text-[8px] text-white/40 font-mono mb-2">&nbsp;</span>
                        @endif

                        <!-- Pedestal Stand Base (Silver) -->
                        <div
                            class="w-full h-12 rounded-t-lg border-t border-slate-500/30 bg-gradient-to-b from-[#2d3748] to-[#1a202c] flex items-center justify-center shadow-lg relative overflow-hidden">
                            <div
                                class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-slate-400/50 to-transparent">
                            </div>
                            <span class="font-display font-bold text-lg text-slate-400/80 tracking-wider">#2</span>
                        </div>
                    </div>

                    <!-- 1st Place (Gold Champion) -->
                    <div class="flex flex-col items-center">
                        <!-- Crown -->
                        <span
                            class="material-symbols-outlined text-[#ffd76a] text-3xl -mb-2 animate-crown drop-shadow-[0_0_10px_rgba(255,215,106,0.8)] z-20"
                            style="font-variation-settings: 'FILL' 1;">crown</span>

                        <!-- Avatar & Gold Badge -->
                        <div class="relative mb-3 flex flex-col items-center">
                            <div
                                class="w-16 h-16 rounded-full border-[3px] border-[#ffd76a] bg-[#1a202c] flex items-center justify-center overflow-hidden shadow-[0_0_20px_rgba(255,215,106,0.5)] relative z-10 animate-gold-aura">
                                @if ($photo1)
                                    <img src="{{ $photo1 }}" alt="{{ $top1->name }}"
                                        class="w-full h-full object-cover" loading="lazy" decoding="async">
                                @else
                                    <span
                                        class="font-bold text-[#ffd76a] text-lg">{{ strtoupper(mb_substr(trim($top1->name ?? 'N'), 0, 1)) }}</span>
                                @endif
                            </div>
                            
                            <div
                                class="w-6 h-6 rounded-full bg-gradient-to-br from-[#ffd76a] via-[#f4bf32] to-[#d97706] text-slate-900 font-black text-[11px] flex items-center justify-center absolute -bottom-2 z-30 shadow-[0_0_10px_rgba(255,215,106,0.6)]">
                                1</div>
                        </div>

                        <!-- Name & Gold Score -->
                        <p class="font-display text-xs font-bold text-white truncate w-[90px] mb-1">
                            {{ $top1->name ?? '—' }}</p>
                        <span
                            class="font-mono text-[10px] font-bold text-[#ffd76a] border border-[#ffd76a]/30 px-3 py-0.5 rounded-full mb-1">
                            @if ($quiz['visibility'] === 'leaderboard')
                                {{ $top1->reward ?? 0 }} X Marks
                            @elseif ($quiz['visibility'] === 'fastest_finger_first')
                                {{ number_format($top1->reward ?? 0) }} Marks
                            @else
                                {{ number_format($top1->reward ?? 0) }} Pts
                            @endif
                        </span>
                        {{-- ✅ Top 1 Point History --}}
                           {{-- ✅ Top 1 Point History (Center Aligned) --}}
                         @if($quiz->visibility === 'leaderboard' && isset($top1->point_history))
                            @php
                                $oldPt1 = $top1->point_history->old_point ?? 0;
                                $newPt1 = $top1->point_history->new_point ?? 0;
                                $fmtOld1 = rtrim(rtrim(sprintf('%.2f', $oldPt1), '0'), '.') ?: '0';
                                $fmtNew1 = rtrim(rtrim(sprintf('%.2f', $newPt1), '0'), '.') ?: '0';
                                
                                if ($newPt1 > $oldPt1) {
                                    $tc1 = '#00E5A0'; $ti1 = 'trending_up';
                                } elseif ($newPt1 < $oldPt1) {
                                    $tc1 = '#ef4444'; $ti1 = 'trending_down';
                                } else {
                                    $tc1 = '#94a3b8'; $ti1 = 'trending_flat';
                                }
                            @endphp

                            <div class="flex flex-col items-center gap-1 mt-1.5 pt-1.5 border-t border-white/10 w-full">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold text-amber-200/60 uppercase tracking-wider">Last</span>
                                    <span class="text-[11px] font-mono font-bold text-amber-400">{{ $fmtOld1 }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold uppercase tracking-wider flex items-center gap-0.5" style="color: {{ $tc1 }}99;">
                                        <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">{{ $ti1 }}</span> 
                                        Current
                                    </span>
                                    <span class="text-[11px] font-mono font-bold drop-shadow-sm" style="color: {{ $tc1 }};">{{ $fmtNew1 }}</span>
                                </div>
                            </div>
                        @endif 
                        
                        @if ($top1)
                            <span class="text-[9px] text-white/50 font-mono mb-2">
                                {{ $top1->correct }} / {{ $totalQuestions }} Ans
                            </span>
                        @else
                            <span class="text-[9px] text-white/50 font-mono mb-2">&nbsp;</span>
                        @endif

                        <!-- Pedestal Stand Base (Gold) -->
                        <div
                            class="w-full h-16 rounded-t-lg border-t-2 border-[#ffd76a]/60 bg-gradient-to-b from-[#744210] to-[#2d3748] flex flex-col items-center justify-center shadow-[0_-5px_20px_rgba(255,215,106,0.15)] relative overflow-hidden">
                            <div
                                class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#ffd76a] to-transparent opacity-80">
                            </div>
                            <span class="font-display font-bold text-2xl text-[#ffd76a] tracking-wider">#1</span>
                        </div>
                    </div>

                    <!-- 3rd Place (Bronze) -->
                    <div class="flex flex-col items-center">
                        <!-- Avatar & Badge -->
                        <div class="relative mb-3 flex flex-col items-center">
                            <div
                                class="w-12 h-12 rounded-full border-[2px] border-[#cd7f32] bg-[#1a202c] flex items-center justify-center overflow-hidden shadow-[0_0_15px_rgba(205,127,50,0.3)] relative z-10">
                                @if ($photo3)
                                    <img src="{{ $photo3 }}" alt="{{ $top3rd->name }}"
                                        class="w-full h-full object-cover" loading="lazy" decoding="async">
                                @else
                                    <span
                                        class="font-bold text-[#cd7f32] text-sm">{{ strtoupper(mb_substr(trim($top3rd->name ?? 'N'), 0, 1)) }}</span>
                                @endif
                            </div>
                            <div
                                class="w-5 h-5 rounded-full bg-gradient-to-br from-[#cd7f32] to-[#8c4a16] text-white font-black text-[10px] flex items-center justify-center absolute -bottom-2 z-20 shadow-md">
                                3</div>
                        </div>

                        <!-- Name & Score -->
                        <p class="font-display text-[11px] font-bold text-white truncate w-[80px] mb-1">
                            {{ $top3rd->name ?? '—' }}</p>
                        <span
                            class="font-mono text-[9px] font-bold text-[#e2a061] bg-white/5 px-2 py-0.5 rounded-full border border-white/10 mb-1">
                            @if ($quiz['visibility'] === 'leaderboard')
                                {{ $top3rd->reward ?? 0 }} X Marks
                            @elseif ($quiz['visibility'] === 'fastest_finger_first')
                                {{ number_format($top3rd->reward ?? 0) }} Marks
                            @else
                                {{ number_format($top3rd->reward ?? 0) }} Pts
                            @endif
                        </span>

                         {{-- ✅ Top 3 Point History --}}
                        
                        @if($quiz->visibility === 'leaderboard' && isset($top3rd->point_history))
                            @php
                                $oldPt3 = $top3rd->point_history->old_point ?? 0;
                                $newPt3 = $top3rd->point_history->new_point ?? 0;
                                $fmtOld3 = rtrim(rtrim(sprintf('%.2f', $oldPt3), '0'), '.') ?: '0';
                                $fmtNew3 = rtrim(rtrim(sprintf('%.2f', $newPt3), '0'), '.') ?: '0';
                                
                                if ($newPt3 > $oldPt3) {
                                    $tc3 = '#00E5A0'; $ti3 = 'trending_up';
                                } elseif ($newPt3 < $oldPt3) {
                                    $tc3 = '#ef4444'; $ti3 = 'trending_down';
                                } else {
                                    $tc3 = '#94a3b8'; $ti3 = 'trending_flat';
                                }
                            @endphp

                            <div class="flex flex-col items-center gap-1 mt-1.5 pt-1.5 border-t border-white/10 w-full">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold text-amber-200/60 uppercase tracking-wider">Last</span>
                                    <span class="text-[11px] font-mono font-bold text-amber-400">{{ $fmtOld3 }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold uppercase tracking-wider flex items-center gap-0.5" style="color: {{ $tc3 }}99;">
                                        <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">{{ $ti3 }}</span> 
                                        Current
                                    </span>
                                    <span class="text-[11px] font-mono font-bold drop-shadow-sm" style="color: {{ $tc3 }};">{{ $fmtNew3 }}</span>
                                </div>
                            </div>
                        @endif
                            
                        @if ($top3rd)
                            <span class="text-[8px] text-white/40 font-mono mb-2">
                                {{ $top3rd->correct }} / {{ $totalQuestions }} Ans
                            </span>
                        @else
                            <span class="text-[8px] text-white/40 font-mono mb-2">&nbsp;</span>
                        @endif

                        <!-- Pedestal Stand Base (Bronze) -->
                        <div
                            class="w-full h-10 rounded-t-lg border-t border-amber-700/50 bg-gradient-to-b from-[#4a2b16] to-[#1a202c] flex items-center justify-center shadow-lg relative overflow-hidden">
                            <div
                                class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#cd7f32]/50 to-transparent">
                            </div>
                            <span class="font-display font-bold text-lg text-[#cd7f32]/80 tracking-wider">#3</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- @include('fsrapp.home.components.banners', ['banners' => $banners,'pageType' => 'quiz_leaderboard'])
        @include('fsrapp.home.components.banners', ['banners' => $banners,'pageType' => 'quiz_history_leaderboard']) --}}

            @include('fsrapp.home.components.banners', [
                'banners' => $banners,
                'pageType' => $pageType,
            ])
            <!-- ===== Sticky "Your Rank" Row ===== -->
            <style>
                @keyframes bg-pan-green {
                    0% {
                        background-position: 0% 50%;
                    }

                    50% {
                        background-position: 100% 50%;
                    }

                    100% {
                        background-position: 0% 50%;
                    }
                }

                .animate-bg-pan-green {
                    background: linear-gradient(90deg, #07241c, #0a4d36, #07241c);
                    background-size: 200% 200%;
                    animation: bg-pan-green 4s ease-in-out infinite;
                }
            </style>
            @php
                $myEntry = $myEntryData ?? null;
                $myPhoto = $myEntry ? $getValidPhoto($myEntry->profile_photo ?? null) : null;
            @endphp

            @if ($myEntry)
                <div
                    class="glass-card rounded-2xl p-3.5 mb-4 flex items-center justify-between transition-all animate-bg-pan-green border border-[#00E5A0]/20 shadow-[0_4px_15px_rgba(0,229,160,0.2)]">
                    <div class="flex items-center gap-3 min-w-0">
                        <span
                            class="font-mono text-sm font-bold text-[#00E5A0] shrink-0">#{{ $myEntry->rank ?? '—' }}</span>
                        <div
                            class="w-10 h-10 rounded-full bg-gradient-to-br from-[#00E5A0]/40 to-[#00E5A0]/10 border-2 border-[#00E5A0] flex items-center justify-center shrink-0 overflow-hidden shadow-[0_0_10px_rgba(0,229,160,0.3)]">
                            @if ($myPhoto)
                                <img src="{{ $myPhoto }}" alt="You" class="w-full h-full object-cover"
                                    loading="lazy" decoding="async">
                            @else
                                <span
                                    class="font-bold text-[#00E5A0] text-sm">{{ strtoupper(mb_substr(trim($myEntry->name ?? 'U'), 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-display text-sm font-bold text-[#00E5A0] truncate">
                                {{ $myEntry->name ?? 'You' }}
                                <span
                                    class="text-[10px] bg-[#00E5A0] text-slate-900 px-1.5 py-0.5 rounded font-mono font-black ml-1 uppercase shadow-sm">YOU</span>
                            </p>
                            <p class="text-[11px] text-white/70 mt-0.5 flex items-center gap-1 font-mono">
                                <span
                                    class="text-[#00E5A0] font-bold">{{ $myEntry->correct }}</span>/{{ $totalQuestions }}
                                Ans
                                <span class="text-white/40">•</span>
                                <span class="text-amber-400 font-bold">{{ $myEntry->total_time }}s</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="font-mono text-sm font-black text-[#00E5A0] drop-shadow-md">

                            @if ($quiz['visibility'] === 'leaderboard')
                                <span style="font-size: 18px; ">
                                        {{ $myEntry->reward ?? 0 }}
                                    </span> X Marks
                            @elseif ($quiz['visibility'] === 'fastest_finger_first')
                                <span style="font-size: 18px; ">
                                        {{ number_format($myEntry->reward ?? 0) }}
                                    </span> Marks
                            @else
                                {{ number_format($myEntry->reward ?? 0) }} Pts
                            @endif
                        </span>

                        
                        @if($quiz->visibility === 'leaderboard' && isset($myEntry->point_history))
                            @php
                                $myOldPt = $myEntry->point_history->old_point ?? 0;
                                $myNewPt = $myEntry->point_history->new_point ?? 0;
                                $myFmtOld = rtrim(rtrim(sprintf('%.2f', $myOldPt), '0'), '.') ?: '0';
                                $myFmtNew = rtrim(rtrim(sprintf('%.2f', $myNewPt), '0'), '.') ?: '0';
                                
                                if ($myNewPt > $myOldPt) {
                                    $myTc = '#00E5A0'; $myTi = 'trending_up';
                                } elseif ($myNewPt < $myOldPt) {
                                    $myTc = '#ef4444'; $myTi = 'trending_down';
                                } else {
                                    $myTc = '#94a3b8'; $myTi = 'trending_flat';
                                }
                            @endphp

                            <div class="flex flex-col items-end gap-1 mt-1.5 pt-1.5 border-t border-white/10">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold text-amber-200/60 uppercase tracking-wider">Last</span>
                                    <span class="text-[11px] font-mono font-bold text-amber-400">{{ $myFmtOld }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] font-semibold uppercase tracking-wider flex items-center gap-0.5" style="color: {{ $myTc }}99;">
                                        <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">{{ $myTi }}</span> 
                                        Current
                                    </span>
                                    <span class="text-[11px] font-mono font-bold drop-shadow-sm" style="color: {{ $myTc }};">{{ $myFmtNew }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- ===== Leaderboard Ranks Table ===== -->
            <div id="load-more-anchor"
                class="glass-card rounded-2xl border border-white/10 bg-white/[0.02] overflow-hidden mb-4 shadow-xl">
                <!-- Table Header -->
                <div
                    class="grid grid-cols-12 px-4 py-3 bg-white/5 border-b border-white/10 text-[11px] font-bold text-white/40 uppercase tracking-wider">
                    <div class="col-span-2">Rank</div>
                    <div class="col-span-7">Player</div>
                    <div class="col-span-3 text-right">
                        @if ($quiz['visibility'] === 'leaderboard')
                            X Marks
                        @elseif ($quiz['visibility'] === 'fastest_finger_first')
                            Marks
                        @else
                            Points
                        @endif
                    </div>
                </div>

                <!-- Table Rows Body -->
                <div class="divide-y divide-white/5" id="lb-rows-container">
                    @forelse($leaderboard as $entry)
                        @php
                            $rank = $entry->rank;
                            $isMe = auth()->check() && $entry->user_id == auth()->id();
                            $photoUrl = $getValidPhoto($entry->profile_photo ?? null);

                            $rowBg = 'hover:bg-white/[0.04]';
                            $rankColor = 'text-white/60';
                            $ptsStyle = 'text-white/80';
                            if ($rank == 1) {
                                $rowBg = 'bg-amber-500/10 hover:bg-amber-500/20 border-l-2 border-amber-400';
                                $rankColor = 'text-[#ffd76a] font-black';
                                $ptsStyle = 'shimmer-gold-text font-black';
                            } elseif ($rank == 2) {
                                $rowBg = 'bg-white/5 hover:bg-white/10 border-l-2 border-slate-300';
                                $rankColor = 'text-slate-300 font-bold';
                                $ptsStyle = 'text-slate-200 font-bold';
                            } elseif ($rank == 3) {
                                $rowBg = 'bg-amber-900/10 hover:bg-amber-900/20 border-l-2 border-amber-600';
                                $rankColor = 'text-[#f3d3b3] font-bold';
                                $ptsStyle = 'text-[#f3d3b3] font-bold';
                            } elseif ($isMe) {
                                $rowBg = 'bg-[#00E5A0]/10 hover:bg-[#00E5A0]/20 border-l-2 border-[#00E5A0]';
                                $rankColor = 'text-[#00E5A0] font-bold';
                                $ptsStyle = 'text-[#00E5A0] font-bold';
                            }
                        @endphp

                        <div
                            class="grid grid-cols-12 px-4 py-3 items-center gap-2 transition-all duration-200 {{ $rowBg }}">
                            <!-- Rank -->
                            <div class="col-span-2 flex items-center gap-1 font-mono text-xs {{ $rankColor }}">
                                @if ($rank == 1)
                                    <span class="text-sm">🥇</span> 1
                                @elseif ($rank == 2)
                                    <span class="text-sm">🥈</span> 2
                                @elseif ($rank == 3)
                                    <span class="text-sm">🥉</span> 3
                                @else
                                    #{{ $rank }}
                                @endif
                            </div>

                            <!-- Player Info -->
                            <div class="col-span-7 flex items-center gap-2.5 min-w-0">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#7c3aed]/30 to-[#d946ef]/30 border border-[#e879f9]/30 flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold text-[#e879f9] shadow-sm">
                                    @if ($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $entry->name }}"
                                            class="w-full h-full object-cover" loading="lazy" decoding="async">
                                    @else
                                        <span>{{ strtoupper(mb_substr(trim($entry->name ?? 'N'), 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-display text-xs font-bold text-[#e8ddff] truncate">
                                        {{ $entry->name ?? 'No player' }}
                                        @if ($isMe)
                                            <span class="text-[10px] text-[#00E5A0] font-mono font-bold">(You)</span>
                                        @endif
                                    </span>
                                    <span class="text-[10px] text-white/40 mt-0.5 flex items-center gap-1 font-mono">
                                        <span
                                            class="text-emerald-400 font-bold">{{ $entry->correct }}</span>/{{ $totalQuestions }}
                                        Ans
                                        <span class="text-white/20">•</span>
                                        <span class="text-amber-400 font-bold">{{ $entry->total_time }}s</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Points -->
                            <div class="col-span-3 text-right font-mono text-xs {{ $ptsStyle }}">


                                @if ($quiz['visibility'] === 'leaderboard')
                                    <span style="font-size: 18px; ">
                                        {{ $entry->reward ?? 0 }}
                                    </span> X Marks
                                @elseif ($quiz['visibility'] === 'fastest_finger_first')
                                    <span style="font-size: 14px; font-weight: bold;">
                                        {{ number_format($entry->reward ?? 0) }}
                                    </span> Marks
                                @else
                                    {{ number_format($entry->reward ?? 0) }} Pts
                                @endif

                                @if($quiz->visibility === 'leaderboard' && isset($entry->point_history))
                                    @php
                                        $oldPt = $entry->point_history->old_point ?? 0;
                                        $newPt = $entry->point_history->new_point ?? 0;
                                        $fmtOld = rtrim(rtrim(sprintf('%.2f', $oldPt), '0'), '.') ?: '0';
                                        $fmtNew = rtrim(rtrim(sprintf('%.2f', $newPt), '0'), '.') ?: '0';
                                        
                                        if ($newPt > $oldPt) {
                                            $trendColor = '#00E5A0';
                                            $trendIcon = 'trending_up';
                                            $trendLabel = 'Current';
                                        } elseif ($newPt < $oldPt) {
                                            $trendColor = '#ef4444';
                                            $trendIcon = 'trending_down';
                                            $trendLabel = 'Current';
                                        } else {
                                            $trendColor = '#94a3b8';
                                            $trendIcon = 'trending_flat';
                                            $trendLabel = 'Current';
                                        }
                                    @endphp

                                    <div class="flex flex-col items-end gap-1 mt-1.5 pt-1.5 border-t border-white/10">
                                        <!-- Old Points -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] font-semibold text-amber-200/60 uppercase tracking-wider">Last</span>
                                            <span class="text-[11px] font-mono font-bold text-amber-400">{{ $fmtOld }}</span>
                                        </div>
                                        
                                        <!-- New Points (Conditional Color) -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] font-semibold uppercase tracking-wider flex items-center gap-0.5" style="color: {{ $trendColor }}99;">
                                                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1;">{{ $trendIcon }}</span> 
                                                {{ $trendLabel }}
                                            </span>
                                            <span class="text-[11px] font-mono font-bold drop-shadow-sm" style="color: {{ $trendColor }};">{{ $fmtNew }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center border-t border-white/5">
                            <span class="material-symbols-outlined text-white/20 text-4xl mb-1">emoji_events</span>
                            <p class="text-xs text-white/50">No rankings found yet for this quiz.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Load More Container -->
                @if ($totalCount > $limit)
                    <div class="p-3 bg-white/[0.01] border-t border-white/5 text-center">
                        <a href="{{ request()->fullUrlWithQuery(['limit' => $limit + 10]) }}#load-more-anchor"
                            class="fsr-load-more-btn">
                            <span class="material-symbols-outlined text-[16px]">expand_more</span>
                            Load More
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Back to History Link -->
        <div class="mt-4 text-center">
            {{-- <a href="{{ url()->previous() }}" --}}
            <a href="{{ route('fsrapp.home') }}"
                class="fsr-play-btn w-full py-3 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">home</span>
                Go to Home
            </a>
        </div>

    </main>

    <button id="lb-scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Scroll to top"
        class="fixed bottom-20 right-4 z-40 w-11 h-11 rounded-full bg-gradient-to-tr from-[#7c3aed] to-[#d946ef] text-white shadow-[0_4px_16px_rgba(217,70,239,0.4)] flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 hover:scale-110 active:scale-95">
        <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
    </button>
@endsection

@push('scripts')
    <script>
        (function() {
            const fab = document.getElementById('lb-scroll-top');
            if (fab) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 300) {
                        fab.classList.remove('opacity-0', 'pointer-events-none');
                    } else {
                        fab.classList.add('opacity-0', 'pointer-events-none');
                    }
                }, {
                    passive: true
                });
            }


        })();
    </script>
@endpush
