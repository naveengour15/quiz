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
                        {{ __('app.privacy_policy') }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        FSR Quiz Legal & Privacy
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-16 min-h-screen relative z-10">

        <!-- Top Hero Card -->
        <section class="mb-5 rounded-2xl border border-[#818cf8]/30 bg-gradient-to-br from-[#1e144a] via-[#2d1b54] to-[#431f6d] p-5 shadow-[0_12px_30px_rgba(129,140,248,0.18)]">
            <div class="flex items-center gap-3 mb-2.5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                    style="background: rgba(129,140,248,0.2); border: 1px solid rgba(129,140,248,0.35);">
                    <span class="material-symbols-outlined text-2xl text-[#a5b4fc]">shield</span>
                </div>
                <div>
                    <h2 class="font-display text-lg font-extrabold leading-tight text-[#e8ddff]">FSR Quiz Privacy Policy</h2>
                    <p class="text-[11px] font-semibold text-[#ffd76a]">Last Updated: October 2026</p>
                </div>
            </div>
            <p class="text-[12.5px] leading-relaxed text-[#e8ddff]/80">
                This policy explains what information FSR Quiz collects, how we use it, and the choices and rights you have regarding your personal data.
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
                        1. Who This Policy Covers
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        This policy applies to anyone who uses the FSR Quiz app or website — whether you have created an account or are playing as a guest. It explains how your information is handled during quiz participation, score calculation, and reward redemptions.
                    </p>
                </div>

                <!-- Section 2 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        2. Information We Collect
                    </h3>
                    <ul class="text-[12px] space-y-2 text-[#e8ddff]/75 pl-3 list-disc">
                        <li><strong class="text-[#e8ddff]">Account Data:</strong> Mobile number used for secure OTP verification, your player name, and optional profile details.</li>
                        <li><strong class="text-[#e8ddff]">Quiz Activity:</strong> Question responses, time taken, score calculation, and rank tracking to determine fair gameplay.</li>
                        <li><strong class="text-[#e8ddff]">Wallet & Rewards:</strong> Points accumulated, reward history, and redemption details. Points cannot be transferred or converted to direct cash.</li>
                        <li><strong class="text-[#e8ddff]">Device Data:</strong> Basic device model and OS version used purely to ensure app stability and deliver notifications.</li>
                    </ul>
                </div>

                <!-- Section 3 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        3. How We Use Your Information
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        Your information is used strictly to provide you with seamless gameplay, calculate real-time leaderboard ranks, authenticate you securely via OTP, process prize redemptions, and prevent cheating or bot participation.
                    </p>
                </div>

                <!-- Section 4 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        4. Data Protection & Sharing
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        We do not sell, rent, or trade your personal information with third parties for marketing purposes. All user transactions and communications are encrypted and kept safe.
                    </p>
                </div>

                <!-- Section 5 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        5. Account Deletion & Rights
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75 mb-2">
                        You have full control over your data. You may delete your account at any time directly through the app under Profile &rarr; Delete Account, which immediately deactivates your login and clears your session.
                    </p>
                    <a href="{{ route('fsrapp.delete-account') }}" class="text-[11px] font-bold text-red-400 hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">person_remove</span>
                        Go to Delete Account page &rarr;
                    </a>
                </div>

                <!-- Section 6 -->
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <h3 class="font-display text-[14px] font-bold text-[#ffd76a] mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ffd76a]"></span>
                        6. Contact & Support
                    </h3>
                    <p class="text-[12px] leading-relaxed text-[#e8ddff]/75">
                        For any privacy-related queries or requests, you can email our support team at <a href="mailto:support@chaloonline.in" class="text-[#00E5A0] underline">support@chaloonline.in</a>.
                    </p>
                </div>
            </div>
        @endif

    </main>
@endsection

