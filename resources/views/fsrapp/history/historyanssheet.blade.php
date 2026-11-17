@extends('fsrapp.layouts.app')

@push('styles')
    <style>
        /* Premium translucent backgrounds */
        .ans-card {
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .ans-card.correct {
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .ans-card.correct:hover {
            background: rgba(16, 185, 129, 0.07);
            border-color: rgba(16, 185, 129, 0.4);
        }

        .ans-card.incorrect {
            background: rgba(239, 68, 68, 0.04);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .ans-card.incorrect:hover {
            background: rgba(239, 68, 68, 0.07);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .status-badge {
            font-family: var(--font-display, sans-serif);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.05em;
        }

        .time-badge {
            font-size: 10px;
            color: rgba(232, 221, 255, 0.5);
            font-family: monospace;
            background: rgba(255, 255, 255, 0.06);
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .q-text {
            font-family: var(--font-display, sans-serif);
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.5;
            margin: 12px 0;
        }

        .q-sub-text {
            font-size: 12px;
            color: rgba(232, 221, 255, 0.6);
            line-height: 1.5;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-family: sans-serif;
        }

        .ans-info-row {
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ans-info-row p {
            margin: 0;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .label-correct {
            color: #10b981;
            font-weight: 700;
            font-family: var(--font-display, sans-serif);
        }

        .val-correct {
            color: #a7f3d0;
            font-weight: 600;
        }

        .label-wrong {
            color: #ef4444;
            font-weight: 700;
            font-family: var(--font-display, sans-serif);
        }

        .val-wrong {
            color: #fca5a5;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <!-- ===================== HEADER ===================== -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.85); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ route('fsrapp.history') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors shrink-0"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-base font-extrabold tracking-tight truncate" style="color:#e8ddff;">
                        {{ $quiz->title ?? 'Quiz Review' }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        Your Performance Sheet
                    </p>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold font-display shrink-0 ml-2"
                style="background:rgba(16, 185, 129, 0.15); color:#a7f3d0; border:1px solid rgba(16, 185, 129, 0.35);">
                <span class="material-symbols-outlined text-[15px]">task_alt</span>
                Answer Sheet
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen relative z-10">

        <!-- ===== Summary Stats Card ===== -->
        <div class="grid grid-cols-3 gap-3 text-center mb-5">
            <div class="glass-card rounded-2xl p-3.5 border border-white/10" style="background: rgba(255, 255, 255, 0.02);">
                <p class="font-label-caps text-[9px] uppercase text-white/50 tracking-wider">TOTAL QS</p>
                <p class="font-headline-md text-lg font-bold text-white mt-1">{{ $totalQuestionsCount }}</p>
            </div>
            <div class="glass-card rounded-2xl p-3.5 border border-emerald-500/30"
                style="background: rgba(16, 185, 129, 0.05);">
                <p class="font-label-caps text-[9px] uppercase text-emerald-400 tracking-wider">CORRECT</p>
                <p class="font-headline-md text-lg font-bold text-emerald-400 mt-1">{{ $correctCount }}</p>
            </div>
            <div class="glass-card rounded-2xl p-3.5 border border-purple-500/30"
                style="background: rgba(168, 85, 247, 0.05);">
                <p class="font-label-caps text-[9px] uppercase text-purple-300 tracking-wider">TOTAL TIME</p>
                <p class="font-headline-md text-lg font-bold text-purple-300 mt-1">{{ number_format($totalTimeSum, 3) }}s
                </p>
            </div>
        </div>

        @include('fsrapp.home.components.banners', [ 'banners' => $banners,'pageType' => 'quiz_history_answer_sheet'])

        <!-- Questions Answer Sheet List -->
        <div class="space-y-4">
            @foreach ($sheet as $idx => $item)
                @php
                    $isCorrect = $item->is_correct;
                    $englishQ = $item->question;
                    $hindiQ = $item->hindi_question;
                    $userAns = $item->user_answer;
                    $correctAns = $item->correct_answer;
                    $qAudio = $item->question_audio ?? null;
                    $qMedia = $item->media_url ?? null;
                @endphp

                <div class="ans-card {{ $isCorrect ? 'correct' : 'incorrect' }}">
                    <div class="flex justify-between items-center gap-2">
                        <span class="status-badge {{ $isCorrect ? 'text-emerald-400' : 'text-red-400' }}">
                            <span class="material-symbols-outlined text-[17px]"
                                style="font-variation-settings: 'FILL' 1;">{{ $isCorrect ? 'check_circle' : 'cancel' }}</span>
                            Q{{ $idx + 1 }}. {{ $isCorrect ? 'Correct' : 'Incorrect' }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if (!empty($qAudio))
                                <button type="button"
                                    class="q-audio-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-500/20 hover:bg-purple-500/30 border border-purple-400/40 text-purple-300 active:scale-95 text-xs font-bold shrink-0 shadow-sm transition-all"
                                    data-audio-url="{{ $qAudio }}" onclick="toggleQuestionSheetAudio(this)"
                                    title="Play Question Audio">
                                    <span class="material-symbols-outlined text-[15px] q-audio-icon">volume_up</span>
                                    <span class="q-audio-label text-[10px] uppercase font-bold tracking-wider">Audio</span>
                                </button>
                            @endif
                            <span class="time-badge">
                                {{ $item->total_time ? number_format($item->total_time, 3) . 's' : '-' }}
                            </span>
                        </div>
                    </div>

                    @if (!empty($qMedia))
                        <div
                            class="q-img-wrap my-2.5 rounded-xl overflow-hidden border border-white/10 bg-black/40 shadow-inner max-h-52 flex items-center justify-center pointer-events-none select-none">
                            <img src="{{ $qMedia }}" alt="Question Media"
                                class="w-full h-auto max-h-52 object-contain rounded-xl select-none pointer-events-none"
                                loading="lazy"
                                onerror="this.closest('.q-img-wrap').style.display='none'">
                        </div>
                    @endif

                    <div class="q-text">
                        @if (!empty($hindiQ) && trim($hindiQ) !== '')
                            <p class="text-white font-bold">{{ $hindiQ }}</p>
                            @if (!empty($englishQ))
                                <p class="q-sub-text">{{ $englishQ }}</p>
                            @endif
                        @else
                            <p class="text-white font-bold">{{ $englishQ }}</p>
                        @endif
                    </div>

                    <div class="ans-info-row">
                        <p>
                            <span class="label-correct">Correct Answer:</span>
                            <span class="val-correct">{{ $correctAns }}</span>
                        </p>
                        <p>
                            <span class="text-white/60 font-semibold">Your Answer:</span>
                            <span class="{{ $isCorrect ? 'val-correct' : 'val-wrong' }}">{{ $userAns }}</span>
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

    </main>
@endsection

@push('scripts')
    <script>
        let activeQuestionAudio = null;
        let activeQuestionBtn = null;

        function toggleQuestionSheetAudio(btn) {
            const audioUrl = btn.getAttribute('data-audio-url');
            if (!audioUrl) return;

            if (activeQuestionBtn === btn && activeQuestionAudio && !activeQuestionAudio.paused) {
                stopQuestionSheetAudio();
                return;
            }

            stopQuestionSheetAudio();

            try {
                const audio = new Audio(audioUrl);
                audio.volume = 1.0;
                activeQuestionAudio = audio;
                activeQuestionBtn = btn;

                btn.classList.add('!bg-purple-600', '!text-white', '!border-purple-400', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                btn.classList.remove('bg-purple-500/20', 'text-purple-300');
                const icon = btn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'pause';
                const label = btn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Playing';

                audio.play().catch(e => {
                    console.warn('Failed to play question audio:', e);
                    stopQuestionSheetAudio();
                });

                audio.onended = function() {
                    stopQuestionSheetAudio();
                };
                audio.onerror = function() {
                    stopQuestionSheetAudio();
                };
            } catch (e) {
                console.error('Question audio error:', e);
                stopQuestionSheetAudio();
            }
        }

        function stopQuestionSheetAudio() {
            if (activeQuestionAudio) {
                try {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.currentTime = 0;
                } catch (e) {}
                activeQuestionAudio = null;
            }
            if (activeQuestionBtn) {
                activeQuestionBtn.classList.remove('!bg-purple-600', '!text-white', '!border-purple-400', 'shadow-[0_0_15px_rgba(168,85,247,0.7)]', 'animate-pulse');
                activeQuestionBtn.classList.add('bg-purple-500/20', 'text-purple-300');
                const icon = activeQuestionBtn.querySelector('.q-audio-icon');
                if (icon) icon.textContent = 'volume_up';
                const label = activeQuestionBtn.querySelector('.q-audio-label');
                if (label) label.textContent = 'Audio';
                activeQuestionBtn = null;
            }
        }
    </script>
@endpush
