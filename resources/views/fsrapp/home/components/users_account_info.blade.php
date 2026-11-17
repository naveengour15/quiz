<!-- ===== Users Account Info Component ===== -->
@php
    use App\Models\Leaderboard;
    use Illuminate\Support\Facades\Cache;
    $u = $user ?? \Illuminate\Support\Facades\Auth::user();
    $userId = $u->id ?? null;
    $user_rank = 0;
    if ($userId) {
        $user_rank = Cache::remember("user_lb_rank_{$userId}", 300, function () use ($userId) {
            $leaderboard = Leaderboard::where('user_id', $userId)->where('status', 'active')->first();
            return $leaderboard ? $leaderboard->rank : 0;
        });
    }
@endphp


<div class="grid grid-cols-3 gap-2.5 mb-4">
    <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#00E5A0]/25 to-[#00E5A0]/5">
            <span class="material-symbols-outlined text-[#00E5A0] text-[19px]" style="font-variation-settings:'FILL' 1;">account_balance_wallet</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#ffd76a] leading-tight">
            {{ (float) ($u->wallet_balance ?? 0) == (int) ($u->wallet_balance ?? 0) ? (int) ($u->wallet_balance ?? 0) : (float) ($u->wallet_balance ?? 0) }}</p>
        <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">{{ __('app.wallet') }}</p>
    </div>
    {{-- <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#ffd76a]/25 to-[#ffd76a]/5">
            <span class="material-symbols-outlined text-[#ffd76a] text-[19px]" style="font-variation-settings:'FILL' 1;">military_tech</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#e8ddff] leading-tight">{{ $user_rank ?? '—' }}
        </p>
        <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">RANK</p>
    </div> --}}
    {{-- <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#d2bbff]/25 to-[#d2bbff]/5">
            <span class="material-symbols-outlined text-[#d2bbff] text-[19px]" style="font-variation-settings:'FILL' 1;">workspace_premium</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#d2bbff] leading-tight">
            {{ $u->leader_board_point ?? 0 }}</p>
        <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">LB MARKS</p>
    </div> --}}
    {{-- <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#e879f9]/25 to-[#e879f9]/5">
            <span class="material-symbols-outlined text-[#e879f9] text-[19px]" style="font-variation-settings:'FILL' 1;">add_circle</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#e879f9] leading-tight">
            {{ $u->create_quiz_limit ?? 0 }}</p>
        <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">CREATE LIMIT</p>
    </div> --}}
    <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#7c3aed]/25 to-[#7c3aed]/5">
            <span class="material-symbols-outlined text-[#a78bfa] text-[19px]" style="font-variation-settings:'FILL' 1;">sports_esports</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#a78bfa] leading-tight">
            {{ $u->available_quiz_limit ?? 0 }}</p>
            <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">{{ __('app.remaining_quiz') }}</p>
    </div>
    <div class="pf-stat">
        <div class="pf-stat-icon kbc-hex bg-gradient-to-br from-[#d2bbff]/25 to-[#d2bbff]/5">
            <span class="material-symbols-outlined text-[#d2bbff] text-[19px]" style="font-variation-settings:'FILL' 1;">workspace_premium</span>
        </div>
        <p class="font-display text-base font-extrabold text-[#d2bbff] leading-tight">
            {{ (float) ($u->leader_board_point ?? 0) == (int) ($u->leader_board_point ?? 0) ? (int) ($u->leader_board_point ?? 0) : (float) ($u->leader_board_point ?? 0) }}
        </p>
        <p class="font-mono text-white/50 text-[9px] mt-0.5 tracking-wider uppercase">{{ __('app.leaderboard_points') }}</p>
    </div>
</div>
