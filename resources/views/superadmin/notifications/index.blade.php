@extends('superadmin.layouts.app')
@section('title', 'Notifications')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
    @push('styles')
        <style>

            .form-grid-02 .field label{
                display: flex;
                align-items: center;
                white-space: nowrap;
            }


            .campaign-status-change {
                min-width: 140px;
                padding: 8px 12px;
                border: 1px solid #dcdfe6;
                border-radius: 8px;
                background: #fff;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .campaign-status-change:focus {
                outline: none;
                border-color: #4F46E5;
            }
        </style>
    @endpush

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Notifications</h1>
                <p>Compose and schedule multi-channel campaigns.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Channels</th>
                        <th>Segment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>{{ implode(', ', $c->channels ?? []) }}</td>
                            <td>{{ $c->segment }}</td>
                            {{-- <td>
            @php $stClass = ['draft'=>'neutral','scheduled'=>'warn','sent'=>'success'][$c->status] ?? 'neutral'; @endphp
            <span class="badge {{ $stClass }}">{{ ucfirst($c->status) }}</span>
          </td> --}}
                            <td>
                                <select class="campaign-status-change" data-id="{{ $c->id }}">
                                    <option value="draft" {{ $c->status == 'draft' ? 'selected' : '' }}>
                                        Draft
                                    </option>

                                    <option value="scheduled" {{ $c->status == 'scheduled' ? 'selected' : '' }}>
                                        Scheduled
                                    </option>

                                    <option value="sent" {{ $c->status == 'sent' ? 'selected' : '' }}>
                                        Sent
                                    </option>
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:var(--ink-faint);">No campaigns yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
             <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $campaigns])
            </div> 
        </div>

        <div class="form-card">
            <div class="fh">
                <h3>New Campaign</h3>
            </div>
            <form method="POST" action="{{ route('superadmin.notifications.store') }}">
                @csrf
                <div class="form-grid form-grid-02">
                    <div class="field"><label>Campaign Name <span class="req">*</span></label><input type="text"
                            name="name" required></div>
                    <div class="field"><label>Channels <span class="req">*</span></label>
                        <div class="tag-pills">
                            @foreach (['Push', 'Email', 'SMS', 'WhatsApp', 'In-App'] as $ch)
                                <label class="tag-pill" style="cursor:pointer;"><input type="checkbox" name="channels[]"
                                        value="{{ $ch }}" style="margin-right:4px;">{{ $ch }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field"><label>Target Segment <span class="req">*</span></label>
                        <select name="segment" required>
                            @foreach (['All Users', 'Free Users', 'Silver Subscribers', 'Gold Subscribers', 'Inactive 7 Days', 'Inactive 14 Days', 'Inactive 30 Days'] as $seg)
                                <option>{{ $seg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Schedule <span class="req">*</span></label>
                        <select name="schedule_type" required>
                            <option value="now">Send Now</option>
                            <option value="later">Schedule for Later</option>
                        </select>
                    </div>
                    <div class="field"><label>Schedule Date &amp; Time <span class="hint">(if Later)</span></label><input
                            type="datetime-local" name="scheduled_at"></div>
                    <div class="field full"><label>Title <span class="req">*</span></label><input type="text"
                            name="title" required></div>
                    <div class="field full"><label>Message Body <span class="req">*</span></label>
                        <textarea name="message" placeholder="Keep it under 120 characters for push notifications…" required></textarea>
                    </div>
                    <div class="field full"><label>Deep Link <span class="hint">(optional)</span></label><input
                            type="text" name="deep_link" placeholder="quizhub://home/daily-quiz"></div>
                </div>
                <div class="form-foot"><button class="btn btn-primary" type="submit">Schedule Campaign</button></div>
            </form>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('change', function(e) {

            if (!e.target.classList.contains('campaign-status-change')) {
                return;
            }

            fetch("{{ route('superadmin.notifications.changeStatus') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: e.target.dataset.id,
                        status: e.target.value
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if (data.success) {

                        showAlert(
                            'success',
                            'Success',
                            'Campaign status updated successfully.'
                        );

                    } else {

                        showAlert(
                            'warning',
                            'Warning',
                            'Unable to update campaign status.'
                        );

                    }

                })
                .catch(() => {

                    showAlert(
                        'error',
                        'Error',
                        'Something went wrong.'
                    );

                });

        });
    </script>
@endpush
