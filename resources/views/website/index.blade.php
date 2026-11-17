<!-- ===================== HERO ===================== -->
@include('website.layout.header')

<section class="relative z-10 max-w-6xl mx-auto px-6 pt-36 pb-20 grid md:grid-cols-2 gap-14 items-center">
    <div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full mb-6"
            style="background:rgba(244,191,50,0.1); border:1px solid rgba(244,191,50,0.3);">
            <span class="w-1.5 h-1.5 rounded-full" style="background:#4ade80; box-shadow:0 0 6px #4ade80;"></span>
            <span class="text-[12px] font-bold tracking-wide" style="color:#ffe9b0;">2.4L+ PLAYERS ALREADY
                WINNING</span>
        </div>
        <h1 class="font-display font-extrabold leading-[1.05] text-5xl md:text-[56px]" style="color:#f5f0ff;">
            Play quizzes.<br>
            <span class="grad-text">Win real rewards.</span>
        </h1>
        <p class="mt-6 text-[16.5px] leading-relaxed max-w-md" style="color:rgba(232,221,255,0.65);">
            Play daily quiz rounds, sharpen up with practice sets, and turn your points into mobile recharge, bill
            payments, and gift vouchers. Zero entry fee, real rewards every week.
        </p>
        <div class="flex flex-wrap items-center gap-3 mt-8">
            <a href="{{ route('fsrapp.login') }}"
                class="btn-primary px-7 py-3.5 rounded-xl font-display font-bold text-[15px] flex items-center gap-2">
                <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                Play Free Now
            </a>
            <a href="#how" class="btn-ghost px-7 py-3.5 rounded-xl font-display font-bold text-[15px]">See how it
                works</a>
        </div>
        <div class="flex items-center gap-8 mt-10">
            <div>
                <p class="stat-num text-2xl" style="color:#ffe9b0;">2,000+</p>
                <p class="text-[11.5px] mt-0.5" style="color:rgba(232,221,255,0.5);">Rewarded every year</p>
            </div>
            <div class="w-px h-9" style="background:var(--border-glass);"></div>
            <div>
                <p class="stat-num text-2xl" style="color:#e8ddff;">4.7★</p>
                <p class="text-[11.5px] mt-0.5" style="color:rgba(232,221,255,0.5);">App store rating</p>
            </div>
            <div class="w-px h-9" style="background:var(--border-glass);"></div>
            <div>
                <p class="stat-num text-2xl" style="color:#e8ddff;">12</p>
                <p class="text-[11.5px] mt-0.5" style="color:rgba(232,221,255,0.5);">Quiz categories</p>
            </div>
        </div>
    </div>

    <!-- ===== Signature: live product mockup ===== -->
    <div class="relative flex justify-center">
        <div class="absolute rounded-full"
            style="width:340px; height:340px; top:50%; left:50%; transform:translate(-50%,-50%); background:radial-gradient(circle, rgba(232,121,249,0.16) 0%, rgba(167,139,250,0.1) 45%, transparent 72%); filter:blur(4px); z-index:0;">
        </div>
        <div class="coin-field">
            <span class="coin text-lg" style="left:8%; animation-delay:0s;">🪙</span>
            <span class="coin text-sm" style="left:75%; animation-delay:2.2s;">🪙</span>
            <span class="coin text-base" style="left:40%; animation-delay:4.4s;">🪙</span>
            <span class="coin text-sm" style="left:90%; animation-delay:6.6s;">🪙</span>
        </div>
        <div class="signature-border float-el" style="width:290px;">
            <div class="rounded-[27px] p-5" style="background:linear-gradient(160deg,#241542,#1a0e33);">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full"
                        style="background:rgba(244,191,50,0.15); color:#ffe9b0; border:1px solid rgba(244,191,50,0.35);">Q4
                        · SCIENCE</span>
                    <span class="font-mono text-[13px] font-bold" style="color:#ffe9b0;">00:08</span>
                </div>
                <h3 class="font-display font-extrabold text-[15px] leading-snug mb-4" style="color:#f5f0ff;">Which
                    planet is known as the Red Planet?</h3>
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl"
                        style="border:1px solid var(--border-glass); background:rgba(255,255,255,0.03);">
                        <span class="w-6 h-6 hex-shape flex items-center justify-center text-[11px] font-bold font-mono"
                            style="background:rgba(232,221,255,0.1); color:#e8ddff;">A</span>
                        <span class="text-[12.5px] font-semibold" style="color:#e8ddff;">Venus</span>
                    </div>
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl"
                        style="border:1px solid rgba(74,222,128,0.6); background:linear-gradient(135deg, rgba(74,222,128,0.16), rgba(74,222,128,0.03));">
                        <span class="w-6 h-6 hex-shape flex items-center justify-center text-[11px] font-bold font-mono"
                            style="background:linear-gradient(160deg,#86efac,#22c55e); color:#06280f;">B</span>
                        <span class="text-[12.5px] font-semibold" style="color:#e8ddff;">Mars</span>
                        <span class="material-symbols-outlined text-[16px] ml-auto"
                            style="color:#4ade80;">check_circle</span>
                    </div>
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl"
                        style="border:1px solid var(--border-glass); background:rgba(255,255,255,0.03);">
                        <span class="w-6 h-6 hex-shape flex items-center justify-center text-[11px] font-bold font-mono"
                            style="background:rgba(232,221,255,0.1); color:#e8ddff;">C</span>
                        <span class="text-[12.5px] font-semibold" style="color:#e8ddff;">Jupiter</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute -bottom-4 -left-2 glass-card px-4 py-2.5 flex items-center gap-2 float-el"
            style="animation-delay:1s;">
            <span class="material-symbols-outlined text-[16px]" style="color:#ffe9b0;">monetization_on</span>
            <span class="font-mono font-bold text-[13px]" style="color:#ffe9b0;">+150 pts</span>
        </div>
    </div>
</section>

<!-- ===================== HOW IT WORKS ===================== -->
<section id="how" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <p class="text-[12px] font-bold tracking-widest mb-2" style="color:#e879f9;">HOW IT WORKS</p>
    <h2 class="font-display font-extrabold text-3xl md:text-4xl mb-12" style="color:#f5f0ff;">From sign-up to
        payout, in three steps</h2>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="step-banner s1">
            <div class="step-num relative z-10" style="color:#3a2400;">1</div>
            <p class="font-display text-lg font-extrabold mt-4 relative z-10" style="color:#3a2400;">Verify with
                OTP</p>
            <p class="text-[13px] mt-1.5 relative z-10" style="color:rgba(58,36,0,0.75);">Sign up in seconds, no
                forms — just your number.</p>
        </div>
        <div class="step-banner s2">
            <div class="step-num relative z-10">2</div>
            <p class="font-display text-lg font-extrabold mt-4 relative z-10" style="color:#fff;">Play live
                quizzes</p>
            <p class="text-[13px] mt-1.5 relative z-10" style="color:rgba(255,255,255,0.82);">Answer fast, beat
                the timer, climb the leaderboard.</p>
        </div>
        <div class="step-banner s3">
            <div class="step-num relative z-10">3</div>
            <p class="font-display text-lg font-extrabold mt-4 relative z-10" style="color:#fff;">Redeem your
                points</p>
            <p class="text-[13px] mt-1.5 relative z-10" style="color:rgba(255,255,255,0.82);">Convert to recharge,
                bill payments, or gift vouchers.</p>
        </div>
    </div>
</section>

<!-- ===================== FEATURES ===================== -->
<section id="features" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <p class="text-[12px] font-bold tracking-widest mb-2" style="color:#e879f9;">WHY FSRQUIZ</p>
    <h2 class="font-display font-extrabold text-3xl md:text-4xl mb-12" style="color:#f5f0ff;">Everything you need
        in one app</h2>
    <div class="grid md:grid-cols-3 gap-4">
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(244,191,50,0.28), rgba(217,70,239,0.14));">
                <span class="material-symbols-outlined text-[20px]" style="color:#ffd76a;">category</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Quiz categories</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">GK, Sports, Bollywood,
                Science, History and more — fresh sets every day.</p>
        </div>
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(96,165,250,0.28), rgba(96,165,250,0.1));">
                <span class="material-symbols-outlined text-[20px]" style="color:#93c5fd;">school</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Practice quiz</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">Sharpen up in practice
                mode before you jump into ranked rounds.</p>
        </div>
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(217,70,239,0.28), rgba(217,70,239,0.1));">
                <span class="material-symbols-outlined text-[20px]" style="color:#f0abfc;">redeem</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Redeem offers</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">Mobile recharge,
                electricity bill payment, Flipkart & Amazon gift cards, and vouchers.</p>
        </div>
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(168,85,247,0.28), rgba(168,85,247,0.1));">
                <span class="material-symbols-outlined text-[20px]" style="color:#d8b4fe;">history</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Quiz history</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">Every quiz you've played,
                scores and points — all in one place.</p>
        </div>
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(74,222,128,0.28), rgba(74,222,128,0.1));">
                <span class="material-symbols-outlined text-[20px]" style="color:#86efac;">poll</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Surveys</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">Answer quick surveys to
                earn extra points and unlock more quiz plays.</p>
        </div>
        <div class="glass-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                style="background:linear-gradient(160deg, rgba(244,191,50,0.32), rgba(244,191,50,0.1));">
                <span class="material-symbols-outlined text-[20px]" style="color:#ffe9b0;">emoji_events</span>
            </div>
            <h3 class="font-display font-bold text-[15px] mb-1.5" style="color:#e8ddff;">Yearly leaderboard</h3>
            <p class="text-[13px] leading-relaxed" style="color:rgba(232,221,255,0.55);">Climb the yearly rankings
                and win points — rewards for the top 2,000 players.</p>
        </div>
    </div>
</section>

<!-- ===================== YEARLY LEADERBOARD REWARDS ===================== -->
<section id="leaderboard" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <p class="text-[12px] font-bold tracking-widest mb-2" style="color:#e879f9;">YEARLY LEADERBOARD</p>
    <h2 class="font-display font-extrabold text-3xl md:text-4xl mb-3" style="color:#f5f0ff;">Top players win big,
        every year</h2>
    <p class="text-[14.5px] mb-10 max-w-lg" style="color:rgba(232,221,255,0.55);">Points earned across the year
        decide your rank. Rewards go out to up to 2,000 players.</p>

    <div class="grid sm:grid-cols-2 md:grid-cols-5 gap-4">
        <div class="glass-card p-5 text-center"
            style="border-color: rgba(244,191,50,0.5); background: linear-gradient(160deg, rgba(244,191,50,0.14), rgba(255,255,255,0.02));">
            <p class="text-[11px] font-bold tracking-wide mb-2" style="color:#ffe9b0;">🥇 RANK 1</p>
            <p class="stat-num text-2xl" style="color:#ffe9b0;">51,000</p>
            <p class="text-[11px] mt-1" style="color:rgba(232,221,255,0.45);">points</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-[11px] font-bold tracking-wide mb-2" style="color:#e8ddff;">🥈 RANK 2</p>
            <p class="stat-num text-2xl" style="color:#e8ddff;">21,000</p>
            <p class="text-[11px] mt-1" style="color:rgba(232,221,255,0.45);">points</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-[11px] font-bold tracking-wide mb-2" style="color:#e8ddff;">🥉 RANK 3</p>
            <p class="stat-num text-2xl" style="color:#e8ddff;">11,000</p>
            <p class="text-[11px] mt-1" style="color:rgba(232,221,255,0.45);">points</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-[11px] font-bold tracking-wide mb-2" style="color:#e8ddff;">RANK 4</p>
            <p class="stat-num text-2xl" style="color:#e8ddff;">4,000</p>
            <p class="text-[11px] mt-1" style="color:rgba(232,221,255,0.45);">points</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-[11px] font-bold tracking-wide mb-2" style="color:#e8ddff;">RANK 5</p>
            <p class="stat-num text-2xl" style="color:#e8ddff;">4,000</p>
            <p class="text-[11px] mt-1" style="color:rgba(232,221,255,0.45);">points</p>
        </div>
    </div>
    <p class="text-[13px] mt-5" style="color:rgba(232,221,255,0.45);">+ many more rewards for ranks up to <span
            style="color:#ffd76a; font-weight:700;">2,000 players</span>.</p>
</section>

<!-- ===================== CATEGORIES ===================== -->
<section id="categories" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <p class="text-[12px] font-bold tracking-widest mb-2" style="color:#e879f9;">CATEGORIES</p>
    <h2 class="font-display font-extrabold text-3xl md:text-4xl mb-12" style="color:#f5f0ff;">Pick your arena</h2>
    <div class="flex flex-wrap gap-3">
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#ffd76a;">public</span><span
                class="text-[13.5px] font-semibold">General Knowledge</span></div>
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#93c5fd;">sports_cricket</span><span
                class="text-[13.5px] font-semibold">Sports</span></div>
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#f0abfc;">movie</span><span
                class="text-[13.5px] font-semibold">Bollywood</span></div>
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#86efac;">science</span><span
                class="text-[13.5px] font-semibold">Science</span></div>
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#7dd3fc;">history_edu</span><span
                class="text-[13.5px] font-semibold">History</span></div>
        <div class="cat-chip glass-card px-5 py-3 flex items-center gap-2.5"><span
                class="material-symbols-outlined text-[18px]" style="color:#d8b4fe;">smartphone</span><span
                class="text-[13.5px] font-semibold">Tech & Trends</span></div>
    </div>
</section>

<!-- ===================== REVIEWS ===================== -->
<section id="reviews" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <p class="text-[12px] font-bold tracking-widest mb-2" style="color:#e879f9;">PLAYERS LOVE IT</p>
    <h2 class="font-display font-extrabold text-3xl md:text-4xl mb-12" style="color:#f5f0ff;">4.7 stars, 40K+
        reviews</h2>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="glass-card p-6">
            <div class="flex gap-0.5 mb-3">
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
            </div>
            <p class="text-[13.5px] leading-relaxed mb-4" style="color:rgba(232,221,255,0.75);">"Redeemed my first
                recharge in under 2 minutes. Daily GK rounds have become part of my routine now."</p>
            <p class="text-[12.5px] font-bold" style="color:#e8ddff;">Rohit K. <span class="font-normal"
                    style="color:rgba(232,221,255,0.4);">· Indore</span></p>
        </div>
        <div class="glass-card p-6">
            <div class="flex gap-0.5 mb-3">
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
            </div>
            <p class="text-[13.5px] leading-relaxed mb-4" style="color:rgba(232,221,255,0.75);">"The leaderboard
                keeps me coming back — genuinely competitive, not just another timepass app."</p>
            <p class="text-[12.5px] font-bold" style="color:#e8ddff;">Priya J. <span class="font-normal"
                    style="color:rgba(232,221,255,0.4);">· Pune</span></p>
        </div>
        <div class="glass-card p-6">
            <div class="flex gap-0.5 mb-3">
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star</span>
                <span class="material-symbols-outlined text-[16px]"
                    style="color:#ffd76a; font-variation-settings:'FILL' 1;">star_half</span>
            </div>
            <p class="text-[13.5px] leading-relaxed mb-4" style="color:rgba(232,221,255,0.75);">"Surveys + quiz
                combo is smart. I earn points twice as fast compared to other reward apps."</p>
            <p class="text-[12.5px] font-bold" style="color:#e8ddff;">Manish K. <span class="font-normal"
                    style="color:rgba(232,221,255,0.4);">· Bhopal</span></p>
        </div>
    </div>
</section>

<!-- ===================== DOWNLOAD CTA ===================== -->
<section id="download" class="relative z-10 max-w-6xl mx-auto px-6 py-20">
    <div class="signature-border">
        <div class="rounded-[27px] p-10 md:p-14 grid md:grid-cols-2 gap-10 items-center"
            style="background:linear-gradient(135deg, #2a1650, #1a0e33 60%, #3a1230);">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full mb-4"
                    style="background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.3);">
                    <span class="w-1.5 h-1.5 rounded-full"
                        style="background:#4ade80; box-shadow:0 0 6px #4ade80;"></span>
                    <span class="text-[11.5px] font-bold tracking-wide" style="color:#86efac;">NO APP INSTALL
                        NEEDED — PLAY ON WEB</span>
                </div>
                <h2 class="font-display font-extrabold text-3xl md:text-[38px] leading-tight" style="color:#f5f0ff;">
                    Your next quiz round is waiting.</h2>
                <p class="mt-3 text-[15px]" style="color:rgba(232,221,255,0.6);">FSRQuiz runs right in your
                    browser — no Play Store or App Store needed. Tap play or scan the code to jump straight in.</p>
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <a href="{{ route('fsrapp.login') }}"
                        class="btn-primary px-6 py-3 rounded-xl font-display font-bold text-[14px] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">play_arrow</span> Play Now
                    </a>
                    <a href="{{ route('fsrapp.login') }}"
                        class="font-mono text-[12.5px] font-semibold px-1"
                        style="color:#e879f9;">fsrquiz.com/fsrapp/login</a>
                </div>
            </div>
            <div class="flex flex-col items-center md:items-end gap-3">
                <div class="rounded-2xl p-2"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(244,191,50,0.3);">
                    <img src="data:image/png;base64,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"
                        alt="Scan to play FSRQuiz" width="168" height="168" class="rounded-xl block">
                </div>
                <p class="text-[11.5px] font-semibold" style="color:rgba(232,221,255,0.5);">Scan with your camera
                    to play instantly</p>
            </div>
        </div>
    </div>
</section>

@include('website.layout.footer')