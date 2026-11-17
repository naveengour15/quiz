<!-- ===== Stats Strip Component ===== -->
<div class="grid grid-cols-3 gap-3 mb-6">
    <div class="fsr-stat-card">
        <div class="fsr-stat-icon"
            style="background:linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08));">
            <span class="material-symbols-outlined text-[16px]" style="color:#ffd76a;">local_fire_department</span>
        </div>
        <div>
            <p class="font-display text-lg font-extrabold leading-none" style="color:#e8ddff;">
                {{ $streak ?? 0 }}</p>
            <p class="text-[10.5px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.day_streak') }}</p>
        </div>
    </div>
    <div class="fsr-stat-card">
        <div class="fsr-stat-icon"
            style="background:linear-gradient(160deg, rgba(168,85,247,0.35), rgba(168,85,247,0.1));">
            <span class="material-symbols-outlined text-[16px]" style="color:#d8b4fe;">emoji_events</span>
        </div>
        <div>
            <p class="font-display text-lg font-extrabold leading-none" style="color:#e8ddff;">
                #{{ $rank ?? 0 }}</p>
            <p class="text-[10.5px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.your_rank') }}</p>
        </div>
    </div>
    <div class="fsr-stat-card">
        <div class="fsr-stat-icon"
            style="background:linear-gradient(160deg, rgba(74,222,128,0.3), rgba(74,222,128,0.08));">
            <span class="material-symbols-outlined text-[16px]" style="color:#86efac;">verified</span>
        </div>
        <div>
            <p class="font-display text-lg font-extrabold leading-none" style="color:#e8ddff;">86%</p>
            <p class="text-[10.5px] mt-1" style="color:rgba(232,221,255,0.5);">{{ __('app.accuracy') }}</p>
        </div>
    </div>
</div>
