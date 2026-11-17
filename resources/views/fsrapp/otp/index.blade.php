@extends('fsrapp.layouts.app')
@section('content')

    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
    </div>

    <div class="max-w-md mx-auto min-h-screen flex flex-col justify-center px-4 py-10 relative z-10">

        <!-- Header Icon & Title -->
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="pf-neon-ring w-18 h-18 mb-4">
                <div class="w-full h-full rounded-full overflow-hidden bg-[#1e1638] flex items-center justify-center shadow-[0_0_24px_rgba(217,70,239,0.35)]">
                    <span class="material-symbols-outlined text-[32px]" style="color:#d8b4fe;">sms</span>
                </div>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-[#e8ddff]">Verify OTP</h2>
            <p class="text-[13px] text-[#c9b6ff]/75 mt-1 text-center max-w-[280px] leading-relaxed">
                We've sent a 4-digit OTP via <span class="text-[#86efac] font-bold">SMS</span> to <span class="text-[#ffd76a] font-mono font-bold" id="otp-mobile-display">+91 {{ $mobile }}</span>
            </p>
        </div>

        <!-- Notifications -->
        @if (session('error'))
            <div class="mb-4 p-3.5 bg-red-500/15 border border-red-500/30 text-red-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="mb-4 p-3.5 bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3.5 bg-red-500/15 border border-red-500/30 text-red-300 rounded-xl text-center font-medium text-sm backdrop-blur-md">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- OTP Form Card -->
        @php
            $redirectParam = request('redirect', session('redirect_to', session('url.intended')));
        @endphp
        <form action="{{ route('fsrapp.verify-otp', array_filter(['redirect' => $redirectParam])) }}" method="POST"
            class="rounded-3xl p-6 relative overflow-hidden"
            style="background: linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)); backdrop-filter: blur(16px); border: 1px solid rgba(196, 158, 255, 0.2); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);"
            id="otp-form">
            @csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            @if ($redirectParam)
                <input type="hidden" name="redirect" value="{{ $redirectParam }}">
            @endif

            <!-- Digits -->
            <div class="flex justify-center gap-3 my-2" id="otp-boxes">
                <input maxlength="1" name="otp[]" inputmode="numeric" data-idx="0"
                    class="otp-box w-14 h-16 text-center bg-[rgba(255,255,255,0.05)] border border-[rgba(196,158,255,0.2)] focus:border-[#f4bf32] focus:bg-[rgba(244,191,50,0.1)] focus:shadow-[0_0_18px_rgba(244,191,50,0.3)] rounded-2xl font-mono text-3xl font-extrabold text-[#ffe9b0] focus:outline-none transition-all"
                    required autofocus>
                <input maxlength="1" name="otp[]" inputmode="numeric" data-idx="1"
                    class="otp-box w-14 h-16 text-center bg-[rgba(255,255,255,0.05)] border border-[rgba(196,158,255,0.2)] focus:border-[#f4bf32] focus:bg-[rgba(244,191,50,0.1)] focus:shadow-[0_0_18px_rgba(244,191,50,0.3)] rounded-2xl font-mono text-3xl font-extrabold text-[#ffe9b0] focus:outline-none transition-all"
                    required>
                <input maxlength="1" name="otp[]" inputmode="numeric" data-idx="2"
                    class="otp-box w-14 h-16 text-center bg-[rgba(255,255,255,0.05)] border border-[rgba(196,158,255,0.2)] focus:border-[#f4bf32] focus:bg-[rgba(244,191,50,0.1)] focus:shadow-[0_0_18px_rgba(244,191,50,0.3)] rounded-2xl font-mono text-3xl font-extrabold text-[#ffe9b0] focus:outline-none transition-all"
                    required>
                <input maxlength="1" name="otp[]" inputmode="numeric" data-idx="3"
                    class="otp-box w-14 h-16 text-center bg-[rgba(255,255,255,0.05)] border border-[rgba(196,158,255,0.2)] focus:border-[#f4bf32] focus:bg-[rgba(244,191,50,0.1)] focus:shadow-[0_0_18px_rgba(244,191,50,0.3)] rounded-2xl font-mono text-3xl font-extrabold text-[#ffe9b0] focus:outline-none transition-all"
                    required>
            </div>

            <!-- Submit -->
            <button type="submit" id="verify-otp-btn"
                style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%); color: #1a0b3d; font-weight: 800; box-shadow: 0 8px 25px rgba(217, 70, 239, 0.4); border: none;"
                class="w-full mt-6 py-4 rounded-2xl font-display text-base tracking-wide flex items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <span class="material-symbols-outlined text-[20px] font-bold">verified</span>
                <span>Verify & Continue</span>
            </button>

            <!-- Secondary Actions -->
            <div class="flex justify-between items-center mt-5 pt-3 border-t border-[rgba(255,255,255,0.08)]">
                <a href="{{ route('fsrapp.login', array_filter(['redirect' => $redirectParam])) }}" class="text-[#c9b6ff]/75 hover:text-[#e8ddff] text-xs font-semibold flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    <span>Change number</span>
                </a>
                <button type="submit" form="resend-otp-form" class="text-[#ffd76a] hover:text-[#ffe9b0] text-xs font-bold flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">autorenew</span>
                    <span>Resend OTP</span>
                </button>
            </div>
        </form>

        <form id="resend-otp-form" action="{{ route('fsrapp.resend-otp', array_filter(['redirect' => $redirectParam])) }}" method="POST" class="hidden">
            @csrf
            @if ($redirectParam)
                <input type="hidden" name="redirect" value="{{ $redirectParam }}">
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            try {
                if (typeof window.ensureSoundCache === 'function') {
                    window.ensureSoundCache();
                }
                if (typeof window.preloadAllQuizSounds === 'function') {
                    window.preloadAllQuizSounds();
                }
            } catch (e) {}

            const boxes = document.querySelectorAll('.otp-box');
            if (boxes.length) {
                boxes[0].focus();
                boxes.forEach((b, i) => {
                    b.addEventListener('input', () => {
                        b.value = b.value.replace(/\D/g, '');
                        if (b.value && boxes[i + 1]) boxes[i + 1].focus();
                    });
                    b.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !b.value && boxes[i - 1]) boxes[i - 1].focus();
                    });
                });
            }
        });
    </script>
@endpush