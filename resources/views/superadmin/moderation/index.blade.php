@extends('superadmin.layouts.app')
@section('title', 'Moderation')
@section('content')

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Content Moderation</h1>
                <p>Flagged questions, quizzes, and comments awaiting a decision.</p>
            </div>
            <span class="badge danger" style="padding:8px 14px;font-size:11px;">{{ $queue->count() }} pending</span>
        </div>

        <div class="split-panel">
            <div>
                @forelse($queue as $item)
                    <a href="{{ route('superadmin.moderation.index', ['item' => $item->id]) }}"
                        style="text-decoration:none;color:inherit;">
                        <div class="queue-item {{ $active && $active->id === $item->id ? 'active' : '' }}">
                            <h5>{{ ucfirst($item->item_type) }} · "{{ str($item->title)->limit(50) }}"</h5>
                            <p>Flagged {{ $item->flagged_count }}x · Reason: {{ $item->reason }}
                                @if ($item->ai_score)
                                    · AI score: {{ $item->ai_score }}
                                @endif
                            </p>
                        </div>
                    </a>
                @empty
                    <p style="color:var(--ink-faint);font-size:12px;">Nothing in the moderation queue. 🎉</p>
                @endforelse
            </div>

            @if ($active)
                <div class="card">
                    <h3>Review — {{ str($active->title)->limit(50) }}</h3>
                    <form method="POST" action="{{ route('superadmin.moderation.decide', $active) }}">
                        @csrf
                        <div class="form-grid">
                            <div class="field full"><label>Reporter Comments</label>
                                <textarea disabled>{{ $active->reporter_comment }}</textarea>
                            </div>
                            <div class="field"><label>Decision <span class="req">*</span></label>
                                <select name="decision" required>
                                    <option value="approve">Approve as-is</option>
                                    <option value="edit_republish">Edit and republish</option>
                                    <option value="remove">Remove question</option>
                                    <option value="dismiss">Dismiss report (no issue found)</option>
                                </select>
                            </div>
                            <div class="field"><label>Notify Content Creator</label>
                                <select name="notify_creator">
                                    <option value="1">Yes, send explanation</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="field full"><label>Correct the Answer Key <span class="hint">(if
                                        editing)</span></label><input type="text" name="corrected_answer"></div>
                            <div class="field full"><label>Internal Reason <span class="req">*</span> <span
                                        class="hint">— logged to audit trail</span></label>
                                <textarea name="internal_reason" required></textarea>
                            </div>
                        </div>
                        <div class="form-foot"><button class="btn btn-primary" type="submit">Submit Decision</button></div>
                    </form>
                </div>
            @endif
        </div>
    </div>

@endsection
