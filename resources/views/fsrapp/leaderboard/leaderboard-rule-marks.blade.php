  <!-- ✅ NEW: Quiz Rules Modal -->
    <div id="quiz-rules-modal" class="fixed inset-0 z-[9999] hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeQuizRulesModal()"></div>
        <!-- Changed to Bottom Sheet -->
        <div class="absolute bottom-0 w-full max-w-md mx-auto left-1/2 -translate-x-1/2 pb-safe">
            <div class="bg-[#130b2b] rounded-t-3xl overflow-hidden shadow-[0_-10px_40px_rgba(0,0,0,0.5)] relative pt-3 flex flex-col max-h-[85vh]">
                
                <!-- Drag Handle -->
                <div class="w-12 h-1.5 bg-white/10 rounded-full mx-auto mb-4"></div>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 mb-4">
                    <h3 class="text-lg font-extrabold text-white tracking-wide">
                        Quiz Marks Guide
                    </h3>
                    <button onclick="closeQuizRulesModal()" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white/60 hover:text-white hover:bg-white/20 transition-all shrink-0">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="px-6 pb-2 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                    
                    <!-- 1. Fastest Finger First -->
                    <div class="bg-white/5 rounded-[20px] p-4 border border-white/5 hover:bg-white/10 transition-colors">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-orange-400 text-lg">bolt</span>
                            <h4 class="text-[13px] font-bold text-white">Fastest Finger First</h4>
                        </div>
                        <ul class="text-[12px] text-white/50 space-y-1.5 ml-1 font-medium">
                            <li class="flex gap-2 leading-relaxed"><span class="text-orange-400">•</span> Sirf 2 options honge, 1 sahi chunein.</li>
                            <li class="flex gap-2 leading-relaxed"><span class="text-orange-400">•</span> Solve as many questions as possible within the given time.</li>
                            <li class="flex gap-2 leading-relaxed"><span class="text-orange-400">•</span> Sahi answers ke marks yearly leaderboard me add honge.</li>
                        </ul>
                    </div>

                    <!-- 2. Leaderboard Quiz -->
                    <div class="bg-white/5 rounded-[20px] p-4 border border-white/5 hover:bg-white/10 transition-colors">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-[#00E5A0] text-lg">emoji_events</span>
                            <h4 class="text-[13px] font-bold text-white">Weekly Quiz</h4>
                        </div>
                        <ul class="text-[12px] text-white/50 space-y-1.5 ml-1 font-medium">
                            <li class="flex gap-2 leading-relaxed"><span class="text-[#00E5A0]">•</span> Rank ke hisaab se marks multiply honge (jaise 2X).</li>
                            <li class="flex gap-2 leading-relaxed"><span class="text-[#00E5A0]">•</span> Multiplied marks yearly leaderboard me update honge.</li>
                        </ul>
                    </div>

                    <!-- 3. Daily Quiz -->
                    <div class="bg-white/5 rounded-[20px] p-4 border border-white/5 hover:bg-white/10 transition-colors">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-blue-400 text-lg">quiz</span>
                            <h4 class="text-[13px] font-bold text-white">Daily Quiz</h4>
                        </div>
                        <ul class="text-[12px] text-white/50 space-y-1.5 ml-1 font-medium">
                            <li class="flex gap-2 leading-relaxed"><span class="text-blue-400">•</span> Sahi jawab dene par marks leaderboard me add honge.</li>
                            <li class="flex gap-2 leading-relaxed"><span class="text-blue-400">•</span> Winning points final rank ke hisaab se wallet me add honge.</li>
                        </ul>
                    </div>

                </div>
                
                <!-- Modal Footer -->
                <div class="p-6 pt-4">
                    <button onclick="closeQuizRulesModal()" class="w-full py-3.5 bg-gradient-to-r from-[#b340ff] to-[#df3aff] hover:opacity-90 text-white text-[15px] font-bold rounded-[20px] transition-all shadow-[0_4px_20px_rgba(223,58,255,0.25)]">
                        Got it!
                    </button>
                </div>
            </div>
        </div>
    </div>

