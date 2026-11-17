@extends('fsrapp.layouts.app')
@section('content')
    @include('fsrapp.layouts.header')

    <style>
        .premium-wallet-card {
            background: radial-gradient(120% 120% at 50% 0%, rgba(124, 58, 237, 0.22) 0%, rgba(217, 70, 239, 0.08) 50%, rgba(15, 8, 36, 0.95) 100%);
            border: 1px solid rgba(196, 158, 255, 0.25);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .premium-wallet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 200%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.03), transparent);
            transform: skewX(-25deg);
            transition: 0.75s;
            pointer-events: none;
        }

        .premium-wallet-card:hover::before {
            left: 120%;
        }

        .action-card-glow {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
        }

        .action-card-glow:hover {
            transform: translateY(-3px) scale(1.02);
            border-color: rgba(196, 158, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }

        .transaction-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
        }

        .transaction-item:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateX(2px);
        }

        .glowing-coin {
            animation: coin-bounce 3s ease-in-out infinite;
        }

        @keyframes coin-bounce {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-5px) rotate(5deg);
            }
        }
    </style>

    <main class="max-w-md mx-auto px-4 pt-20 pb-28 min-h-screen">

        <!-- ===== Laravel Session Alerts ===== -->
        @if (session('success'))
            <div
                class="mb-4 p-3.5 rounded-xl bg-[#00E5A0]/20 border border-[#00E5A0]/50 text-[#00E5A0] text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-4 p-3.5 rounded-xl bg-red-950/80 border border-red-500/30 text-red-200 text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ session('error') }}
            </div>
        @endif

        <!-- ===== Wallet Balance Hero Card ===== -->
        <div class="premium-wallet-card rounded-3xl p-6 mb-5 text-center shadow-2xl">
            <!-- Ambient Glow Orbs -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-[#7c3aed]/30 rounded-full blur-3xl pointer-events-none">
            </div>
            <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-[#ffd76a]/20 rounded-full blur-3xl pointer-events-none">
            </div>

            <div
                class="relative z-10 w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4 shadow-[0_8px_30px_rgba(244,191,80,0.3)] bg-gradient-to-br from-[#ffd76a] via-[#f4bf32] to-[#d97706] glowing-coin">
                <span class="material-symbols-outlined text-[#160d2f] text-3xl"
                    style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
            </div>

            <p class="relative z-10 text-[10px] font-extrabold tracking-widest text-[#e8ddff]/60 uppercase">{{ __('app.total_points_balance') }}</p>
            <div class="relative z-10 flex items-center justify-center gap-1.5 mt-1.5">
                <span
                    class="font-display text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-[#ffd76a] via-[#fff5d0] to-[#ffd76a]"
                    id="wallet-balance">
                    {{ number_format($walletBalance ?? 0, 0) }}
                </span>
                <span class="font-display text-lg font-bold text-[#ffd76a] mt-1.5">{{ __('app.pts') }}</span>
            </div>
            <p class="relative z-10 text-[11px] text-[#e8ddff]/40 mt-2">{{ __('app.use_points_redeem') }}</p>
        </div>

        @include('fsrapp.home.components.banners', ['banners' => $banners,'pageType' => 'wallet'])

        <!-- ===== Quick Action Cards ===== -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <a href="{{ route('fsrapp.redeem.index') }}"
                class="action-card-glow rounded-2xl p-4 flex flex-col items-center gap-2">
                <div
                    class="w-11 h-11 rounded-2xl bg-[#e879f9]/15 border border-[#e879f9]/30 flex items-center justify-center text-[#e879f9] shadow-inner">
                    <span class="material-symbols-outlined text-[24px]">redeem</span>
                </div>
                <div class="text-center">
                    <span class="block text-xs font-extrabold text-[#e8ddff]">{{ __('app.redeem_points') }}</span>
                    <span class="block text-[10px] text-white/40 mt-0.5">{{ __('app.vouchers_cash') }}</span>
                </div>
            </a>
            <a href="{{ route('fsrapp.referral') }}"
                class="action-card-glow rounded-2xl p-4 flex flex-col items-center gap-2">
                <div
                    class="w-11 h-11 rounded-2xl bg-[#00E5A0]/15 border border-[#00E5A0]/30 flex items-center justify-center text-[#00E5A0] shadow-inner">
                    <span class="material-symbols-outlined text-[24px]">share</span>
                </div>
                <div class="text-center">
                    <span class="block text-xs font-extrabold text-[#e8ddff]">{{ __('app.refer_and_earn') }}</span>
                    <span class="block text-[10px] text-white/40 mt-0.5">{{ __('app.invite_friends') }}</span>
                </div>
            </a>
        </div>

        <!-- ===== Transaction History Section ===== -->
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-display text-base font-bold text-[#e8ddff] flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-[#d2bbff]">history</span> {{ __('app.transaction_history') }}
            </h2>
            @if (isset($walletHistory) && count($walletHistory) > 0)
                <span class="text-xs font-semibold text-white/40">{{ $totalCount ?? count($walletHistory) }} {{ __('app.entries') }}</span>
            @endif
        </div>

        <div class="space-y-2.5" id="wallet-history">
            @include('fsrapp.wallet.partials.transactions')
        </div>

        <div id="wallet-load-more-wrap" class="mt-4 text-center" style="{{ isset($walletHistory) && method_exists($walletHistory, 'hasMorePages') && $walletHistory->hasMorePages() ? '' : 'display:none;' }}">
            <button id="wallet-load-more-btn" type="button" class="fsr-load-more-btn">
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
                {{ __('app.load_more') }}
            </button>
        </div>
    </main>

    @include('fsrapp.layouts.bottommenu')
@endsection

@push('scripts')
    <script>
        (function() {
            const historyContainer = document.getElementById('wallet-history');
            const loadMoreWrap = document.getElementById('wallet-load-more-wrap');
            const loadMoreBtn = document.getElementById('wallet-load-more-btn');

            let nextPageUrl = '{!! isset($walletHistory) && method_exists($walletHistory, 'nextPageUrl') ? $walletHistory->nextPageUrl() : '' !!}';
            let isLoading = false;

            function loadNextTransactions() {
                if (!nextPageUrl || isLoading) return;
                isLoading = true;

                if (loadMoreBtn) {
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> ' + @json(__('app.loading'));
                    loadMoreBtn.style.opacity = '0.7';
                }

                fetch(nextPageUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.html) {
                        historyContainer.insertAdjacentHTML('beforeend', data.html);
                    }
                    nextPageUrl = data.next_page;
                    if (loadMoreWrap) {
                        loadMoreWrap.style.display = nextPageUrl ? 'block' : 'none';
                    }
                })
                .catch(err => {
                    console.error('Error loading wallet history:', err);
                })
                .finally(() => {
                    isLoading = false;
                    if (loadMoreBtn) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">expand_more</span> ' + @json(__('app.load_more'));
                        loadMoreBtn.style.opacity = '1';
                    }
                });
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadNextTransactions);
            }

            if ('IntersectionObserver' in window && loadMoreWrap) {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && nextPageUrl && !isLoading) {
                        loadNextTransactions();
                    }
                }, { rootMargin: '250px' });
                observer.observe(loadMoreWrap);
            }
        })();
    </script>
@endpush
