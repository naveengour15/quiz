@extends('fsrapp.layouts.app')

@section('content')
    <!-- Fixed Mobile App Header -->
    <header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2"
        style="background: rgba(13, 6, 32, 0.90); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex justify-between items-center px-4 h-full w-full">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ route('fsrapp.profile') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer hover:bg-white/10 active:scale-95 transition-all shrink-0"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1);">
                    <span class="material-symbols-outlined text-[19px]" style="color:#e8ddff;">arrow_back</span>
                </a>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-base font-extrabold tracking-tight truncate" style="color:#e8ddff;">
                        {{ __('app.delete_account_title') }}
                    </h1>
                    <p class="text-[11px] leading-none mt-0.5" style="color:rgba(232,221,255,0.45);">
                        {{ __('app.delete_account_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-20 pb-16 min-h-screen relative z-10">

        <!-- Danger Hero Banner -->
        <section class="mb-5 rounded-2xl border border-red-500/30 bg-gradient-to-br from-[#300c14] via-[#45101d] to-[#2b0821] p-5 shadow-[0_12px_30px_rgba(239,68,68,0.2)]">
            <div class="flex items-center gap-3.5 mb-2.5">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                    style="background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4);">
                    <span class="material-symbols-outlined text-3xl text-red-400">person_remove</span>
                </div>
                <div>
                    <h2 class="font-display text-lg font-extrabold leading-tight text-red-200">
                        {{ __('app.delete_account_title') }}
                    </h2>
                    <p class="text-[11px] font-semibold text-red-400/90 mt-0.5">
                        {{ __('app.delete_account_warning') }}
                    </p>
                </div>
            </div>
            <p class="text-[12px] leading-relaxed text-[#e8ddff]/80 mt-2">
                Deleting your account will permanently deactivate your access to FSR Quiz. Please review what happens before proceeding.
            </p>
        </section>

        <!-- What Will Happen Card -->
        <div class="glass-card rounded-2xl p-5 border border-red-500/20 bg-red-950/20 mb-4">
            <h3 class="font-display text-[14px] font-bold text-red-300 mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-red-400">warning</span>
                What will happen:
            </h3>
            <ul class="text-[12px] space-y-2.5 text-[#e8ddff]/80">
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-red-400 text-[16px] shrink-0 mt-0.5">cancel</span>
                    <span>Your active login session will be immediately signed out and terminated.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-red-400 text-[16px] shrink-0 mt-0.5">cancel</span>
                    <span>All your accumulated wallet points, prize rewards, and referral links will be permanently forfeited.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-red-400 text-[16px] shrink-0 mt-0.5">cancel</span>
                    <span>Your profile name, rank, and answersheet records will no longer be visible on live leaderboards.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-red-400 text-[16px] shrink-0 mt-0.5">cancel</span>
                    <span>You will not be able to log in or play quizzes with this registered mobile number ({{ $user->mobile ?? 'your account' }}).</span>
                </li>
            </ul>
        </div>

        <!-- Data Retention Info -->
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 mb-6">
            <h4 class="font-display text-[13px] font-bold text-[#ffd76a] mb-1.5 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">info</span>
                Data retention notice
            </h4>
            <p class="text-[11.5px] leading-relaxed text-[#e8ddff]/70">
                Some transaction, anti-fraud, and security audit logs may be securely archived for a limited duration where required by law, regulatory compliance, or dispute resolution. We do not use deleted accounts for live services.
            </p>
        </div>

        <!-- Deletion Form with Checkbox & Action Button -->
        <form id="delete-account-form" action="{{ route('fsrapp.profile.delete-account') }}" method="POST">
            @csrf

            <!-- Confirmation Checkbox -->
            <label class="flex items-start gap-3 p-4 rounded-xl border border-red-500/30 bg-red-950/30 cursor-pointer select-none mb-5 hover:bg-red-950/40 transition-colors">
                <input type="checkbox" id="confirm-check" class="mt-0.5 w-4 h-4 rounded border-red-400 text-red-600 focus:ring-red-500 bg-black/40">
                <span class="text-[12px] text-red-200 leading-snug">
                    I understand that deleting my account is permanent, non-reversible, and I will lose all points and quiz progress.
                </span>
            </label>

            <!-- Submit Button (Triggers Modal) -->
            <button type="button" id="trigger-delete-modal-btn" disabled
                class="w-full py-3.5 rounded-xl font-display font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all opacity-50 cursor-not-allowed bg-gradient-to-r from-red-600 to-rose-700 text-white shadow-lg shadow-red-900/40">
                <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                {{ __('app.delete_account_btn') }}
            </button>
        </form>

        <!-- Cancel and Go Back -->
        <div class="text-center mt-4">
            <a href="{{ route('fsrapp.profile') }}" class="text-xs text-white/60 hover:text-white transition-colors underline">
                No, keep my account and return to Profile
            </a>
        </div>

    </main>

    <!-- ===== Delete Confirmation Modal ===== -->
    <div id="delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm hidden opacity-0 transition-opacity duration-200">
        <div class="glass-card max-w-sm w-full rounded-2xl p-6 border border-red-500/40 bg-[#1e0a12] text-center shadow-2xl scale-95 transition-transform duration-200" id="modal-box">
            <div class="w-14 h-14 rounded-full mx-auto flex items-center justify-center mb-4 bg-red-500/20 border border-red-500/50">
                <span class="material-symbols-outlined text-3xl text-red-400">warning</span>
            </div>
            <h3 class="font-display text-lg font-extrabold text-[#e8ddff] mb-1.5">
                {{ __('app.delete_account_confirm') }}
            </h3>
            <p class="text-xs text-[#e8ddff]/70 leading-relaxed mb-6">
                This action is final and immediate. Your points and quiz history will be deactivated, and you will be signed out now.
            </p>
            <div class="flex gap-2.5">
                <button type="button" id="cancel-modal-btn"
                    class="flex-1 py-3 rounded-xl border border-white/20 bg-white/5 text-white/80 font-bold text-xs hover:bg-white/10 active:scale-95 transition-all">
                    Cancel
                </button>
                <button type="button" id="final-confirm-delete-btn"
                    class="flex-1 py-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-700 text-white font-bold text-xs shadow-lg shadow-red-900/50 active:scale-95 transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">check</span>
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const check = document.getElementById('confirm-check');
        const triggerBtn = document.getElementById('trigger-delete-modal-btn');
        const modal = document.getElementById('delete-confirm-modal');
        const modalBox = document.getElementById('modal-box');
        const cancelBtn = document.getElementById('cancel-modal-btn');
        const confirmBtn = document.getElementById('final-confirm-delete-btn');
        const form = document.getElementById('delete-account-form');

        // Toggle button enabled status on checkbox change
        check.addEventListener('change', function () {
            if (this.checked) {
                triggerBtn.disabled = false;
                triggerBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                triggerBtn.classList.add('active:scale-98', 'hover:brightness-110');
            } else {
                triggerBtn.disabled = true;
                triggerBtn.classList.add('opacity-50', 'cursor-not-allowed');
                triggerBtn.classList.remove('active:scale-98', 'hover:brightness-110');
            }
        });

        // Open modal
        triggerBtn.addEventListener('click', function () {
            if (!check.checked) return;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalBox.classList.remove('scale-95');
                modalBox.classList.add('scale-100');
            }, 10);
        });

        // Close modal
        function closeModal() {
            modal.classList.add('opacity-0');
            modalBox.classList.remove('scale-100');
            modalBox.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Final deletion action
        confirmBtn.addEventListener('click', function () {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Deleting...</span>
            `;
            form.submit();
        });
    });
</script>
@endpush

