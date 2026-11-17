<!-- ===== Vibrant Quiz Feedback Component (Submission Only) ===== -->

<!-- 1. Toast Container (Agar layout mein nahi hai toh yeh ensure karega ki toast dikhe) -->
<div id="toast" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%]"></div>

<!-- 2. Feedback Trigger Card -->
<a href="javascript:void(0)" onclick="openFeedbackModal()"
    class="fsr-feedback-animated-bg block mb-3.5 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden transition-all cursor-pointer shadow-lg group hover:border-white/20 hover:shadow-[0_0_20px_rgba(124,58,237,0.4)] hover:scale-[1.01]">
    
    <div class="absolute -right-8 -top-8 w-28 h-28 bg-[#7c3aed]/20 rounded-full blur-2xl pointer-events-none group-hover:bg-[#7c3aed]/30 transition-all"></div>
    <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-[#0ea5e9]/20 rounded-full blur-2xl pointer-events-none group-hover:bg-[#0ea5e9]/30 transition-all"></div>

    <div class="flex items-center gap-3.5 relative z-10">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#7c3aed]/30 via-[#0ea5e9]/25 to-[#db2777]/30 border border-[#7c3aed]/60 flex items-center justify-center shrink-0 shadow-[0_0_12px_rgba(124,58,237,0.35)] group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-[20px] text-white animate-pulse drop-shadow-[0_0_8px_rgba(255,255,255,0.8)]" style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
        </div>

        <div class="flex-1 min-w-0 relative overflow-hidden">
            <p class="text-[13px] font-extrabold text-white  drop-shadow-[0_0_8px_rgba(255,255,255,0.3)]">
                {{ __('app.quiz_feedback') }}
            </p>
            <p class="text-[11.5px] font-semibold text-white/90  flex items-center gap-1.5 mt-0.5">
                <span class="text-[#a5f3fc] font-medium">{{ __('app.help_us_improve') }}</span>
            </p>
        </div>

        {{-- <div class="shrink-0">
            <span class="flex items-center justify-center w-9 h-9 bg-white text-[#7c3aed] rounded-full transition-all active:scale-90 cursor-pointer shadow-lg shadow-black/20 group-hover:scale-110 group-hover:shadow-white/30">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">edit_note</span>
            </span>
        </div> --}}

        <div class="shrink-0">
            <button type="button" class="flex items-center justify-center gap-1.5 px-4 h-9 bg-white text-[#7c3aed] rounded-full transition-all active:scale-90 shadow-lg shadow-black/20 group-hover:scale-110 group-hover:shadow-white/30 text-[11px] font-bold">
        
                {{ __('app.send_feedback') }}
            </button>
</div>
    </div>
</a>

<!-- 3. Feedback Modal -->
<div id="feedback-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/75 backdrop-blur-md px-4">
    <div class="relative w-full max-w-sm rounded-2xl glass-card p-5 bg-[#160d2f]/95 border border-white/20 shadow-2xl space-y-4 transform transition-all scale-95 opacity-0" id="feedback-modal-content">
        <button type="button" onclick="closeFeedbackModal()" class="absolute top-3 right-3 text-white/60 hover:text-white transition"><span class="material-symbols-outlined text-xl">close</span></button>
        <div class="flex flex-col items-center gap-1.5 mb-2">
            <div class="w-10 h-10 rounded-full bg-[#7c3aed]/20 border border-[#7c3aed]/40 flex items-center justify-center text-[#a78bfa] mb-1"><span class="material-symbols-outlined text-2xl">feedback</span></div>
            <h3 class="font-display font-bold text-lg text-white">{{ __('app.quiz_feedback') }}</h3>
            <p class="text-xs text-white/60 text-center">{{ __('app.help_us_improve') }}</p>
        </div>
        <form id="feedback-form" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">{{ __('app.subject') }}</label>
                <input type="text" name="subject" required placeholder="e.g., Question Error, UI Issue" class="w-full px-3 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] transition placeholder-white/30">
            </div>
            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">{{ __('app.your_message') }}</label>
                <textarea name="message" required rows="3" placeholder="Describe the issue or suggestion..." class="w-full px-3 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-sm focus:outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] transition placeholder-white/30 resize-none"></textarea>
            </div>
            <button type="submit" id="feedback-submit-btn" class="w-full py-3 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#db2777] text-white font-bold text-sm shadow-[0_0_15px_rgba(124,58,237,0.4)] hover:brightness-110 active:scale-[0.98] transition flex items-center justify-center gap-2">
                <span class="btn-text">{{ __('app.submit_feedback') }}</span>
                <span class="btn-loader hidden"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
            </button>
        </form>
    </div>
</div>

<!-- 4. JavaScript Logic (Exact Match with Redeem Page) -->
<script>
    // --- Modal Functions ---
    function openFeedbackModal() {
        const modal = document.getElementById('feedback-modal');
        const content = document.getElementById('feedback-modal-content');
        if (modal) { modal.classList.remove('hidden'); setTimeout(() => { content.classList.remove('scale-95', 'opacity-0'); content.classList.add('scale-100', 'opacity-100'); }, 10); }
    }
    function closeFeedbackModal() {
        const modal = document.getElementById('feedback-modal');
        const content = document.getElementById('feedback-modal-content');
        if (modal) { content.classList.remove('scale-100', 'opacity-100'); content.classList.add('scale-95', 'opacity-0'); setTimeout(() => { modal.classList.add('hidden'); document.getElementById('feedback-form').reset(); }, 200); }
    }
    document.getElementById('feedback-modal').addEventListener('click', function(e) { if (e.target === this) closeFeedbackModal(); });

    // --- Toast Notification System (Exact Copy from Redeem Page) ---
    function showToast(msg, type = 'info') {
        let box = document.getElementById('toast');
        if (!box) {
            box = document.createElement('div');
            box.id = 'toast';
            document.body.appendChild(box);
        }

        const colors = {
            info: 'bg-[#160d2f] border-white/10 text-[#e8ddff]',
            error: 'bg-red-950/80 border-red-500/30 text-red-200',
            success: 'bg-[#00E5A0]/20 border-[#00E5A0]/50 text-[#00E5A0]'
        };

        const icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');

        box.className = `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold flex items-center gap-2 ${colors[type] || colors.info}`;
        box.innerHTML = `<span class="material-symbols-outlined text-sm">${icon}</span> ${msg}`;
        
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 3200);
    }

    // --- AJAX Form Submit (Exact Logic from Redeem Page) ---
    document.getElementById('feedback-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = document.getElementById('feedback-submit-btn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        const formData = new FormData(form);

        // Loading state
        submitBtn.disabled = true;
        btnText.textContent = 'Submitting...';
        btnLoader.classList.remove('hidden');

        fetch("{{ route('fsrapp.quiz.feedback.store') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // ✅ SUCCESS: Show Green Toast, Close Modal, Reload Page (like redeem)
                showToast(data.message, 'success');
                closeFeedbackModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                // ❌ ERROR: Show Red Toast, Reset Button
                showToast(data.message || 'Failed to submit feedback.', 'error');
                submitBtn.disabled = false;
                btnText.textContent = 'Submit Feedback';
                btnLoader.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Something went wrong. Please try again.', 'error');
            submitBtn.disabled = false;
            btnText.textContent = 'Submit Feedback';
            btnLoader.classList.add('hidden');
        });
    });
</script>