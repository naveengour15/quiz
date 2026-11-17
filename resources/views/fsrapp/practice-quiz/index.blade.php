@extends('fsrapp.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('fsrapp/css/home.css') }}">
@endpush

@section('content')
    <style>
        header.fsr-header {
            background: rgba(13, 6, 32, 0.75) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border-glass, rgba(255, 255, 255, 0.1));
        }
    </style>
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid var(--border-glass);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-lg font-extrabold tracking-tight leading-none" style="color:#e8ddff;">
                        {{ __('app.practice_quiz_title') }}</h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">{{ __('app.practice_quiz_subtitle') }}</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-10 min-h-screen relative z-10">

        <!-- ===== Ambient Background Orbs ===== -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
            <div class="absolute -top-24 -left-20 w-80 h-80 rounded-full bg-purple-600/20 blur-[90px] animate-pulse"></div>
            <div class="absolute bottom-10 -right-20 w-80 h-80 rounded-full bg-fuchsia-600/20 blur-[90px] animate-pulse"
                style="animation-delay: 2s;"></div>
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full bg-amber-500/10 blur-[100px]">
            </div>
        </div>

        <!-- ===== Hero Banner ===== -->
        <div
            class="relative overflow-hidden rounded-3xl p-5 mb-6 border border-tertiary/40 bg-gradient-to-br from-[#1a0b3d] via-[#32136c] to-[#86198f] shadow-[0_10px_30px_rgba(124,58,237,0.3)]">
            <div class="absolute -top-3 -right-8 w-28 h-28 bg-tertiary/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-tertiary shadow-[0_0_8px_#f4bf32]"></span>
                <span class="text-[11px] font-bold tracking-wider text-amber-200 uppercase">{{ __('app.practice_arena') }}</span>
            </div>

            <h1 class="font-display text-2xl font-black text-white leading-tight mb-2">
                {{ __('app.practice_hero_title') }}
            </h1>
            <p class="text-xs text-purple-200/80 leading-relaxed mb-4">
                {{ __('app.practice_hero_desc') }}
            </p>

            <div class="grid grid-cols-3 gap-2.5 pt-2 border-t border-white/10">
                <div class="bg-black/30 rounded-xl p-2.5 text-center border border-white/5">
                    <span
                        class="block text-lg font-black text-amber-300">{{ $practiceQuizzes ? $practiceQuizzes->count() : 0 }}</span>
                    <span class="text-[10px] text-purple-200/70 font-semibold uppercase">{{ __('app.total_modules') }}</span>
                </div>
                <div class="bg-black/30 rounded-xl p-2.5 text-center border border-white/5">
                    <span class="block text-lg font-black text-mint">{{ number_format($completedCount ?? 0) }}</span>
                    <span class="text-[10px] text-purple-200/70 font-semibold uppercase">{{ __('app.completed') }}</span>
                </div>
                <div class="bg-black/30 rounded-xl p-2.5 text-center border border-white/5">
                    <span class="block text-lg font-black text-tertiary">#{{ ($completedCount ?? 0) + 1 }}</span>
                    <span class="text-[10px] text-purple-200/70 font-semibold uppercase">{{ __('app.active_level') }}</span>
                </div>
            </div>
        </div>

        @include('fsrapp.home.components.banners', [ 'banners' => $banners, 'pageType' => 'practices_list'])
        <!-- ===== Practice Quiz Levels List ===== -->
        <div class="flex items-center justify-between mb-4 px-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary text-xl">school</span>
                <h2 class="font-display text-base font-extrabold text-white">{{ __('app.practice_modules') }}</h2>
            </div>
            <span
                class="text-xs font-bold text-amber-300 bg-amber-400/10 px-3 py-1 rounded-full border border-amber-400/30">
                {{ $completedCount ?? 0 }} / {{ $practiceQuizzes ? $practiceQuizzes->count() : 0 }} {{ __('app.unlocked') }}
            </span>
        </div>

        @if ($practiceQuizzes && $practiceQuizzes->count() > 0)
            <div class="space-y-3 mb-8">
                @foreach ($practiceQuizzes as $quiz)
                    @php
                        $isCompleted = $quiz->is_completed;
                        $isUnlocked = $quiz->is_unlocked;
                        $serial = $quiz->serial_number;
                    @endphp

                    <div class="group relative rounded-2xl p-4 transition-all duration-300 border backdrop-blur-md flex items-center gap-3.5
                        {{ $isCompleted
                            ? 'bg-surface-container/30 border-white/5 opacity-60 cursor-not-allowed'
                            : ($isUnlocked
                                ? 'bg-surface-container/70 border-white/15 hover:border-tertiary/60 hover:shadow-[0_0_20px_rgba(244,191,50,0.25)] cursor-pointer active:scale-[0.98]'
                                : 'bg-surface-container/30 border-white/5 opacity-60 cursor-not-allowed') }}"
                        @if ($isCompleted) onclick="showCompletedLockedToast()"
                        @elseif ($isUnlocked) 
                            onclick="window.location.href='/fsrapp/practice-quiz/play/{{ $quiz->id }}'"
                        @else
                            onclick="showLockedToast({{ $serial - 1 }})" @endif>

                        <!-- Level Badge -->
                        <div
                            class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-sm shrink-0 border font-display transition-all
                            {{ $isCompleted
                                ? 'bg-rose-500/10 border-rose-500/20 text-rose-400'
                                : ($isUnlocked
                                    ? 'bg-amber-400/20 border-amber-400/50 text-amber-300 shadow-[0_0_10px_rgba(244,191,50,0.3)]'
                                    : 'bg-white/5 border-white/10 text-white/40') }}">
                            @if ($isCompleted)
                                <span class="material-symbols-outlined text-base">lock</span>
                            @elseif(!$isUnlocked)
                                <span class="material-symbols-outlined text-base">lock</span>
                            @else
                                #{{ $serial }}
                            @endif
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm text-white truncate group-hover:text-amber-200 transition-colors">
                                {{ __('app.practice_module') }} {{ $serial }}: {{ $quiz->title ?? ($quiz->name ?? __('app.practice_module')) }}
                            </h3>
                            <p class="text-xs mt-0.5 font-medium">
                                @if ($isCompleted)
                                    <span class="text-rose-400 font-semibold">{{ __('app.locked') }}</span> · {{ __('app.already_played') }}
                                @elseif($isUnlocked)
                                    <span class="text-amber-300 font-semibold">{{ __('app.ready_to_play') }}</span> · 10 {{ __('app.questions') }}
                                @else
                                    <span class="text-white/40">{{ __('app.locked') }}</span> · {{ __('app.complete_level_first_short', ['level' => $serial - 1]) }}
                                @endif
                            </p>
                        </div>

                        <!-- Action Button -->
                        @if ($isCompleted)
                            <button type="button"
                                class="px-2.5 py-1.5 rounded-full text-[11px] font-bold shrink-0 bg-white/5 text-rose-300/60 border border-white/10 flex items-center gap-0.5 cursor-not-allowed">
                                <span class="material-symbols-outlined text-[13px]">lock</span>
                                {{ __('app.locked') }}
                            </button>
                        @elseif($isUnlocked)
                            <button type="button"
                                class="px-3.5 py-1.5 rounded-full text-[11px] font-bold shrink-0 bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] hover:brightness-110 shadow-md shadow-fuchsia-500/20 flex items-center gap-0.5 group-hover:scale-105 transition-all">
                                <span>{{ __('app.start') }}</span>
                                <span class="material-symbols-outlined text-[13px]">play_arrow</span>
                            </button>
                        @else
                            <button type="button"
                                class="px-2.5 py-1.5 rounded-full text-[11px] font-bold shrink-0 bg-white/5 text-white/40 border border-white/10 flex items-center gap-0.5 cursor-not-allowed">
                                <span class="material-symbols-outlined text-[13px]">lock</span>
                                {{ __('app.locked') }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Load More Button -->
            @if (isset($totalCount) && isset($limit) && $totalCount > $limit)
                <div class="mt-6 mb-2 text-center" id="load-more-container">
                    <a href="{{ request()->fullUrlWithQuery(['limit' => $limit + 10]) }}#load-more-container"
                        class="inline-flex items-center justify-center gap-2 px-8 py-2.5 rounded-full font-bold text-[14px] bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] hover:brightness-110 shadow-lg shadow-fuchsia-500/20 transition-all">
                        <span class="material-symbols-outlined text-[20px] font-bold">keyboard_arrow_down</span>
                        {{ __('app.load_more') }}
                    </a>
                </div>
            @endif
        @else
            <div class="rounded-2xl p-8 text-center border border-white/10 bg-surface-container/40 mb-8">
                <span class="material-symbols-outlined text-4xl text-white/30 mb-2">quiz</span>
                <p class="text-sm font-semibold text-white/60">{{ __('app.no_practice_modules') }}</p>
            </div>
        @endif

        <!-- Bottom Informational Note -->
        <div
            class="rounded-2xl p-4 border border-white/10 bg-surface-container/50 backdrop-blur-md flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl text-amber-300 shrink-0">tips_and_updates</span>
            <p class="text-xs text-purple-200/80 leading-relaxed">
                {{ __('app.practice_tip') }}
            </p>
        </div>

    </main>



    @push('scripts')
        <script>
            const completeLevelAlert = @json(__('app.complete_level_first_alert', ['level' => '__LEVEL__']));

            function showLockedToast(requiredLevel) {
                const message = completeLevelAlert.replace('__LEVEL__', requiredLevel);
                if (typeof toast === 'function') {
                    toast(message, 'warning');
                } else if (typeof showToast === 'function') {
                    showToast(message, 'warning');
                } else {
                    alert(message);
                }
            }

            function showCompletedLockedToast() {
                const message = @json(__('app.already_played_alert'));
                if (typeof toast === 'function') {
                    toast(message, 'warning');
                } else if (typeof showToast === 'function') {
                    showToast(message, 'warning');
                } else {
                    alert(message);
                }
            }

            function showCompletedToast() {
                showCompletedLockedToast();
            }
        </script>
    @endpush
@endsection
