<!-- WhatsApp Message Selection Modal -->
<div id="whatsappModal"
    class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">

        <!-- Modal Header -->
        <div class="bg-[#7c3aed] p-4 flex justify-between items-center"
            style="background: radial-gradient(120% 160% at 15% 0%, rgba(217, 70, 239, 0.35) 0%, transparent 55%), radial-gradient(120% 160% at 85% 20%, rgba(124, 58, 237, 0.35) 0%, transparent 55%), linear-gradient(135deg, #1a0b3d, #4c1d95 55%, #86198f 100%);">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                    <path
                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                </svg>
                Choose a Message
            </h3>
            <button onclick="closeWhatsAppModal()"
                class="text-white hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>

        <!-- Modal Body (Radio Options) -->
        <div class="p-5 max-h-[60vh] overflow-y-auto" id="messageOptionsContainer" style="    word-wrap: anywhere;">
            <!-- Options yahan JavaScript se dynamically aayenge -->
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 border-t flex gap-3">
            <button onclick="closeWhatsAppModal()"
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2.5 rounded-lg transition">Cancel</button>
            <button type="button" onclick="sendSelectedMessage()"
                style="    background: linear-gradient(85deg, #ffe9b0 0%, #f4c034 100%); color:#1a0b3d"
                class="flex-1 bg-[#7c3aed] hover:bg-[#6d28d9] font-semibold py-2.5 rounded-lg transition flex items-center justify-center gap-2">
                Send Now
            </button>
        </div>
    </div>
</div>

<script>
    // Global variables to store current user data
    let currentRefCode = '';
    let currentMobile = '';
    let shareUrl = '';

    // ✅ Updated Message Templates (Sirf 2 Messages)
    const messageTemplates = [{
            id: 'msg1',
            text: `📱 FREE Mobile Recharge! 🔥\nFSRQuiz खेलो 🎯 Points कमाओ 🏆 और FREE Recharge करो! 😍\nमैंने कर लिया… अब आपकी बारी! 🚀\n👉 [LINK]`
        },
        {
            id: 'msg2',
            text: `🔥 Challenge Accept Karo 🔥\n😏 हिम्मत है तो बिना Google किए खेलो!\nFSRQuiz के 10 Questions का Challenge स्वीकार करो 🔥\n👉 Start Quiz: [LINK]`
        }
    ];

    // 2. Modal Open karne ka function
    function openWhatsAppModal(refCode, mobile) {
        currentRefCode = refCode;
        currentMobile = mobile;

        // Dynamic URL generation
        let loginUrl = "{{ route('fsrapp.login') }}";
        shareUrl = `${loginUrl}?ref=${currentRefCode}`;

        // Modal ke andar options generate karna
        const container = document.getElementById('messageOptionsContainer');
        container.innerHTML = ''; // Purana clear karein

        messageTemplates.forEach((template, index) => {
            // [LINK] ko actual URL se replace karein display ke liye
            let displayText = template.text.replace('[LINK]', shareUrl);

            container.innerHTML += `
                <label class="flex items-start p-3 border border-gray-200 rounded-lg mb-3 cursor-pointer hover:bg-green-50 hover:border-[#25D366] transition-all group">
                    <input type="radio" name="wa_message" value="${index}" class="mt-1 mr-3 accent-[#25D366] w-4 h-4" ${index === 0 ? 'checked' : ''}>
                    <span class="text-sm text-gray-700 whitespace-pre-line leading-relaxed group-hover:text-gray-900">${displayText}</span>
                </label>
            `;
        });

        // Modal show karein
        document.getElementById('whatsappModal').classList.remove('hidden');
    }

    // 3. Modal Close karne ka function
    function closeWhatsAppModal() {
        document.getElementById('whatsappModal').classList.add('hidden');
    }

    // 4. Selected Message ko WhatsApp par bhejne ka function
   /* function sendSelectedMessage() {
        const selectedRadio = document.querySelector('input[name="wa_message"]:checked');

        if (!selectedRadio) {
            alert("Kripya ek message select karein!");
            return;
        }

        const selectedIndex = selectedRadio.value;
        let finalMessage = messageTemplates[selectedIndex].text.replace('[LINK]', shareUrl);

        // WhatsApp requires an international number containing digits only.
        let whatsappNumber = String(currentMobile).trim().replace(/\D/g, '');
        if (whatsappNumber.startsWith('00')) {
            whatsappNumber = whatsappNumber.substring(2);
        }
        if (whatsappNumber.length === 10) {
            whatsappNumber = `91${whatsappNumber}`;
        }

        if (!whatsappNumber) {
            alert('Mobile number is not available.');
            return;
        }

        const url = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(finalMessage)}`;

        // Same user gesture me redirect karne se popup blocker interfere nahi karta.
        closeWhatsAppModal();
        window.location.href = url;
    }*/

    function sendSelectedMessage() {
    const selectedRadio = document.querySelector('input[name="wa_message"]:checked');

        if (!selectedRadio) {
            alert("Kripya ek message select karein!");
            return;
        }

        const selectedIndex = selectedRadio.value;
        let finalMessage = messageTemplates[selectedIndex].text.replace('[LINK]', shareUrl);

        // Modal close
        closeWhatsAppModal();

        // WhatsApp contact list open hogi (normal share)
        window.location.href = `https://wa.me/?text=${encodeURIComponent(finalMessage)}`;
    }

    // Modal ke bahar click karne par band ho jaye
    document.getElementById('whatsappModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeWhatsAppModal();
        }
    });
</script>
