@php
    $forceShowWhatsappPopup = request()->query('show_wa_popup') === '1';
    $shouldShowWhatsappSubscribeModal = $forceShowWhatsappPopup || (bool) ($isWhatsappSubscribed ?? false) === false;
@endphp

@if ($shouldShowWhatsappSubscribeModal)
    <!-- ===== WhatsApp Channel Subscription Popup Modal ===== -->
    <div id="wa-subscribe-modal-backdrop"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-black/75 backdrop-blur-sm px-4 transition-all duration-300 opacity-0 pointer-events-none">

        <div id="wa-subscribe-modal-content"
            class="relative w-full max-w-sm rounded-2xl bg-gradient-to-br from-[#f0fdf4] via-white to-[#dcfce7] border border-[#86efac] shadow-[0_20px_50px_rgba(34,197,94,0.25)] p-5 overflow-hidden transform scale-90 transition-all duration-300">

            <!-- Glowing background accent circle -->
            <div class="absolute -right-8 -top-8 w-28 h-28 bg-green-400/25 rounded-full blur-2xl pointer-events-none">
            </div>
            <div
                class="absolute -left-8 -bottom-8 w-24 h-24 bg-emerald-400/20 rounded-full blur-2xl pointer-events-none">
            </div>

            <!-- Close Button (X) -->
            <button type="button" onclick="closeWhatsappSubscribeModal()"
                class="absolute top-3 right-3 z-20 w-7 h-7 rounded-full flex items-center justify-center bg-emerald-900/10 text-emerald-800 hover:bg-emerald-900/20 transition-all"
                title="Close">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>

            <!-- Header with Icon & Badge -->
            <div class="flex items-center gap-3 mb-3 relative z-10">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 shadow-md shadow-green-500/30 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                        class="w-5 h-5 fill-current text-white">
                        <path
                            d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>
                </div>
                <div>
                    <span
                        class="inline-block bg-green-100 text-green-800 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full border border-green-300">
                        Official Channel
                    </span>
                    <h3 class="font-extrabold text-[#064e3b] text-sm">FSRQuiz Update</h3>
                </div>
            </div>

            <!-- User Specified Content -->
            <div class="flex items-start gap-4 mb-3 relative z-10">
                <div class="flex-1">
                    <p class="text-xs text-emerald-800 font-semibold">
                        Latest Update और Notification के लिए हमारा WhatsApp Channel Subscribe करें।
                    </p>
                </div>
            </div>

            <a href="https://whatsapp.com/channel/0029Vb8XUruBadmcZkqAEC3h" target="_blank"
                onclick="handleWhatsappModalSubscribe(event, this)"
                class="relative z-10 flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white shadow-md shadow-green-600/30 px-4 py-2.5 rounded-xl text-[13px] font-bold active:scale-95 transition-all w-full mb-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="w-4 h-4 fill-current text-white">
                    <path
                        d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157.1zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                </svg>
                Subscribe Channel
            </a>

            <p class="text-[12px] text-red-600 font-bold text-center relative z-10 mt-2 tracking-wide shadow-sm">
                * Note: सभी क्विज़ अलर्ट इसी चैनल पर मिलेंगे।
            </p>
        </div>
    </div>

    <script>
        function showWhatsappSubscribeModal() {
            const backdrop = document.getElementById('wa-subscribe-modal-backdrop');
            const content = document.getElementById('wa-subscribe-modal-content');
            if (backdrop && content) {
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100', 'pointer-events-auto');
                content.classList.remove('scale-90');
                content.classList.add('scale-100');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(showWhatsappSubscribeModal, 600);
        });

        function closeWhatsappSubscribeModal() {
            const backdrop = document.getElementById('wa-subscribe-modal-backdrop');
            const content = document.getElementById('wa-subscribe-modal-content');
            if (backdrop && content) {
                backdrop.classList.remove('opacity-100', 'pointer-events-auto');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                content.classList.remove('scale-100');
                content.classList.add('scale-90');
                setTimeout(() => {
                    backdrop.remove();
                }, 300);
            }
        }

        function handleWhatsappModalSubscribe(event, btn) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            // Send subscription entry to backend
            fetch('{{ route('fsrapp.home.whatsapp-subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _token: csrf
                })
            }).catch(err => {
                console.error('Error recording WhatsApp subscription:', err);
            });

            // Also hide in-page WhatsApp card smoothly if present on the page
            const inPageWaCards = document.querySelectorAll('.block.mb-3');
            inPageWaCards.forEach(card => {
                if (card.textContent.includes('WhatsApp Channel')) {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95) translateY(-10px)';
                    setTimeout(() => card.remove(), 500);
                }
            });

            // Close and remove popup
            closeWhatsappSubscribeModal();
        }
    </script>
@endif
