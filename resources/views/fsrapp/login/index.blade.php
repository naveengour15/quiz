@extends('fsrapp.layouts.app')
@section('content')

    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
    </div>

    <div class="max-w-md mx-auto min-h-screen flex flex-col justify-center px-4 py-10 relative z-10">

        <!-- Logo & Header -->
        <div class="flex flex-col items-center mb-8 text-center">
            <div class="w-20 h-20 mb-4 overflow-hidden flex items-center justify-center">
                <img src="{{ asset('fsrapp/images/logo.webp') }}"
                    alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="font-display text-3xl font-extrabold tracking-tight text-[#e8ddff]">FSRQuiz</h1>
            <p class="text-[13.5px] font-medium text-[#c9b6ff]/75 tracking-wide mt-1">{{ __('app.play_compete_win') }}</p>
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

        <!-- Login Form Card -->
        @php
            $redirectParam = request('redirect', session('redirect_to', session('url.intended')));
        @endphp
        <form action="{{ route('fsrapp.send-otp', array_filter(['ref' => request('ref'), 'redirect' => $redirectParam])) }}" method="POST"
            class="rounded-3xl p-6 relative overflow-hidden"
            style="background: linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)); backdrop-filter: blur(16px); border: 1px solid rgba(196, 158, 255, 0.2); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);">
            @csrf
            @if ($redirectParam)
                <input type="hidden" name="redirect" value="{{ $redirectParam }}">
            @endif

            <h2 class="font-display text-xl font-bold text-[#e8ddff] mb-1">{{ __('app.login_signup') }}</h2>
            <p class="text-[13px] text-[#c9b6ff]/70 mb-6">{{ __('app.enter_mobile_otp') }}</p>

            <label class="font-mono text-[11px] font-bold text-[#ffd76a] tracking-wider uppercase mb-2 block">{{ strtoupper(__('app.mobile_number')) }}</label>
            <div class="flex items-center gap-3 bg-[rgba(255,255,255,0.04)] border border-[rgba(196,158,255,0.2)] focus-within:border-[#d946ef] focus-within:shadow-[0_0_18px_rgba(217,70,239,0.3)] rounded-2xl px-4 py-3.5 transition-all">
                <span class="font-mono font-bold text-[#ffd76a] text-base border-r border-[rgba(255,255,255,0.12)] pr-3">+91</span>
                <input id="login-mobile" name="mobile" type="tel" inputmode="numeric" maxlength="10" placeholder="98765 43210"
                    value="{{ old('mobile', session('pending_mobile')) }}" required
                    style="border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important;"
                    class="flex-1 min-w-0 text-[#f3edff] placeholder:text-[#c9b6ff]/35 font-mono text-lg font-bold tracking-widest focus:outline-none focus:ring-0">
            </div>

            <button type="submit" id="login-send-otp"
                style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%); color: #1a0b3d; font-weight: 800; box-shadow: 0 8px 25px rgba(217, 70, 239, 0.4); border: none;"
                class="w-full mt-6 py-4 rounded-2xl font-display text-base tracking-wide flex items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <span>{{ __('app.send_otp') }}</span>
                <span class="material-symbols-outlined text-[20px] font-bold">arrow_forward</span>
            </button>
        </form>

        <p class="text-center text-[#c9b6ff]/60 text-[12px] mt-6">{{ __('app.terms_privacy') }}</p>
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
            } catch (e) {
                console.warn('Sound preload on login error:', e);
            }
        });
    </script>
@endpush