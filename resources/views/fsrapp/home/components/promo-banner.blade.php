<!-- ===== Promo Banner Component ===== -->
@if (isset($megaTournaments) && $megaTournaments->count() > 0)
    @foreach ($megaTournaments as $tournament)
        <div class="fsr-banner mb-6" style="min-height:150px;">
            <div class="fsr-banner-target">
                <div class="fsr-banner-ring"></div>
                <div class="absolute inset-3 rounded-full flex items-center justify-center"
                    style="background:radial-gradient(circle, rgba(217,70,239,0.25), transparent 70%);">
                    <span style="font-size:44px; filter:drop-shadow(0 0 12px rgba(217,70,239,0.5));">🎯</span>
                </div>
            </div>

            <div class="fsr-banner-float" style="top:14px; right:14px;">
                <p class="text-[9.5px] font-semibold flex items-center gap-1" style="color:#d8b4fe;">
                    <span class="material-symbols-outlined text-[11px]">emoji_events</span> Rewards
                </p>
                <p class="font-mono text-[12px] font-bold mt-0.5" style="color:#ffe9b0;">Up to
                    {{ $tournament->no_of_points ?? '1,000' }} pts</p>
            </div>

            <div class="relative z-10">
                <span class="fsr-banner-badge">
                    <span class="material-symbols-outlined text-[12px]">bolt</span> NEW TOURNAMENT
                </span>
                <h3 class="font-display text-[20px] font-extrabold leading-tight mt-2" style="color:#fff;">
                    {{ $tournament->title ?? 'Weekly Mega Tournament' }}</h3>
                <p class="text-[12px] mt-1 max-w-[62%]" style="color:rgba(232,221,255,0.65);">Compete live, climb
                    the board & win big — launching soon.</p>
                <button class="fsr-banner-cta mt-3">
                    <span class="material-symbols-outlined text-[15px]">notifications_active</span>
                    Notify Me
                </button>
            </div>
        </div>
    @endforeach
@else
    <div class="fsr-banner mb-6" style="min-height:150px;">
        <div class="fsr-banner-target">
            <div class="fsr-banner-ring"></div>
            <div class="absolute inset-3 rounded-full flex items-center justify-center"
                style="background:radial-gradient(circle, rgba(217,70,239,0.25), transparent 70%);">
                <span style="font-size:44px; filter:drop-shadow(0 0 12px rgba(217,70,239,0.5));">🎯</span>
            </div>
        </div>
        <div class="fsr-banner-float" style="top:14px; right:14px;">
            <p class="text-[9.5px] font-semibold flex items-center gap-1" style="color:#d8b4fe;">
                <span class="material-symbols-outlined text-[11px]">emoji_events</span> Rewards
            </p>
            <p class="font-mono text-[12px] font-bold mt-0.5" style="color:#ffe9b0;">Up to 1,000 pts</p>
        </div>
        <div class="relative z-10">
            <span class="fsr-banner-badge">
                <span class="material-symbols-outlined text-[12px]">bolt</span> NEW TOURNAMENT
            </span>
            <h3 class="font-display text-[20px] font-extrabold leading-tight mt-2" style="color:#fff;">Weekly
                Mega<br>Tournament</h3>
            <p class="text-[12px] mt-1 max-w-[62%]" style="color:rgba(232,221,255,0.65);">Compete live, climb the
                board & win big — launching soon.</p>
            <button class="fsr-banner-cta mt-3">
                <span class="material-symbols-outlined text-[15px]">notifications_active</span>
                Notify Me
            </button>
        </div>
    </div>
@endif
