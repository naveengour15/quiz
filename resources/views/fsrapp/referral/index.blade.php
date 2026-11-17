@extends('fsrapp.layouts.app')
@section('content')
    <!-- ===== Top Navigation Header ===== -->
    <header
        class="fixed top-0 w-full z-40 bg-[#0d062030] backdrop-blur-xl border-b border-white/10 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2 flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('fsrapp.home') }}"
                class="w-9 h-9 rounded-full flex items-center justify-center bg-white/5 border border-white/10 hover:bg-white/10 text-[#e8ddff] transition-colors active:scale-90">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <h1 class="font-display text-lg font-bold text-[#e8ddff]">{{ __('app.refer_earn') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('fsrapp.wallet.index') }}"
                class="fsr-wallet-pill flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-[#ffe9b0]">
                <span class="material-symbols-outlined text-[16px] text-[#f4bf32]">account_balance_wallet</span>
                <span class="font-mono text-xs font-semibold" id="fsr-wallet-points">Pts
                    {{ Auth::check() ? number_format(Auth::user()->wallet_balance) : '0' }}</span>
            </a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-28 min-h-screen relative z-10">

        <!-- ===== Toast Notification ===== -->
        <div style="z-index: 99999;     
    position: absolute;
    top: 68px;
    width: 100%;
    text-align: center;"
            id="toast"
            class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] hidden max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold">
        </div>

        <!-- ===== Hero Referral Card ===== -->
        <div
            class="relative overflow-hidden rounded-3xl border border-white/10 p-6 mb-5 text-center shadow-2xl bg-gradient-to-br from-[#7c3aed]/25 via-[#160d2f] to-[#160d2f]">
            <!-- Ambient Glow Orbs -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#7c3aed]/25 rounded-full blur-2xl pointer-events-none">
            </div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-[#ffd76a]/15 rounded-full blur-2xl pointer-events-none">
            </div>

            <!-- Icon Badge -->
            <div
                class="relative z-10 w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-3 shadow-[0_0_30px_rgba(244,191,80,0.35)] bg-gradient-to-br from-[#ffd76a] to-[#d97706]">
                <span class="material-symbols-outlined text-[#160d2f] text-3xl"
                    style="font-variation-settings: 'FILL' 1;">card_giftcard</span>
            </div>

            <h3 class="relative z-10 font-display text-xl font-extrabold text-[#ffe9b0] mb-1">{{ __('app.invite_friends_earn_points') }}
            </h3>
            <p class="relative z-10 text-xs text-white/60 mb-5 leading-relaxed max-w-[280px] mx-auto">{{ __('app.invite_friends_earn_points_desc') }}</p>

            <!-- Referral Code Box -->
            <div style="    display: flex;
    gap: 12px;"
                class="relative z-10 flex items-center justify-between p-2.5 pl-4 rounded-2xl border border-amber-500/30 bg-black/40 shadow-inner"
                id="referral-code-box">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-white/40 tracking-wider uppercase">{{ strtoupper(__('app.your_code')) }}:</span>
                    <span class="font-mono text-lg font-bold text-[#ffd76a] tracking-widest"
                        id="referral-code">{{ $referralCode ?? '—' }}</span>
                </div>
                <div style="    display: flex;
    gap: 10px;
    align-items: center;">
                    <button id="copy-referral-btn" type="button" onclick="copyReferralCode()"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-black transition-all shadow-md active:scale-95 flex items-center gap-1.5 cursor-pointer bg-gradient-to-r from-[#f4bf32] to-[#ffd76a]">
                        <span class="material-symbols-outlined text-[15px]">content_copy</span>
                        {{-- <span>{{ __('app.copy') }}</span> --}}
                    </button>
                    <!-- Perfectly Rounded WhatsApp Button -->
                    <span onclick="openWhatsAppModal('{{ $user->referral_code }}', '{{ $user->mobile }}')"
                        class="bg-[#25D366] hover:bg-[#1ebe5d] text-white w-7 h-7 rounded-full transition-all active:scale-95 flex items-center justify-center mx-auto cursor-pointer shadow-md shadow-green-500/20">

                        <!-- Simple WhatsApp Icon (Thoda bada kiya taaki circle mein achha dikhe) -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== My Referrals List Section ===== -->
        <div class="mb-4 flex items-center justify-between">
            <h4 class="font-display text-sm font-bold text-[#e8ddff] flex items-center gap-2">
                <span class="material-symbols-outlined text-[#00E5A0] text-[18px]">group</span>
                <span>{{ __('app.my_referrals') }}</span>
            </h4>
            <span class="text-xs text-white/40 font-semibold">{{ count($referrals ?? []) }} {{ __('app.total') }}</span>
        </div>

        <div class="space-y-3" id="referral-list">
            @forelse ($referrals as $ref)
                @php
                    $referredUser = $ref;
                    $name = $referredUser->name ?? ($referredUser->player_name ?? 'Friend');
                    $reward = 10; // Default reward or dynamic if needed
                    $status = __('app.joined');
                    $date = $referredUser->created_at ? $referredUser->created_at->format('d M Y') : '';
                @endphp
                <div class="glass-card rounded-2xl p-3.5 flex items-center gap-3 border border-white/10 bg-white/[0.02]">
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#7c3aed]/30 to-[#d946ef]/30 border border-[#e879f9]/30 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[#e879f9] text-[20px]">person</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-display text-xs font-bold text-[#e8ddff] truncate">{{ $name }}</p>
                        <p class="text-[11px] text-white/40 mt-0.5">{{ $status }} {{ $date ? '• ' . $date : '' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="font-mono text-xs font-bold text-[#00E5A0]">+{{ $reward }} Pts</span>
                    </div>
                </div>
            @empty
                <div class="glass-card rounded-2xl p-6 text-center border border-white/5 bg-white/[0.01]">
                    <span class="material-symbols-outlined text-white/20 text-3xl mb-1">group_off</span>
                    <p class="text-xs text-white/50">{{ __('app.share_code_start') }}</p>
                </div>
            @endforelse
        </div>

    </main>

    @include('fsrapp.layouts.bottommenu')
    @include('fsrapp.profile.referral-msg.msg') 
@endsection

@push('scripts')
    <script>
        function copyReferralCode() {
            const codeEl = document.getElementById('referral-code');
            const code = codeEl ? codeEl.textContent.trim() : '';

            if (!code || code === '—' || code === '...') {
                showToast('No referral code available to copy', 'error');
                return;
            }

            navigator.clipboard.writeText(code).then(() => {
                showToast('Referral code copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Could not copy code automatically', 'error');
            });
        }

        function showToast(msg, type = 'info') {
            const box = document.getElementById('toast');
            if (!box) return;
            const colors = {
                info: 'bg-[#160d2f] border-white/10 text-[#e8ddff]',
                error: 'bg-red-950/80 border-red-500/30 text-red-200',
                success: 'bg-[#00E5A0]/20 border-[#00E5A0]/50 text-[#00E5A0]'
            };
            box.className =
                `fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold ${colors[type] || colors.info}`;
            box.textContent = msg;
            box.classList.remove('hidden');
            setTimeout(() => box.classList.add('hidden'), 3000);
        }
    </script>
@endpush
