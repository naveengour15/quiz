@extends('superadmin.layouts.app')

@section('title', 'Redeem & Voucher Settings')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@push('styles')
<style>
    /* Reusing your existing styles for perfect consistency */
    .form-grid-box { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .form-card { background: var(--paper, #ffffff); border: 1px solid var(--line, #e2e8f0); border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .fh { margin-bottom: 16px; border-bottom: 1px solid var(--line, #e2e8f0); padding-bottom: 12px; }
    .fh h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--ink, #0f172a); }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label { font-size: 13px; font-weight: 600; color: var(--ink, #334155); }
    .field input, .field select { padding: 10px 12px; border: 1px solid var(--line, #cbd5e1); border-radius: 8px; font-size: 14px; transition: all 0.2s; background: var(--paper, #fff); color: var(--ink, #0f172a); }
    .field input:focus, .field select:focus { outline: none; border-color: var(--indigo, #6366f1); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .form-foot { margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px; }
    .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary { background: var(--indigo, #6366f1); color: white; }
    .btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
    .btn-ghost { background: transparent; color: var(--ink-soft, #64748b); border: 1px solid var(--line, #e2e8f0); }
    .btn-ghost:hover { background: #f8fafc; color: var(--ink, #0f172a); }

    /* Reusing your Toggle Switch CSS */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        cursor: pointer;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1; border-radius: 24px; transition: 0.4s ease;
    }
    .toggle-slider:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background-color: white; border-radius: 50%;
        transition: 0.4s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .day-toggle-checkbox:checked + .toggle-slider { background-color: #10b981 !important; }
    .day-toggle-checkbox:checked + .toggle-slider:before { transform: translateX(20px); }

    .setting-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 10px; transition: all 0.2s ease;
    }
    .setting-row:hover { border-color: var(--indigo, #6366f1); background: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.06); }
    .setting-info { flex: 1; }
    .setting-info .lbl { font-size: 14px; font-weight: 700; color: var(--ink, #0f172a); margin-bottom: 4px; }
    .setting-info .desc { font-size: 12px; color: var(--ink-soft, #64748b); }
    .setting-actions { display: flex; align-items: center; gap: 20px; }
    .limit-input { width: 120px !important; text-align: center; }

    @media (max-width: 768px) {
        .form-grid-box { grid-template-columns: 1fr; }
        .setting-row { flex-direction: column; align-items: flex-start; gap: 16px; }
        .setting-actions { width: 100%; justify-content: space-between; }
        .limit-input { width: 100% !important; }
    }
</style>
@endpush

@section('content')
<div class="panel active">
    <!-- Page Head with Back Button -->
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 16px;">
        <div>
            <h1>⚙️ Redeem & Voucher Settings</h1>
            <p style="color: var(--ink-soft, #64748b); font-size: 13px; margin-top: 4px;">Configure limits and activation status for recharges and vouchers.</p>
        </div>

        <!-- ✅ Back Button to Redeem Off Days -->
        <a href="{{ route('superadmin.redeem-off-days.index') }}" class="btn btn-ghost">
            ← Back to Redeem Off Days
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; border: 1px solid #bbf7d0;">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- ========== MAIN SETTINGS FORM ========== --}}
    <div class="form-card">
        <div class="fh">
            <h3>📊 Limit Configurations</h3>
            <p style="font-size: 12px; color: var(--ink-soft); margin-top: 4px;">
                Set maximum amounts and toggle rules ON/OFF. Inactive rules will be ignored by the system.
            </p>
        </div>

        <form method="POST" action="{{ route('superadmin.redeem-voucher-settings.update') }}">
            @csrf

            <div class="form-grid-box">
                @foreach($settings as $setting)
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="lbl">{{ ucwords(str_replace('_', ' ', $setting->type)) }}</div>
                            <div class="desc">Key: <code style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:11px;">{{ $setting->type }}</code></div>
                        </div>
                        
                        <div class="setting-actions">
                            <!-- Limit Value Input -->
                            <div class="field" style="margin: 0;">
                                <input type="number" 
                                       name="settings[{{ $setting->type }}][limit_value]" 
                                       class="limit-input" 
                                       value="{{ old('settings.' . $setting->type . '.limit_value', $setting->limit_value) }}" 
                                       min="0" 
                                       required>
                            </div>

                            <!-- Hidden input for Type -->
                            <input type="hidden" name="settings[{{ $setting->type }}][type]" value="{{ $setting->type }}">

                            <!-- Hidden input for Inactive state (default if unchecked) -->
                            <input type="hidden" name="settings[{{ $setting->type }}][status]" value="inactive">

                            <!-- Active/Inactive Toggle Switch -->
                            <label class="toggle-switch" title="Toggle to activate/deactivate this rule">
                                <input type="checkbox" 
                                       name="settings[{{ $setting->type }}][status]" 
                                       value="active" 
                                       {{ $setting->status == 'active' ? 'checked' : '' }}
                                       class="day-toggle-checkbox">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="form-foot">
                <a href="{{ route('superadmin.redeem-off-days.index') }}" class="btn btn-ghost">Cancel</a>
                <button class="btn btn-primary" type="submit">💾 Save All Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection