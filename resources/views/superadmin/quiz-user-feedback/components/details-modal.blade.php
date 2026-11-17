<style>
    .admin-timeline-box{
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    @media(max-width:575px){
        .admin-timeline-box {
            grid-template-columns: repeat(1, 1fr);
        }
    }


</style>

<!-- Feedback Details Modal -->
<div class="modal fade" id="feedbackDetailsModal" tabindex="-1" role="dialog" aria-labelledby="feedbackDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-height: 90vh; display: flex; flex-direction: column; border: none;">
            <!-- ✅ Improved Modal Header (Perfect Corner Alignment) -->
            <div class="modal-header" style="background: #FBFAFF); color: var(--ink); border-radius: 12px 12px 0 0; padding: 18px 24px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;     border-bottom: 1px solid var(--line);">
                <h5 class="modal-title" id="feedbackDetailsModalLabel" style="margin: 0; font-weight: 600; font-size: 1.1rem; letter-spacing: 0.5px;">
                    📋 Feedback Details
                </h5>
                <button type="button" class="close-modal-btn" data-modal-id="feedbackDetailsModal" 
                        style="background: rgba(255,255,255,0.2); border: none;color: var(--ink-faint); font-size: 24px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; line-height: 1;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'" 
                        onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <span aria-hidden="true" style="font-weight: 300;">&times;</span>
                </button>
            </div>

            <!-- Modal Body - Scrollable -->
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex: 1; border-radius: 0 0 12px 12px;">
                
                <!-- User & Status Info -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <div>
                        <strong style="    color: var(--ink-faint); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">User:</strong>
                        <p id="modalUser" style="margin: 4px 0 0 0; font-weight: 600; color: #333; font-size: 12px;"></p>
                    </div>
                    <div style="text-align: right;">
                        <strong style="color: var(--ink-faint); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Status:</strong>
                        <p id="modalStatus" style="margin: 4px 0 0 0; font-weight: 600; color: #333; font-size: 12px;"></p>
                    </div>
                </div>

                <div class="feedback-info-box">
                <!-- Subject -->
                <div style="margin-bottom: 20px;">
                    <h6 style="color: var(--ink-faint); font-weight: 600; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">📌 Subject</h6>
                    <div id="modalSubject" style="border: 1px solid var(--line); border-radius: 9px; padding: 9px 11px; font-family: var(--font-body); font-size: 12px; background: var(--paper);color: var(--ink);"></div>
                </div>

                <!-- User Message -->
                <div style="margin-bottom: 20px;">
                    <h6 style="color: var(--ink-faint); font-weight: 600; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">💬 User Message</h6>
                    <div id="modalMessage" style="border: 1px solid var(--line); border-radius: 9px; padding: 9px 11px; font-family: var(--font-body); font-size: 12px; background: var(--paper);color: var(--ink);"></div>
                </div>
                </div>

                <!-- Admin Actions Timeline -->
                <div style="margin-top: 25px;">
                    <h6 style="color: #666; font-weight: 600; margin-bottom: 15px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                        📝 Admin Actions Timeline
                    </h6>

                <div class="admin-timeline-box">
                    <!-- Reviewed -->
                    <div style="padding: 15px; background: #f0f7ff; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; flex-wrap:wrap; gap:10px;">
                            <strong style="color: #2563eb; font-size: 12px; text-transform: uppercase;">1️⃣ Reviewed By</strong>
                            <span id="modalReviewBy" style="color: #666; font-size: 12px; font-style: italic;"></span>
                        </div>
                        <p id="modalReviewNote" style="margin: 0; color: #333; font-size: 12px; line-height: 1.5;"></p>
                    </div>

                    <!-- Replied -->
                    <div style="padding: 15px; background: #faf0ff; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; flex-wrap:wrap; gap:10px;">
                            <strong style="color: #9333ea; font-size: 12px; text-transform: uppercase;">2️⃣ Replied By</strong>
                            <span id="modalReplyBy" style="color: #666; font-size: 12px; font-style: italic;"></span>
                        </div>
                        <p id="modalReplyMsg" style="margin: 0; color: #333; font-size: 12px; line-height: 1.5; white-space: pre-wrap;"></p>
                    </div>

                    <!-- Closed -->
                    <div style="padding: 15px; background: #fff0f0; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; flex-wrap:wrap; gap:10px;">
                            <strong style="color: #dc2626; font-size: 12px; text-transform: uppercase;">3️⃣ Closed By</strong>
                            <span id="modalCloseBy" style="color: #666; font-size: 12px; font-style: italic;"></span>
                        </div>
                        <p id="modalCloseNote" style="margin: 0; color: #333; font-size: 12px; line-height: 1.5;"></p>
                    </div>
                </div>
                </div>
            </div>

            <!-- ✅ Footer Removed as requested -->
        </div>
    </div>
</div>

<style>
    /* Modal Overlay */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }
    
    .modal-backdrop.show {
        opacity: 1;
    }
    
    /* Modal Container */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1050;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
    }
    
    .modal.show {
        display: block;
    }
    
    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
        transform: translate(0, -50px);
    }
    
    .modal.show .modal-dialog {
        transform: none;
    }
    
    /* Modal Dialog */
    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 800px;
        pointer-events: none;
    }
    
    .modal-dialog-centered {
        display: flex;
        align-items: center;
        min-height: calc(100% - 3.5rem);
    }
    
    /* Modal Content */
    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        background-color: #fff;
        background-clip: padding-box;
        outline: 0;
        pointer-events: auto;
    }
    
    /* Custom Scrollbar */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>