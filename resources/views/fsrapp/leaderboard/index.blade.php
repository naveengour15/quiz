@extends('fsrapp.layouts.app')

@section('content')
    @include('fsrapp.layouts.header')

    <main class="max-w-md mx-auto px-4 pt-20 pb-28 min-h-screen relative z-10">

        <!-- ===== Hero Leaderboard Card ===== -->
        <div class="relative overflow-hidden rounded-3xl border border-white/10 pt-6 px-6 pb-0 mb-5 text-center shadow-2xl bg-gradient-to-br from-[#7c3aed]/30 via-[#160d2f] to-[#0f0824]">
            <!-- Ambient Glow Orbs & Light Rays -->
            <div class="absolute -top-12 -right-12 w-40 h-40 bg-[#7c3aed]/30 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
            <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-[#ffd76a]/20 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Icon Badge -->
            <div class="relative z-10 w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-3 shadow-[0_0_30px_rgba(244,191,80,0.4)] bg-gradient-to-br from-[#ffd76a] via-[#f4bf32] to-[#d97706] animate-subtle-float">
                <span class="material-symbols-outlined text-[#160d2f] text-3xl" style="font-variation-settings: 'FILL' 1;">emoji_events</span>
            </div>

            <h3 class="relative z-10 font-display text-2xl font-extrabold shimmer-gold-text mb-1">{{ __('app.leaderboard_title') }}</h3>
            <p class="relative z-10 text-xs text-white/60 mb-6 leading-relaxed max-w-[280px] mx-auto">{{ __('app.leaderboard_subtitle') }}</p>

            <div class="relative z-10 flex items-center gap-3 mb-6">
                <span class="flex-1 h-[1px] bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"></span>
                <span class="font-display text-[11px] font-bold text-[#ffd76a] uppercase tracking-widest bg-amber-500/10 px-3 py-1 rounded-full border border-amber-500/20 shadow-sm">{{ __('app.top_3_champions') }}</span>
                <span class="flex-1 h-[1px] bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"></span>
            </div>

            @php
                $getValidPhoto = function ($photo) {
                    if (empty($photo)) return null;
                    if (\Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])) return $photo;
                    $path = ltrim($photo, '/');
                    $s3Base = rtrim(config('filesystems.disks.s3.url') ?: env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/');
                    return \Illuminate\Support\Str::startsWith($path, 'storage/') ? asset($path) : ($s3Base . '/' . $path);
                };

                $top1 = isset($top3) ? $top3->firstWhere('lb_rank', 1) ?? $top3->first() : null;
                $top2 = isset($top3)
                    ? $top3->firstWhere('lb_rank', 2) ?? ($top3->count() > 1 ? $top3->skip(1)->first() : null)
                    : null;
                $top3rd = isset($top3)
                    ? $top3->firstWhere('lb_rank', 3) ?? ($top3->count() > 2 ? $top3->skip(2)->first() : null)
                    : null;

                $photo1 = $top1 ? $getValidPhoto($top1->profile_photo ?? null) : null;
                $photo2 = $top2 ? $getValidPhoto($top2->profile_photo ?? null) : null;
                $photo3 = $top3rd ? $getValidPhoto($top3rd->profile_photo ?? null) : null;
            @endphp

            <!-- ===== Creative 3D Glass Podium (Flush to Bottom Baseline) ===== -->
            <div class="relative z-10 grid grid-cols-3 gap-2 items-end pt-2 px-1">
                
                <!-- 2nd Place (Silver) -->
                <div class="flex flex-col items-center">
                    <!-- Avatar & Badge -->
                    <div class="relative mb-2">
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-slate-200 to-slate-400 text-slate-950 font-black text-[11px] flex items-center justify-center absolute -top-2 left-1/2 -translate-x-1/2 z-20 border border-white shadow-md">2</div>
                        <div class="w-16 h-16 rounded-full border-2 border-slate-300 bg-gradient-to-tr from-[#3d2f70] to-[#211644] flex items-center justify-center overflow-hidden shadow-[0_0_15px_rgba(203,213,225,0.3)] relative z-10">
                            @if ($photo2)
                                <img src="{{ $photo2 }}" alt="{{ $top2->name ?? '2' }}" width="64" height="64" class="w-full h-full object-cover" loading="lazy" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <span class="hidden font-bold text-slate-200 text-sm">{{ strtoupper(mb_substr(trim($top2->name ?? 'N'), 0, 1)) }}</span>
                            @else
                                <span class="font-bold text-slate-200 text-sm">{{ strtoupper(mb_substr(trim($top2->name ?? 'N'), 0, 1)) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Name & Score -->
                    <p class="font-display text-xs font-bold text-[#e8ddff] truncate max-w-[85px] mb-0.5">{{ $top2->name ?? '—' }}</p>
                    <span class="font-mono text-[11px] font-bold text-slate-300 bg-white/5 px-2 py-0.5 rounded-full border border-white/10 mb-2">{{ number_format($top2->lb_points ?? 0) }} </span>

                    <!-- Pedestal Stand Base (Silver) -->
                    <div class="w-full h-24 rounded-t-2xl border-t-2 border-x border-slate-400/40 bg-gradient-to-t from-slate-400/30 via-slate-400/10 to-slate-400/20 backdrop-blur-md flex items-center justify-center shadow-lg">
                        <span class="font-display font-black text-2xl text-slate-300/40 tracking-wider">#2</span>
                    </div>
                </div>

                <!-- 1st Place (Gold Champion) -->
                <div class="flex flex-col items-center -mt-6">
                    <!-- Crown -->
                    <span class="material-symbols-outlined text-[#ffd76a] text-3xl -mb-1 animate-crown drop-shadow-[0_0_10px_rgba(255,215,106,0.9)] z-20" style="font-variation-settings: 'FILL' 1;">crown</span>

                    <!-- Avatar & Gold Badge -->
                    <div class="relative mb-2">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#ffd76a] via-[#f4bf32] to-[#d97706] text-slate-950 font-black text-xs flex items-center justify-center absolute -top-2.5 left-1/2 -translate-x-1/2 z-30 border-2 border-white shadow-[0_0_12px_rgba(255,215,106,0.9)]">1</div>
                        <div class="w-20 h-20 rounded-full border-4 border-[#ffd76a] bg-gradient-to-tr from-[#3d2f70] to-[#211644] flex items-center justify-center overflow-hidden animate-gold-aura relative z-10">
                            @if ($photo1)
                                <img src="{{ $photo1 }}" alt="{{ $top1->name ?? '1' }}" width="80" height="80" class="w-full h-full object-cover" loading="lazy" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <span class="hidden font-bold text-[#ffe9b0] text-base">{{ strtoupper(mb_substr(trim($top1->name ?? 'N'), 0, 1)) }}</span>
                            @else
                                <span class="font-bold text-[#ffe9b0] text-base">{{ strtoupper(mb_substr(trim($top1->name ?? 'N'), 0, 1)) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Name & Gold Score -->
                    <p class="font-display text-xs font-extrabold text-[#ffe9b0] truncate max-w-[95px] mb-0.5">{{ $top1->name ?? '—' }}</p>
                    <span class="font-mono text-xs font-bold shimmer-gold-text bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/30 mb-2 shadow-sm">{{ number_format($top1->lb_points ?? 0) }} </span>

                    <!-- Pedestal Stand Base (Gold) -->
                    <div class="w-full h-32 rounded-t-2xl border-t-2 border-x border-amber-400/50 bg-gradient-to-t from-amber-500/40 via-amber-500/15 to-amber-500/25 backdrop-blur-md flex flex-col items-center justify-center shadow-[0_-5px_25px_rgba(255,215,106,0.3)]">
                        <span class="font-display font-black text-3xl text-[#ffd76a]/60 tracking-wider">#1</span>
                        <span class="text-[9px] font-bold text-[#ffd76a]/80 uppercase tracking-widest">WINNER</span>
                    </div>
                </div>

                <!-- 3rd Place (Bronze) -->
                <div class="flex flex-col items-center">
                    <!-- Avatar & Badge -->
                    <div class="relative mb-2">
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-[#cd7f32] to-[#8c4a16] text-white font-black text-[11px] flex items-center justify-center absolute -top-2 left-1/2 -translate-x-1/2 z-20 border border-white shadow-md">3</div>
                        <div class="w-16 h-16 rounded-full border-2 border-[#cd7f32] bg-gradient-to-tr from-[#3d2f70] to-[#211644] flex items-center justify-center overflow-hidden shadow-[0_0_15px_rgba(205,127,50,0.3)] relative z-10">
                            @if ($photo3)
                                <img src="{{ $photo3 }}" alt="{{ $top3rd->name ?? '3' }}" width="64" height="64" class="w-full h-full object-cover" loading="lazy" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <span class="hidden font-bold text-[#f3d3b3] text-sm">{{ strtoupper(mb_substr(trim($top3rd->name ?? 'N'), 0, 1)) }}</span>
                            @else
                                <span class="font-bold text-[#f3d3b3] text-sm">{{ strtoupper(mb_substr(trim($top3rd->name ?? 'N'), 0, 1)) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Name & Score -->
                    <p class="font-display text-xs font-bold text-[#e8ddff] truncate max-w-[85px] mb-0.5">{{ $top3rd->name ?? '—' }}</p>
                    <span class="font-mono text-[11px] font-bold text-[#f3d3b3] bg-white/5 px-2 py-0.5 rounded-full border border-white/10 mb-2">{{ number_format($top3rd->lb_points ?? 0) }} </span>

                    <!-- Pedestal Stand Base (Bronze) -->
                    <div class="w-full h-18 rounded-t-2xl border-t-2 border-x border-amber-700/40 bg-gradient-to-t from-amber-800/30 via-amber-800/10 to-amber-800/20 backdrop-blur-md flex items-center justify-center shadow-lg">
                        <span class="font-display font-black text-2xl text-[#f3d3b3]/40 tracking-wider">#3</span>
                    </div>
                </div>

            </div>
        </div>

        <div class="fsr-lazy-section lazy-banners mb-4">
            @include('fsrapp.home.components.banners', ['banners' => $banners,'pageType' => 'yearly_leaderboard'])
        </div>

        {{-- ✅ NEW: View Leaderboard History Card (Premium Design) --}}
        <a href="{{ route('fsrapp.history.my-leaderboard') }}" class="block group mb-5">
            <div class="history-card-glow rounded-2xl p-4 flex items-center justify-between border border-[#7c3aed]/30 bg-[#7c3aed]/10 hover:border-[#7c3aed]/60 hover:bg-[#7c3aed]/15 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#7c3aed]/20 border border-[#7c3aed]/40 flex items-center justify-center text-[#c49eff] group-hover:scale-110 group-hover:shadow-[0_0_15px_rgba(124,58,237,0.4)] transition-all duration-300">
                        <span class="material-symbols-outlined text-[24px]">history</span>
                    </div>
                    <div class="text-left">
                        <span class="block text-sm font-extrabold text-[#e8ddff]">View Leaderboard History</span>
                        <span class="block text-[11px] text-white/50 mt-0.5">Check your past marks </span>
                    </div>
                </div>
                <span class="material-symbols-outlined text-[#c49eff] group-hover:translate-x-1 transition-transform duration-300">arrow_forward_ios</span>
            </div>
        </a>
        {{-- ✅ END: View History Card --}}

        <!-- ===== Sticky "Your Rank" Row ===== -->
        @php
            $authId = auth()->id();
            $myEntry = null;
            $inTop3 = false;
            if ($authId && isset($top3)) {
                $inTop3 = $top3->contains('user_id', $authId);
            }
            if ($authId && !$inTop3) {
                $myEntry = $leaderboard->first(fn($e) => $e->user_id == $authId) ?? null;
                if (!$myEntry && isset($myRank)) {
                    $myEntry = $myRank;
                }
            }
            $myPhoto = $myEntry ? $getValidPhoto($myEntry->profile_photo ?? null) : null;
        @endphp

        @if ($myEntry)
            <div class="glass-card rounded-2xl p-3.5 mb-4 border border-[#00E5A0]/40 bg-[#00E5A0]/10 flex items-center justify-between shadow-lg hover:border-[#00E5A0]/60 transition-all lb-row-animate">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="font-mono text-sm font-bold text-[#00E5A0] shrink-0">#{{ $myEntry->lb_rank ?? '—' }}</span>
                    <div class="w-9 h-9 rounded-full bg-[#00E5A0]/20 border border-[#00E5A0]/40 flex items-center justify-center shrink-0 overflow-hidden">
                        @if ($myPhoto)
                            <img src="{{ $myPhoto }}" alt="You" width="36" height="36" class="w-full h-full object-cover" loading="lazy" decoding="async" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <span class="hidden font-bold text-[#00E5A0] text-xs">{{ strtoupper(mb_substr(trim($myEntry->name ?? 'U'), 0, 1)) }}</span>
                        @else
                            <span class="font-bold text-[#00E5A0] text-xs">{{ strtoupper(mb_substr(trim($myEntry->name ?? 'U'), 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-display text-xs font-bold text-[#00E5A0] truncate">{{ $myEntry->name ?? __('app.you') }} <span class="text-[10px] bg-[#00E5A0]/20 px-1.5 py-0.5 rounded font-mono font-bold">{{ strtoupper(__('app.you')) }}</span></p>
                        <p class="text-[11px] text-white/50">{{ __('app.your_rank') }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <span class="font-mono text-xs font-bold text-[#00E5A0]">{{ number_format($myEntry->lb_points ?? 0) }} </span>
                </div>
            </div>
        @endif


        <!-- ===== Leaderboard Ranks Table ===== -->
        <div class="glass-card rounded-2xl border border-white/10 bg-white/[0.02] overflow-hidden mb-4 shadow-xl">
            <!-- Table Header -->
            <div class="grid grid-cols-12 px-4 py-3 bg-white/5 border-b border-white/10 text-[11px] font-bold text-white/40 uppercase tracking-wider">
                <div class="col-span-2">{{ __('app.rank') }}</div>
              
                <div class="col-span-5">{{ __('app.player') }}</div>
                  <div class="col-span-2">Marks</div>
                <div class="col-span-3 text-right">{{ __('app.rewards') }}</div>
            </div>

            <!-- Table Rows Body -->
            <div class="divide-y divide-white/5" id="lb-rows-container">
                @include('fsrapp.leaderboard.partials.rows')
            </div>

            <!-- Load More Wrap -->
            <div id="lb-load-more-wrap" class="p-4 text-center border-t border-white/5" style="{{ isset($leaderboard) && method_exists($leaderboard, 'hasPages') && $leaderboard->hasPages() ? '' : 'display:none;' }}">
                <button id="lb-load-more-btn" type="button" class="fsr-load-more-btn">
                    <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    {{ __('app.load_more_ranks') }}
                </button>
            </div>
        </div>

    </main>

    @include('fsrapp.layouts.bottommenu')

    <button id="lb-scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Scroll to top"
        class="fixed bottom-20 right-4 z-40 w-11 h-11 rounded-full bg-gradient-to-tr from-[#7c3aed] to-[#d946ef] text-white shadow-[0_4px_16px_rgba(217,70,239,0.4)] flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 hover:scale-110 active:scale-95">
        <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
    </button>
@endsection

@push('scripts')
    <script>
        (function() {
            // Scroll top FAB controller
            const fab = document.getElementById('lb-scroll-top');
            if (fab) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 400) {
                        fab.classList.remove('opacity-0', 'pointer-events-none');
                    } else {
                        fab.classList.add('opacity-0', 'pointer-events-none');
                    }
                }, { passive: true });
            }

            // Load More Pagination via Controlled Click with Anti-Spam Throttle
            const rowsContainer = document.getElementById('lb-rows-container');
            const loadMoreWrap = document.getElementById('lb-load-more-wrap');
            const loadMoreBtn = document.getElementById('lb-load-more-btn');

            let nextPageUrl = '{!! isset($leaderboard) && method_exists($leaderboard, 'nextPageUrl') ? $leaderboard->nextPageUrl() : '' !!}';
            let isLoading = false;

            function normalizePageUrl(url) {
                if (!url) return null;
                try {
                    const parsed = new URL(url, window.location.origin);
                    return window.location.origin + parsed.pathname + parsed.search;
                } catch(e) {
                    return url;
                }
            }

            function loadNextPage() {
                if (!nextPageUrl || isLoading) return;
                isLoading = true;

                if (loadMoreBtn) {
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> ' + @json(__('app.loading'));
                    loadMoreBtn.style.opacity = '0.7';
                }

                const targetUrl = normalizePageUrl(nextPageUrl);

                fetch(targetUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (data && data.html) {
                        rowsContainer.insertAdjacentHTML('beforeend', data.html);
                    }
                    nextPageUrl = data ? data.next_page : null;
                    if (loadMoreWrap) {
                        loadMoreWrap.style.display = nextPageUrl ? 'block' : 'none';
                    }
                })
                .catch(err => {
                    console.error('Error loading leaderboard entries:', err);
                })
                .finally(() => {
                    // 400ms cooldown to prevent spam-clicking
                    setTimeout(() => {
                        isLoading = false;
                        if (loadMoreBtn) {
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">expand_more</span> ' + @json(__('app.load_more_ranks'));
                            loadMoreBtn.style.opacity = '1';
                        }
                    }, 400);
                });
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadNextPage);
            }
        })();
    </script>
@endpush
