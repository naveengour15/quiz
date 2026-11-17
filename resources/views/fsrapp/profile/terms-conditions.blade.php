@extends('fsrapp.layouts.app')

@section('content')
    <!-- Fixed Mobile App Header -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.90); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ route('fsrapp.profile') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 active:scale-95 transition-all shrink-0"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-base font-extrabold tracking-tight truncate" style="color:#e8ddff;">
                        {{ __('app.terms_conditions') }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        FSR Quiz Terms of Use
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-16 min-h-screen relative z-10">

        <!-- Top Hero Card -->
        <section class="mb-5 rounded-2xl border border-amber-400/30 bg-gradient-to-br from-[#2a1b0e] via-[#3d2716] to-[#4d1f52] p-5 shadow-[0_12px_30px_rgba(251,191,36,0.15)]">
            <div class="flex items-center gap-3 mb-2.5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                    style="background: rgba(251,191,36,0.2); border: 1px solid rgba(251,191,36,0.35);">
                    <span class="material-symbols-outlined text-2xl text-[#ffd76a]">description</span>
                </div>
                <div>
                    <h2 class="font-display text-lg font-extrabold leading-tight text-[#ffd76a]">FSR Quiz Terms of Use</h2>
                    <p class="text-[11px] font-semibold text-[#e8ddff]/70">Rules, eligibility & service terms</p>
                </div>
            </div>
            <p class="text-[12.5px] leading-relaxed text-[#e8ddff]/80">
                Please read these terms carefully before participating in quizzes. By using FSR Quiz, you agree to abide by these terms and conditions.
            </p>
        </section>

        @if ($cmsPage && !empty($cmsPage->content))
            <div class="glass-card rounded-2xl p-5 border border-white/10 bg-white/[0.03] text-sm text-[#e8ddff]/90 leading-relaxed space-y-4">
                {!! $cmsPage->content !!}
            </div>
        @else
            <div class="space-y-3.5">
                <!-- Section 1 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        1. What is FSR Quiz?
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        FSR Quiz is a free-to-play knowledge and skill quiz platform. Users answer questions, score marks, earn points, and climb the leaderboard for rewards like mobile recharges and gift vouchers. There is no entry fee or deposit required to play quizzes on this platform.
                    </p>
                </div>

                <!-- Section 2 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        2. Eligibility & Single Account Rule
                    </h3>
                    <ul class="text-[12px] space-y-2 text-[#e8ddff]/75 pl-3 list-disc">
                        <li>You must be at least 18 years of age or possess legal guardian consent to register.</li>
                        <li><strong>One Person, One Account:</strong> Maintaining multiple accounts using duplicate phone numbers or spoofing to farm points or referral bonuses is strictly prohibited.</li>
                        <li>You are responsible for keeping your OTP and account credentials safe.</li>
                    </ul>
                </div>

                <!-- Section 3 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        3. Fair Play & Scoring Rules
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        All quizzes are timed. Ranks are awarded strictly according to the highest score and shortest completion time. The use of automated scripts, emulators, bots, or any cheating technique will result in immediate disqualification and permanent ban.
                    </p>
                </div>

                <!-- Section 4 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        4. Points & Reward Redemption
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        Points earned in FSR Quiz exist purely inside the platform ecosystem. Points cannot be directly withdrawn as cash or transferred to bank accounts. Points may be redeemed for available recharge plans or digital vouchers as provided in the Redeem section.
                    </p>
                </div>

                <!-- Section 5 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        5. Account Deletion & Termination
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        You may stop using FSR Quiz and delete your account at any time via the Profile &rarr; Delete Account option. FSR Quiz reserves the right to suspend any account engaging in abusive behavior, fraud, or violation of fair play.
                    </p>
                </div>

                <!-- Section 6 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        6. Support & Contact
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        For any support or disputes concerning rules or rewards, contact us at <a href="mailto:support@chaloonline.in" class="text-[#ffd76a] underline">support@chaloonline.in</a>.
                    </p>
                </div>
            </div>
        @endif

    </main>
@endsection

