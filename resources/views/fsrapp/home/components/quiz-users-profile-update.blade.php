@php
    $authUser = \Illuminate\Support\Facades\Auth::user();
    
    // ✅ Yahan apni condition lagayein: Agar profile incomplete hai tabhi dikhaye
    // Example: empty($authUser->address) || empty($authUser->date_of_birth)
    // Abhi ke liye hum check kar rahe hain ki user logged in hai ya nahi
   $showUpdatePrompt = $authUser && ($authUser->profile_status !== 'complete' && $authUser->profile_status !== 'completed');
@endphp

@if ($showUpdatePrompt)
    <!-- ===== Vibrant Profile Update & Points Reward Component ===== -->
    <a href="{{ route('fsrapp.profile') }}" {{-- ✅ Apna actual profile edit route yahan dalein --}}
       class="fsr-profile-animated-bg block mb-3.5 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden transition-all cursor-pointer shadow-lg group hover:border-white/20 hover:shadow-[0_0_20px_rgba(124,58,237,0.4)]">

        <!-- Ambient background highlight lights (Purple & Gold) -->
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-[#f59e0b]/20 rounded-full blur-2xl pointer-events-none group-hover:bg-[#f59e0b]/30 transition-all"></div>
        <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-[#7c3aed]/20 rounded-full blur-2xl pointer-events-none group-hover:bg-[#7c3aed]/30 transition-all"></div>

        <div class="flex items-center gap-3.5 relative z-10">

            <!-- Left Icon: User Edit with vibrant multi-tint glow -->
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#f59e0b]/30 via-[#7c3aed]/25 to-[#4f46e5]/30 border border-[#f59e0b]/60 flex items-center justify-center shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.35)] group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-[20px] text-white animate-pulse drop-shadow-[0_0_8px_rgba(255,255,255,0.8)]" style="font-variation-settings: 'FILL' 1;">person_edit</span>
            </div>

            <!-- Text Content -->
            
            <div class="flex-1 min-w-0 relative overflow-hidden">
                <p class="text-[13px] font-extrabold text-white drop-shadow-[0_0_8px_rgba(255,255,255,0.3)]">
                    20 Points Aapke Liye!
                </p>
                <p class="text-[11.5px] font-semibold text-white/90 mt-0.5 leading-tight">
                   Apni details update karein aur prapt karein.  <strong class="text-[#ffd76a]">20 Bonus Points.</strong> 
                </p>
            </div>

            <!-- Right: Clean "Update Now" Action Button -->
            <div class="shrink-0">
                <span class="flex items-center gap-1.5 px-3 py-2 bg-white text-[#7c3aed] rounded-full text-[11px] font-extrabold tracking-wide transition-all active:scale-90 cursor-pointer shadow-lg shadow-black/20 group-hover:scale-105 group-hover:shadow-white/30">
                    UPDATE
                   
                </span>
            </div>
        </div>
    </a>
@endif