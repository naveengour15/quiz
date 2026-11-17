<!-- ===================== FOOTER ===================== -->
<footer class="relative z-10 border-t" style="border-color: var(--border-glass);">
    <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row justify-between items-center gap-4">
        <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
            <div class="w-9 h-9 flex-shrink-0">
                <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="ftrRing" x1="10%" y1="0%" x2="90%" y2="100%">
                            <stop offset="0%" stop-color="#f0abfc" />
                            <stop offset="35%" stop-color="#e879f9" />
                            <stop offset="70%" stop-color="#a78bfa" />
                            <stop offset="100%" stop-color="#7c3aed" />
                        </linearGradient>
                        <linearGradient id="ftrBubble" x1="20%" y1="0%" x2="90%" y2="100%">
                            <stop offset="0%" stop-color="#c084fc" />
                            <stop offset="100%" stop-color="#6d28d9" />
                        </linearGradient>
                        <linearGradient id="ftrStar" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffe9b0" />
                            <stop offset="100%" stop-color="#f4bf32" />
                        </linearGradient>
                    </defs>
                    <circle cx="300" cy="300" r="290" fill="#1a0b3d" />
                    <circle cx="300" cy="300" r="255" fill="none" stroke="url(#ftrRing)"
                        stroke-width="16" />
                    <path
                        d="M300 205c-62 0-112 44-112 100c0 35 20 66 51 84l-13 46l51-27c7 1 15 2 23 2c62 0 112-44 112-100c0-56-50-104-112-104z"
                        fill="url(#ftrBubble)" />
                    <text x="300" y="335" text-anchor="middle" font-family="Plus Jakarta Sans, Arial, sans-serif"
                        font-weight="800" font-size="96" fill="#ffffff">?</text>
                    <path
                        d="M420 400 l8.5 19 20.8 2.8 -15.1 14.2 3.8 20.8 -18 -10.4 -18 10.4 3.8 -20.8 -15.1 -14.2 20.8 -2.8 z"
                        fill="url(#ftrStar)" />
                </svg>
            </div>
            <span class="font-display font-extrabold text-[15px]">FSRQuiz</span>
        </a>
        <p class="text-[12.5px]" style="color:rgba(232,221,255,0.4);">© 2026 FSRQuiz · A Chalo Online product ·
            Indore, India</p>
        <div class="flex items-center gap-5 text-[12.5px]" style="color:rgba(232,221,255,0.5);">
            <a href="{{ route('website.privacy-policy') }}" class="hover:text-white transition">Privacy</a>
            <a href="{{ route('website.terms-of-use') }}" class="hover:text-white transition">Terms</a>
            <a href="{{ route('website.delete-account') }}" class="hover:text-white transition">Delete account</a>
            {{-- <a href="#" class="hover:text-white transition">Support</a> --}}
        </div>
    </div>
</footer>

</body>

</html>
