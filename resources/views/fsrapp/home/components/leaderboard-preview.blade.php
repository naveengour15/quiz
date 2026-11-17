<!-- ===== Leaderboard Preview Component ===== -->
<div class="flex items-center justify-between mb-3">
    <h2 class="font-display text-base font-bold" style="color:#e8ddff;">{{ __('app.yearly_leaderboard') }}</h2>
    <a href="{{ route('fsrapp.leaderboard') }}" class="text-[12px] font-semibold cursor-pointer"
        style="color:#e879f9;">{{ __('app.full_board') }}</a>
</div>
<div class="flex flex-col gap-2.5">
    @if (isset($topPlayers) && $topPlayers->count() > 0)
        @foreach ($topPlayers as $index => $player)
            <div class="fsr-lb-row {{ $index == 0 ? 'gold' : '' }}">
                <div class="fsr-lb-rank"
                    style="{{ $index == 0 ? 'background:linear-gradient(160deg, #ffe9b0, #f4bf32); color:#3a2400;' : 'background:rgba(255,255,255,0.08); color:#e8ddff;' }}">
                    {{ $index + 1 }}</div>
                <div class="fsr-avatar"
                    style="{{ $index == 0 ? 'background:linear-gradient(160deg, rgba(244,191,50,0.4), rgba(244,191,50,0.15)); color:#ffe9b0;' : 'background:linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.12)); color:#d8b4fe;' }}">
                    {{ strtoupper(substr($player->user?->name ?? 'User', 0, 2)) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13.5px] font-semibold truncate" style="color:#e8ddff;">
                        {{ $player->user?->name ?? 'User' }}</p>
                    <p class="text-[11.5px] truncate" style="color:rgba(232,221,255,0.5);">{{ __('app.rank') }}
                        {{ $player->rank ?? $index + 1 }}</p>
                </div>
                <span class="text-[13px] font-mono font-bold"
                    style="{{ $index == 0 ? 'color:#ffe9b0;' : 'color:#e8ddff;' }}">{{ number_format($player->points ?? 0) }}</span>
            </div>
        @endforeach
     @else
        <!-- ✅ Professional Empty State for Leaderboard -->
        <div class="flex flex-col items-center justify-center py-8 px-4 text-center rounded-2xl mt-4" 
             style="background: rgba(255, 255, 255, 0.03); border: 1px dashed rgba(232, 221, 255, 0.15);">
            
            <!-- Trophy Icon -->
            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
                 style="background: linear-gradient(135deg, rgba(244, 191, 50, 0.2), rgba(244, 191, 50, 0.05)); border: 1px solid rgba(244, 191, 50, 0.3);">
                <span class="material-symbols-outlined text-3xl" style="color:#ffd76a;">emoji_events</span>
            </div>
            
            <!-- Title -->
            <h3 class="font-display text-sm font-bold mb-1" style="color: #e8ddff;">{{ __('app.no_rankings_yet') }}</h3>
            
            <!-- Subtitle -->
            <p class="text-[12px] max-w-[220px]" style="color: rgba(232, 221, 255, 0.5);">
                {{ __('app.first_leaderboard_spot') }}
            </p>
            
            <!-- Call to Action Button -->
            <button onclick="navigate('/home')" 
                    class="mt-4 px-5 py-2 rounded-lg text-xs font-semibold transition-transform active:scale-95 flex items-center gap-1.5"
                    style="background: linear-gradient(135deg, #f4bf32, #d97706); color: #3a2400; box-shadow: 0 4px 12px rgba(244, 191, 50, 0.25);">
                <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                {{ __('app.play_now') }}
            </button>
        </div>
    @endif
</div>
