<!-- ===== Practice Quiz Component ===== -->
@if ($practiceQuizzes && $practiceQuizzes->count() > 0)
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.practice_quiz') }}</h2>
        <a href="{{ route('fsrapp.practice-quiz.index') }}" class="text-[12px] font-semibold" style="color:#e879f9;">{{ __('app.see_all') }}</a>
    </div>

    <div class="flex flex-col gap-3 mb-6">
        @foreach ($practiceQuizzes as $quiz)
            @php
                $isCompleted = $quiz->is_completed ?? false;
                $isUnlocked = $quiz->is_unlocked ?? $loop->first;
                $serial = $quiz->serial_number ?? $loop->iteration;
                $playUrl = route('fsrapp.practice-quiz.play', $quiz->id);
            @endphp

            @if (!$isCompleted && $isUnlocked)
                <div class="w-full border-rotate-wrapper" onclick="window.location.href='{{ $playUrl }}'">
                    <div
                        class="w-full p-3.5 transition-all flex items-center justify-between gap-2.5 border-rotate-inner hover:brightness-110">

                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <!-- Serial Badge -->
                            <div
                                class="w-9 h-9 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 font-display bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                #{{ $serial }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-semibold truncate" style="color:#e8ddff;">
                                    Level {{ $serial }}: {{ $quiz->title ?? ($quiz->name ?? 'Practice Quiz') }}
                                </p>
                                <p class="text-[11px] mt-0.5 truncate" style="color:rgba(232,221,255,0.5);">
                                    <span style="color:#ffd76a; font-weight:600;">{{ __('app.unlocked') }}</span> · {{ __('app.play') }}
                                </p>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button
                            class="px-3.5 py-1.5 rounded-full text-[11px] font-bold shrink-0 bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] hover:brightness-110 shadow-md shadow-fuchsia-500/20">
                            {{ __('app.start') }}
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <script>
        if (typeof window.showPracticeLockedToast === 'undefined') {
            window.showPracticeLockedToast = function(requiredLevel) {
                if (typeof toast === 'function') {
                    toast('Please complete Level ' + requiredLevel + ' practice quiz first to unlock this level! 🔒',
                        'warning');
                } else if (typeof showToast === 'function') {
                    showToast('Please complete Level ' + requiredLevel +
                        ' practice quiz first to unlock this level! 🔒', 'warning');
                } else {
                    alert('Please complete Level ' + requiredLevel + ' practice quiz first to unlock this level!');
                }
            };
        }
        if (typeof window.showPracticeCompletedToast === 'undefined') {
            window.showPracticeCompletedToast = function() {
                if (typeof toast === 'function') {
                    toast('You have already completed this practice quiz! 🎯', 'info');
                } else if (typeof showToast === 'function') {
                    showToast('You have already completed this practice quiz! 🎯', 'info');
                } else {
                    alert('You have already completed this practice quiz!');
                }
            };
        }
    </script>
@endif
