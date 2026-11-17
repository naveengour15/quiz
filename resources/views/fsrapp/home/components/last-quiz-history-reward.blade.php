<!-- ===== Last Quiz Winners Component ===== -->
<div class="mt-1 mb-2">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-display text-base font-bold" style="color:#e8ddff;">🏆 {{ __('app.winners') }}:
            {{ $lastQuizData['title'] ?? __('app.no_recent_quiz') }}</h2>
    </div>

    <div class="flex flex-col gap-2.5">
        @if (isset($lastQuizData['winners']) && count($lastQuizData['winners']) > 0)
            @foreach ($lastQuizData['winners'] as $index => $winner)
                <div class="fsr-lb-row {{ $index == 0 ? 'gold' : '' }}">
                    <div class="fsr-lb-rank"
                        style="{{ $index == 0 ? 'background:linear-gradient(160deg, #ffe9b0, #f4bf32); color:#3a2400;' : ($index == 1 ? 'background:linear-gradient(160deg, #e2e8f0, #94a3b8); color:#0f172a;' : ($index == 2 ? 'background:linear-gradient(160deg, #fcd34d, #b45309); color:#451a03;' : 'background:rgba(255,255,255,0.08); color:#e8ddff;')) }}">
                        {{ $winner['rank'] ?? $index + 1 }}
                    </div>

                    <div class="fsr-avatar"
                        style="{{ $index == 0 ? 'background:linear-gradient(160deg, rgba(244,191,50,0.4), rgba(244,191,50,0.15)); color:#ffe9b0;' : 'background:linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.12)); color:#d8b4fe;' }}">
                        {{ strtoupper(substr($winner['user_name'] ?? 'User', 0, 2)) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-[13.5px] font-semibold truncate" style="color:#e8ddff;">
                            {{ $winner['user_name'] ?? 'User' }}
                        </p>
                        <p class="text-[11.5px] truncate" style="color:rgba(232,221,255,0.5);">
                            {{ __('app.score') }}: {{ $winner['total_correct_answer'] }}/{{ $winner['total_question'] }} &bull; {{ __('app.time') }}:
                            {{ $winner['total_time'] }}s
                        </p>
                    </div>

                    <div class="flex flex-col items-end">
                        <span class="text-[13px] font-mono font-bold"
                            style="{{ $index == 0 ? 'color:#ffe9b0;' : 'color:#4ade80;' }}">
                            {{ $winner['reward_type'] == 'money' ? '₹' : '' }}{{ $winner['reward'] }}
                        </span>
                        <span class="text-[10px]" style="color:rgba(232,221,255,0.4);">
                            {{ $winner['reward_type'] === 'money' ? __('app.money') : __('app.point') }}
                        </span>
                    </div>
                </div>
            @endforeach
        @else
            <!-- ✅ Professional Empty State -->
            <div class="flex flex-col items-center justify-center py-8 px-4 text-center rounded-2xl mt-4"
                style="background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(232, 221, 255, 0.15);">

                <!-- Trophy Icon -->
                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                    style="background: linear-gradient(135deg, rgba(244, 191, 50, 0.2), rgba(244, 191, 50, 0.05)); border: 1px solid rgba(244, 191, 50, 0.3);">
                    <span class="material-symbols-outlined text-3xl" style="color: #ffd76a;">emoji_events</span>
                </div>

                <!-- Title -->
                <h3 class="font-display text-sm font-bold mb-1" style="color: #e8ddff;">{{ __('app.no_winners_yet') }}</h3>

                <!-- Subtitle -->
                <p class="text-[12px] max-w-[220px]" style="color: rgba(232, 221, 255, 0.5);">
                    {{ __('app.play_latest_quizzes') }}
                </p>
            </div>
        @endif
    </div>
</div>
