@foreach ($upcomingQuizzes as $quiz)
    @php
        $joinedArray = $joinedQuizIds ?? [];
        $inactiveArray = $inactiveQuizIds ?? [];
        $notifiedArray = $notifiedQuizIds ?? [];
        $userLimit = (int) (\Illuminate\Support\Facades\Auth::user()?->available_quiz_limit ?? 0);

        $hasStarted = $quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->isPast();
        $startTimetamp = $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)->timestamp : 0;
        $secondsUntilStart = $quiz->scheduled_at
            ? \Carbon\Carbon::now()->diffInSeconds(\Carbon\Carbon::parse($quiz->scheduled_at), false)
            : 0;
        $canJoin = $secondsUntilStart <= 2400; // 40 minutes = 2400 seconds
        $maxPoints =
            $quiz->rankRewards && $quiz->rankRewards->count() > 0
                ? $quiz->rankRewards->max('reward')
                : $quiz->no_of_points ?? '500';

        $hasJoined = in_array($quiz->id, $joinedArray);
        $hasInactive = in_array($quiz->id, $inactiveArray);
        $hasNotified = in_array($quiz->id, $notifiedArray);
        $isFinished =
            $quiz->complete_status == 'completed' ||
            ($quiz->availability_end && \Carbon\Carbon::parse($quiz->availability_end)->isPast());

        $isLimitExhausted = $userLimit <= 0 && !$hasJoined && !$hasInactive;
    @endphp

    <!-- Rewards Bottom Sheet for this Quiz (Blade Component) -->
    <div id="prizes-sheet-{{ $quiz->id }}"
        class="fixed inset-0 z-[2147483647] flex items-end hidden transition-all duration-300"
        style="-webkit-transform: translateZ(0); transform: translateZ(0);">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            onclick="closePrizesSheet({{ $quiz->id }})">
        </div>
        <div class="relative z-[1] w-full max-w-md mx-auto bg-[#1a0b3d] rounded-t-3xl border-t border-purple-500/30 shadow-[0_-10px_40px_rgba(168,85,247,0.15)] flex flex-col max-h-[80vh] transform translate-y-full transition-transform duration-300"
            id="prizes-sheet-content-{{ $quiz->id }}">
            <div class="w-full flex justify-center pt-3 pb-2 cursor-pointer"
                onclick="closePrizesSheet({{ $quiz->id }})">
                <div class="w-12 h-1.5 rounded-full bg-white/20"></div>
            </div>

            <div class="px-5 pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-xl font-extrabold text-[#e8ddff]">
                         @if ($quiz->visibility === 'leaderboard')
                         Leaderboard Marks
                         @elseif ($quiz->visibility === 'fastest_finger_first')
                         Leaderboard Marks
                         @else
                         Prize Distribution
                         @endif
                        

                    </h3>
                    <button onclick="closePrizesSheet({{ $quiz->id }})"
                        class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="overflow-y-auto max-h-[60vh] pr-1 pb-6 space-y-2.5 custom-scrollbar">
                    @if ($quiz->rankRewards && $quiz->rankRewards->count() > 0)
                        @foreach ($quiz->rankRewards->sortBy('start_rank') as $reward)
                            <div
                                class="flex items-center justify-between p-3.5 rounded-xl border border-white/5 bg-white/[0.02]">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center font-mono font-bold text-[13px] {{ $reward->start_rank <= 3 ? 'bg-gradient-to-br from-[#ffd76a] to-[#d99b1d] text-[#3a2400] shadow-[0_0_12px_rgba(244,191,50,0.3)]' : 'bg-white/10 text-[#d8b4fe]' }}">
                                        {{-- #{{ $reward->start_rank }}{{ $reward->end_rank > $reward->start_rank ? '-' . $reward->end_rank : '' }} --}}
                                          #{{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <p class="text-[13px] font-bold text-[#e8ddff]">Rank
                                            {{ $reward->start_rank }}{{ $reward->end_rank > $reward->start_rank ? ' to ' . $reward->end_rank : '' }}
                                        </p>
                                        <p class="text-[11px] text-white/50">Guaranteed Reward</p>
                                    </div>
                                </div>
                                @if ($quiz->visibility === 'leaderboard')

                                    @php
                                        // ✅ Smart Formatting: 0.5 -> 0.5, 1.2 -> 1.2, 2.0 -> 2
                                        $rewardVal = $reward->reward ?? 0;
                                        $fmtReward = rtrim(rtrim(sprintf('%.2f', $rewardVal), '0'), '.') ?: '0';
                                    @endphp
                                
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#ffd76a]/10 border border-[#ffd76a]/20">
                                        <span class="material-symbols-outlined text-[16px] text-[#ffd76a]">toll</span>
                                        <span class="font-mono font-bold text-[14px] text-[#ffe9b0]">{{ $fmtReward }} X Marks</span>
                                    </div>
       
                                @else
                                    @if ($reward->voucher_id && $reward->voucher)
                                        <div
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#ffd76a]/10 border border-[#ffd76a]/20">
                                            <span
                                                class="material-symbols-outlined text-[16px] text-[#ffd76a]">card_giftcard</span>
                                            <span
                                                class="font-mono font-bold text-[12px] text-[#ffe9b0] truncate max-w-[120px]"
                                                title="{{ $reward->voucher->name }}">{{ $reward->voucher->name }}</span>
                                        </div>
                                    @else
                                        <div
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#ffd76a]/10 border border-[#ffd76a]/20">
                                            <span
                                                class="material-symbols-outlined text-[16px] text-[#ffd76a]">toll</span>
                                            <span
                                                class="font-mono font-bold text-[14px] text-[#ffe9b0]">{{ number_format($reward->reward) }}</span>
                                        </div>
                                    @endif
                                @endif    
                            </div>
                        @endforeach
                    @elseif ($quiz->visibility === 'fastest_finger_first')  
                      {{-- ⚡ FASTEST FINGER FIRST MESSAGE --}}
  
                    <div class="p-5 rounded-xl border border-[#f4bf32]/20 bg-[#f4bf32]/5 text-center">
                        
                        <!-- Header Section -->
                        <span class="material-symbols-outlined text-[40px] text-[#f4bf32] block mb-2 animate-pulse" style="font-variation-settings: 'FILL' 1;">
                            bolt
                        </span>
                        <h4 class="text-[15px] font-bold text-[#f4bf32] mb-1.5">Fastest Finger First Challenge</h4>
                        <p class="text-[12px] text-white/70 mb-4 leading-relaxed">
                            Speed aur Accuracy dono matter karti hai! <br>
                            Quiz me maximum questions solve karein.
                        </p>

                        <!-- ✨ Clear Feature Highlights Box -->
                        <div class="space-y-2.5 text-left bg-black/20 rounded-lg p-3.5 border border-white/5">
                            
                            <!-- Feature 1: 2 Options -->
                            <div class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-[#f4bf32] mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">
                                    check_circle
                                </span>
                                <p class="text-[11.5px] text-white/80 leading-snug">
                                    <span class="text-[#f4bf32] font-bold">2-Option Quiz:</span> 
                                    Har question me sirf 2 options honge. Aapko bas 1 sahi option select karna hai.
                                </p>
                            </div>

                            <!-- Feature 2: 1 Minute Time Limit -->
                            {{-- <div class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-[#ef4444] mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">
                                    timer
                                </span>
                                <p class="text-[11.5px] text-white/80 leading-snug">
                                    <span class="text-[#ef4444] font-bold">1 Minute Time Limit:</span> 
                                    Aapke paas quiz attempt karne ke liye sirf 60 seconds hain!
                                </p>
                            </div> --}}

                            <!-- Feature 3: Yearly Leaderboard -->
                            <div class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-[#00E5A0] mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">
                                    leaderboard
                                </span>
                                <p class="text-[11.5px] text-white/80 leading-snug">
                                    <span class="text-[#00E5A0] font-bold">Yearly Leaderboard:</span> 
                                    Quiz me jitne correct answers denge, utne marks aapke yearly leaderboard score me add honge.
                                </p>
                            </div>

                        </div>
                    </div>

                    @else
                        <div class="py-8 text-center text-white/50 text-[13px]">
                            <span
                                class="material-symbols-outlined text-[32px] block mb-2 opacity-50">sentiment_dissatisfied</span>
                            No prize distribution available.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Join Details Sheet (Blade Component) -->
    <div id="quiz-join-sheet-{{ $quiz->id }}"
        class="fixed inset-0 z-[2147483647] flex items-end hidden transition-all duration-300"
        style="-webkit-transform: translateZ(0); transform: translateZ(0);">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            onclick="closeQuizJoinSheet({{ $quiz->id }})"></div>
        <div class="relative z-[1] w-full max-w-md mx-auto bg-[#1a0b3d] rounded-t-3xl border-t border-purple-500/30 shadow-[0_-10px_40px_rgba(168,85,247,0.15)] flex flex-col transform translate-y-full transition-transform duration-300"
            id="quiz-join-sheet-content-{{ $quiz->id }}">
            <div class="w-full flex justify-center pt-3 pb-2 cursor-pointer"
                onclick="closeQuizJoinSheet({{ $quiz->id }})">
                <div class="w-12 h-1.5 rounded-full bg-white/20"></div>
            </div>

            <div class="px-5 pb-8 pt-1 text-center">
                @if ($isFinished)
                    <span
                        class="inline-block mb-3 bg-[#86efac] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">ALREADY
                        PLAYED</span>
                @elseif ($hasJoined)
                    <span
                        class="inline-block mb-3 bg-[#e879f9] text-[#1a0b3d] text-[10px] font-extrabold px-3 py-1.5 rounded-full shadow-lg">YOU
                        HAVE JOINED</span>
                @endif

                <h3 class="font-display text-2xl font-extrabold text-[#ffe9b0] mb-2 leading-tight">
                    {{ $quiz->title ?? 'Quiz' }}</h3>
                @if (!empty($quiz->description))
                    <p class="text-[13px] text-[#e8ddff]/70 mb-4 line-clamp-3">{{ $quiz->description }}
                    </p>
                @endif

                <div class="grid grid-cols-3 gap-3 mt-4 mb-5">
                    <div
                        class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                        <span
                            class="material-symbols-outlined text-[18px] text-[#ffd76a] mb-1">emoji_events</span>

                             @if ($quiz->visibility === 'leaderboard' || $quiz->visibility === 'fastest_finger_first')
                                <p class="text-[10px] font-bold text-white/50 mb-0.5">LEADERBOARD</p>
                                <p class="text-[#ffe9b0] font-mono font-bold text-[13px]">Marks</p>
                            @else
                        <p class="text-[10px] font-bold text-white/50 mb-0.5">PRIZE POOL</p>
                        <p class="text-[#ffe9b0] font-mono font-bold text-[13px]">{{ $maxPoints }} Pts
                        </p>
                        @endif
                    </div>
                    <div
                        class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                        <span
                            class="material-symbols-outlined text-[18px] text-[#86efac] mb-1">confirmation_number</span>
                        <p class="text-[10px] font-bold text-white/50 mb-0.5">ENTRY FEE</p>
                        <p class="text-[#86efac] font-mono font-bold text-[13px]">
                            {{ empty($quiz->entry_fee) || $quiz->entry_fee == 0 ? 'Free' : $quiz->entry_fee }}
                        </p>
                    </div>
                    <div
                        class="bg-white/[0.03] p-3 rounded-xl border border-white/10 text-center flex flex-col items-center justify-center">
                        <span
                            class="material-symbols-outlined text-[18px] text-[#e879f9] mb-1">groups</span>
                        <p class="text-[10px] font-bold text-white/50 mb-0.5">JOINED</p>
                        <p class="text-[#f0abfc] font-mono font-bold text-[13px]">
                            {{ number_format($quiz->no_of_players ?? 0) }}</p>
                    </div>
                </div>
                {{-- ******special instruation start*********** --}}
                              
                @if ($quiz->visibility === 'fastest_finger_first')
                <div class="p-4 mb-4 rounded-xl bg-slate-900/60 border border-amber-500/30 shadow-[0_0_20px_rgba(245,158,11,0.1)]">
                    
                    <!-- Header Section -->
                    <div class="flex items-center gap-2.5 mb-3 pb-2.5 border-b border-amber-500/20">
                        <span class="material-symbols-outlined text-[24px] text-amber-400" style="font-variation-settings: 'FILL' 1;">bolt</span>
                        <h4 class="font-bold text-amber-400 text-[16px] tracking-wide">Fastest Finger First</h4>
                    </div>
                    
                    <!-- Points Section -->
                    <ul class="space-y-3 text-[13px]">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-[18px] text-amber-500 shrink-0" style="font-variation-settings: 'FILL' 1;">quiz</span>
                            <span class="flex-1 leading-snug text-gray-300">
                                <strong class="text-amber-300 font-semibold">2 Options : </strong> question me sirf 2 options honge 1 select kare.
                            </span>
                        </li>
                        
                        {{-- <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-[18px] text-amber-500 shrink-0" style="font-variation-settings: 'FILL' 1;">timer</span>
                            <span class="flex-1 leading-snug text-gray-300">
                                <strong class="text-amber-300 font-semibold">Time Limit : </strong> Sirf 1 min me maximum questions solve karein.
                            </span>
                        </li> --}}

                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-[18px] text-amber-500 shrink-0" style="font-variation-settings: 'FILL' 1;">leaderboard</span>
                            <span class="flex-1 leading-snug text-gray-300">
                                <strong class="text-amber-300 font-semibold">Yearly Score : </strong> Sahi answers ke marks yearly leaderboard me add honge.
                            </span>
                        </li>
                    </ul> 
                </div>
                @elseif ($quiz->visibility === 'leaderboard')
                    <div class="p-4 mb-4 rounded-xl bg-slate-900/60 border border-amber-500/30 shadow-[0_0_20px_rgba(245,158,11,0.1)]">
    
                        <!-- Header Section: Gold Color for High Contrast -->
                        <div class="flex items-center gap-2.5 mb-3 pb-2.5 border-b border-amber-500/20">
                            <span class="material-symbols-outlined text-[24px] text-amber-400" style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                            <h4 class="font-bold text-amber-400 text-[16px] tracking-wide">Leaderboard Quiz</h4>
                        </div>
                        
                        <!-- Points Section: Light Gray for Readability -->
                        <ul class="space-y-3 text-[13px]">
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-[20px] text-amber-500 mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                                <span class="flex-1 leading-snug text-gray-300">
                                    <strong class="text-amber-300 font-semibold">Multiplier : </strong> Rank ke hisaab se marks multiply honge 
                                    <!-- 2X Badge -->
                                    <span class="text-amber-300 font-bold whitespace-nowrap bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded text-[11px] ml-1">(jaise 2X)</span>.
                                </span>
                            </li>
                            
                            <li class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-[20px] text-amber-500 mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">leaderboard</span>
                                <span class="flex-1 leading-snug text-gray-300">
                                    <strong class="text-amber-300 font-semibold">Yearly Score : </strong> Multiplied marks yearly leaderboard me update honge.
                                </span>
                            </li>
                        </ul> 
                    </div>
                @else
                    <div class="p-4 mb-4 rounded-xl bg-gradient-to-br from-blue-500/15 to-indigo-500/10 border border-blue-400/30 shadow-sm">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="material-symbols-outlined text-[20px] text-blue-400" style="font-variation-settings: 'FILL' 1;">quiz</span>
                            <h4 class="font-bold text-blue-300 text-[15px]">Daily Quiz</h4>
                        </div>
                        {{-- <p class="text-[12px] text-blue-200/80 mb-3 italic">Apni knowledge test karein aur points kamayein!</p> --}}
                        
                        <ul class="space-y-2 text-[13px] text-blue-50">
                            {{-- <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[16px] text-blue-400 mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                <span><strong class="text-blue-300">Sahi Jawab:</strong> Multiple questions mein se sahi option select karein.</span>
                            </li> --}}
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[16px] text-blue-400 mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">leaderboard</span>
                                <span><strong class="text-blue-300">Leaderboard Marks:</strong> Jitne sahi jawab denge, utne marks aapke leaderboard me bhi add honge.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="material-symbols-outlined text-[16px] text-blue-400 mt-0.5 shrink-0" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
                                <span><strong class="text-blue-300">Wallet Credit:</strong> Winning points aapki final rank ke hisaab se wallet mein add honge.</span>
                            </li>
                        </ul>
                    </div>
                @endif
                
                {{-- ******special instruation end*********** --}}

                @if ($isFinished)
                    <a href="{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.leaderboard', $quiz->id) : route('fsrapp.quiz.winners', $quiz->id) }}"
                        class="w-full bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">leaderboard</span> View
                        Leaderboard
                    </a>
                @elseif ($hasJoined)
                    <script>try{localStorage.setItem('quiz_joined_{{ $quiz->id }}','1');localStorage.setItem('fsr_quiz_joined_{{ $quiz->id }}','1');}catch(e){}</script>
                    <a href="{{ $quiz->visibility === 'fastest_finger_first' ? route('fsrapp.fff.waiting', $quiz->id) : route('fsrapp.quizes.waiting', $quiz->id) }}"
                        onclick="if(window.markAudioUnlocked){window.markAudioUnlocked();}else{try{sessionStorage.setItem('fsr_audio_unlocked','1');sessionStorage.setItem('fsr_joined_from_home','1');localStorage.setItem('fsr_audio_unlocked','1');}catch(e){}}"
                        class="w-full bg-gradient-to-r from-[#86efac] to-[#3b82f6] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(59,130,246,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">play_arrow</span> Play Quiz
                    </a>
                @elseif ($hasInactive)
                    <div
                        class="p-3 mb-3 rounded-xl bg-amber-500/15 border border-amber-400/30 text-amber-200 text-xs font-semibold text-center leading-relaxed">
                        ⚠️ Quiz player limit complete try next time
                    </div>
                    <button type="button" disabled
                        class="w-full bg-white/10 text-white/40 py-3.5 rounded-xl font-display font-bold text-[15px] cursor-not-allowed flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">block</span> Player Limit Full
                    </button>
                @elseif (!$canJoin)
                    <div
                        class="p-3 mb-3 rounded-xl bg-purple-500/15 border border-purple-400/30 text-purple-200 text-xs font-semibold text-center leading-relaxed">
                        ⚠️ {{ __('app.quiz_join_40min_warning') }}
                    </div>
                    <button type="button" disabled
                        class="w-full bg-white/10 text-white/40 py-3.5 rounded-xl font-display font-bold text-[15px] cursor-not-allowed flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">lock_clock</span> Joining Opens 40 Min Before
                    </button>
                @else
                    @include('fsrapp.home.components.banners', [
                        'banners' => $banners,
                        'pageType' => 'join',
                    ])

                    <form action="{{ route('fsrapp.quizes.join.post', $quiz->id) }}" method="POST"
                        onsubmit="handleTodayQuizJoin(event, {{ $quiz->id }})" class="w-full">
                        @csrf
                        @if ($quiz->is_private)
                            <input name="password" type="password" placeholder="Enter quiz password"
                                required
                                class="mb-4 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-[#e8ddff] placeholder-white/30 focus:outline-none focus:border-[#e879f9]/50 focus:ring-1 focus:ring-[#e879f9]/50 transition-all">
                        @endif
                        <button type="{{ $isLimitExhausted ? 'button' : 'submit' }}"
                            @if ($isLimitExhausted) onclick="toast('Aapki quiz limit khatam ho gayi hai! 🎯', 'warning')" @endif
                            class="w-full bg-gradient-to-r from-[#f4bf32] via-[#d946ef] to-[#7c3aed] text-[#1a0b3d] py-3.5 rounded-xl font-display font-bold text-[15px] shadow-[0_8px_22px_rgba(217,70,239,0.3)] active:scale-95 transition-transform flex items-center justify-center gap-2 {{ $isLimitExhausted ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <span class="material-symbols-outlined text-[19px]">play_circle</span> Join
                            Quiz Now
                        </button>
                    </form>
                @endif

                <button type="button" onclick="closeQuizJoinSheet({{ $quiz->id }})"
                    class="w-full mt-3 py-2.5 text-[#e8ddff]/60 font-bold text-[14px] hover:text-[#e8ddff] transition-colors rounded-xl hover:bg-white/5">Cancel</button>
            </div>
        </div>
    </div>
@endforeach
