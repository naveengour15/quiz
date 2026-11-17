<!-- ===== Top Quiz Players Component ===== -->
<div class="mt-4 mb-2">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-display text-base font-bold" style="color:#e8ddff;">🔥 {{ __('app.most_active_players') }}</h2>
    </div>

    <div class="flex flex-col gap-2.5">
        @if (isset($topQuizPlayers) && count($topQuizPlayers) > 0)
            @foreach ($topQuizPlayers as $index => $player)
                <div class="fsr-lb-row {{ $index == 0 ? 'gold' : '' }}">
                    <div class="fsr-lb-rank"
                        style="{{ $index == 0 ? 'background:linear-gradient(160deg, #ffe9b0, #f4bf32); color:#3a2400;' : ($index == 1 ? 'background:linear-gradient(160deg, #e2e8f0, #94a3b8); color:#0f172a;' : ($index == 2 ? 'background:linear-gradient(160deg, #fcd34d, #b45309); color:#451a03;' : 'background:rgba(255,255,255,0.08); color:#e8ddff;')) }}">
                        {{ $index + 1 }}
                    </div>

                    <div class="fsr-avatar"
                        style="{{ $index == 0 ? 'background:linear-gradient(160deg, rgba(244,191,50,0.4), rgba(244,191,50,0.15)); color:#ffe9b0;' : 'background:linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.12)); color:#d8b4fe;' }}">
                        {{ strtoupper(substr($player['name'] ?? 'User', 0, 2)) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-[13.5px] font-semibold truncate" style="color:#e8ddff;">
                            {{ $player['name'] ?? 'User' }}
                        </p>
                        <p class="text-[11.5px] truncate" style="color:rgba(232,221,255,0.5);">
                            {{ __('app.top_performer') }}
                        </p>
                    </div>

                    <div class="flex flex-col items-end">
                        <span class="text-[13px] font-mono font-bold" style="color:#4ade80;">
                            {{ $player['correct_answers_count'] ?? 0 }}
                        </span>
                        <span class="text-[10px]" style="color:rgba(232,221,255,0.4);">
                            {{ __('app.correct_answers') }}
                        </span>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
