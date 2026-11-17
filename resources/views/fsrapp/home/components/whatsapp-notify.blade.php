@php
    $waNotifyStatus = 'inactive';
    if (\Illuminate\Support\Facades\Auth::check()) {
        $notifyRecord = \App\Models\UsersWhatsappNotify::where('user_id', \Illuminate\Support\Facades\Auth::id())->first();
        if ($notifyRecord) {
            $waNotifyStatus = $notifyRecord->status;
        }
    }
@endphp

<div class="bg-gradient-to-br from-[#f0fdf4] via-white to-[#dcfce7] rounded-xl shadow-[0_8px_20px_-4px_rgba(34,197,94,0.15)] border border-[#86efac] p-4 mb-4 mt-4 relative overflow-hidden">
    <!-- Glowing background accent -->
    <div class="absolute -right-6 -top-6 w-24 h-24 bg-green-400/20 rounded-full blur-xl pointer-events-none"></div>

    <!-- Notification Section -->
    <div class="flex items-start gap-4 mb-3 relative z-10">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-500 shadow-sm flex items-center justify-center flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-5 h-5 fill-current text-white"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-800 text-sm mb-1">WhatsApp Notifications</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                अगर आप अपने WhatsApp पर Quiz के Notification पाना चाहते हैं, तो अभी Notify करें।
            </p>
        </div>
    </div>
    
    <div class="flex items-center justify-between mb-4 relative z-10">
        <button onclick="toggleGlobalWhatsappNotify(this)" id="global-wa-notify-btn" class="flex items-center gap-2 {{ $waNotifyStatus === 'active' ? 'bg-red-50 text-red-500 border border-red-200' : 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md shadow-green-500/20' }} px-4 py-2.5 rounded-xl text-[13px] font-bold hover:opacity-90 transition-all w-full justify-center">
            @if($waNotifyStatus === 'active')
                <span class="material-symbols-outlined text-[18px]">notifications_off</span> Cancel Notification
            @else
                <span class="material-symbols-outlined text-[18px]">notifications_active</span> Notify Me
            @endif
        </button>
    </div>

    <hr class="border-green-200/60 mb-4 relative z-10">

    <!-- Channel Subscribe Section -->
    <div class="flex items-start gap-4 mb-3 relative z-10">
        <div class="flex-1">
            <p class="text-xs text-emerald-800 font-semibold">
                Latest Update और Notification के लिए हमारा WhatsApp Channel Subscribe करें।
            </p>
        </div>
    </div>
    <a href="https://whatsapp.com/channel/0029Vb8XUruBadmcZkqAEC3h" target="_blank" class="relative z-10 flex items-center justify-center gap-2 bg-white text-green-600 border border-green-200 shadow-sm px-4 py-2.5 rounded-xl text-[13px] font-bold hover:bg-green-50 transition-all w-full mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-4 h-4 fill-current"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
        Subscribe Channel
    </a>
    <p class="text-[12px] text-red-600 font-bold text-center relative z-10 mt-2 tracking-wide shadow-sm">
        * Note: Next Friday से Quiz का Update सिर्फ़ Channel पर ही मिलेगा।
    </p>
</div>

<script>
function toggleGlobalWhatsappNotify(btn) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    
    // UI optimistic update
    btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">sync</span> Processing...';
    btn.classList.add('opacity-75');
    btn.style.pointerEvents = 'none';

    fetch('{{ route("fsrapp.home.whatsapp-notify.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            _token: csrf
        })
    }).then(res => res.json())
      .then(data => {
          btn.classList.remove('opacity-75');
          btn.style.pointerEvents = 'auto';
          
          if (data.status === 'active') {
              btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">notifications_off</span> Cancel Notification';
              btn.className = 'flex items-center gap-2 bg-red-50 text-red-500 border border-red-200 px-4 py-2.5 rounded-xl text-[13px] font-bold hover:opacity-90 transition-all w-full justify-center';
          } else {
              btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">notifications_active</span> Notify Me';
              btn.className = 'flex items-center gap-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md shadow-green-500/20 px-4 py-2.5 rounded-xl text-[13px] font-bold hover:opacity-90 transition-all w-full justify-center';
          }
      }).catch(err => {
          btn.classList.remove('opacity-75');
          btn.style.pointerEvents = 'auto';
          btn.innerHTML = 'Error, try again';
      });
}
</script>
