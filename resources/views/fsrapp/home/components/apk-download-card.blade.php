@php
    // Sirf active status wala latest APK fetch karo
    $latestApk = \App\Models\ApkUpdate::where('tenant_id', 1)
                                      ->where('status', 'active')
                                      ->latest()
                                      ->first();
@endphp

@if($latestApk)
<!-- ===== Clean & Attractive APK Download Card ===== -->
<a href="{{ $latestApk->apk_url }}" download 
   class="fsr-apk-download-card block mb-4 p-5 rounded-2xl border border-emerald-400/35 bg-gradient-to-br from-[#064e3b] via-[#047857] to-[#059669] relative overflow-hidden transition-all duration-300 cursor-pointer shadow-[0_10px_25px_-5px_rgba(4,120,87,0.4),0_0_15px_rgba(16,185,129,0.2)] group hover:border-emerald-300/60 hover:shadow-[0_14px_30px_rgba(16,185,129,0.5)] hover:scale-[1.01] active:scale-[0.99]">
    
    <!-- Ambient Glow Highlights -->
    <div class="absolute -right-10 -top-10 w-36 h-36 bg-emerald-300/25 rounded-full blur-3xl pointer-events-none group-hover:bg-emerald-300/35 transition-all"></div>
    <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-teal-300/20 rounded-full blur-2xl pointer-events-none group-hover:bg-teal-300/30 transition-all"></div>
    <div class="absolute top-0 left-0 right-0 h-[1.5px] bg-gradient-to-r from-transparent via-emerald-200/50 to-transparent pointer-events-none"></div>

    <div class="relative z-10 flex flex-col items-center text-center">
        <!-- Big Bold Title (Clean & Prominent) -->
        <h3 class="text-[20px] sm:text-[22px] font-black text-white leading-snug tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)] mb-3.5">
            @if($latestApk->release_title)
                {{ $latestApk->release_title }}
            @else
                Download Android App 📱
            @endif
        </h3>

        <!-- Attractive Download Button -->
        <div class="w-full">
            <span class="flex items-center justify-center gap-2.5 w-full py-3 px-6 bg-white text-[#047857] hover:bg-emerald-50 rounded-xl transition-all font-black text-[15px] shadow-[0_6px_18px_rgba(0,0,0,0.18)] group-hover:shadow-[0_8px_24px_rgba(255,255,255,0.4)] active:scale-[0.98]">
                <span>Download Now</span>
                <span class="material-symbols-outlined text-[20px] group-hover:translate-y-0.5 transition-transform" style="font-variation-settings: 'FILL' 1;">download</span>
            </span>
        </div>
    </div>
</a>

<!-- Simple Toast on Click -->
<script>
    document.querySelector('.fsr-apk-download-card')?.addEventListener('click', function() {
        let box = document.getElementById('toast') || document.createElement('div');
        box.id = 'toast';
        box.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-[90%] px-4 py-3 rounded-xl border glass-card shadow-lg text-xs font-semibold flex items-center gap-2 bg-emerald-600/95 border-emerald-400/50 text-white transition-all duration-300';
        box.innerHTML = '<span class="material-symbols-outlined text-sm">downloading</span> App download started...';
        
        let oldToast = document.getElementById('toast');
        if(oldToast && oldToast !== box) oldToast.remove();
        
        document.body.appendChild(box);
        setTimeout(() => {
            box.style.opacity = '0';
            box.style.transform = 'translate(-50%, -20px)';
            setTimeout(() => box.remove(), 300);
        }, 3000);
    });
</script>
@endif
 