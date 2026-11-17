@extends('superadmin.layouts.app')

@section('title', 'App Update (APK)')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>
    /* Exact same styling as Quiz Settings for perfect consistency */
    .form-grid-box-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .form-card { background: var(--paper, #ffffff); border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .fh { margin-bottom: 16px; border-bottom: 1px solid var(--line, #e2e8f0); padding-bottom: 12px; }
    .fh h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--ink, #0f172a); }
    
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label { font-size: 13px; font-weight: 600; color: var(--ink, #334155); }
    .field input, .field textarea { 
        padding: 10px 12px; 
        border: 1px solid var(--line, #cbd5e1); 
        border-radius: 8px; 
        font-size: 14px; 
        transition: all 0.2s; 
        background: var(--paper, #fff); 
        color: var(--ink, #0f172a); 
        width: 100%;
        box-sizing: border-box;
    }
    .field input:focus, .field textarea:focus { 
        outline: none; 
        border-color: var(--indigo, #6366f1); 
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); 
    }
    
    .form-foot { margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px; }
    .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary { background: var(--indigo, #6366f1); color: white; }
    .btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
    .req { color: #ef4444; }
    
    /* Info Card for Current Version Display */
    .info-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; }
    .info-card h3 { color: #166534; font-size: 15px; font-weight: 700; margin: 0 0 12px 0; }
    .info-card p { font-size: 14px; color: #15803d; margin-bottom: 8px; line-height: 1.5; }
    .info-card a { color: #2563eb; text-decoration: underline; word-break: break-all; }
    .info-card .notes { font-size: 13px; color: #166534; background: #dcfce7; padding: 12px; border-radius: 8px; margin-top: 12px; line-height: 1.5; }

    @media (max-width: 768px) {
        .form-grid-box-2 { grid-template-columns: 1fr; }
        .field[style*="grid-column: span 2"] { grid-column: span 1 !important; }
    }

        /* ========================================== */
    /* ✅ NEW: Compact Status Toggle Card & Toast */
    /* ========================================== */
    
    .status-toggle-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .status-label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }
    .status-action {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .status-badge {
        font-size: 12px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s;
    }
    .badge-active { background: #dcfce7; color: #166534; }
    .badge-inactive { background: #fee2e2; color: #991b1b; }

    .toggle-switch { 
        position: relative; 
        display: inline-block; 
        width: 44px; 
        height: 24px; 
        cursor: pointer; 
        vertical-align: middle; 
    }
    .toggle-switch input { 
        opacity: 0; 
        width: 0; 
        height: 0; 
    }
    .toggle-slider { 
        position: absolute; 
        top: 0; left: 0; right: 0; bottom: 0; 
        background-color: #cbd5e1; 
        border-radius: 24px; 
        transition: 0.4s ease; 
    }
    .toggle-slider:before { 
        position: absolute; 
        content: ""; 
        height: 18px; 
        width: 18px; 
        left: 3px; 
        bottom: 3px; 
        background-color: white; 
        border-radius: 50%; 
        transition: 0.4s ease; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.2); 
    }
    .toggle-switch input:checked + .toggle-slider { 
        background-color: #10b981 !important; 
    }
    .toggle-switch input:checked + .toggle-slider:before { 
        transform: translateX(20px); 
    }
    .toggle-switch input:disabled + .toggle-slider { 
        opacity: 0.5; 
        cursor: not-allowed; 
    }

    .ajax-toast { 
        position: fixed; 
        bottom: 24px; 
        right: 24px; 
        padding: 12px 20px; 
        border-radius: 8px; 
        font-size: 14px; 
        font-weight: 600; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        z-index: 9999; 
        transition: all 0.3s; 
        opacity: 0; 
        transform: translateY(20px); 
        color: white; 
    }
    .ajax-toast.show { 
        opacity: 1; 
        transform: translateY(0); 
    }
    .ajax-toast.success { 
        background: #10b981; 
    }
    .ajax-toast.error { 
        background: #ef4444; 
    }

    @media (max-width: 768px) {
        .status-toggle-card { 
            flex-direction: column; 
            align-items: flex-start; 
            gap: 12px; 
        }
        .status-action { 
            width: 100%; 
            justify-content: space-between; 
        }
    }
</style>
@endpush

@section('content')
<div class="panel active">
    {{-- Page Header --}}
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 16px;">
        <div>
            <h1>📱 App Update (APK Management)</h1>
            <p style="color: var(--ink-soft, #64748b); font-size: 13px; margin-top: 4px;">Upload new APK. The old file will be automatically deleted and replaced.</p>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; border: 1px solid #bbf7d0;">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if($errors->any())
        <div class="alert alert-danger" style="background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; border: 1px solid #fecaca;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========== SECTION 1: UPLOAD FORM ========== --}}
    <div class="form-card">
        <div class="fh">
            <h3>🚀 Upload New APK</h3>
        </div>
        
        <form method="POST" action="{{ route('superadmin.apk-updates.update') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-grid-box-2">
                <div class="field">
                    <label>App Version <span class="req">*</span></label>
                    <input type="text" name="version" value="{{ old('version', $update->version ?? '1.0.0') }}" required placeholder="e.g., 1.0.2">
                </div>

                <div class="field">
                    <label>Build Number <span class="req">*</span></label>
                    <input type="number" name="build_number" value="{{ old('build_number', $update->build_number ?? 1) }}" required placeholder="e.g., 2 (Must be higher than previous)">
                </div>

                {{-- ✅ NEW: Release Title Field --}}
                <div class="field" style="grid-column: span 2;">
                    <label>Release Title <span class="req">*</span></label>
                    <input type="text" 
                           name="release_title" 
                           value="{{ old('release_title', $update->release_title ?? '') }}" 
                           required 
                           placeholder="e.g., Major Update v2.0, New Features Added">
                </div>

                <div class="field" style="grid-column: span 2;">
                    <label>Release Notes (What's New)</label>
                    <textarea name="release_notes" rows="3" placeholder="e.g., Bug fixes, New redeem feature added...">{{ old('release_notes', $update->release_notes ?? '') }}</textarea>
                </div>

                <div class="field" style="grid-column: span 2;">
                    <label>Upload New APK File <span class="req">*</span></label>
                    <input type="file" name="apk_file" accept=".apk" required style="background: #f8fafc;">
                    <small style="color: var(--ink-soft, #64748b); font-size: 12px; margin-top: 4px; display: block;">
                        ℹ️ Max size: 200MB. File will be saved as <b>app-release.apk</b>. Old file will be permanently deleted.
                    </small>
                </div>
            </div>

            <div class="form-foot">
                <button class="btn btn-primary" type="submit">
                    💾 Upload & Replace APK
                </button>
            </div>
        </form>
    </div>

    {{-- ========== SECTION 2: CURRENT ACTIVE VERSION DETAILS ========== --}}
    @if($update)

     {{-- ✅ NEW: Compact Status Toggle Card (Download se upar) --}}
        <div class="status-toggle-card">
            <div class="status-label">
                <span>📡</span> Update Availability Status
            </div>
            <div class="status-action">
                <label class="toggle-switch">
                    <input type="checkbox" 
                           class="status-toggle-ajax" 
                           data-id="{{ $update->id }}" 
                           {{ ($update->status ?? 'active') === 'active' ? 'checked' : '' }}>
                    <span class="toggle-slider"></span>
                </label>
                <span id="status-text-{{ $update->id }}" class="status-badge {{ ($update->status ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive' }}">
                    {{ ucfirst($update->status ?? 'active') }}
                </span>
            </div>
        </div>
    <div class="info-card">
        <h3>✅ Current Active Version Details</h3>
        <p><strong>Version:</strong> {{ $update->version }} <span style="color: #64748b;">(Build: {{ $update->build_number }})</span></p>
        <p><strong>Download URL:</strong> <br> <a href="{{ $update->apk_url }}" target="_blank">{{ $update->apk_url }}</a></p>
        
        @if($update->release_notes)
            <div class="notes">
                <strong>📝 Release Notes:</strong> {{ $update->release_notes }}
            </div>
        @endif
    </div>
    @endif
</div>
{{-- @endsection --}}
{{-- Toast Container --}}
<div id="ajax-toast" class="ajax-toast"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.status-toggle-ajax');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.dataset.id;
            const newStatus = this.checked ? 'active' : 'inactive';
            const statusText = document.getElementById('status-text-' + id);
            const originalChecked = this.checked;

            // Loading state
            this.disabled = true;

            fetch("{{ route('superadmin.apk-updates.status') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                this.disabled = false;
                if (data.success) {
                    // Update UI text, color, and badge class
                    statusText.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    statusText.className = 'status-badge ' + (newStatus === 'active' ? 'badge-active' : 'badge-inactive');
                    showToast('Status updated to ' + newStatus, 'success');
                } else {
                    // Revert on failure
                    this.checked = originalChecked;
                    showToast('Failed to update status', 'error');
                }
            })
            .catch(error => {
                this.checked = originalChecked;
                this.disabled = false;
                showToast('Network error. Please try again.', 'error');
            });
        });
    });

    function showToast(message, type = 'success') {
        const toast = document.getElementById('ajax-toast');
        toast.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
        toast.className = 'ajax-toast ' + type + ' show';
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});
</script>
@endpush