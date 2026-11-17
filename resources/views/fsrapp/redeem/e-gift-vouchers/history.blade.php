@extends('fsrapp.layouts.app')
@section('content')
     <!-- ✅ Updated Header with Back to Redeem & Correct Title -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2 bg-[#0F172A]/90 backdrop-blur-md border-b border-white/5">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('fsrapp.redeem.index') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 transition-colors"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255, 255, 255, 0.1);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-display text-lg font-extrabold tracking-tight leading-none" style="color:#e8ddff;">
                        E-Gift Voucher History
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">Track status of your claimed vouchers</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto pt-20 pb-24 px-4 min-h-screen bg-[#0F172A]">
        
    

        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-display text-xl font-bold text-[#e8ddff] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#00E5A0]">receipt_long</span>
                    Your Voucher History
                </h2>
                <p class="text-[12px] text-white/50 mt-1">Track status and details of your claimed gift vouchers</p>
            </div>
            <span class="text-[11.5px] text-white/50 font-semibold bg-white/5 px-3 py-1 rounded-full border border-white/10">
                {{ $totalCount ?? (count($userVoucherRequests ?? []) + count($vouchers ?? [])) }} Total
            </span>
        </div>

        @php
            $pendingRequests = $pendingRequests ?? collect($userVoucherRequests ?? [])->where('status', 'pending');
            $approvedRequests = $approvedRequests ?? collect($userVoucherRequests ?? [])->where('status', 'approved')->sortByDesc('approve_date');
            $cancelledRequests = $cancelledRequests ?? collect($userVoucherRequests ?? [])->whereIn('status', ['rejected', 'cancelled']);
        @endphp

        <!-- Tabs Navigation -->
        <div class="flex border-b border-white/10 mb-4 sticky top-20 bg-[#0F172A] z-10 pt-2">
            <button onclick="switchVoucherTab('pending')" id="v-tab-pending" class="flex-1 py-3 text-sm font-semibold text-[#ffd76a] border-b-2 border-[#ffd76a] transition-colors">
                Pending <span class="ml-1 text-[10px] bg-white/10 px-1.5 py-0.5 rounded-full">{{ $pendingRequests->count() }}</span>
            </button>
            <button onclick="switchVoucherTab('approved')" id="v-tab-approved" class="flex-1 py-3 text-sm font-semibold text-white/50 border-b-2 border-transparent transition-colors">
                Approved <span class="ml-1 text-[10px] bg-white/10 px-1.5 py-0.5 rounded-full">{{ $approvedRequests->count() }}</span>
            </button>
            <button onclick="switchVoucherTab('cancelled')" id="v-tab-cancelled" class="flex-1 py-3 text-sm font-semibold text-white/50 border-b-2 border-transparent transition-colors">
                Cancelled <span class="ml-1 text-[10px] bg-white/10 px-1.5 py-0.5 rounded-full">{{ $cancelledRequests->count() }}</span>
            </button>
        </div>

        <!-- ===== 1. Pending Panel ===== -->
        <div id="v-panel-pending" class="v-panel flex flex-col gap-3">
            @forelse ($pendingRequests as $req)
                @php
                    $vName = $req->voucher?->name ?? 'E-Gift Voucher';
                    $vAmount = (float) ($req->voucher_amount ?: ($req->voucher?->amount ?? 0));
                    $reqFeePercent = (float) ($req->fee_percent ?? 0);
                    $reqTotal = (float) ($req->total_amount ?: ($vAmount + ($req->fee_amount ?? 0)));
                @endphp
                <div class="fsr-voucher-card p-3.5 rounded-xl border border-white/10 bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
                                {{-- <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">card_giftcard</span> --}}
                                @if($req->voucher?->image)
                                    <img src="{{ Storage::disk('s3')->url($req->voucher->image) }}"
                                        alt="{{ $vName }}"
                                        class="w-full h-full object-contain rounded-xl">
                                @else
                                    <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">
                                        card_giftcard
                                    </span>
                                @endif

                            </div>
                            <div class="min-w-0">
                                <p class="text-[13.5px] font-bold text-[#e8ddff] truncate">{{ $vName }}</p>
                                <p class="text-[11.5px] text-white/50 mt-0.5">Requested on {{ $req->created_at ? $req->created_at->format('d M Y, h:i A') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="fsr-status pending inline-block">Pending Approval</span>
                            <span class="text-[12px] font-mono font-bold text-[#ffe9b0] block mt-1">-{{ number_format($reqTotal) }} pts</span>
                            @if ($reqFeePercent > 0) <span class="text-[9.5px] text-white/40 block">incl. {{ $reqFeePercent }}% fee</span> @endif
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-amber-300/80 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">info</span>
                        <span>Pending Approval. When approved, details will be shared on your WhatsApp.</span>
                    </div>
                </div>
            @empty
                <div class="glass-card rounded-xl p-4 text-center text-white/50 text-[13px] border border-white/10 bg-white/[0.02]">No pending requests.</div>
            @endforelse
        </div>

        <!-- ===== 2. Approved Panel ===== -->
        <div id="v-panel-approved" class="v-panel hidden flex flex-col gap-3">
            @forelse ($approvedRequests as $req)
                @php
                    $vName = $req->voucher?->name ?? 'E-Gift Voucher';
                    $vAmount = (float) ($req->voucher_amount ?: ($req->voucher?->amount ?? 0));
                    $reqFeePercent = (float) ($req->fee_percent ?? 0);
                    $reqTotal = (float) ($req->total_amount ?: ($vAmount + ($req->fee_amount ?? 0)));
                    
                    $voucherDetails = [
                        'id' => $req->id, 'name' => $vName, 'amount' => $vAmount,
                        'voucher_code' => $req->voucher_code, 'url' => $req->url,
                        'image' => $req->image ? (\Illuminate\Support\Str::startsWith($req->image, ['http://', 'https://']) ? $req->image : asset($req->image)) : null,
                        'remark' => $req->remark,
                        'date' => $req->approve_date ? $req->approve_date->format('d M Y, h:i A') : ($req->updated_at ? $req->updated_at->format('d M Y, h:i A') : ''),
                    ];
                @endphp
                <div class="fsr-voucher-card p-3.5 rounded-xl border border-white/10 bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
                                {{-- <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">card_giftcard</span> --}}

                                   @if($req->voucher?->image)
                                        <img src="{{ Storage::disk('s3')->url($req->voucher->image) }}"
                                            alt="{{ $vName }}"
                                            class="w-full h-full object-contain rounded-xl">
                                    @else
                                        <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">
                                            card_giftcard
                                        </span>
                                    @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13.5px] font-bold text-[#e8ddff] truncate">{{ $vName }}</p>
                                <p class="text-[11.5px] text-white/50 mt-0.5">Requested on {{ $req->created_at ? $req->created_at->format('d M Y, h:i A') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="fsr-status completed inline-block">Approved</span>
                            <span class="text-[12px] font-mono font-bold text-[#ffe9b0] block mt-1">-{{ number_format($reqTotal) }} pts</span>
                            @if ($reqFeePercent > 0) <span class="text-[9.5px] text-white/40 block">incl. {{ $reqFeePercent }}% fee</span> @endif
                        </div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-white/10 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[11.5px] text-emerald-300 font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">verified</span> Approved & Ready
                        </span>
                        <button type="button" onclick='fsrOpenVoucherDetailsModal(@json($voucherDetails))'
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#00E5A0]/20 border border-[#00E5A0]/50 text-[#00E5A0] hover:bg-[#00E5A0]/30 transition-all flex items-center gap-1.5 shadow-sm active:scale-95">
                            <span class="material-symbols-outlined text-sm">visibility</span> <span>View</span>
                        </button>
                    </div>
                    @if ($req->voucher_code)
                        <div class="mt-2 flex items-center justify-between p-2 rounded-lg bg-white/[0.04] border border-white/10">
                            <span class="text-[11px] text-white/60">Voucher Code:</span>
                            <span class="font-mono text-xs font-bold text-[#ffd76a]">{{ $req->voucher_code }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="glass-card rounded-xl p-4 text-center text-white/50 text-[13px] border border-white/10 bg-white/[0.02]">No approved vouchers yet.</div>
            @endforelse
        </div>

        <!-- ===== 3. Cancelled Panel ===== -->
        <div id="v-panel-cancelled" class="v-panel hidden flex flex-col gap-3">
            @forelse ($cancelledRequests as $req)
                @php
                    $vName = $req->voucher?->name ?? 'E-Gift Voucher';
                    $vAmount = (float) ($req->voucher_amount ?: ($req->voucher?->amount ?? 0));
                    $reqFeePercent = (float) ($req->fee_percent ?? 0);
                    $reqTotal = (float) ($req->total_amount ?: ($vAmount + ($req->fee_amount ?? 0)));
                @endphp
                <div class="fsr-voucher-card p-3.5 rounded-xl border border-white/10 bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
                                {{-- <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">card_giftcard</span> --}}
                           
                                @if($req->voucher?->image)
                                    <img src="{{ Storage::disk('s3')->url($req->voucher->image) }}"
                                        alt="{{ $vName }}"
                                        class="w-full h-full object-contain rounded-xl">
                                @else
                                    <span class="material-symbols-outlined text-[20px] text-[#ffd76a]">
                                        card_giftcard
                                    </span>
                                @endif
                           
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13.5px] font-bold text-[#e8ddff] truncate">{{ $vName }}</p>
                                <p class="text-[11.5px] text-white/50 mt-0.5">Requested on {{ $req->created_at ? $req->created_at->format('d M Y, h:i A') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="fsr-status failed inline-block">{{ ucfirst($req->status) }}</span>
                            <span class="text-[12px] font-mono font-bold text-[#ffe9b0] block mt-1">-{{ number_format($reqTotal) }} pts</span>
                            @if ($reqFeePercent > 0) <span class="text-[9.5px] text-white/40 block">incl. {{ $reqFeePercent }}% fee</span> @endif
                        </div>
                    </div>
                    @if ($req->remark)
                        <div class="mt-2 p-2 rounded-lg bg-red-500/10 border border-red-500/20 text-[11.5px] text-red-200">
                            <span class="font-semibold text-red-300">Reason:</span> {{ $req->remark }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="glass-card rounded-xl p-4 text-center text-white/50 text-[13px] border border-white/10 bg-white/[0.02]">No cancelled requests.</div>
            @endforelse
        </div>

        <!-- ===== Legacy Assigned Vouchers (Bottom Section) ===== -->
        @if (isset($vouchers) && count($vouchers) > 0)
            <div class="mt-6 pt-4 border-t border-white/10">
                <h3 class="font-display text-sm font-bold text-white/70 mb-3">Legacy Assigned Vouchers</h3>
                <div class="flex flex-col gap-3">
                    @foreach ($vouchers as $v)
                        @php
                            $vStatus = match (strtolower($v->status)) {
                                'active', 'assigned' => 'active',
                                'used' => 'used',
                                'expired' => 'expired',
                                default => 'active',
                            };
                            $vName = $v->voucher?->name ?? 'Assigned Voucher';
                        @endphp
                        <div class="fsr-voucher-item p-3.5 rounded-xl border border-white/10 bg-white/[0.03] flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[19px] text-[#ffd76a]">local_offer</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[13.5px] font-semibold truncate text-[#e8ddff]">{{ $vName }}</p>
                                    <span class="fsr-status fsr-voucher-status {{ $vStatus }}">{{ ucfirst($vStatus) }}</span>
                                </div>
                                <span class="fsr-voucher-code mt-1 text-[12px] text-white/70 font-mono">{{ $v->code }}</span>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="text-[11.5px] text-white/40">
                                        @if ($vStatus == 'used' && $v->used_date)
                                            Redeemed {{ \Carbon\Carbon::parse($v->used_date)->format('d M Y') }}
                                        @elseif ($vStatus == 'expired' && $v->expire_date)
                                            Expired {{ \Carbon\Carbon::parse($v->expire_date)->format('d M Y') }}
                                        @else
                                            {{ $v->expire_date ? 'Expires ' . \Carbon\Carbon::parse($v->expire_date)->format('d M Y') : '' }}
                                        @endif
                                    </span>
                                    <span class="text-[12.5px] font-mono font-semibold text-[#ffe9b0]">{{ $v->voucher_point ? '₹' . $v->voucher_point : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </main>

    <!-- ===== MODAL: VIEW APPROVED VOUCHER DETAILS ===== -->
    <div class="fixed inset-0 z-[100] hidden bg-black/80 backdrop-blur-sm" id="fsr-view-voucher-modal" onclick="if(event.target===this) fsrCloseVoucherDetailsModal()">
        <!-- Mobile: Bottom sheet | Desktop: Centered modal -->
        <div class="absolute bottom-0 left-0 right-0 md:left-1/2 md:top-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:bottom-auto bg-[#1a0b2e] md:rounded-2xl rounded-t-3xl p-6 max-h-[90vh] md:max-h-[85vh] overflow-y-auto animate-slide-up w-full md:max-w-md md:w-full shadow-2xl">
            <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-6 md:hidden"></div>
            
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display text-lg font-bold text-[#e8ddff] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#00E5A0]">verified</span> Voucher Details
                </h3>
                <button onclick="fsrCloseVoucherDetailsModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-white/10 text-white/70 hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
            
            <div class="p-4 rounded-2xl bg-white/[0.04] border border-white/10 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="fsr-status completed inline-block text-[10.5px]">Approved & Active</span>
                        <h4 id="view-modal-name" class="font-display text-base font-bold text-white mt-1">Voucher</h4>
                    </div>
                    <div class="text-right">
                        <span id="view-modal-amount" class="font-display text-xl font-extrabold text-[#ffe9b0]">₹0</span>
                    </div>
                </div>
                
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
                
                <div id="view-modal-url-section" class="mt-3 hidden">
                    <a id="view-modal-url-link" href="#" target="_blank" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-[#00E5A0]/20 border border-[#00E5A0]/40 text-[#00E5A0] hover:bg-[#00E5A0]/30 transition-all flex items-center justify-center gap-2">
                        <span>Open Voucher Link</span>
                        <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>
                </div>
                
                <div id="view-modal-image-section" class="mb-4 hidden">
                    <label class="text-[11px] font-semibold text-white/60 tracking-wider uppercase block mb-1.5">Voucher Image / Card</label>
                    <div class="rounded-xl overflow-hidden border border-white/10 bg-black/40 flex items-center justify-center max-h-56 p-2">
                        <img id="view-modal-image-el" src="" alt="Voucher Image" class="max-h-52 w-auto object-contain rounded-lg">
                    </div>
                </div>
                
                <div id="view-modal-remark-section" class="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 mb-4 hidden">
                    <span class="text-[11px] font-semibold text-purple-300 block mb-0.5">Admin Note / Details:</span>
                    <p id="view-modal-remark-text" class="text-[12px] text-purple-200 leading-relaxed"></p>
                </div>
                
                <div class="flex items-center justify-between text-[11.5px] text-white/40 mb-4">
                    <span>Approved On:</span>
                    <span id="view-modal-date-text">N/A</span>
                </div>
            </div>
            
            <button onclick="fsrCloseVoucherDetailsModal()" class="w-full py-3 rounded-xl font-display font-semibold text-[14px] bg-white/10 hover:bg-white/15 text-white/90 transition-colors">
                Close
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        let activeVoucherCodeToCopy = '';

        function fsrOpenVoucherDetailsModal(voucher) {
            if (!voucher) return;
            document.getElementById('view-modal-name').innerText = voucher.name || 'E-Gift Voucher';
            document.getElementById('view-modal-amount').innerText = '₹' + Number(voucher.amount || 0).toLocaleString();
            document.getElementById('view-modal-date-text').innerText = voucher.date || 'N/A';

            const codeSection = document.getElementById('view-modal-code-section');
            if (voucher.voucher_code) {
                activeVoucherCodeToCopy = voucher.voucher_code;
                document.getElementById('view-modal-code-text').innerText = voucher.voucher_code;
                codeSection.classList.remove('hidden');
                document.getElementById('view-modal-copy-label').innerText = 'Copy';
            } else {
                activeVoucherCodeToCopy = '';
                codeSection.classList.add('hidden');
            }

            const urlSection = document.getElementById('view-modal-url-section');
            if (voucher.url) {
                document.getElementById('view-modal-url-link').href = voucher.url;
                urlSection.classList.remove('hidden');
            } else { urlSection.classList.add('hidden'); }

            const imgSection = document.getElementById('view-modal-image-section');
            if (voucher.image) {
                document.getElementById('view-modal-image-el').src = voucher.image;
                imgSection.classList.remove('hidden');
            } else { imgSection.classList.add('hidden'); }

            const remarkSection = document.getElementById('view-modal-remark-section');
            if (voucher.remark) {
                document.getElementById('view-modal-remark-text').innerText = voucher.remark;
                remarkSection.classList.remove('hidden');
            } else { remarkSection.classList.add('hidden'); }

            document.getElementById('fsr-view-voucher-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function fsrCloseVoucherDetailsModal() {
            document.getElementById('fsr-view-voucher-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function fsrCopyVoucherCode() {
            if (!activeVoucherCodeToCopy) return;
            navigator.clipboard.writeText(activeVoucherCodeToCopy).then(() => {
                document.getElementById('view-modal-copy-label').innerText = 'Copied!';
                setTimeout(() => { document.getElementById('view-modal-copy-label').innerText = 'Copy'; }, 2500);
            });
        }

        // Tab Switching JavaScript (Aapka exact code)
        function switchVoucherTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.v-panel').forEach(p => p.classList.add('hidden'));
            // Show selected panel
            document.getElementById('v-panel-' + tabName).classList.remove('hidden');
            
            // Reset all tabs styling
            ['pending', 'approved', 'cancelled'].forEach(t => {
                const btn = document.getElementById('v-tab-' + t);
                btn.classList.remove('text-[#ffd76a]', 'border-[#ffd76a]');
                btn.classList.add('text-white/50', 'border-transparent');
            });
            
            // Activate selected tab styling
            const activeBtn = document.getElementById('v-tab-' + tabName);
            activeBtn.classList.remove('text-white/50', 'border-transparent');
            activeBtn.classList.add('text-[#ffd76a]', 'border-[#ffd76a]');
        }
        
        // Initialize first tab as active on page load
        document.addEventListener('DOMContentLoaded', () => {
            switchVoucherTab('pending');
        });
    </script>
    @endpush
@endsection