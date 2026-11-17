@extends('fsrapp.layouts.app')

@section('content')
    <style>
        header.fsr-header {
            background: rgba(13, 6, 32, 0.85) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes surveyEntrance {
            0% {
                opacity: 0;
                transform: translateY(16px) scale(0.97);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .survey-animate-in {
            animation: surveyEntrance 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .survey-card-main {
            background: transparent;
            border: 1px solid transparent;
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }

        .survey-card-main::before,
        .survey-card-main::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
        }

        .survey-card-main::before {
            inset: 0;
            background: conic-gradient(
                from 0deg,
                transparent 0deg,
                transparent 235deg,
                rgba(244, 191, 50, 0.95) 275deg,
                rgba(217, 70, 239, 0.9) 315deg,
                transparent 355deg
            );
            animation: surveyBorderRotate 5s linear infinite;
            z-index: 0;
        }

        .survey-card-main::after {
            inset: 1px;
            background: linear-gradient(145deg, rgba(30, 16, 60, 0.97) 0%, rgba(17, 9, 36, 0.98) 100%);
            z-index: 1;
        }

        .survey-card-main > * {
            position: relative;
            z-index: 2;
        }

        @keyframes surveyBorderRotate {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .survey-card-main::before { animation: none; }
        }

        .survey-card-glow {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(244, 191, 50, 0.3) 0%, rgba(217, 70, 239, 0.12) 50%, transparent 70%);
            pointer-events: none;
        }

        .survey-q-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(244, 191, 50, 0.25), rgba(217, 70, 239, 0.15));
            border: 1px solid rgba(244, 191, 50, 0.5);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px;
            font-weight: 800;
            color: #ffe9b0;
            letter-spacing: 0.3px;
        }

        .survey-reward-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 13px;
            border-radius: 999px;
            background: rgba(74, 222, 128, 0.15);
            border: 1px solid rgba(74, 222, 128, 0.35);
            font-size: 12px;
            font-weight: 700;
            color: #4ade80;
            box-shadow: 0 0 12px rgba(74, 222, 128, 0.2);
        }

        .survey-opt-card {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 14px;
            padding: 18px 20px;
            border-radius: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(124, 58, 237, 0.08));
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            user-select: none;
            position: relative;
            overflow: hidden;
            flex: 0 0 100%;
            width: 100%;
        }

        .survey-options-row {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
            margin-bottom: 24px;
        }

        .survey-opt-card:hover {
            border-color: rgba(244, 191, 50, 0.65);
            background: linear-gradient(135deg, rgba(244, 191, 50, 0.15), rgba(217, 70, 239, 0.18));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(244, 191, 50, 0.15);
        }

        .survey-opt-card:active {
            transform: scale(0.97);
        }

        .survey-opt-card.selected {
            border-color: #f4bf32;
            background: linear-gradient(135deg, rgba(244, 191, 50, 0.3), rgba(217, 70, 239, 0.25));
            box-shadow: 0 0 28px rgba(244, 191, 50, 0.45);
        }

        .survey-opt-letter {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            color: #ffe9b0;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .survey-opt-card.selected .survey-opt-letter {
            background: linear-gradient(135deg, #f4bf32, #d946ef);
            border-color: transparent;
            color: #1a0b3d;
            box-shadow: 0 2px 12px rgba(244, 191, 50, 0.6);
        }

    </style>

    <!-- Ambient Glow Field -->
    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
        <div class="glow-orb o4"></div>
    </div>

    <!-- ===================== HEADER ===================== -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.home') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="flex-1 min-w-0">
                    <h1 class="font-display text-base font-extrabold tracking-tight leading-none truncate"
                        style="color:#e8ddff;">
                        {{ $survey->name ?? ($survey->title ?? 'Survey') }}
                    </h1>
                    <p id="fsr-q-subtitle" class="text-[11px] leading-none mt-1 font-semibold" style="color:#ffd76a;">
                        Question 1 of {{ count($survey->questions) }}
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-8 min-h-screen">
        <div id="survey-play-root" class="w-full">
            <div class="flex flex-col items-center py-16" style="color: rgba(232, 221, 255, 0.6);">
                <div class="w-10 h-10 border-4 rounded-full animate-spin mb-4"
                    style="border-color: rgba(244,191,50,0.2); border-top-color: #f4bf32;"></div>
                <p class="text-sm font-semibold">Loading survey questions...</p>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const escapeHtml = (str) => String(str ?? '')
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

            const surveyId = {{ $id }};
            const isQuizReward = @json(($survey->reward_type ?? '') === 'quiz' || ($survey->quiz && $survey->quiz > 0));
            const rewardPoints = {{ ($survey->reward_type ?? '') === 'quiz' ? ($survey->quiz ?? 1) : ($survey->point ?? 50) }};
            const rewardType = isQuizReward ? 'Quiz Play Limit' : 'Points';
            
            const allQuestions = @json($survey->questions);
            const existingAnswers = @json($existingAnswers ?? (object) []);

            // Filter out questions that have already been answered
            const unansweredQuestions = allQuestions.filter(q => {
                return !(q.id in existingAnswers);
            });

            let currentIndex = 0;

            if (!allQuestions.length) {
                document.querySelector('#survey-play-root').innerHTML = `
      <div class="glass-card p-6 text-center border border-white/10 rounded-2xl shadow-xl">
        <span class="material-symbols-outlined text-4xl mb-2" style="color: rgba(232, 221, 255, 0.4);">assignment_late</span>
        <p class="text-sm mb-4" style="color: rgba(232, 221, 255, 0.6);">No questions found for this survey.</p>
        <a href="{{ route('fsrapp.home') }}" class="block w-full py-3.5 rounded-xl font-bold text-sm text-[#1a0b3d] active:scale-95 transition-all text-center cursor-pointer" style="background: linear-gradient(135deg, #f4bf32, #d946ef); box-shadow: 0 4px 15px rgba(244,191,50,0.3);">Back to Home</a>
      </div>`;
                return;
            }

            if (!unansweredQuestions.length) {
                // All answered -> show completion
                renderCompletionScreen(rewardPoints);
                return;
            }

            function renderCompletionScreen(points) {
                try {
                    if (window.Sound) {
                        Sound.ensureCtx();
                        Sound.coin();
                    }
                } catch (e) {
                    console.warn('Sound playback skipped:', e);
                }

                const subtitleEl = document.querySelector('#fsr-q-subtitle');
                if (subtitleEl) {
                    subtitleEl.innerText = `Completed`;
                }

                const pts = (points !== undefined && points !== null && points > 0) ? points : rewardPoints;

                document.querySelector('#survey-play-root').innerHTML = `
      <div class="survey-card-main p-6 text-center flex flex-col items-center survey-animate-in">
        <div class="survey-card-glow"></div>
        <div class="w-20 h-20 rounded-full flex items-center justify-center mb-5 relative z-10" style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%); border: 2px solid rgba(244,191,50,0.6); box-shadow:0 0 30px rgba(244,191,50,0.5);">
          <span class="material-symbols-outlined text-4xl text-[#1a0b3d]">emoji_events</span>
        </div>
        <h2 class="font-display text-2xl font-extrabold mb-1.5 text-[#ffe9b0] relative z-10">{{ __('app.survey_completed') }}</h2>
        <p class="text-xs mb-6 relative z-10 text-[#e8ddff]/70">Your reward has been successfully added to your account balance.</p>
        
        <div class="w-full rounded-2xl p-5 mb-6 relative z-10" style="background: linear-gradient(135deg, rgba(244,191,50,0.2), rgba(217,70,239,0.15)); border: 1px solid rgba(244,191,50,0.45); box-shadow: 0 0 20px rgba(244,191,50,0.15);">
          <p class="text-[11px] font-extrabold uppercase tracking-widest mb-1.5 text-amber-300">REWARD UNLOCKED</p>
          <p class="font-display text-2xl font-black text-[#ffe9b0]">+${escapeHtml(pts)} ${escapeHtml(rewardType)} 🎯</p>
        </div>

        <div class="w-full space-y-3 relative z-10">
          <a href="{{ route('fsrapp.home') }}" onclick="event.preventDefault(); event.stopPropagation(); window.location.assign(this.href); return false;" class="block w-full py-4 rounded-xl font-display font-extrabold text-[15px] text-[#1a0b3d] active:scale-95 transition-all text-center cursor-pointer shadow-lg shadow-fuchsia-500/25" style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);">Play Today's Quiz Now</a>
          <a href="{{ route('fsrapp.survey.index') }}" onclick="event.preventDefault(); event.stopPropagation(); window.location.assign(this.href); return false;" class="block w-full py-3 rounded-xl font-display font-bold text-[13px] text-[#e8ddff]/80 hover:text-white transition-colors text-center bg-white/5 border border-white/10 hover:bg-white/10">View Other Surveys</a>
        </div>
      </div>`;
            }

            function renderCurrentQuestion() {
                if (currentIndex >= unansweredQuestions.length || !unansweredQuestions[currentIndex]) {
                    renderCompletionScreen(rewardPoints);
                    return;
                }

                const q = unansweredQuestions[currentIndex];
                const qId = q.id;
                const qTitle = escapeHtml(q.question ?? q.title ?? `Question ${currentIndex + 1}`);
                const yesText = escapeHtml(q.yes || 'Yes');
                const noText = escapeHtml(q.no || 'No');

                // Calculate current question sequence (1-indexed) out of total questions
                const answeredBefore = allQuestions.length - unansweredQuestions.length;
                const currentSeq = answeredBefore + currentIndex + 1;
                const totalSeq = allQuestions.length;

                // Update header subtitle
                const subtitleEl = document.querySelector('#fsr-q-subtitle');
                if (subtitleEl) {
                    subtitleEl.innerText = `Question ${currentSeq} of ${totalSeq}`;
                }

                document.querySelector('#survey-play-root').innerHTML = `
      <div class="survey-animate-in">
        <!-- ===== Info Banner: Home Screen Style ===== -->
        <div class="p-4 mb-4 rounded-2xl relative overflow-hidden border border-amber-400/30" style="background: linear-gradient(135deg, rgba(244,191,50,0.15) 0%, rgba(217,70,239,0.12) 60%, rgba(124,58,237,0.15) 100%); backdrop-filter: blur(16px);">
            <div class="absolute -left-8 -top-8 w-24 h-24 bg-[#ffd76a]/20 rounded-full blur-xl pointer-events-none"></div>

            <div class="flex items-center gap-3 relative z-10">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md shadow-amber-400/30"
                    style="background: linear-gradient(135deg, #f4bf32 0%, #d946ef 60%, #7c3aed 100%);">
                    <span class="material-symbols-outlined text-[20px] text-[#1a0b3d]">bolt</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display text-[14px] font-extrabold leading-tight text-[#ffe9b0]">
                        Earn Quiz Play Limit & Points!
                    </h3>
                    <p class="text-[11px] mt-0.5 leading-snug truncate text-[#e8ddff]/85">
                        Answer survey to unlock daily <span class="text-amber-300 font-bold">Quiz Plays</span> & rewards! 🎯
                    </p>
                </div>
            </div>
        </div>

        <!-- ===== Question Card ===== -->
        <div class="survey-card-main p-6 mb-5">
            <div class="survey-card-glow"></div>
            <div class="flex items-center justify-between gap-2 mb-4 relative z-10">
                <span class="survey-q-badge">
                    <span class="material-symbols-outlined text-[14px]">help</span>
                    Q${currentSeq} of ${totalSeq}
                </span>
                <span class="survey-reward-pill">
                    🎁 +${escapeHtml(rewardPoints)} ${escapeHtml(rewardType)}
                </span>
            </div>
            <h2 class="font-display text-xl font-extrabold leading-snug relative z-10 text-[#ffe9b0]">${qTitle}</h2>
            <p class="text-[11.5px] mt-3 flex items-center gap-1.5 relative z-10 text-[#e8ddff]/60">
                <span class="material-symbols-outlined text-[15px] text-amber-400">touch_app</span>
                Tap an option below to submit response
            </p>
        </div>

        <!-- ===== Yes / No Option Cards ===== -->
                <div class="survey-options-row">
          <div id="survey-opt-yes" class="survey-opt-card">
            <div class="survey-opt-letter">A</div>
                        <span class="text-[15px] font-bold text-[#e8ddff] truncate">${yesText}</span>
          </div>
          <div id="survey-opt-no" class="survey-opt-card">
            <div class="survey-opt-letter">B</div>
                        <span class="text-[15px] font-bold text-[#e8ddff] truncate">${noText}</span>
          </div>
        </div>
      </div>
      `;

                const optYes = document.querySelector('#survey-opt-yes');
                const optNo = document.querySelector('#survey-opt-no');
                if (optYes) optYes.onclick = () => handleChoice(qId, 'yes', '#survey-opt-yes');
                if (optNo) optNo.onclick = () => handleChoice(qId, 'no', '#survey-opt-no');
            }

            async function handleChoice(qId, answerVal, targetOptId) {
                const yesBtn = document.querySelector('#survey-opt-yes');
                const noBtn = document.querySelector('#survey-opt-no');

                // Highlight selected card visually immediately
                const targetOpt = document.querySelector(targetOptId);
                if (targetOpt) {
                    targetOpt.classList.add('selected');
                }

                if (yesBtn) yesBtn.style.pointerEvents = 'none';
                if (noBtn) noBtn.style.pointerEvents = 'none';

                try {
                    const response = await fetch('{{ route('fsrapp.survey.submit', $id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            question_id: qId,
                            answer: answerVal
                        })
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error(data.error || 'Failed to submit response');
                    }

                    if (data.completed || (currentIndex + 1) >= unansweredQuestions.length) {
                        renderCompletionScreen(data.points || rewardPoints);
                    } else {
                        currentIndex++;
                        renderCurrentQuestion();
                    }
                } catch (e) {
                    if (yesBtn) yesBtn.style.pointerEvents = 'auto';
                    if (noBtn) noBtn.style.pointerEvents = 'auto';
                    if (targetOpt) targetOpt.classList.remove('selected');
                    const notify = (typeof showToast === 'function' ? showToast : (typeof toast === 'function' ? toast : alert));
                    notify(e.message || 'Failed to submit response', 'error');
                }
            }

            // Start rendering
            renderCurrentQuestion();
        });
    </script>
@endpush
