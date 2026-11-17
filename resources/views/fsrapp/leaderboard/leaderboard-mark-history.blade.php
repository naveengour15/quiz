@extends('fsrapp.layouts.app')

@section('content')


    <style>
        header.fsr-header {
            background: rgba(13, 6, 32, 0.5) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border-glass, rgba(255, 255, 255, 0.1));
        }
        .history-card-glow {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(124, 58, 237, 0.2);
            background: rgba(124, 58, 237, 0.05);
            backdrop-filter: blur(10px);
        }
        .history-card-glow:hover {
            transform: translateY(-2px);
            border-color: rgba(124, 58, 237, 0.5);
            background: rgba(124, 58, 237, 0.1);
        }
    </style>

    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.leaderboard') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255, 255, 255, 0.1);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-lg font-extrabold tracking-tight leading-none" style="color:#e8ddff;">
                        Leaderboard History
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">View your earned marks step-by-step</p>
                </div>
            </div>
            <!-- ✅ Guidance Icon Button -->
            <button onclick="openQuizRulesModal()" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255, 255, 255, 0.1);" title="Quiz Guidance & Rules">
                <span class="material-symbols-outlined text-[19px] text-[#e8ddff]" style="font-variation-settings: 'FILL' 1;">lightbulb</span>
            </button>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-6 min-h-screen relative z-10">

        <!-- ✅ Points Summary Card (Hero Banner Style) -->
        <div class="relative overflow-hidden rounded-3xl p-5 mb-6 border border-[#f4bf32]/40 bg-gradient-to-br from-[#1a0b3d] via-[#32136c] to-[#86198f] shadow-[0_10px_30px_rgba(124,58,237,0.3)]">
            <div class="absolute -top-3 -right-8 w-28 h-28 bg-[#f4bf32]/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center gap-2 mb-2 relative z-10">
                <span class="w-2 h-2 rounded-full bg-[#f4bf32] shadow-[0_0_8px_#f4bf32]"></span>
                <span class="text-[11px] font-bold tracking-wider text-amber-200 uppercase">LEADERBOARD </span>
            </div>

            <h1 class="font-display text-2xl font-black text-white leading-tight mb-2 relative z-10">
                Marks History Overview
            </h1>
            <p class="text-xs text-purple-200/80 leading-relaxed mb-4 relative z-10">
                Track your progress. View your current and previous marks earned from quizzes below.
            </p>

            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-white/10 relative z-10">
                <!-- Current Marks -->
                <div class="bg-black/30 rounded-xl p-3 text-center border border-white/5">
                    <span class="block text-xl font-black text-amber-300">{{ number_format($newMarks, 2) }}</span>
                    <span class="text-[10px] text-purple-200/70 font-semibold uppercase mt-1 inline-block tracking-wide">Current Marks</span>
                </div>
                
                <!-- Previous Marks -->
                <div class="bg-black/30 rounded-xl p-3 text-center border border-white/5">
                    <span class="block text-xl font-black text-[#00E5A0]">{{ number_format($oldMarks, 2) }}</span>
                    <span class="text-[10px] text-purple-200/70 font-semibold uppercase mt-1 inline-block tracking-wide">Previous Marks</span>
                </div>
            </div>
        </div>

        <!-- History List -->
        <div class="space-y-3" id="history-container">
            @forelse ($history as $item)
                @php
                    $quizName = $item->quiz ? $item->quiz->title : 'Quiz ID: ' . ($item->quiz_id ?? 'N/A');
                    // $points = $item->new_point ?? 0;
                     $points = ($item->new_point ?? 0) - ($item->old_point ?? 0);
                    $type = ucfirst($item->type ?? 'credit');
                    $isCredit = strtolower($type) === 'credit';
                    $date = $item->created_at ? $item->created_at->format('d M Y, h:i A') : '';
                    
                    $badgeColor = $isCredit ? 'bg-[#00E5A0]/15 text-[#00E5A0] border-[#00E5A0]/30' : 'bg-red-500/15 text-red-400 border-red-500/30';
                    $pointColor = $isCredit ? 'text-[#00E5A0]' : 'text-red-400';
                    $iconName = $isCredit ? 'trending_up' : 'trending_down';
                @endphp

                <div class="history-card-glow rounded-2xl p-4 flex items-center gap-3">
                    <!-- Icon Badge -->
                    <div class="w-12 h-12 rounded-xl {{ $badgeColor }} border flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">{{ $iconName }}</span>
                    </div>
                    
                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#e8ddff] truncate">{{ $quizName }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-white/5 text-white/60 border border-white/10 uppercase">{{ $type }}</span>
                            <p class="text-[11px] text-white/50">{{ $date }}</p>
                        </div>
                    </div>
                    
                    <!-- Points Earned -->
                    <div class="text-right shrink-0">
                        <span class="block text-sm font-extrabold {{ $pointColor }}">
                            {{ $isCredit ? '+' : '' }}{{ number_format($points, 2) }}
                        </span>
                        <span class="text-[10px] text-white/40 font-semibold">Marks</span>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="rounded-2xl p-8 text-center border border-white/10 bg-white/[0.02] backdrop-blur-md mt-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-3 text-white/40">
                        <span class="material-symbols-outlined text-3xl">history</span>
                    </div>
                    <p class="text-xs font-semibold text-white/50">No history found</p>
                    <p class="text-[11px] text-white/40 mt-1 max-w-[200px] mx-auto">Aapne abhi tak kisi leaderboard quiz me points earn nahi kiye hain.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($history->hasPages())
            <div class="mt-6 flex justify-center">
                {{ $history->links('pagination::tailwind') }}
            </div>
        @endif

    </main>

  
    @include('fsrapp.leaderboard.leaderboard-rule-marks')

      <!-- ✅ Modal JavaScript -->
    <script>
        function openQuizRulesModal() {
            document.getElementById('quiz-rules-modal').classList.remove('hidden');
        }
        function closeQuizRulesModal() {
            document.getElementById('quiz-rules-modal').classList.add('hidden');
        }
        
        // ESC key se bhi modal band ho jaye
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeQuizRulesModal();
        });
    </script>

@endsection  