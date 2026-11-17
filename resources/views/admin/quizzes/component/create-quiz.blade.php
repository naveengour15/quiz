<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

<style>
    .form-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 16px 20px !important;
    }

    @media (max-width: 992px) {
        .form-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (max-width: 576px) {
        .form-grid {
            grid-template-columns: 1fr !important;
        }
    }

    .field.full {
        grid-column: 1 / -1 !important;
    }

    /* Lifeline Toggle Switch Cards */
    .toggle-switch-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
        min-height: 52px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .toggle-switch-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }
    .toggle-switch-card.active {
        background: #f0f7ff;
        border-color: #3b82f6;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.12);
    }
    .toggle-switch-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .toggle-switch-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .toggle-switch-desc {
        font-size: 11.5px;
        color: #64748b;
    }
    .switch-input-wrap {
        position: relative;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }
    .switch-input-wrap input[type="checkbox"] {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s ease;
        border-radius: 24px;
    }
    .switch-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .switch-input-wrap input[type="checkbox"]:checked + .switch-slider {
        background-color: #2563eb;
    }
    .switch-input-wrap input[type="checkbox"]:checked + .switch-slider:before {
        transform: translateX(20px);
    }
</style>
<div class="panel active">
    <div class="page-head">
        <div>
            <h1>{{ $editing ? 'Edit Quiz' : 'Create Quiz' }}</h1>
            <p>Assemble from the question bank, then configure access &amp; completion rules.</p>
        </div>
    </div>

    <div class="form-card" style="margin-top: 20px;">

        <form id="quizCreateEditForm" method="POST"
            action="{{ $editing ? route('admin.quizzes.update', $editing) : route('admin.quizzes.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="form-grid">
                <div class="field"><label>Quiz Title <span class="req">*</span></label><input type="text"
                        name="title" value="{{ $editing->title ?? old('title') }}" required></div>
                <div class="field">
                    <label>Category</label>
                    <select name="category_id" id="category_id">
                        <option value="">— Select Category —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}"
                               
                                  {{ old('category_id', $editing->category_id ?? ($categories->first()->id ?? '')) == $c->id ? 'selected' : '' }}>
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

                <div class="field">
                    <label>Level <span class="req">*</span></label>
                    <select name="level_id" required>
                        <option value="">— Select Level —</option>
                        @foreach ($Level as $lvl)
                            <option value="{{ $lvl->id }}"
                           
                                   {{ old('level_id', $editing->level_id ?? ($Level->first()->id ?? '')) == $lvl->id ? 'selected' : '' }}>
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
                             
                                 {{ old('type', $editing->type ?? ($QuizType->first()->id ?? '')) == $qt->id ? 'selected' : '' }}>
                                {{ $qt->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Visibility <span class="req">*</span></label>

                    @if (isset($editing))
                     
                        <select name="visibility_display" class="visibility" disabled>
                            <option value="public" @selected($editing->visibility === 'public')>Public</option>
                            <option value="private" @selected($editing->visibility === 'private')>Private</option>
                            <option value="leaderboard" @selected($editing->visibility === 'leaderboard')>Leaderboard</option>
                            <option value="practice" @selected($editing->visibility === 'practice')>Practice</option>
                            <option value="mahaquiz" @selected($editing->visibility === 'mahaquiz')>Mahaquiz</option>
                        </select>

                      
                        <input type="hidden" name="visibility" value="{{ $editing->visibility }}">
                    @else
                   
                        <select name="visibility" class="visibility" required>
                            <option value="public" selected>Public</option>
                            <option value="private">Private</option>
                            <option value="leaderboard">Leaderboard</option>
                            <option value="practice">Practice</option>
                            <option value="mahaquiz">Mahaquiz</option>
                        </select>
                    @endif
                </div>

                <div class="field" id="certificateOnPass"><label>Certificate on Pass <span
                            class="req">*</span></label>
                    <select name="certificate_on_pass" required>
                        <option value="0" @selected(($editing->certificate_on_pass ?? false) == 0)>No</option>
                        <option value="1" @selected(($editing->certificate_on_pass ?? false) == 1)>Yes</option>
                    </select>
                </div>

                <div class="field">
                    <label>Question Limit</label>
                    <input type="number"
                        value="{{ $editing->question_limit ?? old('question_limit', quizSetting('admin_que_limit', 10)) }}"
                        disabled>
                    <input type="hidden" name="question_limit" id="question_limit"
                        value="{{ $editing->question_limit ?? old('question_limit', quizSetting('admin_que_limit', 10)) }}">
                </div>

                <div class="field">
                    <label>Time / Question (Sec)</label>
                    <input type="number" name="per_que_time_limit_sec"
                        value="{{ $editing->per_que_time_limit_sec ?? old('per_que_time_limit_sec', 10) }}" disabled>

                    <input type="hidden" name="per_que_time_limit_sec" id="per_que_time_limit_sec"
                        value="{{ $editing->per_que_time_limit_sec ?? old('per_que_time_limit_sec', 10) }}">
                </div>

                <div class="field" id="maxPlayersField"><label>Max Players</label><input type="number"
                        name="no_of_players" value="{{ $editing->no_of_players ?? old('no_of_players', 500) }}">
                </div>

                <div class="field" id="negativeMarkingField"><label>Negative Marking <span
                            class="req">*</span></label>
                    <select name="negative_marking" required>
                        <option value="0" @selected(($editing->negative_marking ?? false) == 0)>No</option>
                        <option value="1" @selected(($editing->negative_marking ?? false) == 1)>Yes</option>
                    </select>
                </div>





                <input type="hidden" name="status" value="{{ $editing->status ?? 'scheduled' }}">
                <input type="hidden" name="retake_allowed" value="0">

                <div class="field" id="scheduledAtField">
                    <label>Schedule Date &amp; Time <span class="hint">(if Scheduled)</span></label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                        value="{{ $editing?->scheduled_at?->format('Y-m-d\TH:i') }}">
                </div>
            </div>
            <div id="scheduleConflictAlert" class="schedule-conflict-alert" style="display:none; margin-top: 15px;">
                <div class="alert-content">
                    <div class="alert-icon">⚠</div>
                    <div class="alert-text">
                        <div class="alert-title">Schedule Conflict</div>
                        <div class="alert-message"></div>
                    </div>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            </div>


            <div class="form-grid" style="margin-top: 16px; ">
                @if (isset($editing))
                    <input type="hidden" id="quiz_id" value="{{ $editing->id }}">
                @else
                    <input type="hidden" id="quiz_id" value="">
                @endif

                <div class="field" id="beforeMinuteEntry">
                    <label>Before Time Entry (Minutes)</label>
                    <input type="number" id="before_minute_entry" name="before_minute_entry"
                        value="{{ old('before_minute_entry', $editing->before_minute_entry ?? 0) }}">
                </div>

                <div class="field" id="fiftyFiftyField">
                    <label>50:50 Lifeline</label>
                    <div class="toggle-switch-card @if(old('fifty_fifty', $editing->fifty_fifty ?? 0)) active @endif" onclick="$(this).find('input[type=checkbox]').click();">
                        <div class="toggle-switch-info">
                            <span class="toggle-switch-title">
                                <i class="fas fa-bolt" style="color: #f59e0b;"></i> 50:50 Option
                            </span>
                            <span class="toggle-switch-desc">Removes 2 wrong choices</span>
                        </div>
                        <div class="switch-input-wrap" onclick="event.stopPropagation();">
                            <input type="hidden" name="fifty_fifty" value="0">
                            <input type="checkbox" id="fifty_fifty" name="fifty_fifty" value="1" @checked(old('fifty_fifty', $editing->fifty_fifty ?? 0)) onchange="$(this).closest('.toggle-switch-card').toggleClass('active', this.checked)">
                            <span class="switch-slider"></span>
                        </div>
                    </div>
                </div>

                <div class="field" id="switchOptionField">
                    <label>Switch Option Lifeline</label>
                    <div class="toggle-switch-card @if(old('switch_option', $editing->switch_option ?? 0)) active @endif" onclick="$(this).find('input[type=checkbox]').click();">
                        <div class="toggle-switch-info">
                            <span class="toggle-switch-title">
                                <i class="fas fa-sync-alt" style="color: #2563eb;"></i> Switch Option
                            </span>
                            <span class="toggle-switch-desc">Allows question/option switch</span>
                        </div>
                        <div class="switch-input-wrap" onclick="event.stopPropagation();">
                            <input type="hidden" name="switch_option" value="0">
                            <input type="checkbox" id="switch_option" name="switch_option" value="1" @checked(old('switch_option', $editing->switch_option ?? 0)) onchange="$(this).closest('.toggle-switch-card').toggleClass('active', this.checked)">
                            <span class="switch-slider"></span>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="login_time_period" name="login_time_period"
                    value="{{ optional($editing?->login_time_period)->format('Y-m-d\TH:i') }}">

                {{-- Quiz Filter Header --}}
                {{-- <div class="field full" style="margin-top:12px;">
                    <label
                        style="text-transform:none;font-weight:700;color:var(--ink);font-size:14px;border-bottom:2px solid #e2e8f0;padding-bottom:6px;display:block;">
                        <i class="fas fa-filter text-primary"></i> Quiz Filter Settings
                    </label>
                </div> 

                <div class="field">
                    <label>Adds Campaign</label>
                    <select name="ads_campaign_id" id="ads_campaign_id">
                        <option value="">Select Campaign</option>
                        @foreach ($AdsCampaigns as $campaign)
                            <option value="{{ $campaign->id }}" @selected(old('ads_campaign_id', $quizFilter->ads_campaign_id ?? '') == $campaign->id)>
                                {{ $campaign->campaign_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>State</label>
                    <select name="state" id="state">
                        <option value="">Select State</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected(old('state', $quizFilter->state ?? '') == $state->id)>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>City</label>
                    <select name="city" id="city">
                        <option value="">Select City</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city', $quizFilter->city ?? '') == $city->id)>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Area</label>
                    <input type="text" name="area" value="{{ old('area', $quizFilter->area ?? '') }}"
                        placeholder="Enter Area">
                </div>

                <div class="field">
                    <label>Age Range</label>
                    <input type="text" name="age_range"
                        value="{{ old('age_range', $quizFilter->age_range ?? '') }}" placeholder="18-25">
                </div>

                <div class="field">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="all" @selected(old('gender', $quizFilter->gender ?? 'all') == 'all')> All </option>
                        <option value="male" @selected(old('gender', $quizFilter->gender ?? '') == 'male')> Male </option>
                        <option value="female" @selected(old('gender', $quizFilter->gender ?? '') == 'female')> Female </option>
                    </select>
                </div>

                <div class="field">
                    <label>Hobby</label>
                    <input type="text" name="hobby" value="{{ old('hobby', $quizFilter->hobby ?? '') }}"
                        placeholder="Cricket, Music">
                </div>

                <div class="field">
                    <label>Filter Status</label>
                    <select name="filter_status">
                        <option value="active" @selected(old('filter_status', $quizFilter->status ?? 'active') == 'active')> Active </option>
                        <option value="inactive" @selected(old('filter_status', $quizFilter->status ?? '') == 'inactive')> Inactive </option>
                    </select>
                </div>--}}
            </div>



            <div class="form-foot" style="margin-top:24px;">
                <a class="btn btn-ghost" href="{{ route('admin.quizzes.index') }}">Cancel</a>
                <button id="saveQuizSubmitBtn" class="btn btn-primary"
                    type="submit">{{ $editing ? 'Update Quiz' : 'Save Quiz & Continue' }}</button>
            </div>
        </form>

    </div>
</div>
<style>
    .schedule-conflict-alert {
        width: 100%;
        margin-bottom: 18px;
        border: 1px solid #f5c2c7;
        background: linear-gradient(135deg, #fff5f5, #ffe8e8);
        border-left: 6px solid #dc3545;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(220, 53, 69, .12);
        animation: fadeDown .3s ease;
    }

    .schedule-conflict-alert .alert-content {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 16px 18px;
    }

    .schedule-conflict-alert .alert-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .schedule-conflict-alert .alert-text {
        flex: 1;
    }

    .schedule-conflict-alert .alert-title {
        color: #b42318;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .schedule-conflict-alert .alert-message {
        color: #7a1f1f;
        font-size: 14px;
        line-height: 1.6;
    }

    .schedule-conflict-alert .alert-close {
        border: none;
        background: none;
        font-size: 24px;
        color: #b42318;
        cursor: pointer;
        line-height: 1;
    }

    .schedule-conflict-alert .alert-close:hover {
        color: #7a0000;
    }

    @keyframes fadeDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    const subs = @json($subcategories);

    const selectedCategory = "{{ old('category_id', $editing->category_id ?? ($categories->first()->id ?? '')) }}";
    const selectedSubCategory = "{{ old('sub_category_id', $editing->sub_category_id ?? '') }}";

    function loadSubCategories(categoryId, useExistingSubCategory = false) {

        let html = '<option value="">— Select Sub Category —</option>';

        let categorySubs = subs.filter(function(s) {
            return String(s.parent_id) === String(categoryId);
        });

        categorySubs.forEach(function(s, index) {

            let isSelected = false;

            // Sirf initial edit/load par existing subcategory select karo
            if (useExistingSubCategory && selectedSubCategory) {
                isSelected = String(s.id) === String(selectedSubCategory);
            }

            // Agar existing subcategory nahi mili
            // ya category change hui hai, first subcategory select karo
            if (!isSelected && index === 0) {
                isSelected = true;
            }

            html += `<option value="${s.id}" ${isSelected ? 'selected' : ''}>
                        ${s.name}
                    </option>`;
        });

        document.getElementById('sub_category_id').innerHTML = html;
    }

    const category = document.getElementById('category_id');

    // Category manually change karne par
    // hamesha us category ki FIRST subcategory select hogi
    category.addEventListener('change', function() {
        loadSubCategories(this.value, false);
    });

    // Page load par existing category/subcategory maintain karo
    if (selectedCategory) {
        loadSubCategories(selectedCategory, true);
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
<script>
    $('#state').on('change', function() {

        let stateId = $(this).val();

        $('#city').html('<option>Loading...</option>');

        $.get("{{ route('admin.states.cities', ':id') }}".replace(':id', stateId), function(cities) {

            let html = '<option value="">Select City</option>';

            $.each(cities, function(i, city) {
               
                html += `<option value="${city.id}">${city.name}</option>`;
            });

            $('#city').html(html);

        });

    });
</script>

<script>
    $(document).ready(function() {

        let scheduleAlertTimer = null;
        let isAutoFilling = false; // Prevent re-trigger during auto-fill

        function showScheduleConflict(message, type = 'danger') {
            clearTimeout(scheduleAlertTimer);

            let bgColor, textColor, borderColor, icon;

            if (type === 'success') {
                bgColor = '#d1fae5';
                textColor = '#065f46';
                borderColor = '#10b981';
                icon = '✅';
            } else if (type === 'info') {
                bgColor = '#dbeafe';
                textColor = '#1e40af';
                borderColor = '#3b82f6';
                icon = 'ℹ️';
            } else {
                bgColor = '#fee2e2';
                textColor = '#991b1b';
                borderColor = '#ef4444';
                icon = '⚠️';
            }

            $('#scheduleConflictAlert')
                .css({
                    'background': bgColor,
                    'border': `2px solid ${borderColor}`,
                    'color': textColor,
                    'border-radius': '8px',
                    'padding': '12px 16px',
                    'margin-top': '10px'
                });

            $('#scheduleConflictAlert .alert-message').html(message);
            $('#scheduleConflictAlert').stop(true, true).slideDown(300);

            scheduleAlertTimer = setTimeout(function() {
                $('#scheduleConflictAlert').slideUp(300);
            }, 5000);
        }

        function hideScheduleConflict() {
            clearTimeout(scheduleAlertTimer);
            $('#scheduleConflictAlert').slideUp(300);
        }

        $('.alert-close').on('click', function() {
            hideScheduleConflict();
        });

        function checkQuizSlot() {

            // Prevent re-trigger during auto-fill
            if (isAutoFilling) {
                isAutoFilling = false;
                return;
            }

            let scheduled_at = $('#scheduled_at').val();

            if (scheduled_at === '') {
                hideScheduleConflict();
                return;
            }

            $.ajax({
                url: "{{ route('admin.quizzes.check-slot') }}",
                type: "POST",
                dataType: "json",
                data: {
                    _token: "{{ csrf_token() }}",
                    scheduled_at: scheduled_at,
                    question_limit: $('#question_limit').val(),
                    quiz_id: $('#quiz_id').val()
                },

                success: function(res) {

                    //  Slot available — no conflict
                    if (res.status === true) {
                        hideScheduleConflict();
                        return;
                    }
                },

                error: function(xhr) {

                    if (xhr.status === 409) {
                        // Conflict detected
                        let res = xhr.responseJSON;
                        let message = res.message || 'Slot conflict detected.';

                        if (res.has_next && res.next_available) {
                            //  Show conflict + suggest next slot
                            let nextTime = res.next_available.datetime;

                            let fullMessage = `
                            <strong>${message}</strong><br>
                            🔄 <strong>Auto-adjusting to next available slot:</strong><br>
                            📅 ${nextTime}
                        `;

                            showScheduleConflict(fullMessage, 'info');

                            //  Auto-fill with next available time
                            isAutoFilling = true;
                            $('#scheduled_at').val(res.next_available.input_value);

                        } else {
                            // No next slot found
                            showScheduleConflict(message +
                                '<br>❌ No available slot in the next 7 days.');
                            $('#scheduled_at').val('');
                        }

                        return;
                    }

                    // Other errors
                    let message = 'Unable to validate schedule. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    showScheduleConflict(message);
                }

            });

        }

        // Check when date/time changes
        $('#scheduled_at').on('change', function() {
            checkQuizSlot();
        });

        // Check on edit page load
        if ($('#scheduled_at').val() !== '') {
            checkQuizSlot();
        }

    });
</script>

<script>
    $(document).ready(function() {

        // Initial state check
        togglePracticeFields();

        // On visibility change
        $('.visibility').on('change', function() {
            togglePracticeFields();
        });

        function togglePracticeFields() {
            const visibility = $('.visibility').val();
            const isPractice = visibility === 'practice';

            // Fields ko hide/show karein
            const fields = ['#maxPlayersField', '#negativeMarkingField', '#scheduledAtField',
                '#beforeMinuteEntry', '#certificateOnPass'
            ];

            fields.forEach(selector => {
                const $field = $(selector);

                if (isPractice) {
                    // Hide field with smooth animation
                    $field.slideUp(200);

                    // Remove required attribute
                    $field.find('input, select').prop('required', false);

                    // Clear values (optional)
                    // $field.find('input, select').val('');
                } else {
                    // Show field
                    $field.slideDown(200);

                    // Re-add required if needed
                    if (selector === '#maxPlayersField' || selector === '#negativeMarkingField' ||
                        selector === '#beforeMinuteEntry' || selector === '#certificateOnPass') {
                        $field.find('input, select').prop('required', true);
                    }
                }
            });

            // Optional: Show info message for practice mode
            if (isPractice) {
                if ($('#practiceNotice').length === 0) {
                    $('#visibility').closest('.field').after(`
                    
                `);
                }
            } else {
                $('#practiceNotice').remove();
            }
        }

        // Prevent duplicate form submission & disable button on submit
        $('#quizCreateEditForm').on('submit', function(e) {
            const form = this;
            if ($(form).data('submitting') === true) {
                e.preventDefault();
                return false;
            }

            if (form.checkValidity && !form.checkValidity()) {
                return true;
            }

            $(form).data('submitting', true);
            const $btn = $('#saveQuizSubmitBtn');
            if ($btn.length) {
                $btn.prop('disabled', true).addClass('disabled').css({
                    'pointer-events': 'none',
                    'opacity': '0.75'
                });
                const loadingText = '{{ $editing ? "Updating Quiz..." : "Saving Quiz & Continuing..." }}';
                $btn.html('<span style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin 0.75s linear infinite;margin-right:8px;vertical-align:-2px;"></span>' + loadingText);
            }
        });

    });
</script>
