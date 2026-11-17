@php
    $authUser = \Illuminate\Support\Facades\Auth::user();
    $isSubscribed = false;

    if ($authUser) {
        $notifyRecord = \Illuminate\Support\Facades\DB::table('user_whatsapp_subscribe')
            ->where('user_id', $authUser->id)
            ->where('status', 'subscribed')
            ->first();

        if ($notifyRecord) {
            $isSubscribed = true;
        }
    }

    // Check karein ki kya user abhi Profile page par hai
    $isProfilePage = str_contains(request()->path(), 'profile') || request()->routeIs('*profile*');

    // Logic: Profile par hamesha show, Home par sirf tab jab subscribed na ho
    $showComponent = $isProfilePage ? true : !$isSubscribed;
    $btnText = $isSubscribed ? 'Subscribed' : 'Subscribe';
@endphp

@if($showComponent)
<!-- Toast Container -->
<div id="toast-wa" class="fixed top-4 left-1/2 z-[100] hidden max-w-[90%]"></div>

<!-- ===== Premium Light Yellow WhatsApp Component ===== -->
<div class="block mb-3 p-3.5 rounded-2xl border border-yellow-200/80 relative overflow-hidden transition-all shadow-lg group max-w-md mx-auto bg-gradient-to-br from-yellow-50 via-amber-50 to-yellow-100 hover:shadow-[0_0_30px_rgba(251,191,36,0.3)] hover:scale-[1.02] hover:border-yellow-300">
    
    <!-- Soft light glowing accents -->
    <div class="absolute -right-8 -top-8 w-28 h-28 bg-yellow-300/30 rounded-full blur-2xl pointer-events-none group-hover:bg-yellow-300/40 transition-all duration-500"></div>
    <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-amber-200/40 rounded-full blur-2xl pointer-events-none group-hover:bg-amber-200/50 transition-all duration-500"></div>

    <div class="flex items-center gap-3.5 relative z-10">
        <!-- WhatsApp Icon Box -->
        <div class="w-11 h-11 rounded-xl bg-white flex items-center justify-center shadow-md shadow-yellow-200/50 group-hover:scale-110 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-6 h-6 fill-current text-amber-500">
                <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
            </svg>
        </div>

        <!-- Text Content -->
        <div class="flex-1">
            <p class="text-[15px] font-extrabold text-slate-800 tracking-wide">WhatsApp Channel</p>
            <p class="text-[12px] font-semibold text-slate-600 mt-0.5">Daily Quiz & Knowledge Updates</p>
        </div>

        <!-- Subscribe Button (FIXED: Clickable in both states) -->
        <div class="shrink-0">
            <a href="https://whatsapp.com/channel/0029Vb8XUruBadmcZkqAEC3h" 
                target="_blank"
                onclick="handleWhatsappSubscribeClick(event, this)"
                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-yellow-400 hover:bg-yellow-500 text-slate-900 rounded-full text-[13px] font-extrabold shadow-md shadow-yellow-400/30 hover:shadow-lg hover:shadow-yellow-500/40 hover:scale-105 active:scale-95 transition-all duration-300 border border-yellow-300/50 {{ $isSubscribed ? 'bg-slate-200 text-slate-700 border-slate-300 hover:bg-slate-300 shadow-slate-200/50' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-4 h-4 fill-current text-slate-800">
                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
                <span class="btn-text">{{ $btnText }}</span>
                <span class="btn-loader hidden"><svg class="animate-spin h-4 w-4 text-slate-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
            </a>
        </div>
    </div>
</div>

<!-- ===== JavaScript Logic ===== -->
<script>
    function showWaToast(msg, type = 'info') {
        let box = document.getElementById('toast-wa');
        if (!box) {
            box = document.createElement('div');
            box.id = 'toast-wa';
            document.body.appendChild(box);
        }

        const colors = {
            info: 'bg-white border-yellow-200 text-slate-800 shadow-lg',
            error: 'bg-red-50 border-red-200 text-red-700 shadow-lg',
            success: 'bg-gradient-to-r from-yellow-100 to-amber-100 border-yellow-300 text-slate-800 font-bold shadow-lg'
        };

        const icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');

        box.className = `fixed top-4 left-1/2 -translate-x-1/2 z-[100] toast-enter max-w-[90%] px-4 py-3 rounded-xl border glass-card text-xs font-semibold flex items-center gap-2 ${colors[type] || colors.info}`;
        box.innerHTML = `<span class="material-symbols-outlined text-sm text-amber-500">${icon}</span> ${msg}`;
        
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 3200);
    }

    function handleWhatsappSubscribeClick(event, btn) {
        event.preventDefault();
        event.stopPropagation();

        const href = btn.getAttribute('href');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const btnText = btn.querySelector('.btn-text');
        const btnLoader = btn.querySelector('.btn-loader');

        // Loading state
        btnText.classList.add('hidden');
        btnLoader.classList.remove('hidden');
        btn.classList.add('opacity-75', 'cursor-not-allowed');

        fetch('{{ route("fsrapp.home.whatsapp-subscribe") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ _token: csrf })
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state immediately
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
            btn.classList.remove('opacity-75', 'cursor-not-allowed');

            if (data.success) {
                localStorage.setItem('wa_subscribed', '1');
                showWaToast(data.message || 'Successfully subscribed!', 'success');
                window.open(href, '_blank');
                
                // Hide component smoothly ONLY if it was on Home page (not profile)
                const isProfilePage = window.location.pathname.includes('profile');
                if (!isProfilePage) {
                    const component = btn.closest('.block');
                    if(component) {
                        component.style.transition = 'all 0.5s ease';
                        component.style.opacity = '0';
                        component.style.transform = 'scale(0.95) translateY(-10px)';
                        setTimeout(() => component.remove(), 500);
                    }
                }
            } else {
                showWaToast(data.message || 'Failed to subscribe.', 'error');
                window.open(href, '_blank'); 
            }
        })
        .catch(error => {
            console.error('Subscribe Error:', error);
            showWaToast('Something went wrong.', 'error');
            btnText.classList.remove('hidden');
            btnLoader.classList.add('hidden');
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
            window.open(href, '_blank'); 
        });
    }
</script>
@endif