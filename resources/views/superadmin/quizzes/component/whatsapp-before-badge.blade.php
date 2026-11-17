{{-- WhatsApp Modal - Forced with !important --}}
<div id="wa-modal" style="display:none; position:fixed !important; top:0 !important; left:0 !important; width:100vw !important; height:100vh !important; z-index:999999 !important; overflow:hidden;">
    <div style="position:absolute !important; top:0 !important; left:0 !important; width:100% !important; height:100% !important; background:rgba(0,0,0,0.6) !important; backdrop-filter:blur(4px) !important;" onclick="closeWAModal()"></div>
    <div style="position:absolute !important; top:50% !important; left:50% !important; transform:translate(-50%,-50%) !important; width:100% !important; max-width:450px !important; padding:16px !important;">
        <div style="background:white !important; border-radius:16px !important; box-shadow:0 20px 60px rgba(0,0,0,0.3) !important; overflow:hidden !important;">
            
            {{-- Header --}}
            <div style="display:flex !important; align-items:center !important; justify-content:space-between !important; padding:16px 20px !important; background:linear-gradient(135deg,#22c55e,#059669) !important;">
                <h3 style="margin:0 !important; color:white !important; font-size:18px !important; font-weight:700 !important;">WhatsApp Reminder</h3>
                <button onclick="closeWAModal()" style="background:none !important; border:none !important; color:white !important; font-size:22px !important; cursor:pointer !important; padding:0 !important; line-height:1 !important;">&times;</button>
            </div>
            
            
            {{-- Body --}}
            <div style="padding:24px !important;">
                <p style="margin:0 0 8px 0 !important; font-size:14px !important; color:#4b5563 !important; text-align:center !important;">
                    Quiz shuru hone se kitne minute pehle WhatsApp message bhejna hai?
                </p>
                
            
                {{-- Scheduled Time Display --}}
                <div style="background:#f0fdf4 !important; border:1px solid #bbf7d0 !important; border-radius:8px !important; padding:10px !important; margin-bottom:16px !important; text-align:center !important;">
                    <span id="wa-modal-title-display" style="font-weight:800 !important; display:block !important; margin-top:4px !important; font-size:14px !important; color:#1e3a8a !important; word-break:break-word !important; line-height:1.4 !important;">--</span>
                    </span>
                    <span style="font-size:12px !important; color:#166534 !important; font-weight:600 !important;">
                        📅 Quiz Scheduled at: <span id="wa-modal-schedule-display" style="font-weight:800 !important;">--</span>
                    </span>
                </div>

                <form onsubmit="updateWATime(event)">
                    @csrf
                    <input type="hidden" id="wa-quiz-id">
                    <input type="hidden" id="wa-scheduled-iso">
                    
                    {{-- ✅ CHANGE: min="20" kar diya hai --}}
                    <input type="number" id="wa-minutes" min="10" max="1440" required
                        style="width:100% !important; padding:12px 16px !important; border:2px solid #e5e7eb !important; border-radius:10px !important; text-align:center !important; font-size:24px !important; font-weight:700 !important; outline:none !important; box-sizing:border-box !important;" 
                        placeholder="30" onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#e5e7eb'">
                    
                    <p style="margin:8px 0 16px 0 !important; font-size:12px !important; color:#dc2626 !important; text-align:center !important; font-weight:600 !important;">
                        ⚠️ Minimum 10 minutes pehle ka time set karna zaroori hai.
                    </p>

                    <div style="display:flex !important; gap:12px !important; margin-top:16px !important;">
                        <button type="button" onclick="closeWAModal()" 
                            style="flex:1 !important; padding:12px !important; border:2px solid #e5e7eb !important; background:white !important; color:#374151 !important; font-weight:600 !important; border-radius:10px !important; cursor:pointer !important; font-size:14px !important;">
                            Cancel
                        </button>
                        <button type="submit" id="wa-submit-btn"
                            style="flex:1 !important; padding:12px !important; border:none !important; background:linear-gradient(135deg,#22c55e,#059669) !important; color:white !important; font-weight:600 !important; border-radius:10px !important; cursor:pointer !important; font-size:14px !important;">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ Route name ko placeholder ke sath define karein
const waUpdateRoute = "{{ route('superadmin.quizzes.update-whatsapp-before', ':id') }}";

window.openWhatsAppModal = function(quizId, minutes, title, scheduledIso) {
    document.getElementById('wa-quiz-id').value = quizId;
    document.getElementById('wa-minutes').value = minutes;
    document.getElementById('wa-scheduled-iso').value = scheduledIso;
    // ✅ NEW: Set Quiz Title in Modal
    document.getElementById('wa-modal-title-display').innerText = title || 'Unknown Quiz';
    
    
    // Format and show scheduled time in modal
    if (scheduledIso) {
        const dateObj = new Date(scheduledIso);
        const formattedDate = dateObj.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        const formattedTime = dateObj.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('wa-modal-schedule-display').innerText = formattedDate + ', ' + formattedTime;
    }
    
    document.getElementById('wa-modal').style.display = 'block';
};

window.closeWAModal = function() {
    document.getElementById('wa-modal').style.display = 'none';
};

window.updateWATime = function(e) {
    e.preventDefault();
    const quizId = document.getElementById('wa-quiz-id').value;
    const minutes = parseInt(document.getElementById('wa-minutes').value);
    const btn = document.getElementById('wa-submit-btn');
    
    // ✅ STRICT VALIDATION: 20 minute se kam allow nahi hoga
    if (isNaN(minutes) || minutes < 10) {
        alert('⚠️ WhatsApp message kam se kam 10 minute pehle schedule karna zaroori hai!');
        return; // Function yahin ruk jayega, submit nahi hoga
    }
    
    btn.innerHTML = 'Updating...';
    btn.disabled = true;
    
    // Yahan placeholder ko actual quiz ID se replace kar rahe hain
    const finalUrl = waUpdateRoute.replace(':id', quizId);

    fetch(finalUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: JSON.stringify({ whatsapp_message_sent_before_minutes: minutes })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error!');
            btn.innerHTML = 'Update';
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error!');
        btn.innerHTML = 'Update';
        btn.disabled = false;
    });
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') window.closeWAModal();
});
</script>