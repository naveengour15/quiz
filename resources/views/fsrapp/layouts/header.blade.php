<header class="fsr-header fixed top-0 w-full z-40 h-16 max-w-md mx-auto left-1/2 -translate-x-1/2">
    <div class="flex justify-between items-center px-4 h-full w-full">

        <!-- LEFT: logo + brand -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 overflow-hidden flex items-center justify-center shrink-0">
                <img src="{{ asset('fsrapp/images/logo.webp') }}" alt="Logo" width="40" height="40" class="w-full h-full object-contain">
            </div>
            {{-- <h1 class="font-display text-lg font-extrabold tracking-tight" style="color:#e8ddff;">FSRQuiz</h1> --}}
        </div>

        <!-- RIGHT: Language Icon + wallet points -->
        <div class="flex items-center gap-2.5">
            <span
                onclick="openWhatsAppModal('{{ Auth::check() ? Auth::user()->referral_code : '' }}', '{{ Auth::check() ? Auth::user()->mobile : '' }}')"
                class="bg-[#25D366] hover:bg-[#1ebe5d] text-white w-7 h-7 rounded-full transition-all active:scale-95 flex items-center justify-center cursor-pointer shadow-md shadow-green-500/20"
                title="Share on WhatsApp">

                <!-- ✅ Icon size 16x16 kar diya hai (content_copy ke barabar) -->
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="currentColor">
                    <path
                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                </svg>
            </span>
            @php
                $currentLang = app()->getLocale();
            @endphp
            <button type="button" onclick="openLangModal()" class="fsr-lang-pill" id="header-lang-btn"
                title="Select Language">
                <span class="material-symbols-outlined text-[18px] text-[#ffe9b0]">translate</span>
                <span id="current-lang-code"
                    class="uppercase text-xs font-bold text-[#ffe9b0]">{{ $currentLang }}</span>
            </button>

            <a href="{{ route('fsrapp.wallet.index') }}" class="fsr-wallet-pill">
                <span class="fsr-wallet-points" id="fsr-wallet-points">Pts
                    {{ Auth::check() ? number_format(Auth::user()->wallet_balance) : '0' }}</span>
            </a>
        </div>

    </div>
</header>

<!-- Language Selection Modal Popup -->
<div id="language-modal"
    class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/75 backdrop-blur-md px-4">
    <div
        class="relative w-full max-w-xs rounded-2xl glass-card p-5 bg-[#160d2f]/95 border border-white/20 shadow-2xl text-center space-y-4">
        <button type="button" onclick="closeLangModal()"
            class="absolute top-3 right-3 text-white/60 hover:text-white transition">
            <span class="material-symbols-outlined text-xl">close</span>
        </button>

        <div class="flex flex-col items-center gap-1.5">
            <div
                class="w-10 h-10 rounded-full bg-primary/20 border border-primary/40 flex items-center justify-center text-primary mb-1">
                <span class="material-symbols-outlined text-2xl">translate</span>
            </div>
            <h3 class="font-display font-bold text-lg text-white">{{ __('app.select_language') }}</h3>
            <p class="text-xs text-white/60">{{ __('app.choose_language') }}</p>
        </div>

        <form action="{{ route('fsrapp.profile.update-language') }}" method="POST" class="space-y-4 pt-1">
            @csrf
            <div class="space-y-2.5 text-left">
                <label onclick="setLangChoice('en')" id="lang-opt-en"
                    class="flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm font-semibold {{ $currentLang === 'en' ? 'bg-primary/20 border-primary text-primary shadow-[0_0_12px_rgba(244,191,50,0.3)]' : 'bg-white/5 border-white/10 text-white/80 hover:bg-white/10' }}">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="language" value="en" class="accent-primary"
                            {{ $currentLang === 'en' ? 'checked' : '' }}>
                        <span>{{ __('app.english') }}</span>
                    </div>
                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-white/10 text-white/70">EN</span>
                </label>

                <label onclick="setLangChoice('hi')" id="lang-opt-hi"
                    class="flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm font-semibold {{ $currentLang === 'hi' ? 'bg-primary/20 border-primary text-primary shadow-[0_0_12px_rgba(244,191,50,0.3)]' : 'bg-white/5 border-white/10 text-white/80 hover:bg-white/10' }}">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="language" value="hi" class="accent-primary"
                            {{ $currentLang === 'hi' ? 'checked' : '' }}>
                        <span>{{ __('app.hindi') }}</span>
                    </div>
                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-white/10 text-white/70">HI</span>
                </label>

                <label onclick="setLangChoice('hinglish')" id="lang-opt-hinglish"
                    class="flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm font-semibold {{ $currentLang === 'hinglish' ? 'bg-primary/20 border-primary text-primary shadow-[0_0_12px_rgba(244,191,50,0.3)]' : 'bg-white/5 border-white/10 text-white/80 hover:bg-white/10' }}">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="language" value="hinglish" class="accent-primary"
                            {{ $currentLang === 'hinglish' ? 'checked' : '' }}>
                        <span>Hinglish</span>
                    </div>
                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-white/10 text-white/70">HIN</span>
                </label>
            </div>

            <button type="submit"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-[#ffe9b0] to-[#f4bf32] text-[#3f2e00] font-bold text-sm shadow-[0_0_15px_rgba(244,191,50,0.4)] hover:brightness-110 active:scale-[0.98] transition">
                {{ __('app.submit') }}
            </button>
        </form>
    </div>
</div>
@include('fsrapp.profile.referral-msg.msg')
<script>
    function openLangModal() {
        const modal = document.getElementById('language-modal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeLangModal() {
        const modal = document.getElementById('language-modal');
        if (modal) modal.classList.add('hidden');
    }

    function setLangChoice(lang) {
        const options = ['en', 'hi', 'hinglish'];
        const selectedClass =
            'flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm font-semibold bg-primary/20 border-primary text-primary shadow-[0_0_12px_rgba(244,191,50,0.3)]';
        const unselectedClass =
            'flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer transition-all text-sm font-semibold bg-white/5 border-white/10 text-white/80 hover:bg-white/10';

        options.forEach(option => {
            const label = document.getElementById(`lang-opt-${option}`);
            const radio = document.querySelector(`input[name="language"][value="${option}"]`);
            if (label) label.className = option === lang ? selectedClass : unselectedClass;
            if (radio) radio.checked = option === lang;
        });
    }
</script>
