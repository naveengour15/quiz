@extends('fsrapp.layouts.app')

@section('content')
    <header class="fsr-header fixed top-0 z-40 mx-auto h-16 w-full max-w-md left-1/2 -translate-x-1/2"
        style="background:rgba(13,6,32,0.85); backdrop-filter:blur(18px); border-bottom:1px solid rgba(255,255,255,0.1);">
        <div class="flex h-full w-full items-center px-4">
            <a href="{{ route('fsrapp.home') }}"
                class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#e8ddff] transition hover:bg-white/10 active:scale-95"
                aria-label="Back to Home">
                <span class="material-symbols-outlined text-[19px]">arrow_back</span>
            </a>
            <div class="ml-3">
                <h1 class="font-display text-[15px] font-extrabold leading-none tracking-tight text-[#e8ddff]">
                    {{ __('app.how_fsrquiz_works') }}
                </h1>
                <p class="mt-1 text-[11px] font-semibold leading-none text-[#e8ddff]/45">{{ __('app.fsrquiz_guide') }}</p>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen">
        <section class="mb-5 rounded-2xl border border-amber-300/30 bg-gradient-to-br from-[#24134d] via-[#32145f] to-[#541b72] p-5 shadow-[0_12px_30px_rgba(124,58,237,0.22)]">
            <h2 class="font-display text-2xl font-extrabold leading-tight text-[#ffe9b0]">{{ __('app.about_fsrquiz') }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-[#e8ddff]/80">
                {{ __('app.how_intro') }}
            </p>
        </section>

        <div class="space-y-4">
            <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <h2 class="font-display text-lg font-extrabold text-amber-200">{{ __('app.how_daily_quiz') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] leading-relaxed text-[#e8ddff]/80">
                    <li>{{ __('app.how_daily_1') }}</li>
                    <li>{{ __('app.how_daily_2') }}</li>
                    <li>{{ __('app.how_daily_3') }}</li>
                    <li>{{ __('app.how_daily_4') }}</li>
                    <li>{{ __('app.how_daily_5') }}</li>
                </ul>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <h2 class="font-display text-lg font-extrabold text-amber-200">{{ __('app.how_leaderboard_quiz') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] leading-relaxed text-[#e8ddff]/80">
                    <li>{{ __('app.how_leaderboard_1') }}</li>
                    <li>{{ __('app.how_leaderboard_2') }}</li>
                    <li>{{ __('app.how_leaderboard_3') }}</li>
                    <li>{{ __('app.how_leaderboard_4') }}</li>
                    <li>{{ __('app.how_leaderboard_5') }}</li>
                    <li>{{ __('app.how_leaderboard_6') }}</li>
                </ul>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <h2 class="font-display text-lg font-extrabold text-amber-200">{{ __('app.how_fastest_finger') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] leading-relaxed text-[#e8ddff]/80">
                    <li>{{ __('app.how_fastest_1') }}</li>
                    <li>{{ __('app.how_fastest_2') }}</li>
                    <li>{{ __('app.how_fastest_3') }}</li>
                    <li>{{ __('app.how_fastest_4') }}</li>
                    <li>{{ __('app.how_fastest_5') }}</li>
                    <li>{{ __('app.how_fastest_6') }}</li>
                </ul>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <h2 class="font-display text-lg font-extrabold text-amber-200">{{ __('app.how_yearly_leaderboard') }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] leading-relaxed text-[#e8ddff]/80">
                    <li>{{ __('app.how_yearly_1') }}</li>
                    <li>{{ __('app.how_yearly_2') }}</li>
                    <li>{{ __('app.how_yearly_3') }}</li>
                    <li>{{ __('app.how_yearly_4') }}</li>
                    <li>{{ __('app.how_top_rewards') }}</li>
                    <li>🥇 {{ __('app.how_yearly_5') }}</li>
                    <li>🥈 {{ __('app.how_yearly_6') }}</li>
                    <li>🥉 {{ __('app.how_yearly_7') }}</li>
                    <li>{{ __('app.how_yearly_8') }}</li>
                    <li>{{ __('app.how_yearly_9') }}</li>
                    <li>{{ __('app.how_yearly_10') }}</li>
                </ul>
            </section>
        </div>
    </main>
@endsection
