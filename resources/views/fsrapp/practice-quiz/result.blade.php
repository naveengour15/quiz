@extends('fsrapp.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('fsrapp/css/home.css') }}">
@endpush

@section('content')
    <main class="min-h-screen px-container-margin pt-8 pb-12 flex flex-col items-center justify-center text-center kbc-stage text-white relative">
        <!-- Ambient Home Glow Field -->
        <div class="glow-field">
            <div class="glow-orb o1"></div>
            <div class="glow-orb o2"></div>
            <div class="glow-orb o3"></div>
        </div>

        <div class="kbc-spotlight"></div>
        <div class="kbc-stage-rings"><span></span><span></span><span></span><span></span></div>
        <div id="result-root" class="relative z-[1] w-full max-w-md">
            <div class="w-12 h-12 border-4 border-tertiary/30 border-t-tertiary rounded-full spin mx-auto mb-4"></div>
            <p class="text-on-surface-variant text-sm font-bold tracking-wide">Loading practice result...</p>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const quizId = @json($id ?? null) || (typeof params !== 'undefined' ? params.id : null) || (location.pathname.split('/').pop());
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function qs(sel, root = document) { return root.querySelector(sel); }
            function unwrap(json) {
                if (json == null) return json;
                if (json.data !== undefined) return json.data;
                if (json.result !== undefined) return json.result;
                return json;
            }
            function escapeHtml(s) {
                return (s ?? '').toString().replace(/[&<>"']/g, c => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            }
            function navigateTo(path) {
                window.location.href = path;
            }

            async function practiceApi(path, options = {}) {
                const url = path.startsWith('http') ? path : (path.startsWith('/fsrapp') || path.startsWith('/practice-quiz') ? window.location.origin + path : window.location.origin + '/fsrapp' + path);
                const method = options.method || 'GET';
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                };
                try {
                    const res = await fetch(url, {
                        method,
                        headers,
                        body: options.body ? (typeof options.body === 'string' ? options.body : JSON.stringify(options.body)) : undefined
                    });
                    const text = await res.text();
                    let data;
                    try { data = JSON.parse(text); } catch(e) { data = { raw: text }; }
                    if (!res.ok) {
                        throw new Error((data && (data.message || data.error)) || `Request failed (${res.status})`);
                    }
                    return data;
                } catch (e) {
                    console.error('[Practice Quiz Request Error]', e);
                    throw e;
                }
            }

            try {
                const res = await practiceApi(`/fsrapp/practice-quiz/my-result/${quizId}`, { method: 'GET' });
                const data = unwrap(res) || res || {};

                const root = qs('#result-root');
                if (!root) return;

                if (!data.success) {
                    root.innerHTML = `
                        <div class="kbc-card rounded-3xl p-6 border border-white/10 bg-surface-container/80 backdrop-blur-xl">
                            <p class="text-amber-200 font-bold mb-4 text-base">${escapeHtml(data.message || 'No practice result found')}</p>
                            <button id="btn-err-home" class="w-full py-3.5 rounded-2xl font-bold bg-gradient-to-r from-tertiary via-amber-400 to-tertiary text-black hover:brightness-110 active:scale-95 transition-all cursor-pointer">Back to Home</button>
                        </div>
                    `;
                    const errHomeBtn = qs('#btn-err-home');
                    if (errHomeBtn) errHomeBtn.addEventListener('click', () => navigateTo('/fsrapp/home'));
                    return;
                }

                const totalQ = data.total_questions || 10;
                const correctQ = data.total_correct_answer || 0;
                const pct = Math.round((correctQ / totalQ) * 100);
                const totalTime = data.total_time || 0;
                const avgTime = data.per_ans_average_time || 0;

                root.innerHTML = `
                    <div class="kbc-card rounded-3xl p-6 border border-white/15 bg-surface-container/80 backdrop-blur-xl shadow-2xl">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-tertiary/20 via-amber-400/30 to-purple-500/20 border-2 border-tertiary flex items-center justify-center mx-auto mb-4 shadow-[0_0_25px_rgba(244,191,50,0.4)]">
                            <span class="material-symbols-outlined text-tertiary text-4xl">workspace_premium</span>
                        </div>
                        
                        <h2 class="font-display text-2xl font-black text-white mb-1">Practice Complete!</h2>
                        <p class="text-on-surface-variant text-xs font-semibold mb-6">Here is how you performed in this practice session</p>

                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-black/40 rounded-2xl p-4 border border-white/10 text-center">
                                <span class="block text-3xl font-black text-mint">${correctQ} / ${totalQ}</span>
                                <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Score</span>
                            </div>
                            <div class="bg-black/40 rounded-2xl p-4 border border-white/10 text-center">
                                <span class="block text-3xl font-black text-tertiary">${pct}%</span>
                                <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Accuracy</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <div class="bg-black/30 rounded-xl p-3 border border-white/5 text-center">
                                <span class="block text-base font-extrabold text-white">${totalTime}s</span>
                                <span class="text-[9px] text-on-surface-variant uppercase font-bold tracking-wider">Total Time</span>
                            </div>
                            <div class="bg-black/30 rounded-xl p-3 border border-white/5 text-center">
                                <span class="block text-base font-extrabold text-white">${avgTime}s</span>
                                <span class="text-[9px] text-on-surface-variant uppercase font-bold tracking-wider">Avg / Answer</span>
                            </div>
                        </div>

                        <p class="text-[11px] text-purple-200/70 mb-6 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20">
                            💡 Practice results sharpen your knowledge and prepare you for live competing quizzes!
                        </p>

                        <div class="flex flex-col gap-3">
                            <button id="btn-review-sheet" class="w-full py-4 rounded-2xl font-display font-black text-base bg-gradient-to-r from-tertiary via-amber-400 to-tertiary text-black shadow-[0_0_20px_rgba(244,191,50,0.4)] hover:brightness-110 active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-xl">fact_check</span>
                                <span>Review Answer Sheet</span>
                            </button>
                            <button id="btn-try-again" class="w-full py-3.5 rounded-2xl font-bold text-sm bg-surface-container-high border border-white/10 text-white hover:bg-surface-container-highest transition-all cursor-pointer flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">replay</span>
                                <span>Play Again</span>
                            </button>
                            <button id="btn-res-home" class="w-full py-3.5 rounded-2xl font-bold text-sm bg-transparent border border-white/10 text-on-surface-variant hover:text-white transition-all cursor-pointer">
                                Back to Home
                            </button>
                        </div>
                    </div>
                `;

                const reviewSheetBtn = qs('#btn-review-sheet');
                if (reviewSheetBtn) reviewSheetBtn.addEventListener('click', () => navigateTo(`/fsrapp/practice-quiz/review/${quizId}`));
                const tryAgainBtn = qs('#btn-try-again');
                if (tryAgainBtn) tryAgainBtn.addEventListener('click', () => navigateTo(`/fsrapp/practice-quiz/play/${quizId}`));
                const resHomeBtn = qs('#btn-res-home');
                if (resHomeBtn) resHomeBtn.addEventListener('click', () => navigateTo('/fsrapp/home'));
            } catch (e) {
                console.error('[Result Load Error]', e);
            }
        });
    </script>
@endpush
