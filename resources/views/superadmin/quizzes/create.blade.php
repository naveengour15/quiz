@extends('superadmin.layouts.app')
@section('title', $editing ? 'Edit Quiz' : 'Create Quiz')
@section('content')  

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>{{ $editing ? 'Edit Quiz' : 'Create Quiz' }}</h1>
                <p>Assemble from the question bank, then configure access &amp; completion rules.</p>
            </div>
        </div>

        <div class="form-card" style="margin-top: 20px;">

            <form method="POST"
                action="{{ $editing ? route('superadmin.quizzes.update', $editing) : route('superadmin.quizzes.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="form-grid create-form-grid">
                    <div class="field">
                        <label>Quiz Title <span class="req">*</span></label>
                        <input type="text" name="title" value="{{ $editing->title ?? old('title') }}" required>
                    </div>

                    <div class="field">
                        <label>Category</label>
                        <select name="category_id" id="category_id">
                            <option value="">— Select Category —</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('category_id', $editing->category_id ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Sub Category</label>
                        <select name="sub_category_id" id="sub_category_id">
                            <option value="">— Select Sub Category —</option>
                        </select>
                    </div>

                    {{-- <div class="field"><label>Level ID <span class="hint">(optional)</span></label><input type="number" name="level_id" value="{{ $editing->level_id ?? old('level_id') }}"></div> --}}

                    <div class="field">
                        <label>Level <span class="req">*</span></label>
                        <select name="level_id" required>
                            <option value="">— Select Level —</option>
                            @foreach ($Level as $lvl)
                                <option value="{{ $lvl->id }}"
                                    {{ old('level_id', $editing->level_id ?? '') == $lvl->id ? 'selected' : '' }}>
                                    {{ $lvl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Quiz Type <span class="req">*</span></label>
                        <select name="type" required>
                            <option value="">— Select Quiz Type —</option>

                            @foreach ($QuizType as $qt)
                                <option value="{{ $qt->id }}"
                                    {{ old('type', $editing->type ?? '') == $qt->id ? 'selected' : '' }}>
                                    {{ $qt->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- <div class="field full">
                        <label>Questions <span class="req">*</span> <span class="hint">— tick to include in this
                                quiz</span></label>
                        <div class="opt-list"
                            style="max-height:220px;overflow-y:auto;border:1px solid var(--line);border-radius:9px;padding:10px;">
                            @php $selectedIds = $editing?->questions->pluck('id')->toArray() ?? []; @endphp
                            @foreach ($questionBank as $q)
                                <div class="opt-row">
                                    <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                        @checked(in_array($q->id, $selectedIds))>
                                    <span style="font-size:12px;">{{ str($q->question_text)->limit(70) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div> --}}

                    <div class="field full" style="margin-top:6px;">
                        <label style="text-transform:none;font-weight:700;color:var(--ink-soft);font-size:12px;">Quiz Configuration Settings</label>
                    </div>

                    <div class="field">
                        <label>Visibility <span class="req">*</span></label>
                        <select name="visibility" required>
                            <option value="public" @selected(($editing->visibility ?? 'public') === 'public')>Public</option>
                            <option value="private" @selected(($editing->visibility ?? '') === 'private')>Private</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Password Protected <span class="hint">(if Private)</span></label>
                        <input type="text" name="password" value="{{ $editing->password ?? old('password') }}">
                    </div>

                    <div class="field">
                        <label>Retake Allowed <span class="req">*</span></label>
                        <select name="retake_allowed" required>
                            <option value="1" @selected(($editing->retake_allowed ?? true) == 1)>Yes</option>
                            <option value="0" @selected(($editing->retake_allowed ?? true) == 0)>No</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Max Attempts Limit</label>
                        <input type="number" name="max_attempts"
                            value="{{ $editing->max_attempts ?? old('max_attempts') }}">
                    </div>

                    <div class="field">
                        <label>Pass / Fail Threshold (%) <span class="req">*</span></label>
                        <input type="number" name="pass_percentage" value="{{ $editing->pass_percentage ?? 60 }}" required>
                    </div>

                    <div class="field">
                        <label>Certificate on Pass <span class="req">*</span></label>
                        <select name="certificate_on_pass" required>
                            <option value="0" @selected(($editing->certificate_on_pass ?? false) == 0)>No</option>
                            <option value="1" @selected(($editing->certificate_on_pass ?? false) == 1)>Yes</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Time Limit (minutes)</label>
                        <input type="number" name="time_limit_minutes" value="{{ $editing->time_limit_minutes ?? old('time_limit_minutes') }}">
                    </div>

                    <div class="field full" style="margin-top:6px;">
                        <label style="text-transform:none;font-weight:700;color:var(--ink-soft);font-size:12px;">Rewards &
                            Points</label>
                    </div>

                    <div class="field">
                        <label>No of Points</label>
                        <input type="number" step="0.01" name="no_of_points"
                            value="{{ $editing->no_of_points ?? old('no_of_points', 0) }}">
                    </div>

                    <div class="field">
                        <label>Total Reward Amount</label>
                        <input type="number" step="0.01"
                            name="total_reward_amount"
                            value="{{ $editing->total_reward_amount ?? old('total_reward_amount', 0) }}">
                    </div>

                    <div class="field full" style="margin-top:6px;">
                        <label style="text-transform:none;font-weight:700;color:var(--ink-soft);font-size:12px;">Additional Limits</label>
                    </div>

                    <div class="field">
                        <label>Question Limit <span class="req">*</span></label>
                        @php
                            $defaultQuestionLimit = (int) quizSetting('superadmin_que_limit', 10);
                            if (isset($editing)) {
                                $initialLimit = $editing->question_limit ?? ($editing->visibility === 'fastest_finger_first' ? 15 : $defaultQuestionLimit);
                            } else {
                                $initialLimit = old('visibility') === 'fastest_finger_first' ? 15 : $defaultQuestionLimit;
                            }
                        @endphp
                        <input type="number" name="question_limit" id="question_limit" value="{{ old('question_limit', $initialLimit) }}" min="1" required>
                    </div>

                    <div class="field">
                        <label>Time / Question (Sec)</label>
                        <input type="number" name="per_que_time_limit_sec"
                            value="{{ $editing->per_que_time_limit_sec ?? old('per_que_time_limit_sec', 0) }}">
                    </div>

                    <div class="field">
                        <label>Max Players</label><input type="number" name="no_of_players"
                            value="{{ $editing->no_of_players ?? old('no_of_players', 0) }}">
                    </div>

                    <div class="field">
                        <label>Negative Marking <span class="req">*</span></label>
                        <select name="negative_marking" required>
                            <option value="0" @selected(($editing->negative_marking ?? false) == 0)>No</option>
                            <option value="1" @selected(($editing->negative_marking ?? false) == 1)>Yes</option>
                        </select>
                    </div>

                    <div class="field full" style="margin-top:6px;">
                        <label style="text-transform:none;font-weight:700;color:var(--ink-soft);font-size:12px;">Publish
                            State
                        </label>
                    </div>

                    <div class="field">
                        <label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            @foreach (['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $val => $label)
                                <option value="{{ $val }}" @selected(($editing->status ?? 'draft') === $val)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Approve Status <span class="req">*</span></label>
                        <select name="approve_status" required>
                            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                                <option value="{{ $val }}" @selected(($editing->approve_status ?? 'pending') === $val)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Complete Status <span class="req">*</span></label>
                        <select name="complete_status" required>
                            <option value="pending" @selected(($editing->complete_status ?? 'pending') === 'pending')>Pending</option>
                            <option value="completed" @selected(($editing->complete_status ?? '') === 'completed')>Completed</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Schedule Date &amp; Time <span class="hint">(if Scheduled)</span></label>
                        <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                            value="{{ $editing?->scheduled_at?->format('Y-m-d\TH:i') }}">
                    </div>

                    <div class="field">
                        <label>Before Time Entry (Minutes)</label>
                        <input type="number" id="before_minute_entry" name="before_minute_entry"
                            value="{{ old('before_minute_entry', $editing->before_minute_entry ?? 0) }}">
                    </div>

                    <div class="field">
                        <label>Login Period &amp; Time</label>
                        <input type="datetime-local" id="login_time_period" name="login_time_period" readonly
                            value="{{ $editing?->login_time_period?->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="form-foot">
                    <a class="btn btn-ghost" href="{{ route('superadmin.quizzes.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $editing ? 'Update Quiz' : 'Save Quiz' }}</button>
                </div>
            </form>

        </div>
    </div>

    @include('superadmin.component.select_question')
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        const subs = @json($subcategories);

        const selectedCategory = "{{ old('category_id', $editing->category_id ?? '') }}";
        const selectedSubCategory = "{{ old('sub_category_id', $editing->sub_category_id ?? '') }}";

        function loadSubCategories(categoryId) {

            let html = '<option value="">— Select Sub Category —</option>';

            subs.forEach(function(s) {
                if (String(s.parent_id) === String(categoryId)) {
                    html += `<option value="${s.id}" ${String(s.id) === String(selectedSubCategory) ? 'selected' : ''}>
                        ${s.name}
                    </option>`;
                }
            });

            document.getElementById('sub_category_id').innerHTML = html;
        }

        const category = document.getElementById('category_id');

        // Change event
        category.addEventListener('change', function() {
            loadSubCategories(this.value);
        });

        // Edit page ya validation error ke baad auto load
        if (selectedCategory) {
            loadSubCategories(selectedCategory);
        }
    </script>

    <script>
        const schedule = document.getElementById('scheduled_at');
        const before = document.getElementById('before_minute_entry');
        const login = document.getElementById('login_time_period');

        function updateLoginTime() {

            if (!schedule.value) {
                login.value = '';
                return;
            }

            let dt = new Date(schedule.value);

            let mins = parseInt(before.value || 0);

            // Login time = Schedule Time - Before Minutes
            dt.setMinutes(dt.getMinutes() - mins);

            let y = dt.getFullYear();
            let m = String(dt.getMonth() + 1).padStart(2, '0');
            let d = String(dt.getDate()).padStart(2, '0');
            let h = String(dt.getHours()).padStart(2, '0');
            let i = String(dt.getMinutes()).padStart(2, '0');

            login.value = `${y}-${m}-${d}T${h}:${i}`;
        }

        schedule.addEventListener('change', updateLoginTime);
        before.addEventListener('input', updateLoginTime);

        // Edit page par bhi auto fill
        updateLoginTime();
    </script>
@endpush
