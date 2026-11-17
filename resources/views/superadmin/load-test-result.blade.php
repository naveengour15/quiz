@extends('superadmin.layouts.app')
@section('title', 'Load Test Result')
@section('content')
<div class="panel active settings-wrapper" style="padding: 24px;">
    <div class="settings-page-head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 700;">Load Test Execution Result</h1>
            <p style="margin: 6px 0 0; color: var(--ink-faint); font-size: 13px;">View console output and summary of the quiz load test execution.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            @if(!empty($downloadUrl))
                <a href="{{ $downloadUrl }}" download class="btn btn-primary" style="text-decoration: none; padding: 10px 16px; border-radius: 8px;">
                    <i class="fas fa-download"></i> Download Report (JSON)
                </a>
            @endif
            <a href="{{ route('superadmin.settings.index') }}" class="btn btn-secondary" style="text-decoration: none; padding: 10px 16px; border-radius: 8px; background: #6c757d; color: #fff;">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>
    </div>

    <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; color: {{ ($success ?? true) ? '#166534' : '#991b1b' }}; background: {{ ($success ?? true) ? '#dcfce7' : '#fee2e2' }};">
        {{ ($success ?? true) ? 'Load test completed successfully.' : 'Load test failed. Review the output below.' }}
    </div>

    <div class="form-card" style="background: #1e1e2f; color: #dcdcdc; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 15px; border-bottom: 1px solid #333; padding-bottom: 10px;">
            <i class="fas fa-terminal" style="color: #4ade80;"></i> Console Output:
        </h4>
        <pre style="white-space: pre-wrap; word-break: break-all; font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #e5e7eb; margin: 0; max-height: 600px; overflow-y: auto;">{{ $output }}</pre>
    </div>
</div>
@endsection
