<!-- ========================================== -->
<!-- COPY QUIZ MODAL                            -->
<!-- ========================================== -->
<div id="copyQuizModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div style="background: #fff; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden;">
        
        <!-- Header -->
        <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #1e293b;">📋 Duplicate Quiz</h3>
            <button type="button" onclick="closeCopyModal()" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer;">&times;</button>
        </div>
        
        <!-- Body -->
        <div style="padding: 24px;">
            <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">
                You are duplicating: <strong id="copyQuizTitle" style="color: #0f172a;"></strong><br>
                Please select a <strong>new schedule time</strong> for this copy.
            </p>

            <!-- ✅ Hidden Inputs (ID aur Action URL dono) -->
            <input type="hidden" id="copyQuizId">
            <input type="hidden" id="copyQuizActionUrl">

            <!-- Error Message Container -->
            <div id="copyQuizError" style="display: none; margin-bottom: 16px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; color: #991b1b; font-size: 13px;">
                <span id="copyQuizErrorMessage"></span>
            </div>

            <div style="margin-bottom: 0;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #334155;">New Schedule Date & Time <span style="color: red;">*</span></label>
                <input type="datetime-local" id="new_scheduled_at" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc;">
            <button class="btn btn-ghost" type="button" onclick="closeCopyModal()" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
            <button class="btn btn-primary" type="button" id="confirmCopyBtn" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; min-width: 120px;">
                <span id="copyBtnText">Duplicate Quiz</span>
                <span id="copyBtnLoader" style="display: none;">
                    <span style="display:inline-block;width:14px;height:14px;border:2px solid white;border-right-color:transparent;border-radius:50%;animation:spin 0.75s linear infinite;margin-right:8px;vertical-align:-2px;"></span>
                    Processing...
                </span>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

@push('scripts')
<script>
    // 1. Modal Open/Close Functions
    function openCopyModal(quizId, quizTitle, actionUrl) {
        document.getElementById('copyQuizModal').style.display = 'flex';
        document.getElementById('copyQuizId').value = quizId;
        document.getElementById('copyQuizActionUrl').value = actionUrl; // ✅ URL set karein
        document.getElementById('copyQuizTitle').textContent = quizTitle;
        document.getElementById('copyQuizError').style.display = 'none';
        document.getElementById('new_scheduled_at').value = '';
    }

    function closeCopyModal() {
        document.getElementById('copyQuizModal').style.display = 'none';
    }

    // 2. Copy Button Click Event Listener
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-copy-quiz');
        if (btn) {
            const quizId = btn.getAttribute('data-id');
            const quizTitle = btn.getAttribute('data-title');
            const actionUrl = btn.getAttribute('data-url'); // ✅ Route URL yahan se aayega
            
            openCopyModal(quizId, quizTitle, actionUrl);
        }
    });

    // 3. AJAX Submit using Fetch API
    document.getElementById('confirmCopyBtn').addEventListener('click', function() {
        const actionUrl = document.getElementById('copyQuizActionUrl').value; // ✅ Dynamic URL use karein
        const newScheduledAt = document.getElementById('new_scheduled_at').value;
        const errorBox = document.getElementById('copyQuizError');
        const errorMsg = document.getElementById('copyQuizErrorMessage');

        // Validation
        if (!newScheduledAt) {
            errorMsg.textContent = 'Please select a valid schedule time.';
            errorBox.style.display = 'block';
            return;
        }

        // Show Loader
        document.getElementById('copyBtnText').style.display = 'none';
        document.getElementById('copyBtnLoader').style.display = 'inline-block';
        this.disabled = true;
        errorBox.style.display = 'none';

        // CSRF Token safe check (Fallback ke sath)
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? 
                          document.querySelector('meta[name="csrf-token"]').getAttribute('content') : 
                          '{{ csrf_token() }}';

        // Fetch API Call using Dynamic Route URL
        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                new_scheduled_at: newScheduledAt
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errorMsg.textContent = data.message || 'Something went wrong.';
                errorBox.style.display = 'block';
                resetButton();
            }
        })
        .catch(error => {
            errorMsg.textContent = 'Network error. Please check your connection.';
            errorBox.style.display = 'block';
            resetButton();
        });

        function resetButton() {
            document.getElementById('copyBtnText').style.display = 'inline-block';
            document.getElementById('copyBtnLoader').style.display = 'none';
            document.getElementById('confirmCopyBtn').disabled = false;
        }
    });
</script>
@endpush