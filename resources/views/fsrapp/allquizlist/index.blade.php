@extends('fsrapp.layouts.app')

@section('content')
    <style>
        @keyframes fsrTwinkle {

            0%,
            100% {
                opacity: .2;
                transform: scale(0.8);
            }

            50% {
                opacity: .8;
                transform: scale(1.2);
            }
        }

        @keyframes driftA {
            0% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(30px, -20px) scale(1.08);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }

        .glow-orb {
            position: fixed;
            border-radius: 9999px;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }

        .fsr-quiz-card {
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(196, 158, 255, 0.18);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(12px);
        }

        .fsr-quiz-card:hover {
            border-color: rgba(217, 70, 239, 0.45);
            transform: translateY(-2px);
        }
    </style>

    <!-- Ambient Background Glows -->
    <div class="glow-orb w-72 h-72 top-10 -left-20 bg-purple-600/20" style="animation: driftA 12s ease-in-out infinite;">
    </div>
    <div class="glow-orb w-80 h-80 top-1/2 -right-24 bg-fuchsia-600/15"
        style="animation: driftA 16s ease-in-out infinite 2s;"></div>

    <!-- Header -->
    <header class="fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.88); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10 text-[#e8ddff] hover:bg-white/10 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <h1 class="font-display text-lg font-bold text-[#e8ddff] tracking-wide">All Quizzes</h1>
            </div>
        </div>
    </header>

    <main class="pt-20 pb-24 px-4 min-h-screen max-w-md mx-auto relative z-10">
        <!-- Hero Banner -->
        <div class="rounded-2xl p-4 mb-5 border border-white/10 relative overflow-hidden"
            style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.25), rgba(217, 70, 239, 0.15));">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <span
                        class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-full bg-amber-400/20 text-[#ffd76a] border border-amber-400/30">
                        Live Directory
                    </span>
                    <h2 class="font-display text-lg font-extrabold text-[#ffe9b0] mt-1.5 leading-tight">Explore Quizzes</h2>
                    <p class="text-[11.5px] text-[#e8ddff]/70 mt-0.5">Select a category and play to win coins & rewards!</p>
                </div>
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#ffd76a]/20 to-[#d946ef]/20 border border-[#ffd76a]/30 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl text-[#ffd76a]">military_tech</span>
                </div>
            </div>
        </div>

        @include('fsrapp.home.components.banners', ['banners' => $banners,'pageType' => 'latest_quiz'])
        <!-- Category Filter Chips -->
        <div class="flex gap-2 overflow-x-auto scrollbar-none mb-5 pb-1" id="quiz-cat-filters"
            style="scrollbar-width: none; -ms-overflow-style: none;">
            <a href="{{ route('fsrapp.allquizlist.index') }}"
                class="cat-chip whitespace-nowrap px-4 py-2 rounded-full text-xs {{ !request('category_id') ? 'font-bold bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] shadow-md shadow-fuchsia-500/20 border border-amber-300/40' : 'font-semibold bg-white/[0.05] text-[#e8ddff]/70 border border-white/10 hover:bg-white/10' }} transition-all">
                All Quizzes
            </a>
            @foreach ($categories as $cat)
                <a href="{{ route('fsrapp.allquizlist.index', ['category_id' => $cat->id]) }}"
                    class="cat-chip whitespace-nowrap px-4 py-2 rounded-full text-xs {{ request('category_id') == $cat->id ? 'font-bold bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] shadow-md shadow-fuchsia-500/20 border border-amber-300/40' : 'font-semibold bg-white/[0.05] text-[#e8ddff]/70 border border-white/10 hover:bg-white/10' }} transition-all">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <!-- Quiz Cards Grid -->
        <div class="grid grid-cols-1 gap-4" id="quiz-list-grid">
            @forelse ($quizzes as $quiz)
                @php
                    $hasStarted = $quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->isPast();
                    $startTimetamp = $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)->timestamp : 0;
                    $secondsUntilStart = $quiz->scheduled_at
                        ? \Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($quiz->scheduled_at), false)
                        : 0;
                    $canJoin = $secondsUntilStart <= 900; // 15 minutes = 900 seconds
                    $maxPoints =
                        $quiz->rankRewards && $quiz->rankRewards->count() > 0
                            ? $quiz->rankRewards->max('reward')
                            : $quiz->no_of_points ?? '500';

                    $hasJoined = in_array($quiz->id, $joinedArray ?? []);
                    $hasInactive = in_array($quiz->id, $inactiveArray ?? []);
                    $hasNotified = in_array($quiz->id, $notifiedArray ?? []);
                    $isFinished =
                        $quiz->complete_status == 'completed' ||
                        ($quiz->availability_end && \Carbon\Carbon::parse($quiz->availability_end)->isPast());

                    $typeName = $quiz->quizType->name ?? ($quiz->category->name ?? 'Quiz');
                    $userLimit = (int) (\Illuminate\Support\Facades\Auth::user()?->available_quiz_limit ?? 0);
                    $isLimitExhausted = !$hasJoined && !$hasInactive && $userLimit <= 0;
                @endphp

                <div class="fsr-quiz-card rounded-2xl p-4 flex flex-col justify-between transition-all duration-300 group">
                    <div>
                        <!-- Header Status & Timer -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full inline-block"
                                    style="{{ $hasStarted && !$isFinished ? 'background:#ef4444;box-shadow:0 0 8px #ef4444;' : ($isFinished ? 'background:#6b7280;' : 'background:#22c55e;box-shadow:0 0 8px #22c55e;') }}"></span>
                                <span class="text-[11px] font-bold tracking-wide"
                                    style="{{ $hasStarted && !$isFinished ? 'color:#fca5a5;' : ($isFinished ? 'color:#9ca3af;' : 'color:#86efac;') }}">
                                    {{ $isFinished ? 'COMPLETED' : ($hasStarted ? 'STARTED' : 'UPCOMING') }}
                                </span>
                            </div>

                            @if ($quiz->scheduled_at)
                                <div class="flex items-center gap-1 bg-white/10 px-2.5 py-1 rounded-full border border-white/10 text-[10.5px] font-semibold text-[#ffe9b0]">
                                    <span class="material-symbols-outlined text-[13px] text-[#ffd76a]">schedule</span>
                                    <span>{{ \Carbon\Carbon::parse($quiz->scheduled_at)->format('d M, h:i A') }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Main Info & Image -->
                        <div class="flex gap-3 items-center mb-3">
                            @if (!empty($quiz->image) || !empty($quiz->banner))
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-white/5 border border-white/10 shrink-0">
                                    <img src="{{ asset($quiz->image ?? $quiz->banner) }}" alt="{{ $quiz->title }}" class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="w-16 h-16 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-2xl text-[#ffd76a]">quiz</span>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-semibold text-[#e8ddff]/70">{{ $typeName }}</p>
                                <h3 class="font-display text-base font-extrabold text-[#ffe9b0] leading-tight line-clamp-1">
                                    {{ $quiz->title }}
                                </h3>
                                <div class="flex items-center gap-3 mt-1 text-[11px]">
                                    <span class="flex items-center gap-1 text-[#e8ddff]/70">
                                        <span class="material-symbols-outlined text-[13px]">groups</span>
                                        {{ number_format($quiz->no_of_players ?? 0) }} playing
                                    </span>
                                    <span class="flex items-center gap-1 font-bold text-[#ffd76a]">
                                        <span class="material-symbols-outlined text-[13px]">military_tech</span>
                                        Win {{ $maxPoints }} pts
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-2 pt-3 border-t border-white/10 flex gap-2">
                        @if ($isFinished)
                            <a href="{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.leaderboard', $quiz->id) : route('fsrapp.quiz.winners', $quiz->id) }}"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-white/10 text-[#e8ddff] border border-white/10 hover:bg-white/15 transition-all">
                                <span class="material-symbols-outlined text-[16px]">leaderboard</span>
                                <span>Leaderboard</span>
                            </a>
                        @elseif ($hasJoined)
                            <script>try{localStorage.setItem('quiz_joined_{{ $quiz->id }}','1');localStorage.setItem('fsr_quiz_joined_{{ $quiz->id }}','1');}catch(e){}</script>
                            <a href="{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.waiting', $quiz->id) : route('fsrapp.quizes.waiting', $quiz->id) }}"
                                onclick="if(window.markAudioUnlocked){window.markAudioUnlocked();}else{try{sessionStorage.setItem('fsr_audio_unlocked','1');sessionStorage.setItem('fsr_joined_from_home','1');localStorage.setItem('fsr_audio_unlocked','1');}catch(e){}}"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-blue-500 text-white shadow-md hover:brightness-110 transition-all">
                                <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                                <span>Play Quiz</span>
                            </a>
                        @elseif ($hasInactive)
                            <button type="button"
                                onclick="if(window.showAppAlertModal) { window.showAppAlertModal('⚠️ Is quiz mein player limit full ho gayi hai. Aap is quiz mein join nahi kar sakte. Aap next quiz mein apply/join kar sakte hain.', 'warning'); } else { toast('Is quiz mein player limit full ho gayi hai.', 'warning'); }"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-red-500/20 text-red-300 border border-red-500/40 opacity-70">
                                <span class="material-symbols-outlined text-[16px]">block</span>
                                <span>Limit Full</span>
                            </button>
                        @elseif ($isLimitExhausted)
                            <button disabled onclick="toast('Aapki quiz limit khatam ho gayi hai! 🎯', 'warning')"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-white/5 text-white/50 border border-white/10 opacity-50 cursor-not-allowed">
                                <span class="material-symbols-outlined text-[16px]">play_circle</span>
                                <span>Play Now</span>
                            </button>
                        @elseif ($hasStarted)
                            <button disabled onclick="toast('This quiz has already started.', 'info')"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-white/5 text-white/50 border border-white/10 opacity-50 cursor-not-allowed">
                                <span class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span>Processing...</span>
                            </button>
                        @elseif (!$canJoin)
                            <form action="{{ route('fsrapp.quizes.notify', $quiz->id) }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 transition-all {{ $hasNotified ? 'bg-purple-600/30 border border-purple-500/50 text-[#e879f9]' : 'bg-gradient-to-r from-amber-500 to-purple-600 text-white' }}">
                                    <span class="material-symbols-outlined text-[16px]">{{ $hasNotified ? 'notifications_active' : 'notifications' }}</span>
                                    <span>{{ $hasNotified ? "We'll notify you" : 'Notify Me' }}</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('fsrapp.quizes.join', ['id' => $quiz->id, 'auto_join' => 1]) }}"
                                onclick="if(window.markAudioUnlocked){window.markAudioUnlocked();}else{try{sessionStorage.setItem('fsr_audio_unlocked','1');sessionStorage.setItem('fsr_joined_from_home','1');localStorage.setItem('fsr_audio_unlocked','1');}catch(e){}}"
                                class="w-full py-2.5 rounded-xl font-display font-bold text-xs flex items-center justify-center gap-2 bg-gradient-to-r from-[#f4bf32] to-[#d946ef] text-[#1a0b3d] hover:brightness-110 shadow-md shadow-fuchsia-500/20 transition-all">
                                <span class="material-symbols-outlined text-[16px]">play_circle</span>
                                <span>Play Now</span>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div
                    class="col-span-1 rounded-2xl border border-white/10 p-8 text-center text-[#e8ddff]/50 bg-white/[0.02]">
                    <span class="material-symbols-outlined text-3xl text-white/30 mb-1">quiz</span>
                    <p class="text-xs">No quizzes found in this category.</p>
                </div>
            @endforelse
        </div>

        @if ($quizzes->hasMorePages())
            <div class="mt-6 text-center" id="load-more-container">
                <a href="{{ $quizzes->nextPageUrl() }}#load-more-container"
                    class="fsr-load-more-btn">
                    <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    Load More Quizzes
                </a>
            </div>
        @endif
    </main>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Check local join status: agar locally joined hai, toh Play Now button ko seedha Play Quiz bana kar waiting page ka link de do
                try {
                    document.querySelectorAll('a[href*="/fsrapp/quizes/join/"]').forEach(link => {
                        const parts = link.href.split('/fsrapp/quizes/join/');
                        if (parts.length > 1) {
                            const qId = parts[1].split('?')[0].split('#')[0];
                            if (qId && (localStorage.getItem('quiz_joined_' + qId) === '1' || localStorage.getItem('fsr_quiz_joined_' + qId) === '1')) {
                                link.href = '/fsrapp/quizes/waiting/' + qId;
                                const span = link.querySelector('span:last-child');
                                if (span) span.innerText = 'Play Quiz';
                                const icon = link.querySelector('.material-symbols-outlined');
                                if (icon) icon.innerText = 'play_arrow';
                                link.className = link.className.replace('from-[#f4bf32] to-[#d946ef] text-[#1a0b3d]', 'from-emerald-500 to-blue-500 text-white');
                            }
                        }
                    });
                } catch(e) {}

                @foreach ($quizzes as $q)
                    @if ($q->scheduled_at && !\Carbon\Carbon::parse($q->scheduled_at)->isPast())
                        if (window.registerUpcomingQuizForOneMinAlert) {
                            window.registerUpcomingQuizForOneMinAlert({{ $q->id }}, {{ \Carbon\Carbon::parse($q->scheduled_at)->timestamp }});
                        }
                    @endif
                @endforeach
            });
        </script>
    @endpush
@endsection
