<!-- Question Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-backdrop" onclick="closeImportModal()"></div>
    <div class="modal-content form-card" style="margin-bottom:0; max-width:700px;">
        <div class="fh">
            <div>
                <h3 style="display:inline-block; margin-right: 10px;">Import Questions</h3>
                <span>Upload Excel, CSV, or SQL file to bulk import questions</span>
            </div>
            <button type="button" class="icon-btn" onclick="closeImportModal()"
                style="border:none;background:transparent;box-shadow:none;">✕</button>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('import_summary'))
            <div style="margin-bottom:20px; padding:16px; background:#ecfdf5; border:1px solid #10b981; border-radius:8px;">
                <h4 style="color:#065f46; margin-bottom:12px;">✅ Import Summary</h4>
                <p><strong>Total Rows:</strong> {{ session('import_summary.total') }}</p>
                <p><strong>Imported:</strong> {{ session('import_summary.imported') }}</p>
                <p><strong>Skipped:</strong> {{ session('import_summary.skipped') }}</p>
                
                @if(session('import_summary.errors') && count(session('import_summary.errors')) > 0)
                    <div style="margin-top:12px;">
                        <strong>Errors:</strong>
                        <ul style="margin:8px 0; padding-left:20px;">
                            @foreach(session('import_summary.errors') as $error)
                                <li style="color:#dc2626;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div style="margin-bottom:20px; padding:16px; background:#fef2f2; border:1px solid #ef4444; border-radius:8px; color:#dc2626;">
                ❌ {{ session('error') }}
            </div>
        @endif

        {{-- Download Sample --}}
        <div style="margin-bottom:20px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <div>
                    <h4 style="margin:0 0 4px 0; color:#1e293b;">📥 Download Sample File</h4>
                    <p style="margin:0; color:#64748b; font-size:13px;">Get a CSV template with example questions</p>
                </div>
                <a href="{{ route('superadmin.questions.downloadSample') }}" class="btn btn-primary" style="white-space:nowrap;">
                    Download Sample
                </a>
            </div>
        </div>

        {{-- Upload Form --}}
        <form method="POST" action="{{ route('superadmin.questions.import') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-grid form-grid-02">
                <div class="field full">
                    <label>Upload Excel/CSV/SQL File <span class="req">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,.sql" required>
                    <small style="color:#64748b; margin-top:8px; display:block;">
                        Supported formats: Excel (.xlsx, .xls), CSV, or SQL INSERT statements for questions. Max size: 10MB.
                        SQL imports accept <code>mcq</code> questions with 4 options and
                        <code>fastest_finger_first</code> questions with 2 options.
                    </small>
                </div>
            </div>

            <div class="form-foot">
                <button type="button" class="btn btn-ghost" onclick="closeImportModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    🚀 Import Questions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportModal() {
    document.getElementById('importModal').classList.add('active');
}

function closeImportModal() {
    document.getElementById('importModal').classList.remove('active');
}

// Auto-open modal if there's import summary or error
document.addEventListener('DOMContentLoaded', function() {
    @if(session('import_summary') || session('error'))
        openImportModal();
    @endif
});
</script>
