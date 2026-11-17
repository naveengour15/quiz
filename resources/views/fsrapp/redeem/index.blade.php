@extends('fsrapp.layouts.app')
@section('content')
    @include('fsrapp.layouts.header')

    {{-- @php
        // Current time ko 'Hi' format mein lein (e.g., 1430 matlab 2:30 PM, 1959 matlab 7:59 PM)
        $currentTime = now()->format('Hi');
        
        // Check karein ki kya time 11:00 (1100) aur 21:00 (2100) ke beech hai
        $isRequestTime = ($currentTime >= '1100' && $currentTime <= '2100');
    @endphp --}}
    @php
        $isRequestTime = $isRequestTime ?? true;
        $startTimeFormatted = $startTimeFormatted ?? '11:00 AM';
        $endTimeFormatted = $endTimeFormatted ?? '09:00 PM';

        $isDayOff = $isDayOff ?? false;
     
        $offDayMessage = $offDayMessage ?? 'Redeem requests are closed today.';
    @endphp

    <main class="max-w-md mx-auto pt-20 pb-24 px-4 min-h-screen">

        <!-- ===== Laravel Session Alerts ===== -->
        @if (session('success'))
            <div class="mb-4 p-3.5 rounded-xl bg-[#00E5A0]/20 border border-[#00E5A0]/50 text-[#00E5A0] text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3.5 rounded-xl bg-red-950/80 border border-red-500/30 text-red-200 text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ session('error') }}
            </div>
        @endif


      

        <div id="toast" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%] toast-enter"></div>

        <!-- Header Row -->
        <div class="flex justify-between items-center mb-5">
            <h4 class="font-display text-lg font-bold text-[#e8ddff]">{{ __('app.reward_catalog') }}</h4>
            <button onclick="fsrSwitchTab('history')" class="text-xs font-bold text-[#ffd76a] hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">history</span>{{ __('app.my_redemptions') }}
            </button>
        </div>

        <!-- Balance / CTA Card -->
        <div class="fsr-balance-card p-5 mb-6">
            <p class="text-[12.5px] font-semibold tracking-wide text-white/70">{{ strtoupper(__('app.available_points')) }}</p>
            <div class="flex items-end gap-2 mt-1">
                <span class="font-display text-4xl font-extrabold text-[#ffe9b0]">{{ number_format($walletBalance ?? 0) }}</span>
                <span class="text-sm mb-1 text-white/60">{{ __('app.points_short') }}</span>
            </div>
            {{-- <button id="fsr-open-modal"
                class="fsr-redeem-btn w-full mt-4 py-3 rounded-xl font-display font-bold text-[15px] flex items-center justify-center gap-2 active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[19px]">redeem</span>
                {{ __('app.new_redeem_request') }}
            </button> --}}

          
            @if($isDayOff)
                 <!-- 🚫 Redeem Request Off Day Card -->
                <div class="w-full mt-4 p-4 rounded-xl border border-red-500/30 bg-red-500/10 flex items-start gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-red-400 text-[28px] shrink-0 mt-0.5">event_busy</span>
                    <div>
                        <h4 class="font-display font-bold text-red-300 text-[14px] mb-1">Redeem Requests Closed</h4>
                        <p class="text-[12.5px] text-red-200/80 leading-relaxed">
                            {!! $offDayMessage !!}
                        </p>
                    </div>
                </div>
         
            @elseif($isRequestTime)
                <!-- ✅ Normal Button (11:00 AM se 9:00 PM ke beech) -->
                <button id="fsr-open-modal"
                    class="fsr-redeem-btn w-full mt-4 py-3 rounded-xl font-display font-bold text-[15px] flex items-center justify-center gap-2 active:scale-95 transition-transform">
                    <span class="material-symbols-outlined text-[19px]">redeem</span>
                    {{ __('app.new_redeem_request') }}
                </button>
            @else
                <!-- ⚠️ Warning Card Box (Time ke bahar) -->
                <div class="w-full mt-4 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 flex items-start gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-amber-400 text-[28px] shrink-0 mt-0.5">schedule</span>
                    <div>
                        <h4 class="font-display font-bold text-amber-300 text-[14px] mb-1">Request Time Restricted</h4>
                        <p class="text-[12.5px] text-amber-200/80 leading-relaxed">
                            Aap redeem request sirf <strong>{{ $startTimeFormatted }} se {{ $endTimeFormatted }}</strong> ke beech hi kar sakte hain. Kripya is samay ke dauran wapis aayein.
                        </p>
                    </div>
                </div>
            @endif


        </div>

    
            <!-- ===== Redeem Banner ===== -->
            {{-- @php
                $activeBanners = isset($banners)
                    ? $banners->filter(function ($banner) {
                        $types = $banner->type ?? [];

                        return in_array('all', $types)
                            || in_array('redeem_request', $types);
                    })->values()
                    : collect();
            @endphp

            @if ($activeBanners->count() > 0)

                <section class="mb-4" id="redeem-banner-section">

                    <div class="relative rounded-2xl" id="redeem-banner-track">

                        <div class="flex overflow-x-auto scrollbar-hide snap-x snap-mandatory rounded-2xl"
                            id="redeem-banner-scroller"
                            style="scroll-behavior: smooth;">

                            @foreach ($activeBanners as $index => $banner)

                                <div class="banner-slide w-full shrink-0 snap-center cursor-pointer"
                                    data-link="{{ $banner->url ?? '' }}"
                                    onclick="if('{{ $banner->url }}'){ window.open('{{ $banner->url }}', '_blank'); }">

                                    <div class="relative w-full rounded-2xl overflow-hidden glass-card flex items-center justify-center min-h-[140px] bg-white/[0.02]">

                                        @if ($banner->banner_file)

                                            @php
                                                $imgUrl = Str::startsWith(
                                                    $banner->banner_file,
                                                    ['http://', 'https://']
                                                )
                                                    ? $banner->banner_file
                                                    : rtrim(
                                                        env(
                                                            'AWS_URL',
                                                            'https://chaloonline.s3.ap-south-1.amazonaws.com'
                                                        ),
                                                        '/'
                                                    ) . '/' . ltrim($banner->banner_file, '/');
                                            @endphp

                                            <img src="{{ $imgUrl }}"
                                                alt="{{ $banner->name }}"
                                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                                {!! $index === 0 ? 'fetchpriority="high"' : '' !!}
                                                class="w-full h-auto object-contain block"
                                                style="border-radius:16px;">

                                        @else

                                            <div class="w-full h-40 vibrant-gradient"
                                                style="border-radius:16px;">
                                            </div>

                                        @endif

                                    </div>

                                </div>

                            @endforeach

                        </div>

                        @if ($activeBanners->count() > 1)

                            <div class="flex justify-center gap-1.5 mt-2"
                                id="redeem-banner-dots">

                                @foreach ($activeBanners as $index => $banner)

                                    <span
                                        class="w-2 h-2 rounded-full transition-all duration-300 redeem-banner-dot {{ $index === 0 ? 'w-5' : 'bg-white/30' }}"
                                        style="{{ $index === 0 ? 'background:#f4bf32;' : '' }}"
                                        data-i="{{ $index }}">
                                    </span>

                                @endforeach

                            </div>

                        @endif

                    </div>

                </section>

                @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {

                            const bannerScroller =
                                document.getElementById('redeem-banner-scroller');

                            const bannerDots =
                                document.querySelectorAll('.redeem-banner-dot');

                            if (bannerScroller && bannerDots.length > 0) {

                                bannerScroller.addEventListener('scroll', function () {

                                    const scrollLeft = bannerScroller.scrollLeft;
                                    const slideWidth = bannerScroller.clientWidth;

                                    const activeIndex =
                                        Math.round(scrollLeft / slideWidth);

                                    bannerDots.forEach((dot, index) => {

                                        if (index === activeIndex) {

                                            dot.classList.add('w-5');
                                            dot.classList.remove('bg-white/30');
                                            dot.style.background = '#f4bf32';

                                        } else {

                                            dot.classList.remove('w-5');
                                            dot.classList.add('bg-white/30');
                                            dot.style.background = '';

                                        }

                                    });

                                });

                            }

                        });
                    </script>
                @endpush

            @endif --}}

            @include('fsrapp.home.components.banners', [ 'banners' => $banners,'pageType' => 'redeem_request'])

          <!-- ===== Quick Action Cards ===== -->



        <!-- Tabs -->
        <div class="fsr-tabs">
            <div class="fsr-tab active" data-tab="history" onclick="fsrSwitchTab('history')">{{ __('app.redeem_history') }}</div>
            <div class="fsr-tab" data-tab="vouchers" onclick="fsrSwitchTab('vouchers')">{{ __('app.vouchers') }}</div>
        </div>

        <!-- Redeem History Panel -->
        <div class="fsr-tab-panel active flex-col gap-3" id="fsr-panel-history">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-display text-base font-bold text-[#e8ddff]">{{ __('app.redeem_history') }}</h2>
                <span class="text-[12px] text-white/50">{{ count($redeemHistories) }} {{ __('app.requests') }}</span>
            </div>

            @forelse ($redeemHistories as $history)
                @php
                    $isMobile = strtolower($history->request_type) === 'mobile recharge' || strtolower($history->request_type) === 'mobile';
                    $statusClass = match (strtolower($history->status)) {
                        'approved', 'completed', 'success' => 'completed',
                        'failed', 'rejected', 'cancelled' => 'failed',
                        default => 'pending',
                    };
                    $statusText = ucfirst(strtolower($history->status));
                @endphp
                <div class="fsr-history-item" style="flex-wrap: wrap;">
                    <div class="fsr-history-icon bg-purple-500/20">
                        @if ($isMobile)
                            <span class="material-symbols-outlined text-[19px] text-[#d8b4fe]">smartphone</span>
                        @else
                            <span class="material-symbols-outlined text-[19px] text-[#ffd76a]">bolt</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[13.5px] font-semibold truncate text-[#e8ddff]">{{ $history->request_type }}</p>
                            <span class="fsr-status {{ $statusClass }}">{{ $statusText }}</span>
                        </div>
                        <p class="text-[12px] truncate mt-0.5 text-white/50">
                            @if ($isMobile)
                                {{ $history->mobile }} · {{ $history->company }}
                            @else
                                Consumer #{{ $history->consumer_number }}
                            @endif
                        </p>
                        <div class="flex items-center justify-between mt-1.5">
                            <span class="text-[11.5px] text-white/40">{{ $history->created_at ? $history->created_at->format('d M Y') : 'N/A' }}</span>
                            <span class="text-[12.5px] font-mono font-semibold text-[#ffe9b0]">-{{ number_format($history->redeem_amount) }} pts</span>
                        </div>
                    </div>

                    @if ($statusClass === 'failed' && !empty($history->approver_note))
                        <div class="w-full mt-1 pt-2 border-t border-white/10">
                            <p class="text-[11.5px] text-red-300"><span class="font-semibold text-red-400">Reason:</span> {{ $history->approver_note }}</p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="glass-card rounded-xl p-4 text-center text-white/60 text-[13px] w-full border border-white/10 bg-white/[0.02]">
                    No redeem history found.
                </div>
            @endforelse
        </div>

        <!-- Vouchers Panel -->
        <div class="fsr-tab-panel flex-col gap-5" id="fsr-panel-vouchers">
            
            <!-- Section 1: Available E-Gift Vouchers -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="font-display text-base font-bold text-[#e8ddff] flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">card_giftcard</span>
                            Available E-Gift Vouchers
                        </h2>
                        <p class="text-[12px] text-white/50">Redeem your points for instant digital vouchers</p>
                    </div>
                    <span class="text-[11.5px] font-semibold px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-[#ffe9b0]">
                        {{ count($eGiftVouchers ?? []) }} Available
                    </span>
                </div>

                <!-- ✅ NEW: View Voucher History Card (Commented out for now) -->
                <div class="mt-2 mb-4">
                    <a href="{{ route('fsrapp.redeem.e-gift-voucher-history') }}" class="block group">
                        <div class="p-4 rounded-2xl border border-[#ffd76a]/30 bg-gradient-to-r from-[#ffd76a]/10 to-transparent group-hover:from-[#ffd76a]/20 transition-all">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-[#ffd76a]/20 flex items-center justify-center text-[#ffd76a] group-hover:scale-110 transition-transform shrink-0">
                                        <span class="material-symbols-outlined text-2xl">receipt_long</span>
                                    </div>
                                    <div>
                                        <h3 class="font-display font-bold text-[#e8ddff] text-base">Voucher History</h3>
                                        <p class="text-[12px] text-white/50 whitespace-nowrap">Track your pending & approved requests</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-white/50 group-hover:translate-x-1 transition-transform shrink-0">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                </div>
     
               @if($isDayOff)
                    <!-- 🚫 Compact Voucher Day Off Card -->
                <div class="relative overflow-hidden rounded-xl border border-red-500/20 bg-red-500/5 p-4 text-center backdrop-blur-sm group mt-4">
                    <div class="absolute -top-8 -right-8 h-16 w-16 rounded-full bg-red-500/10 blur-xl transition-all group-hover:bg-red-500/20"></div>
                    <div class="relative z-10">
                        <div class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-full bg-red-500/10 border border-red-500/20 shadow-[0_0_10px_rgba(239,68,68,0.15)]">
                            <span class="material-symbols-outlined text-2xl text-red-400">card_giftcard</span>
                        </div>
                        <h3 class="font-display text-[13px] font-bold text-red-200 mb-1.5">Voucher Claiming Closed</h3>
                        <p class="text-[12px] leading-relaxed text-white/60">
                            {!! $offDayMessage !!}
                            <span class="block mt-1 text-white/40 text-[11px]">Please plan your claim accordingly.</span>
                        </p>
                    </div>
                </div> 
              
                @elseif($isRequestTime)
                    @if (isset($eGiftVouchers) && count($eGiftVouchers) > 0)
                        <div class="grid grid-cols-1 gap-3.5">
                            @foreach ($eGiftVouchers as $voucher)
                                @php
                                    $feePercent = (float) ($voucherFeePercent ?? ($processingFee->voucher ?? 0));
                                    $vAmount = (float) $voucher->amount;
                                    $feeAmount = round(($vAmount * $feePercent) / 100, 2);
                                    $totalPoints = $vAmount + $feeAmount;
                                    $canAfford = ($walletBalance ?? 0) >= $totalPoints;
                                    $inStock = ($voucher->no_of_voucher == 0) || ($voucher->no_of_used < $voucher->no_of_voucher);
                                    $remainingStock = $voucher->no_of_voucher > 0 ? max(0, $voucher->no_of_voucher - $voucher->no_of_used) : null;
                                    
                                    $vTypeLower = strtolower($voucher->type ?? $voucher->name ?? '');
                                    $brandClass = 'default';
                                    $brandIcon = 'card_giftcard';
                                    $brandBadgeText = $voucher->type ?: 'Gift Voucher';

                                    if (str_contains($vTypeLower, 'amazon')) {
                                        $brandClass = 'amazon';
                                        $brandIcon = 'shopping_bag';
                                    } elseif (str_contains($vTypeLower, 'flipkart')) {
                                        $brandClass = 'flipkart';
                                        $brandIcon = 'shopping_cart';
                                    } elseif (str_contains($vTypeLower, 'blinkit')) {
                                        $brandClass = 'blinkit';
                                        $brandIcon = 'bolt';
                                    }
                                @endphp

                                <div class="fsr-egift-card {{ $brandClass }}-theme p-4 rounded-2xl border transition-all duration-200">
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="fsr-egift-icon {{ $brandClass }}-icon">
                                                @if ($voucher->image)
                                                    {{-- <img src="{{ Str::startsWith($voucher->image, ['http://', 'https://']) ? $voucher->image : asset($voucher->image) }}" alt="{{ $voucher->name }}" class="w-full h-full object-cover rounded-xl"> --}}
                                              
                                                    <img src="{{ Storage::disk('s3')->url($voucher->image) }}" alt="{{ $voucher->name }}"  class="w-full h-full object-cover rounded-xl" loading="lazy">
                                                @else
                                                    <span class="material-symbols-outlined text-[22px]">{{ $brandIcon }}</span>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="fsr-brand-badge {{ $brandClass }}-badge">{{ $brandBadgeText }}</span>
                                                    @if ($remainingStock !== null)
                                                        <span class="text-[11px] text-white/50 font-medium">{{ $remainingStock }} left</span>
                                                    @endif
                                                </div>
                                                <h3 class="font-display text-[15px] font-bold text-[#f3edff] mt-0.5 leading-tight">{{ $voucher->name }}</h3>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-display text-lg font-extrabold text-[#ffe9b0] leading-none block">₹{{ number_format($vAmount) }}</span>
                                            <span class="text-[11px] text-white/90 font-bold block mt-1">{{ number_format($totalPoints) }} pts</span>
                                            @if ($feePercent > 0)
                                                <span class="text-[9.5px] text-amber-300/80 font-medium block">+{{ $feePercent }}% processing fee ({{ number_format($feeAmount) }} pts)</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($voucher->description)
                                        <p class="text-[12px] text-white/60 mb-3.5 leading-relaxed">{{ $voucher->description }}</p>
                                    @endif

                                    <div class="pt-2 border-t border-white/10 flex items-center justify-between gap-3">
                                        <div class="text-[11.5px] text-white/50 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[15px] text-[#00E5A0]">verified</span>
                                            <span>Verified Digital E-Gift</span>
                                        </div>

                                        @if (!$inStock)
                                            <button disabled class="px-4 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-white/40 cursor-not-allowed flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">block</span> Out of Stock
                                            </button>
                                        @elseif ($canAfford)
                                            <button onclick="fsrOpenClaimModal({{ $voucher->id }}, '{{ addslashes($voucher->name) }}', {{ $vAmount }}, {{ $feePercent }}, {{ $feeAmount }}, {{ $totalPoints }}, '{{ $brandClass }}')" 
                                                class="fsr-claim-btn px-4 py-2 rounded-xl text-xs font-bold text-[#1a0b3d] flex items-center gap-1.5 shadow-md active:scale-95 transition-transform">
                                                <span>Claim Voucher</span>
                                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                            </button>
                                        @else
                                            @php
                                                $needed = $totalPoints - ($walletBalance ?? 0);
                                            @endphp
                                            <button disabled title="Insufficient points" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-red-500/10 border border-red-500/25 text-red-300 cursor-not-allowed flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">lock</span>
                                                <span>Need {{ number_format($needed) }} pts</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="glass-card rounded-xl p-5 text-center text-white/60 text-[13px] border border-white/10 bg-white/[0.02]">
                            <span class="material-symbols-outlined text-3xl text-white/30 mb-1 block">card_giftcard</span>
                            No e-gift vouchers available at the moment. Please check back later!
                        </div>
                    @endif
               @else
                    <!-- ⏳ Premium Time Restricted Card -->
                    <div class="relative overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5 text-center backdrop-blur-sm group">
                        <!-- Subtle Background Glow Effect -->
                        <div class="absolute -top-10 -right-10 h-24 w-24 rounded-full bg-amber-500/10 blur-2xl transition-all group-hover:bg-amber-500/20"></div>
                        <div class="absolute -bottom-10 -left-10 h-24 w-24 rounded-full bg-amber-500/5 blur-2xl"></div>
                        
                        <div class="relative z-10">
                            <!-- Icon with Background Circle -->
                            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/20 shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                                <span class="material-symbols-outlined text-3xl text-amber-400">schedule</span>
                            </div>
                            
                            <!-- Heading -->
                            <h3 class="font-display text-base font-bold text-amber-200 mb-2">Claiming Currently Closed</h3>
                            
                            <!-- Description -->
                            <p class="text-[13px] leading-relaxed text-white/60">
                                E-Gift voucher claims are only available between
                                <span class="block mt-1 font-semibold text-amber-400 text-[14px]">{{ $startTimeFormatted }} and {{ $endTimeFormatted }}</span>
                            </p>
                            
                            <!-- Footer Note -->
                            <p class="text-[11.5px] text-white/40 mt-3 flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                Please check back during operational hours.
                            </p>
                        </div>
                    </div>
                @endif       
            </div>

            <!-- Section 2: Your Vouchers (My Claimed/Assigned Vouchers) -->
            {{-- <div class="mt-2">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="font-display text-base font-bold text-[#e8ddff] flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[20px] text-[#00E5A0]">receipt_long</span>
                            Your Vouchers
                        </h2>
                        <p class="text-[12px] text-white/50">Track status and details of your claimed gift vouchers</p>
                    </div>
                    <span class="text-[11.5px] text-white/50 font-semibold">
                        {{ count($userVoucherRequests ?? []) + count($vouchers ?? []) }} Total
                    </span>
                </div>
                

            </div> --}}
        </div>

        <!-- ===== MODAL: REDEEM REQUEST ===== -->
        <div class="fsr-modal-overlay" id="fsr-modal-overlay" onclick="if(event.target===this) fsrCloseModal()">
            <div class="fsr-modal-panel">
                <div class="fsr-modal-handle"></div>

                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-display text-lg font-bold text-[#e8ddff]">Redeem Request</h3>
                    <button id="fsr-close-modal" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Redeem Type Picker -->
                <p class="fsr-field-label">Select Redeem Type</p>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="fsr-type-card active" data-type="mobile" onclick="fsrSelectType('mobile')">
                        <div class="fsr-type-icon">
                            <span class="material-symbols-outlined text-[22px]">smartphone</span>
                        </div>
                        <span class="text-[13px] font-semibold text-center text-[#e8ddff]">Mobile Recharge</span>
                    </div>
                    <div class="fsr-type-card" data-type="electricity" onclick="fsrSelectType('electricity')">
                        <div class="fsr-type-icon">
                            <span class="material-symbols-outlined text-[22px] text-[#d8b4fe]">bolt</span>
                        </div>
                        <span class="text-[13px] font-semibold text-center text-[#e8ddff]">{{ __('app.electricity_bill') }}</span>
                    </div>
                </div>

                <!-- Mobile Recharge Fields -->
                <div id="fsr-fields-mobile" class="flex flex-col gap-4 mb-2">
                    <div>
                        <label class="fsr-field-label">{{ __('app.mobile_number') }}</label>
                        <input type="tel" id="mobile_number" maxlength="10" minlength="10" inputmode="numeric" class="fsr-input"
                            placeholder="{{ __('app.enter_mobile') }}"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>

                    <div>
                        <label class="fsr-field-label">Select Operator</label>
                        <div class="fsr-select-wrap">
                            <span class="fsr-select-prefix">
                                <span class="material-symbols-outlined text-[15px] text-[#d8b4fe]">sim_card</span>
                            </span>
                            <select class="fsr-select" id="mobile_operator" name="company">
                                <option value="">Choose operator</option>
                                @foreach ($simNetworks as $net)
                                    <option value="{{ $net->name }}">{{ $net->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="fsr-field-label">Amount </label>
                        {{-- <input type="number" id="mobile_amount" class="fsr-input" placeholder="Enter recharge amount" min="1"> --}}
                         <input type="number" id="mobile_amount" class="fsr-input" placeholder="Enter recharge point (250 - 5000)" min="250" max="5000" step="1">
                        <p class="text-[11px] text-white/40 mt-1">
                            Minimum 250 and maximum 5000
                        </p>
                    </div>
                </div>

                <!-- Electricity Bill Fields -->
                <div id="fsr-fields-electricity" class="hidden flex-col gap-4 mb-2">
                    <div>
                        <label class="fsr-field-label">Select Zone</label>
                        <div class="fsr-select-wrap">
                            <span class="fsr-select-prefix">
                                <span class="material-symbols-outlined text-[15px] text-[#ffd76a]">bolt</span>
                            </span>
                            <select class="fsr-select" id="elec_zone" name="zone_id">
                                <option value="">Choose zone</option>
                                @foreach ($electricityZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->zone }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="fsr-field-label">Consumer Number</label>
                        <input type="text" id="consumer_number" class="fsr-input" placeholder="Enter consumer number">
                    </div>

                    <div>
                        <label class="fsr-field-label">Bill Amount </label>
                        {{-- <input type="number" id="elec_amount" class="fsr-input" placeholder="Enter bill amount" min="1"> --}}
                       <input type="number" id="elec_amount" class="fsr-input" placeholder="Enter bill point (250 - 5000)" min="250" max="5000" step="1">
                        <p class="text-[11px] text-white/40 mt-1">
                            Minimum 250 and maximum 5000
                        </p>
                    
                    </div>
                </div>

                @if (($walletBalance ?? 0) < 250)
                    <div class="mt-4 p-3 rounded-xl bg-red-500/15 border border-red-500/30">
                        <p class="text-[13px] font-semibold text-center text-red-300">
                            Minimum 250 points are required to make a redeem request. Please play more quizzes to earn points!
                        </p>
                    </div>
                    <button disabled class="fsr-redeem-btn w-full mt-3 py-3 rounded-xl font-display font-bold text-[15px] opacity-50 cursor-not-allowed">
                        Submit Request
                    </button>
                @else
                    <button id="fsr-submit-btn" class="fsr-redeem-btn w-full mt-3 py-3 rounded-xl font-display font-bold text-[15px]">
                        Submit Request
                    </button>
                @endif
            </div>
        </div>

        <!-- ===== MODAL: CLAIM E-GIFT VOUCHER ===== -->
        <div class="fsr-modal-overlay" id="fsr-claim-modal-overlay" onclick="if(event.target===this) fsrCloseClaimModal()">
            <div class="fsr-modal-panel">
                <div class="fsr-modal-handle"></div>

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-lg font-bold text-[#e8ddff] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#ffd76a]">redeem</span>
                        Claim E-Gift Voucher
                    </h3>
                    <button onclick="fsrCloseClaimModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span id="claim-modal-brand" class="fsr-brand-badge default-badge text-[11px]">Gift Voucher</span>
                            <h4 id="claim-modal-name" class="font-display text-base font-bold text-white mt-1">Amazon ₹500 Voucher</h4>
                        </div>
                        <div class="text-right">
                            <span id="claim-modal-total-pts" class="font-display text-xl font-extrabold text-[#ffe9b0]">525 pts</span>
                            <span class="text-[11px] text-white/50 block font-semibold">Total Required</span>
                        </div>
                    </div>

                    <!-- Points Summary Breakdown -->
                    <div class="mt-4 pt-3 border-t border-white/10 flex flex-col gap-2 text-[12.5px]">
                        <div class="flex items-center justify-between text-white/70">
                            <span>Voucher Base Value:</span>
                            <span id="claim-modal-base-points" class="font-mono font-semibold text-white">500 pts</span>
                        </div>
                        <div class="flex items-center justify-between text-amber-300/90" id="claim-modal-fee-row">
                            <span>Processing Fee (<span id="claim-modal-fee-percent">0</span>%):</span>
                            <span id="claim-modal-fee-amount" class="font-mono font-semibold">+0 pts</span>
                        </div>
                        <div class="flex items-center justify-between text-white/70">
                            <span>Your Current Balance:</span>
                            <span class="font-mono font-semibold text-white">{{ number_format($walletBalance ?? 0) }} pts</span>
                        </div>
                        <div class="flex items-center justify-between text-red-300 font-medium">
                            <span>Total Points to Deduct:</span>
                            <span id="claim-modal-deduct" class="font-mono font-bold">-0 pts</span>
                        </div>
                        <div class="flex items-center justify-between font-bold pt-2 border-t border-white/10 text-emerald-300">
                            <span>Remaining Balance:</span>
                            <span id="claim-modal-remaining" class="font-mono text-[14px]">0 pts</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 mb-5 flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-purple-300 text-[18px] shrink-0 mt-0.5">info</span>
                    <p class="text-[11.5px] text-purple-200/90 leading-relaxed">
                        Points including processing fee will be deducted from your account. <strong>When approved, voucher details will be shared on your WhatsApp.</strong>
                    </p>
                </div>

                <input type="hidden" id="claim_voucher_id" value="">

                <div class="flex items-center gap-3">
                    <button onclick="fsrCloseClaimModal()" class="w-1/3 py-3 rounded-xl font-display font-semibold text-[14px] bg-white/10 hover:bg-white/15 text-white/80 transition-colors">
                        Cancel
                    </button>
                    <button id="fsr-confirm-claim-btn" onclick="fsrSubmitVoucherClaim()" class="fsr-redeem-btn flex-1 py-3 rounded-xl font-display font-bold text-[15px] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[19px]">check_circle</span>
                        Confirm & Claim
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== MODAL: CLAIM SUCCESS POPUP (NO AUTO CLOSE) ===== -->
        <div class="fsr-modal-overlay" id="fsr-voucher-success-modal" onclick="if(event.target===this) fsrCloseSuccessModalAndGoToVouchers()">
            <div class="fsr-modal-panel text-center">
                <div class="fsr-modal-handle"></div>

                <div class="flex justify-end mb-1">
                    <button onclick="fsrCloseSuccessModalAndGoToVouchers()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-[#00E5A0] flex items-center justify-center mx-auto mb-3 shadow-[0_0_24px_rgba(0,229,160,0.3)]">
                    <span class="material-symbols-outlined text-3xl">celebration</span>
                </div>

                <h3 class="font-display text-lg font-bold text-white mb-2">Request Saved Successfully!</h3>

                <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 mb-4 text-left">
                    <p id="voucher-success-message-text" class="text-[13px] text-[#ffe9b0] font-medium leading-relaxed">
                        Your voucher Request Saved Successfully. When approve Request then share on your whatsapp and check your vouchers request
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 mb-5 flex items-center gap-2.5 text-left">
                    <span class="material-symbols-outlined text-[#00E5A0] text-[20px] shrink-0">mark_chat_read</span>
                    <p class="text-[11.5px] text-emerald-200">
                        Details & code will be shared on your WhatsApp number once approved by admin.
                    </p>
                </div>

                <button onclick="fsrCloseSuccessModalAndGoToVouchers()" class="fsr-redeem-btn w-full py-3.5 rounded-xl font-display font-bold text-[15px] flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-transform">
                    <span class="material-symbols-outlined text-[19px]">receipt_long</span>
                    Ok
                </button>
            </div>
        </div>

        <!-- ===== MODAL: VIEW APPROVED VOUCHER DETAILS ===== -->
        <div class="fsr-modal-overlay" id="fsr-view-voucher-modal" onclick="if(event.target===this) fsrCloseVoucherDetailsModal()">
            <div class="fsr-modal-panel">
                <div class="fsr-modal-handle"></div>

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-lg font-bold text-[#e8ddff] flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#00E5A0]">verified</span>
                        Voucher Details
                    </h3>
                    <button onclick="fsrCloseVoucherDetailsModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Voucher Header Card -->
                <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="fsr-status completed inline-block text-[10.5px]">Approved & Active</span>
                            <h4 id="view-modal-name" class="font-display text-base font-bold text-white mt-1">Amazon ₹500 Voucher</h4>
                        </div>
                        <div class="text-right">
                            <span id="view-modal-amount" class="font-display text-xl font-extrabold text-[#ffe9b0]">₹500</span>
                        </div>
                    </div>

                    <!-- Voucher Code Section (if present) -->
                    <div id="view-modal-code-section" class="mt-4 pt-3 border-t border-white/10 hidden">
                        <label class="text-[11px] font-semibold text-white/60 tracking-wider uppercase block mb-1.5">Voucher / Coupon Code</label>
                        <div class="flex items-center gap-2 p-2.5 rounded-xl bg-[#130928] border border-[#ffd76a]/40">
                            <span id="view-modal-code-text" class="flex-1 font-mono text-[15px] font-extrabold text-[#ffd76a] tracking-widest truncate select-all"></span>
                            <button id="view-modal-copy-btn" onclick="fsrCopyVoucherCode()" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#ffd76a]/20 border border-[#ffd76a]/40 text-[#ffd76a] hover:bg-[#ffd76a]/30 transition-all flex items-center gap-1 shrink-0 active:scale-95">
                                <span class="material-symbols-outlined text-xs">content_copy</span>
                                <span id="view-modal-copy-label">Copy</span>
                            </button>
                        </div>
                    </div>

                    <!-- Voucher URL Section (if present) -->
                    <div id="view-modal-url-section" class="mt-3 hidden">
                        <a id="view-modal-url-link" href="#" target="_blank" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-[#00E5A0]/20 border border-[#00E5A0]/40 text-[#00E5A0] hover:bg-[#00E5A0]/30 transition-all flex items-center justify-center gap-2">
                            <span>Open Voucher Link</span>
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                        </a>
                    </div>
                </div>

                <!-- Voucher Image Section (if present) -->
                <div id="view-modal-image-section" class="mb-4 hidden">
                    <label class="text-[11px] font-semibold text-white/60 tracking-wider uppercase block mb-1.5">Voucher Image / Card</label>
                    <div class="rounded-xl overflow-hidden border border-white/10 bg-black/40 flex items-center justify-center max-h-56 p-2">
                        <img id="view-modal-image-el" src="" alt="Voucher Image" class="max-h-52 w-auto object-contain rounded-lg">
                    </div>
                </div>

                <!-- Remark Section (if present) -->
                <div id="view-modal-remark-section" class="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 mb-4 hidden">
                    <span class="text-[11px] font-semibold text-purple-300 block mb-0.5">Admin Note / Details:</span>
                    <p id="view-modal-remark-text" class="text-[12px] text-purple-200 leading-relaxed"></p>
                </div>

                <div class="flex items-center justify-between text-[11.5px] text-white/40 mb-4">
                    <span>Approved On:</span>
                    <span id="view-modal-date-text">N/A</span>
                </div>

                <button onclick="fsrCloseVoucherDetailsModal()" class="w-full py-3 rounded-xl font-display font-semibold text-[14px] bg-white/10 hover:bg-white/15 text-white/90 transition-colors">
                    Close
                </button>
            </div>
        </div>

    </main>

    @include('fsrapp.layouts.bottommenu')
@endsection

@push('styles')
    <style>
        .fsr-egift-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.015) 100%), #160b30;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        .fsr-egift-card.amazon-theme {
            border-color: rgba(255, 153, 0, 0.25);
            background: radial-gradient(120% 120% at 100% 0%, rgba(255, 153, 0, 0.08) 0%, transparent 60%), #160b30;
        }
        .fsr-egift-card.flipkart-theme {
            border-color: rgba(40, 116, 240, 0.25);
            background: radial-gradient(120% 120% at 100% 0%, rgba(40, 116, 240, 0.1) 0%, transparent 60%), #160b30;
        }
        .fsr-egift-card.blinkit-theme {
            border-color: rgba(244, 191, 50, 0.28);
            background: radial-gradient(120% 120% at 100% 0%, rgba(244, 191, 50, 0.1) 0%, transparent 60%), #160b30;
        }
        .fsr-egift-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .amazon-icon {
            background: linear-gradient(135deg, rgba(255, 153, 0, 0.25), rgba(255, 153, 0, 0.08));
            border-color: rgba(255, 153, 0, 0.35);
            color: #ff9900;
        }
        .flipkart-icon {
            background: linear-gradient(135deg, rgba(40, 116, 240, 0.25), rgba(40, 116, 240, 0.08));
            border-color: rgba(40, 116, 240, 0.35);
            color: #5c9eff;
        }
        .blinkit-icon {
            background: linear-gradient(135deg, rgba(244, 191, 50, 0.25), rgba(244, 191, 50, 0.08));
            border-color: rgba(244, 191, 50, 0.35);
            color: #ffd76a;
        }
        .default-icon {
            background: linear-gradient(135deg, rgba(217, 70, 239, 0.25), rgba(217, 70, 239, 0.08));
            border-color: rgba(217, 70, 239, 0.35);
            color: #f0abfc;
        }
        .fsr-brand-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            letter-spacing: .3px;
            display: inline-block;
        }
        .amazon-badge {
            background: rgba(255, 153, 0, 0.15);
            color: #ff9900;
            border: 1px solid rgba(255, 153, 0, 0.35);
        }
        .flipkart-badge {
            background: rgba(40, 116, 240, 0.15);
            color: #5c9eff;
            border: 1px solid rgba(40, 116, 240, 0.35);
        }
        .blinkit-badge {
            background: rgba(244, 191, 50, 0.15);
            color: #ffd76a;
            border: 1px solid rgba(244, 191, 50, 0.35);
        }
        .default-badge {
            background: rgba(217, 70, 239, 0.15);
            color: #f0abfc;
            border: 1px solid rgba(217, 70, 239, 0.35);
        }
        .fsr-claim-btn {
            background: linear-gradient(135deg, #f4bf32 0%, #d946ef 100%);
            box-shadow: 0 4px 14px rgba(217, 70, 239, 0.25);
        }
        .fsr-claim-btn:active {
            transform: scale(0.96);
        }
    </style>
@endpush

@push('scripts')
    <script>
        const userWalletBalance = {{ (float) ($walletBalance ?? 0) }};

        // Modal Open / Close (Redeem Request)
        function fsrOpenModal() {
            document.getElementById('fsr-modal-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function fsrCloseModal() {
            document.getElementById('fsr-modal-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('fsr-open-modal')?.addEventListener('click', fsrOpenModal);
        document.getElementById('fsr-close-modal')?.addEventListener('click', fsrCloseModal);

        // Success Popup Modal (No auto close, stays until user explicitly closes it)
        function fsrShowSuccessModal(msg) {
            if (msg) {
                document.getElementById('voucher-success-message-text').innerText = msg;
            }
            document.getElementById('fsr-voucher-success-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function fsrCloseSuccessModalAndGoToVouchers() {
            document.getElementById('fsr-voucher-success-modal').classList.remove('open');
            document.body.style.overflow = '';
            // Reload and switch to vouchers tab
            window.location.href = window.location.pathname + '?tab=vouchers';
        }

        // View Approved Voucher Details Modal
        let activeVoucherCodeToCopy = '';

        function fsrOpenVoucherDetailsModal(voucher) {
            if (!voucher) return;

            document.getElementById('view-modal-name').innerText = voucher.name || 'E-Gift Voucher';
            document.getElementById('view-modal-amount').innerText = '₹' + Number(voucher.amount || 0).toLocaleString();
            document.getElementById('view-modal-date-text').innerText = voucher.date || 'N/A';

            // Voucher Code
            const codeSection = document.getElementById('view-modal-code-section');
            const codeText = document.getElementById('view-modal-code-text');
            if (voucher.voucher_code) {
                activeVoucherCodeToCopy = voucher.voucher_code;
                codeText.innerText = voucher.voucher_code;
                codeSection.classList.remove('hidden');
                document.getElementById('view-modal-copy-label').innerText = 'Copy';
            } else {
                activeVoucherCodeToCopy = '';
                codeSection.classList.add('hidden');
            }

            // Voucher URL
            const urlSection = document.getElementById('view-modal-url-section');
            const urlLink = document.getElementById('view-modal-url-link');
            if (voucher.url) {
                urlLink.href = voucher.url;
                urlSection.classList.remove('hidden');
            } else {
                urlSection.classList.add('hidden');
            }

            // Voucher Image
            const imgSection = document.getElementById('view-modal-image-section');
            const imgEl = document.getElementById('view-modal-image-el');
            if (voucher.image) {
                imgEl.src = voucher.image;
                imgSection.classList.remove('hidden');
            } else {
                imgSection.classList.add('hidden');
            }

            // Remark
            const remarkSection = document.getElementById('view-modal-remark-section');
            const remarkText = document.getElementById('view-modal-remark-text');
            if (voucher.remark) {
                remarkText.innerText = voucher.remark;
                remarkSection.classList.remove('hidden');
            } else {
                remarkSection.classList.add('hidden');
            }

            document.getElementById('fsr-view-voucher-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function fsrCloseVoucherDetailsModal() {
            document.getElementById('fsr-view-voucher-modal').classList.remove('open');
            document.body.style.overflow = '';
        }

        function fsrCopyVoucherCode() {
            if (!activeVoucherCodeToCopy) return;
            navigator.clipboard.writeText(activeVoucherCodeToCopy).then(() => {
                document.getElementById('view-modal-copy-label').innerText = 'Copied!';
                showToast('Voucher code copied to clipboard!', 'success');
                setTimeout(() => {
                    const label = document.getElementById('view-modal-copy-label');
                    if (label) label.innerText = 'Copy';
                }, 2500);
            }).catch(() => {
                showToast('Failed to copy code. Please copy manually.', 'error');
            });
        }

        // Claim Voucher Modal
        function fsrOpenClaimModal(id, name, baseAmount, feePercent, feeAmount, totalAmount, brand) {
            document.getElementById('claim_voucher_id').value = id;
            document.getElementById('claim-modal-name').innerText = name;
            
            document.getElementById('claim-modal-total-pts').innerText = Number(totalAmount).toLocaleString() + ' pts';
            document.getElementById('claim-modal-base-points').innerText = Number(baseAmount).toLocaleString() + ' pts (₹' + Number(baseAmount).toLocaleString() + ')';
            
            const feeRow = document.getElementById('claim-modal-fee-row');
            if (feePercent > 0) {
                document.getElementById('claim-modal-fee-percent').innerText = feePercent;
                document.getElementById('claim-modal-fee-amount').innerText = '+' + Number(feeAmount).toLocaleString() + ' pts';
                feeRow.classList.remove('hidden');
                feeRow.classList.add('flex');
            } else {
                document.getElementById('claim-modal-fee-percent').innerText = '0';
                document.getElementById('claim-modal-fee-amount').innerText = '0 pts';
                feeRow.classList.add('hidden');
                feeRow.classList.remove('flex');
            }

            document.getElementById('claim-modal-deduct').innerText = '-' + Number(totalAmount).toLocaleString() + ' pts';
            
            const remaining = Math.max(0, userWalletBalance - totalAmount);
            document.getElementById('claim-modal-remaining').innerText = Number(remaining).toLocaleString() + ' pts';
            
            const brandBadge = document.getElementById('claim-modal-brand');
            brandBadge.className = `fsr-brand-badge ${brand}-badge text-[11px]`;
            brandBadge.innerText = brand.charAt(0).toUpperCase() + brand.slice(1);

            // Validation button state
            const claimBtn = document.getElementById('fsr-confirm-claim-btn');
            if (userWalletBalance < totalAmount) {
                claimBtn.disabled = true;
                claimBtn.classList.add('opacity-50', 'cursor-not-allowed');
                claimBtn.innerText = 'Insufficient Points';
            } else {
                claimBtn.disabled = false;
                claimBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                claimBtn.innerHTML = '<span class="material-symbols-outlined text-[19px]">check_circle</span> Confirm & Claim';
            }

            document.getElementById('fsr-claim-modal-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function fsrCloseClaimModal() {
            document.getElementById('fsr-claim-modal-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        // Submit Voucher Claim AJAX
        function fsrSubmitVoucherClaim() {
            const voucherId = document.getElementById('claim_voucher_id').value;
            if (!voucherId) return;

            const btn = document.getElementById('fsr-confirm-claim-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-[19px] animate-spin">progress_activity</span> Processing...';

            fetch('{{ route('fsrapp.vouchers.request') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ voucher_id: voucherId })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (body.success) {
                    fsrCloseClaimModal();
                    // Open the persistent success modal (won't auto-close)
                    fsrShowSuccessModal(body.message);
                } else {
                    showToast(body.message || 'Failed to claim voucher.', 'error');
                }
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[19px]">check_circle</span> Confirm & Claim';
            })
            .catch(err => {
                showToast('Network error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined text-[19px]">check_circle</span> Confirm & Claim';
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fsrCloseModal();
                fsrCloseClaimModal();
                fsrCloseVoucherDetailsModal();
                // Notice: We don't auto-dismiss success modal on escape if user hasn't clicked OK
            }
        });

        // Tab Switcher
        function fsrSwitchTab(tab) {
            document.querySelectorAll('.fsr-tab').forEach(function(t) {
                t.classList.toggle('active', t.dataset.tab === tab);
            });
            document.getElementById('fsr-panel-history').classList.toggle('active', tab === 'history');
            document.getElementById('fsr-panel-vouchers').classList.toggle('active', tab === 'vouchers');
        }

        // Auto activate tab if URL query ?tab=vouchers is present
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab === 'vouchers') {
                fsrSwitchTab('vouchers');
            }
        });

        // Type Picker for Mobile / Electricity
        let currentType = 'mobile';

        function fsrSelectType(type) {
            currentType = type;
            document.querySelectorAll('.fsr-type-card').forEach(function(c) {
                c.classList.toggle('active', c.dataset.type === type);
            });
            const mobileEl = document.getElementById('fsr-fields-mobile');
            const elecEl = document.getElementById('fsr-fields-electricity');

            mobileEl.classList.toggle('hidden', type !== 'mobile');
            mobileEl.classList.toggle('flex', type === 'mobile');

            elecEl.classList.toggle('hidden', type !== 'electricity');
            elecEl.classList.toggle('flex', type === 'electricity');
        }

        // AJAX Form Submit with Strict Validation
        const submitBtn = document.getElementById('fsr-submit-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const data = {
                    _token: '{{ csrf_token() }}',
                    type: currentType
                };

                if (currentType === 'mobile') {
                    const mobile = document.getElementById('mobile_number').value.trim();
                    const operator = document.getElementById('mobile_operator').value;
                    const amount = document.getElementById('mobile_amount').value.trim();

                    if (!mobile || !/^[0-9]{10}$/.test(mobile)) {
                        showToast('Please enter a valid 10-digit mobile number.', 'error');
                        return;
                    }
                    if (!operator) {
                        showToast('Please select a SIM operator.', 'error');
                        return;
                    }

                    if (!amount || parseInt(amount) < 250 || parseInt(amount) > 5000) {
                        showToast('Please enter a valid recharge point must be between 250 and 5000.', 'error');
                        return;
                    }

                    data.mobile = mobile;
                    data.company = operator;
                    data.amount = amount;
                } else {
                    const zoneId = document.getElementById('elec_zone').value;
                    const consumerNum = document.getElementById('consumer_number').value.trim();
                    const amount = document.getElementById('elec_amount').value.trim();

                    if (!zoneId) {
                        showToast('Please select an electricity zone.', 'error');
                        return;
                    }
                    if (!consumerNum) {
                        showToast('Please enter your consumer number.', 'error');
                        return;
                    }

                    if (!amount || parseInt(amount) < 250 || parseInt(amount) > 5000) {
                        showToast('Please enter a valid bill point must be between 250 and 5000.', 'error');
                        return;
                    }

                    data.zone_id = zoneId;
                    data.consumer_number = consumerNum;
                    data.amount = amount;
                }

                submitBtn.disabled = true;
                submitBtn.innerText = 'Submitting...';

                fetch('{{ route('fsrapp.redeem.submit') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            showToast(res.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1200);
                        } else {
                            showToast(res.message || 'Failed to submit request.', 'error');
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Submit Request';
                        }
                    })
                    .catch(() => {
                        showToast('Something went wrong. Please try again.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Submit Request';
                    });
            });
        }

        // Toast Notification System
        function showToast(msg, type = 'info') {
            const box = document.getElementById('toast');
            if (!box) return;
            const colors = {
                info: 'bg-[#160d2f] border-white/10 text-[#e8ddff]',
                error: 'bg-red-950/80 border-red-500/30 text-red-200',
                success: 'bg-[#00E5A0]/20 border-[#00E5A0]/50 text-[#00E5A0]'
            };
            box.className =
                `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold ${colors[type] || colors.info}`;
            box.textContent = msg;
            box.classList.remove('hidden');
            setTimeout(() => box.classList.add('hidden'), 3200);
        }
    </script>
@endpush

