@php
    use App\Models\Quiz;
    $rawQuizParam = request()->route('quiz') ?? request()->query('editing');
    $quizObj = $editing ?? ($rawQuizParam instanceof Quiz ? $rawQuizParam : (!empty($rawQuizParam) ? (is_numeric($rawQuizParam) ? Quiz::find($rawQuizParam) : Quiz::resolveQuiz($rawQuizParam)) : null));
    $quizId = $quizObj?->id ?? ($editing->id ?? (is_numeric($rawQuizParam) ? $rawQuizParam : null));

    $questionLimit = 0;
    $hasSwitchOption = false;
    $quizType = 'mcq'; 

    if($quizObj){
        $questionLimit = (int)($quizObj->question_limit ?? 0);
        $hasSwitchOption = (bool)($quizObj->switch_option ?? false);
        $quizType = $quizObj->visibility ?? 'mcq';
    }

    $effectiveLimit = $questionLimit;
    if ($hasSwitchOption && $questionLimit > 0) {
        $effectiveLimit = $questionLimit + 1;
    }

    // Ultra-resilient helper to convert options to normalized array with correct option flag
    /*$getOptionsList = function($optionsRaw, $correctAnswer = null) {
        // Decode string up to 3 times if multi-encoded
        for ($i = 0; $i < 3; $i++) {
            if (is_string($optionsRaw)) {
                $decoded = json_decode($optionsRaw, true);
                if ($decoded !== null && $decoded !== $optionsRaw) {
                    $optionsRaw = $decoded;
                } else {
                    break;
                }
            }
        }
        
        $list = [];
        $cleanCorrect = strtolower(trim((string)$correctAnswer));
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        if (is_array($optionsRaw) || is_object($optionsRaw)) {
            $idx = 0;
            foreach ($optionsRaw as $key => $val) {
                $textStr = '';
                $labelStr = '';
                $isCorrect = false;

                if (is_array($val)) {
                    $textStr = $val['text'] ?? $val['option'] ?? $val['title'] ?? $val['value'] ?? '';
                    if ($textStr === '' && !empty($val)) {
                        $textStr = json_encode($val);
                    }
                    $labelStr = $val['label'] ?? '';
                    if (isset($val['is_correct'])) {
                        $isCorrect = (bool)$val['is_correct'];
                    }
                } elseif (is_object($val)) {
                    $textStr = $val->text ?? $val->option ?? $val->title ?? json_encode($val);
                    $labelStr = $val->label ?? '';
                    if (isset($val->is_correct)) {
                        $isCorrect = (bool)$val->is_correct;
                    }
                } else {
                    $textStr = (string)$val;
                }

                $textStr = trim($textStr);
                if ($textStr === '' && $textStr !== '0') continue;

                if (empty($labelStr)) {
                    $labelStr = is_numeric($key) ? ($letters[$idx] ?? ($idx + 1)) : strtoupper((string)$key);
                }

                $cleanVal = strtolower($textStr);
                $cleanLabel = strtolower(trim((string)$labelStr));
                $cleanKey = strtolower(trim((string)$key));

                if (!$isCorrect && $cleanCorrect !== '') {
                    $isCorrect = ($cleanVal === $cleanCorrect 
                               || $cleanLabel === $cleanCorrect 
                               || $cleanKey === $cleanCorrect
                               || ('option ' . $cleanLabel) === $cleanCorrect
                               || ('option_' . $cleanLabel) === $cleanCorrect
                               || ('option ' . ($idx + 1)) === $cleanCorrect);
                }

                $list[] = [
                    'label' => (string)$labelStr,
                    'text' => (string)$textStr,
                    'is_correct' => (bool)$isCorrect
                ];
                $idx++;
            }
        }
        return $list;
    };*/

    $getOptionsList = function ($options, $correctAnswer = null) {

        // Decode JSON if needed
        for ($i = 0; $i < 3 && is_string($options); $i++) {
            $decoded = json_decode($options, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }

            $options = $decoded;
        }

        if (!is_array($options)) {
            return [];
        }

        $correctAnswer = trim((string) $correctAnswer);

        $result = [];

        $optionIndex = 0;
        foreach ($options as $option) {
            $label = chr(65 + $optionIndex);

            if (is_array($option)) {
                $text = $option['text']
                    ?? $option['option']
                    ?? $option['title']
                    ?? $option['value']
                    ?? '';

            } else {
                $text = (string) $option;
            }

            $text = trim((string) $text);

            /*
            * IMPORTANT:
            * correct_answer ko option index nahi samjhenge.
            * Sirf option ke actual TEXT se compare karenge.
            */
            $isCorrect = (
                $correctAnswer !== '' &&
                $text === $correctAnswer
            );

            $result[] = [
                'label'     => $label,
                'text'      => $text,
                'is_correct' => $isCorrect,
            ];
            $optionIndex++;
        }

        return $result;
    };


@endphp

@push('styles')
<style>
.sq-wrapper {
    font-family: inherit;
}
.sq-top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    gap: 10px;
}
.sq-title {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
}
.sq-subtitle {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748b;
}
.sq-limit-badge {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    color: #2563eb;
    box-shadow: 0 2px 6px rgba(37,99,235,0.08);
}
.sq-limit-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 18px;
    animation: sqFadeIn 0.3s ease-in-out;
}
.sq-limit-banner i {
    font-size: 22px;
    color: #dc2626;
}

@keyframes sqFadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.sq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.card-header {
    border-bottom: 1px solid #e2e8f0;
}

.sq-card-content{
   width: 100%;
}

.question-bank-info-box{
   display: flex; 
   justify-content:space-between; 
   align-items:center; 
   gap:10px;
}


@media (max-width: 992px) {
    .sq-grid {
        grid-template-columns: repeat(1, 1fr);
    }
}

.sq-bank-body, .sq-selected-body {
    max-height: 560px;
    overflow-y: auto;
    /* padding: 12px; */
}

/* Faded Question Bank when limit reached */
.sq-bank-body.limit-reached {
    opacity: 0.35;
    pointer-events: none;
    user-select: none;
    transition: all 0.3s ease;
}

.sq-search-box {
    position: relative;
    width: 240px;
    margin-top: 10px;
    margin-bottom: 10px;
}
.sq-search-box input {
       border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 12.5px;
        padding: 10px;
}
.sq-search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 12px;
}

.sq-bank-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
    gap:10px;
}
.sq-bank-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}
.sq-bank-card.is-selected {
    background: #f8fafc;
    border-color: #cbd5e1;
    opacity: 0.8;
}

.sq-q-text {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.45;
}
.sq-q-options {
    font-size: 12px;
    color: #475569;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
    margin-bottom: 6px;
}
.sq-opt-badge {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 4px 10px;
    border-radius: 6px;
    color: #334155;
    font-size: 12px;
    line-height: 1.4;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}
.sq-opt-badge.is-correct-opt {
    background: #d1fae5 !important;
    color: #047857 !important;
    border-color: #6ee7b7 !important;
    font-weight: 700 !important;
    box-shadow: 0 1px 3px rgba(16, 185, 129, 0.15);
}
.sq-q-correct {
    font-size: 12px;
    font-weight: 600;
    color: #059669;
    margin-top: 4px;
}

/* Selected Question Cards (Draggable) */
.sq-drop-hint {
    font-size: 12px;
    color: #475569;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px dashed #cbd5e1;
}
.sq-selected-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.sq-selected-card:active {
    cursor: grabbing;
}
.sq-selected-card.dragging {
    opacity: 0.45;
    background: #eff6ff;
    border-color: #3b82f6;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
}
.sq-drag-handle {
    font-size: 20px;
    color: #94a3b8;
    cursor: grab;
    padding: 0 4px;
    margin-top: 2px;
    user-select: none;
}
.sq-order-badge {
    background: #2563eb;
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.sq-card-detail {
    flex: 1;
}

/* Bot Correct Switch Pill */
.sq-bot-pill-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    padding: 3px 8px;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
}
.bot-pill-label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}
.sq-pill-group {
    display: inline-flex;
    background: #e2e8f0;
    border-radius: 14px;
    padding: 2px;
}
.sq-pill-btn {
    border: none;
    background: transparent;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 12px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sq-pill-btn.active-no {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
}
.sq-pill-btn.active-yes {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}
.sq-pill-btn.active-switch {
    background: #8b5cf6;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
}
.sq-selected-card.is-switch-card {
    border: 2px solid #8b5cf6 !important;
    background: #faf5ff !important;
}
.sq-switch-pill-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f3e8ff;
    padding: 3px 8px;
    border-radius: 20px;
    border: 1px solid #d8b4fe;
}

.sq-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 10px;
}

.btn-remove-q {
    font-weight: 600 !important;
    font-size: 12px !important;
    padding: 5px 12px !important;
    border-radius: 6px !important;
}

.sq-hindi-question {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px dashed #cbd5e1;
    line-height: 1.5;
}

@media(max-width:575px){
    .sq-top-header{
        flex-direction: column;
        align-items: start;
    }
    .sq-left-col, .sq-right-col{
        width: 100%;
        overflow: hidden;
    }
    .sq-search-box{
        width: 100%;
    }
    .sq-selected-card {
        flex-wrap: wrap;
    }
}




/* --- image css and audio Main Container --- */
    .sq-media-footer {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    /* --- 1. Image Box (Chota & Neat) --- */
    .media-box {
        width: 50px;
        height: 50px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        position: relative;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .media-box:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        transform: translateY(-2px);
    }
    .sq-q-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .zoom-hint {
        position: absolute;
        bottom: 2px;
        right: 2px;
        background: rgba(0,0,0,0.6);
        color: white;
        font-size: 12px;
        padding: 2px 4px;
        border-radius: 4px;
    }

    /* --- 2. Audio Box --- */
    .audio-box {
        display: flex;
        align-items: center;
    }
    .audio-play-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #334155;
        transition: all 0.2s;
    }
    .audio-play-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    .audio-play-btn.playing {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }

    /* --- 3. Modal (Popup) - FIXED CLOSE BUTTON --- */
    .sq-image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        /* Flexbox se perfectly center karega */
        display: none; 
        align-items: center;
        justify-content: center;
    }

    /* Yeh container close button ko image ke paas rakhega */
    .modal-inner-container {
        position: relative;
        max-width: 300px; /* Chota size */
        animation: zoomIn 0.2s ease;
    }

    .sq-modal-content {
        width: 100%;
        height: auto;
        max-height: 400px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        display: block;
    }

    /* Close Button - Ab image ke bilkul upar right me hoga */
    .sq-modal-close {
        position: absolute;
        top: -12px;
        right: -12px;
        width: 32px;
        height: 32px;
        background: #ef4444;
        color: white;
        font-size: 24px;
        font-weight: bold;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: transform 0.2s;
        line-height: 1;
    }
    .sq-modal-close:hover {
        transform: scale(1.1);
        background: #dc2626;
    }

    @keyframes zoomIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    /* Mobile Responsive */
    @media screen and (max-width: 480px) {
        .media-box {
            width: 55px;
            height: 55px;
        }
        .modal-inner-container {
            max-width: 250px; /* Mobile me aur chota */
        }
        .audio-play-btn {
            padding: 6px 12px;
            font-size: 13px;
        }
    }

    /* Question Type Filter Dropdown */
    .sq-type-filter {
        position: relative;
    }

    .sq-type-filter select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 8px center;
        padding-right: 28px;
        font-weight: 500;
        color: #334155;
        transition: all 0.2s ease;
    }

    .sq-type-filter select:hover {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .sq-type-filter select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        outline: none;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .d-flex.align-items-center.gap-3 {
            flex-wrap: wrap;
        }
        
        .sq-type-filter select {
            width: 100%;
        }
    }

    /* All Button - Blue/Primary Color */
    .sq-pill-btn.active-all {
        background: #3b82f6;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
    }

    .sq-pill-btn.active-all:hover {
        background: #2563eb;
    }
        
</style>
@endpush

@if($quizId)
    <input type="hidden" id="questionLimitVal" value="{{ $effectiveLimit }}">
    <input type="hidden" id="hasSwitchOptionVal" value="{{ $hasSwitchOption ? 1 : 0 }}">
    <input type="hidden" id="baseQuestionLimitVal" value="{{ $questionLimit }}">
    
    <div class="sq-wrapper mt-3">
        <!-- Top Info Header -->
        <div class="sq-top-header">
            <div>
                <h4 class="sq-title"><i class="fas fa-list-check text-primary me-2"></i> Step 2: Select & Reorder Questions</h4>
                <p class="sq-subtitle">Pick questions from the left bank. Use drag-and-drop handles on the right to set question sequence.</p>
            </div>
            <div class="sq-limit-badge" style="white-space: nowrap;">
                <span>Limit: </span>
                <span id="limitDisplay">
                    @if($hasSwitchOption && $questionLimit > 0)
                        {{ $questionLimit }} Standard + 1 Switch (Total: {{ $effectiveLimit }})
                    @else
                        {{ $questionLimit > 0 ? $questionLimit : 'Unlimited' }}
                    @endif
                </span>
            </div>
        </div>

        <!-- Limit Alert Warning Banner -->
        <div id="limitAlertBanner" class="sq-limit-banner" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Maximum Question Limit Reached!</strong>
                <div>
                    @if($hasSwitchOption && $questionLimit > 0)
                        You have selected the maximum limit of <strong>{{ $effectiveLimit }}</strong> questions ({{ $questionLimit }} standard + 1 switch question).
                    @else
                        You have selected the maximum limit of <strong id="bannerLimitNum">{{ $questionLimit }}</strong> questions.
                    @endif
                </div>
            </div>
        </div>

        <!-- 2 Column Layout -->
        <div class="sq-grid">
            <!-- Left Side: Available Question Bank -->
            <div class="sq-left-col">
                <div class="card card-02 shadow-sm h-100 border-0" style="border:1px solid #e2e8f0 !important; border-radius:12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-database me-1"></i> Question Bank</h6>
      
                        <div class="d-flex align-items-center gap-3">
                            {{-- Toggle Filter --}}
                             {{-- Question Type Filter Dropdown --}}
                             {{-- 2. Question Type Dropdown (Sirf tab dikhayein jab type 'fastest_finger_first' NA ho) --}}
                            @if($quizType !== 'fastest_finger_first')
                            <span class="sq-type-filter" style="flex-shrink: 0;">
                                <select id="questionTypeFilter" class="form-select form-select-sm" style="width: 110px; font-size: 11px; padding: 6px 28px 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                                    <option value="all">All Types</option>
                                    <option value="mcq">MCQ</option>
                                    <option value="audio">Audio</option>
                                    <option value="image">Image</option>
                                    <option value="image_audio">Image + Audio</option>
                                </select>
                            </span>
                             @else
                                    <span>⚡</span> Fastest Finger
                            @endif
                            <div class="sq-pill-group" id="usageFilterToggle" style="flex-shrink: 0;">
                                <button type="button" class="sq-pill-btn active-all" data-filter="all">All</button>
                                <button type="button" class="sq-pill-btn" data-filter="unused">Unused</button>
                                <button type="button" class="sq-pill-btn" data-filter="used">Used</button>
                            </div>

                            <div class="sq-search-box select-search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="bankSearchInput" class="form-control form-control-sm" placeholder="Search questions…">
                            </div>
                        </div>
                    </div>
                    <div class="card-body sq-bank-body" id="questionBankWrapper">
                        <div class="sq-bank-list" id="questionBankList">
                            @forelse($questionBank as $question)
                                {{-- @php
                                    $bankOpts = $getOptionsList($question->options, $question->correct_answer);
                                    if (empty($bankOpts) && isset($question->getAttributes()['options'])) {
                                        $bankOpts = $getOptionsList($question->getAttributes()['options'], $question->correct_answer);
                                    }
                                    $isSelected = $selectedQuestions->has($question->id);
                                @endphp --}}

                                @php
                                    $bankOpts = $getOptionsList($question->options, $question->correct_answer);

                                    if (empty($bankOpts) && isset($question->getAttributes()['options'])) {
                                        $bankOpts = $getOptionsList(
                                            $question->getAttributes()['options'],
                                            $question->correct_answer
                                        );
                                    }

                                    // Hindi options
                                    $bankHindiOpts = $getOptionsList(
                                        $question->hindi_options,
                                        $question->hindi_correct_answer
                                    );

                                    if (
                                        empty($bankHindiOpts) &&
                                        isset($question->getAttributes()['hindi_options'])
                                    ) {
                                        $bankHindiOpts = $getOptionsList(
                                            $question->getAttributes()['hindi_options'],
                                            $question->hindi_correct_answer
                                        );
                                    }

                                    $isSelected = $selectedQuestions->has($question->id);
                                @endphp

                                {{-- <div class="sq-bank-card {{ $isSelected ? 'is-selected' : '' }}" 
                                     data-id="{{ $question->id }}"
                                     data-text="{{ e($question->question_text) }}"
                                     data-options="{{ e(json_encode($bankOpts)) }}"
                                     data-correct="{{ e($question->correct_answer) }}"> --}}
                                
                                    <div class="sq-bank-card {{ $isSelected ? 'is-selected' : '' }}"  
                                        data-id="{{ $question->id }}" 
                                        data-text="{{ e($question->question_text) }}" 
                                        data-options="{{ e(json_encode($bankOpts)) }}" 
                                        data-correct="{{ e($question->correct_answer) }}"
                                        data-hindi-text="{{ e($question->hindi_question ?? '') }}"
                                        data-hindi-options="{{ e(json_encode($bankHindiOpts)) }}"
                                        data-hindi-correct="{{ e($question->hindi_correct_answer ?? '') }}"
                                         data-is-used="{{ ($question->quizzes_count ?? 0) > 0 ? '1' : '0' }}"
                                         data-type="{{ $question->type ?? 'mcq' }}">

                                    <div class="sq-card-content me-2">
                                        {{-- *********add question history start******* --}}
                                       
                                        <div class="question-meta-info" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 12px; flex-wrap: nowrap; overflow: hidden; width: 100%;">

                                            {{-- 1. Audio Icon (Hamesha dikhega) --}}
                                            <div style="display: flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-weight: 500; flex-shrink: 0;">
                                                @if(!empty($question->question_audio))
                                                    {{-- <span style="font-size: 13px;">🔊</span>  --}}
                                                    @if(!empty($question->question_audio)) {{-- Agar variable name alag hai toh change kar lena --}}
                                                        <div class="audio-box">
                                                            <button class="audio-play-btn" onclick="toggleAudio(this)">
                                                                <span class="icon-play">▶️</span>
                                                                <span class="icon-pause" style="display: none;">⏸️</span>
                                                                <span class="btn-text">Play</span>
                                                            </button>
                                                            {{-- <audio class="hidden-audio" src="{{ Storage::disk('s3')->url($question->question_audio) }}"></audio> --}}
                                                            <audio class="hidden-audio" src="{{ $question->question_audio }}"></audio>
                                                        </div>
                                                    @endif
                                                @else
                                                    <span style="font-size: 13px; opacity: 0.6;">🔇</span> 
                                                @endif
                                            </div>
                                            @if(!empty($question->media_url))
                                                    {{-- <div class="media-box" onclick="openImageModal('{{ Storage::disk('s3')->url($question->media_url) }}')">
                                                        <img src="{{ Storage::disk('s3')->url($question->media_url) }}" 
                                                            alt="Question Image" 
                                                            class="sq-q-thumb">
                                                        <div class="zoom-hint">🔍</div>
                                                    </div> --}}
                                                    <div class="media-box" onclick="openImageModal('{{$question->media_url }}')">
                                                        <img src="{{ $question->media_url }}" 
                                                            alt="Question Image" 
                                                            class="sq-q-thumb">
                                                        <div class="zoom-hint">🔍</div>
                                                    </div>
                                                @endif

                                            {{-- 2. Combined Usage Box (Sirf tab dikhega jab count > 0 ho) --}}
                                            @if(($question->quizzes_count ?? 0) > 0)
                                                <div style="display: flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; flex-shrink: 1; min-width: 0; overflow: hidden;" 
                                                    title="Used in {{ $question->quizzes_count }} Quizzes: {{ $question->quizzes->pluck('title')->implode(', ') }}">

                                                    {{-- Count Section (Bold & Indigo Color with Rounded Badge) --}}
                                                    <span style="color: #4f46e5; font-weight: 600; white-space: nowrap; flex-shrink: 0; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas fa-layer-group" style="font-size: 10px;"></i> 
                                                        <span>Used in</span>
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 10px; background: #4f46e5; color: #ffffff; font-size: 11px; font-weight: 700;">
                                                            {{ $question->quizzes_count }}
                                                        </span>
                                                    </span>

                                                    {{-- Names Section (Muted & Auto Truncated) --}}
                                                    <span style="color: #475569; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        {{ $question->quizzes->pluck('title')->implode(', ') }}
                                                    </span>
                                                </div>
                                            @endif

                                        </div>
                                        {{-- *********add question history end******* --}}

                                        <div class="question-bank-info-box">
                                             <div class="sq-q-text">{{ $question->question_text }}

                                                
                                             </div>
                                               <button type="button" class="btn btn-sm btn-outline-primary btn-add-q flex-shrink-0" 
                                                    onclick="addQuestionToSelected({{ $question->id }})" 
                                                    {{ $isSelected ? 'disabled' : '' }}>
                                                <i class="fas {{ $isSelected ? 'fa-check' : 'fa-plus' }}"></i> 
                                                <span class="btn-text">{{ $isSelected ? 'Added' : 'Add' }}</span>
                                            </button>
                                       </div>

                                       {{-- MODAL (Popup) --}}
                                        <div id="sqImageModal" class="sq-image-modal" onclick="closeImageModal(event)">
                                            <div class="modal-inner-container">
                                                <span class="sq-modal-close" onclick="closeImageModal(event)">&times;</span>
                                                <img class="sq-modal-content" id="sqLargerImage">
                                            </div>
                                        </div>
                                       
                                        <div class="sq-q-options">
                                            @foreach($bankOpts as $opt)
                                                <span class="sq-opt-badge {{ $opt['is_correct'] ? 'is-correct-opt' : '' }}">
                                                    @if($opt['is_correct']) <i class="fas fa-check-circle me-1"></i> @endif
                                                    <strong>{{ $opt['label'] }}:</strong> {{ $opt['text'] }}
                                                </span>
                                            @endforeach
                                        </div>

                                        @if(!empty($question->hindi_question))
                                            <div class="sq-hindi-question">
                                                {{ $question->hindi_question }}
                                            </div>

                                            @if(!empty($bankHindiOpts))
                                                <div class="sq-q-options">
                                                    @foreach($bankHindiOpts as $opt)
                                                        <span class="sq-opt-badge {{ $opt['is_correct'] ? 'is-correct-opt' : '' }}">
                                                            @if($opt['is_correct'])
                                                                <i class="fas fa-check-circle me-1"></i>
                                                            @endif
                                                            <strong>{{ $opt['label'] }}:</strong> {{ $opt['text'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                        {{-- <div class="sq-q-correct"><i class="fas fa-check-circle me-1"></i> Correct: {{ $question->correct_answer }}</div> --}}
                                    </div>
                                    {{-- <button type="button" class="btn btn-sm btn-outline-primary btn-add-q flex-shrink-0" 
                                            onclick="addQuestionToSelected({{ $question->id }})" 
                                            {{ $isSelected ? 'disabled' : '' }}>
                                        <i class="fas {{ $isSelected ? 'fa-check' : 'fa-plus' }}"></i> 
                                        <span class="btn-text">{{ $isSelected ? 'Added' : 'Add' }}</span>
                                    </button> --}}
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">No published questions found in bank.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Selected Questions & Drag and Drop Reorder -->
            <div class="sq-right-col">
                <form method="POST" action="{{ route('superadmin.quizzes.questions.store', $quizId) }}" id="selectedQuestionsForm">
                    @csrf
                    <input type="hidden" name="quiz_id" value="{{ $quizId }}">
                    <div class="card shadow-sm h-100 border-0" style="border:1px solid #e2e8f0 !important; border-radius:12px;">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
                            <div>
                                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-check-double text-success me-1"></i> Selected Questions</h6>
                            </div>
                            <div>
                                <span class="badge bg-primary fs-6 px-3 py-2" style="border-radius: 20px;">
                                    <span id="selectedCount">0</span> / {{ $effectiveLimit > 0 ? $effectiveLimit : '∞' }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body sq-selected-body">
                            <div class="sq-drop-hint mb-3" style="margin-bottom:10px;">
                                <i class="fas fa-grip-vertical me-1 text-primary"></i> Drag items up/down using handle <span class="drag-icon font-weight-bold">⋮⋮</span> to reorder questions.
                            </div>
                            
                            <div class="sq-selected-list" id="selectedListContainer">
                                @php $index = 1; @endphp
                                @foreach($selectedQuestions as $qId => $sq)
                                    @php
                                        $pivotOrder = optional($sq->pivot)->order ?? $index;
                                        $pivotBot = optional($sq->pivot)->bot_correct_answer ?? 'no';
                                        $pivotSwitch = (bool) (optional($sq->pivot)->is_switch_question ?? false);
                                        $sqOpts = $getOptionsList($sq->options, $sq->correct_answer);
                                        if (empty($sqOpts) && isset($sq->getAttributes()['options'])) {
                                            $sqOpts = $getOptionsList($sq->getAttributes()['options'], $sq->correct_answer);
                                        }

                                        $sqHindiOpts = $getOptionsList(
                                            $sq->hindi_options,
                                            $sq->hindi_correct_answer
                                        );

                                        if (
                                            empty($sqHindiOpts) &&
                                            isset($sq->getAttributes()['hindi_options'])
                                        ) {
                                            $sqHindiOpts = $getOptionsList(
                                                $sq->getAttributes()['hindi_options'],
                                                $sq->hindi_correct_answer
                                            );
                                        }
                                    @endphp
                                    <div class="sq-selected-card {{ $pivotSwitch ? 'is-switch-card' : '' }}" draggable="true" data-id="{{ $sq->id }}">
                                        <input type="hidden" name="question_ids[]" value="{{ $sq->id }}">
                                        <input type="hidden" name="order[{{ $sq->id }}]" class="sq-order-input" value="{{ $pivotOrder }}">
                                        <input type="hidden" name="is_switch_question[{{ $sq->id }}]" class="sq-switch-input" value="{{ $pivotSwitch ? 1 : 0 }}">
                                        
                                        <div class="sq-drag-handle" title="Drag to reorder">⋮⋮</div>
                                        <div class="sq-order-badge">{{ $pivotOrder }}</div>
                                        
                                        <div class="sq-card-detail">
                                            <div class="sq-q-text">
                                                @if($pivotSwitch)
                                                    <span class="sq-switch-badge badge me-1" style="background:#8b5cf6; color:#fff; font-size:11px;"><i class="fas fa-sync-alt me-1"></i> Switch Question</span>
                                                @endif
                                                {{ $sq->question_text }}
                                            </div>
                                            <div class="sq-q-options">
                                                @foreach($sqOpts as $opt)
                                                    <span class="sq-opt-badge {{ $opt['is_correct'] ? 'is-correct-opt' : '' }}">
                                                        @if($opt['is_correct']) <i class="fas fa-check-circle me-1"></i> @endif
                                                        <strong>{{ $opt['label'] }}:</strong> {{ $opt['text'] }}
                                                    </span>
                                                @endforeach
                                            </div>

                                            @if(!empty($sq->hindi_question))
                                                <div class="sq-hindi-question">
                                                    {{ $sq->hindi_question }}
                                                </div>

                                                @if(!empty($sqHindiOpts))
                                                    <div class="sq-q-options">
                                                        @foreach($sqHindiOpts as $opt)
                                                            <span class="sq-opt-badge {{ $opt['is_correct'] ? 'is-correct-opt' : '' }}">
                                                                @if($opt['is_correct'])
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                @endif
                                                                <strong>{{ $opt['label'] }}:</strong> {{ $opt['text'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            <div class="sq-bot-wrapper mt-2 d-flex flex-wrap align-items-center gap-2">
                                                <div class="sq-bot-pill-toggle">
                                                    <span class="bot-pill-label">Bot Correct:</span>
                                                    <input type="hidden" name="bot_correct_answer[{{ $sq->id }}]" class="sq-bot-input" value="{{ $pivotBot }}">
                                                    <div class="sq-pill-group">
                                                        <button type="button" class="sq-pill-btn {{ $pivotBot === 'no' ? 'active-no' : '' }}" onclick="setBotValue(this, 'no')">No</button>
                                                        <button type="button" class="sq-pill-btn {{ $pivotBot === 'yes' ? 'active-yes' : '' }}" onclick="setBotValue(this, 'yes')">Yes</button>
                                                    </div>
                                                </div>

                                                @if($hasSwitchOption)
                                                    <div class="sq-switch-pill-toggle ms-2 mt-6">
                                                        <span class="switch-pill-label" style="font-size: 11px; font-weight: 700; color: #6b21a8;"><i class="fas fa-sync-alt me-1"></i> Switch Q:</span>
                                                        <div class="sq-pill-group">
                                                            <button type="button" class="sq-pill-btn {{ !$pivotSwitch ? 'active-no' : '' }}" onclick="setSwitchValue(this, 0)">No</button>
                                                            <button type="button" class="sq-pill-btn {{ $pivotSwitch ? 'active-switch' : '' }}" onclick="setSwitchValue(this, 1)">Yes</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-q flex-shrink-0" onclick="removeQuestionFromSelected({{ $sq->id }})" title="Remove Question">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </button>
                                    </div>
                                    @php $index++; @endphp
                                @endforeach
                            </div>

                            <div id="emptySelectedState" class="sq-empty-state" style="{{ $selectedQuestions->count() > 0 ? 'display:none;' : '' }}">
                                <i class="fas fa-hand-pointer fa-2x mb-2 text-muted"></i>
                                <p class="mb-1 fw-bold">No questions selected yet.</p>
                                <small>Click <strong>"+ Add"</strong> on any question from the left bank.</small>
                            </div>
                        </div>

                        <div class="card-footer text-end bg-white py-3 border-top">
                            <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2" style="margin-top:16px; border-radius: 8px;">
                                <i class="fas fa-save me-1"></i> {{ ($quizType === 'fastest_finger_first' || ($editing?->visibility ?? '') === 'fastest_finger_first') ? 'Save Questions' : 'Save Questions & Proceed to Step 3' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning mt-4 text-center p-4" style="border-radius: 12px;">
        <i class="fas fa-exclamation-circle fa-2x mb-2 text-warning"></i>
        <h5 class="fw-bold">Step 1 (Quiz Details) Required</h5>
        <p class="mb-0">Please fill out Step 1 (Quiz Details) and click Save first before selecting questions.</p>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const limitVal = parseInt(document.getElementById('questionLimitVal')?.value || 0, 10);
    const hasSwitchOption = parseInt(document.getElementById('hasSwitchOptionVal')?.value || 0, 10) === 1;
    const bankList = document.getElementById('questionBankList');
    const bankWrapper = document.getElementById('questionBankWrapper');
    const selectedList = document.getElementById('selectedListContainer');
    const emptyState = document.getElementById('emptySelectedState');
    const selectedCountSpan = document.getElementById('selectedCount');
    const limitBanner = document.getElementById('limitAlertBanner');
    // const bankSearchInput = document.getElementById('bankSearchInput');

    window.setSwitchValue = function(btn, val) {
        const selCard = btn.closest('.sq-selected-card');
        if (!selCard || !selectedList) return;

        if (val === 1) {
            selectedList.appendChild(selCard);
        }
        updateStateAndLimit();
    };

    // Toggle Bot Correct Value Pill
    window.setBotValue = function(btn, val) {
        const toggleWrapper = btn.closest('.sq-bot-pill-toggle');
        if (!toggleWrapper) return;

        const input = toggleWrapper.querySelector('.sq-bot-input');
        if (input) input.value = val;

        const buttons = toggleWrapper.querySelectorAll('.sq-pill-btn');
        buttons.forEach(b => {
            b.classList.remove('active-no', 'active-yes');
        });

        if (val === 'yes') {
            btn.classList.add('active-yes');
        } else {
            btn.classList.add('active-no');
        }
    };

    

    /* PAHLE WALI 
    function buildOptionsHtml(optionsData, correctAnswer) {
        let opts = optionsData;

        if (typeof opts === 'string') {
            try {
                // 1. HTML entities ko pehle normal quotes mein convert karein
                let cleanString = opts.replace(/&quot;/g, '"')
                                    .replace(/&amp;/g, '&')
                                    .replace(/&lt;/g, '<')
                                    .replace(/&gt;/g, '>');
                
                // 2. Ab parse karein
                opts = JSON.parse(cleanString);
            } catch (e) {
                console.warn("Failed to parse options JSON:", e);
                return '<span style="color: #ef4444; font-size: 12px;">Invalid options format</span>';
            }
        }

        if (!opts || (Array.isArray(opts) && opts.length === 0)) {
            return '<span style="color: #9ca3af; font-size: 12px;">No options available</span>';
        }

        const cleanCorrect = String(correctAnswer || '').toLowerCase().trim();
        const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        let html = '<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">';

        if (Array.isArray(opts)) {
            opts.forEach((item, idx) => {
                let text = '';
                let label = letters[idx] || (idx + 1);
                let isCorrect = false;

                if (item && typeof item === 'object') {
                    text = item.text !== undefined ? item.text : (item.option || item.title || item.value || JSON.stringify(item));
                    label = item.label || label;
                    isCorrect = !!item.is_correct;
                } else {
                    text = String(item);
                }

                text = String(text).trim();
                if (text === '' && text !== '0') return;

                const cleanVal = text.toLowerCase();
                const cleanLabel = String(label).toLowerCase().trim();

                if (!isCorrect && cleanCorrect !== '') {
                    isCorrect = (cleanVal === cleanCorrect || 
                                cleanLabel === cleanCorrect || 
                                ('option ' + cleanLabel) === cleanCorrect ||
                                ('option_' + cleanLabel) === cleanCorrect ||
                                ('option ' + (idx + 1)) === cleanCorrect);
                }

                const correctClass = isCorrect ? 'is-correct-opt' : '';
                const bgStyle = isCorrect ? 'background: #dcfce7; border-color: #86efac; color: #166534;' : 'background: #f3f4f6; border-color: #e5e7eb; color: #374151;';
                const checkIcon = isCorrect ? '<i class="fas fa-check-circle me-1" style="color: #10b981;"></i>' : '';

                html += `<span class="sq-opt-badge ${correctClass}" style="padding: 6px 10px; border-radius: 6px; border: 1px solid; font-size: 12.5px; display: inline-flex; align-items: center; ${bgStyle}">${checkIcon}<strong>${label}:</strong>&nbsp;${text}</span>`;
            });
        }
        
        html += '</div>';
        return html;
    }*/

 
      function buildOptionsHtml(optionsData, correctAnswer) {
        let opts = optionsData;
        /*
         * JSON decode
         */
        if (typeof opts === 'string') {
            try {
                // HTML entities decode
                let cleanString = opts
                    .replace(/&quot;/g, '"')
                    .replace(/&#039;/g, "'")
                    .replace(/&amp;/g, '&')
                    .replace(/&lt;/g, '<')
                    .replace(/&gt;/g, '>');

                opts = JSON.parse(cleanString);

            } catch (e) {
                /*
                 * Agar first decode fail ho to direct JSON parse try
                 */
                try {
                    opts = JSON.parse(opts);
                } catch (e2) {

                    console.warn(
                        'Failed to parse options JSON:',
                        opts
                    );

                    return '<span style="color:#ef4444;font-size:12px;">Invalid options format</span>';
                }
            }
        }

        /*
         * Kabhi JSON double encoded ho sakta hai
         */
        if (typeof opts === 'string') {

            try {
                opts = JSON.parse(opts);
            } catch (e) {
                // keep existing value
            }
        }

        /*
         * Empty options
         */
        if (!opts || (Array.isArray(opts) && opts.length === 0)) {

            return `
                <span style="color:#9ca3af;font-size:12px;">
                    No options available
                </span>
            `;
        }

        /*
         * IMPORTANT:
         *
         * Database ke correct_answer column ko authority maanenge.
         *
         * Example:
         *
         * correct_answer = "1"
         *
         * to option text "1" search hoga.
         *
         * Ye "1" ko option number 1 nahi samjhega.
         */

        const cleanCorrect = String(
            correctAnswer ?? ''
        ).trim();


        const letters = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H'
        ];


        let html = `
            <div style="
                display:flex;
                flex-wrap:wrap;
                gap:8px;
                margin-top:8px;
            ">
        `;


        if (Array.isArray(opts)) {
            opts.forEach(function(item, idx) {
                let text = '';
                let label =
                    letters[idx] ||
                    (idx + 1);
                /*
                 * Option object
                 */
                if (
                    item &&
                    typeof item === 'object'
                ) {

                    text =
                        item.text !== undefined
                            ? item.text
                            : (
                                item.option ||
                                item.title ||
                                item.value ||
                                ''
                            );

                    /* * Agar database me custom label hai * to use kar sakte hain,  * warna A/B/C/D... */
                    label =
                        item.label ||
                        label;

                } else {
                    /* * Normal string option */
                    text = String(item ?? '');
                }
                text = String(text).trim();
                /*  * Empty option skip */
                if (text === '' && text !== '0') {
                    return;
                }

                /* =====================================================
                   MAIN FIX
                   
                   Sirf actual option TEXT ko correct_answer se compare
                   karenge.
                   
                   NO:
                   - label matching
                   - option 1 matching
                   - option A matching
                   - index matching
                   - is_correct matching
                   
                   ===================================================== */

                const isCorrect =
                    cleanCorrect !== '' &&
                    text === cleanCorrect;

                const correctClass =
                    isCorrect
                        ? 'is-correct-opt'
                        : '';

                const bgStyle =
                    isCorrect
                        ? 'background:#dcfce7;border-color:#86efac;color:#166534;'
                        : 'background:#f3f4f6;border-color:#e5e7eb;color:#374151;';

                const checkIcon =
                    isCorrect
                        ? '<i class="fas fa-check-circle me-1" style="color:#10b981;"></i>'
                        : '';


                /*
                 * Same old HTML/design maintain kiya hai.
                 * Isse existing CSS/functionality disturb nahi hogi.
                 */

                html += `
                    <span
                        class="sq-opt-badge ${correctClass}"
                        style="
                            padding:6px 10px;
                            border-radius:6px;
                            border:1px solid;
                            font-size:12.5px;
                            display:inline-flex;
                            align-items:center;
                            ${bgStyle}
                        "
                    >
                        ${checkIcon}
                        <strong>${label}:</strong>&nbsp;${text}
                    </span>
                `;
            });
        }

        html += '</div>';
        return html;
    }


    // 1. Bank Search Filter
    // if (bankSearchInput) {
    //     bankSearchInput.addEventListener('keyup', function() {
    //         const term = this.value.toLowerCase().trim();
    //         if (!bankList) return;
    //         const cards = bankList.querySelectorAll('.sq-bank-card');
    //         cards.forEach(card => {
    //             const text = card.dataset.text.toLowerCase();
    //             card.style.display = text.includes(term) ? 'flex' : 'none';
    //         });
    //     });
    // }

    // *********used unused toggel with filter start*******
    // ==========================================
        // 1. Usage Filter (Used / Unused) + Search Logic
     
        let currentUsageFilter = 'all'; // ✅ Default: All (pehle 'unused' tha)
        let currentTypeFilter = 'all';

        const usageFilterToggle = document.getElementById('usageFilterToggle');
        const questionTypeFilter = document.getElementById('questionTypeFilter');
        const bankSearchInput = document.getElementById('bankSearchInput');

        // Toggle button click handler (All/Used/Unused)
        if (usageFilterToggle) {
            usageFilterToggle.addEventListener('click', function(e) {
                if (e.target.classList.contains('sq-pill-btn')) {
                    const buttons = usageFilterToggle.querySelectorAll('.sq-pill-btn');
                    
                    // Saare active classes hatao
                    buttons.forEach(btn => btn.classList.remove('active-no', 'active-yes', 'active-all'));
                    
                    // Naya filter set karo
                    currentUsageFilter = e.target.dataset.filter;
                    
                    // Sahi active class add karo
                    if (currentUsageFilter === 'all') {
                        e.target.classList.add('active-all');
                    } else if (currentUsageFilter === 'unused') {
                        e.target.classList.add('active-no');
                    } else if (currentUsageFilter === 'used') {
                        e.target.classList.add('active-yes');
                    }
                    
                    applyCombinedFilter();
                }
            });
        }

        // Type Filter Dropdown change handler
        if (questionTypeFilter) {
            questionTypeFilter.addEventListener('change', function() {
                currentTypeFilter = this.value;
                applyCombinedFilter();
            });
        }

        // Combined Filter Function (Handles Search + Usage + Type)
        function applyCombinedFilter() {
            if (!bankList) return;
            
            const searchTerm = bankSearchInput ? bankSearchInput.value.toLowerCase().trim() : '';
            const cards = bankList.querySelectorAll('.sq-bank-card');
            
            cards.forEach(card => {
                const text = card.dataset.text.toLowerCase();
                const isUsed = card.dataset.isUsed === '1';
                const questionType = card.dataset.type || 'mcq';
                
                // ✅ Check Usage Condition (Updated for "All")
                let showByUsage = false;
                if (currentUsageFilter === 'all') {
                    showByUsage = true;  // Sab dikhao
                } else if (currentUsageFilter === 'unused') {
                    showByUsage = !isUsed;
                } else if (currentUsageFilter === 'used') {
                    showByUsage = isUsed;
                }
                
                // Check Type Condition
                let showByType = false;
                if (currentTypeFilter === 'all') {
                    showByType = true;
                } else {
                    showByType = questionType === currentTypeFilter;
                }
                
                // Check Search Condition
                const showBySearch = text.includes(searchTerm);
                
                // Show card ONLY if ALL conditions are true
                if (showByUsage && showByType && showBySearch) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Attach keyup event to search input
        if (bankSearchInput) {
            bankSearchInput.addEventListener('keyup', applyCombinedFilter);
        }

        // Initial call to apply default filters on page load
        applyCombinedFilter();
    // *********used unused toggel with filter end*********

    // 2. Add Question Function
    window.addQuestionToSelected = function(id) {
        if (!selectedList) return;
        const currentCount = selectedList.querySelectorAll('.sq-selected-card').length;
        if (limitVal > 0 && currentCount >= limitVal) {
            if (typeof showAlert === 'function') {
                showAlert('warning', 'Question Limit Reached', 'You can select maximum ' + limitVal + ' questions.');
            } else {
                alert('Question limit reached! Maximum ' + limitVal + ' questions allowed.');
            }
            return;
        }

        const bankCard = bankList ? bankList.querySelector(`.sq-bank-card[data-id="${id}"]`) : null;
        if (!bankCard) return;

        const qText = bankCard.dataset.text;
        const qCorrect = bankCard.dataset.correct;
        const optionsHtml = buildOptionsHtml(bankCard.dataset.options, qCorrect);


        const hindiText = bankCard.dataset.hindiText || '';
        const hindiCorrect = bankCard.dataset.hindiCorrect || '';
        const hindiOptionsHtml = buildOptionsHtml( bankCard.dataset.hindiOptions || '[]', hindiCorrect);



        const addBtn = bankCard.querySelector('.btn-add-q');

        bankCard.classList.add('is-selected');
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-check"></i> <span class="btn-text">Added</span>';
        }

        const switchHtml = hasSwitchOption ? `
            <div class="sq-switch-pill-toggle ms-2">
                <span class="switch-pill-label" style="font-size: 11px; font-weight: 700; color: #6b21a8;"><i class="fas fa-sync-alt me-1"></i> Switch Q:</span>
                <div class="sq-pill-group">
                    <button type="button" class="sq-pill-btn active-no" onclick="setSwitchValue(this, 0)">No</button>
                    <button type="button" class="sq-pill-btn" onclick="setSwitchValue(this, 1)">Yes</button>
                </div>
            </div>
        ` : '';

        const newOrder = currentCount + 1;
        const cardElem = document.createElement('div');
        cardElem.className = 'sq-selected-card';
        cardElem.setAttribute('draggable', 'true');
        cardElem.setAttribute('data-id', id);

        cardElem.innerHTML = `
            <input type="hidden" name="question_ids[]" value="${id}">
            <input type="hidden" name="order[${id}]" class="sq-order-input" value="${newOrder}">
            <input type="hidden" name="is_switch_question[${id}]" class="sq-switch-input" value="0">
            
            <div class="sq-drag-handle" title="Drag to reorder">⋮⋮</div>
            <div class="sq-order-badge">${newOrder}</div>
            
            <div class="sq-card-detail">
                <div class="sq-q-text">${qText}</div>
                <div class="sq-q-options">${optionsHtml}</div>
                
                ${hindiText ? `
                    <div class="sq-hindi-question">${hindiText}</div>
                    ${hindiOptionsHtml}
                ` : ''}

                <div class="sq-bot-wrapper mt-2 d-flex flex-wrap align-items-center gap-2">
                    <div class="sq-bot-pill-toggle">
                        <span class="bot-pill-label">Bot Correct:</span>
                        <input type="hidden" name="bot_correct_answer[${id}]" class="sq-bot-input" value="no">
                        <div class="sq-pill-group">
                            <button type="button" class="sq-pill-btn active-no" onclick="setBotValue(this, 'no')">No</button>
                            <button type="button" class="sq-pill-btn" onclick="setBotValue(this, 'yes')">Yes</button>
                        </div>
                    </div>
                    ${switchHtml}
                </div>
            </div>
            
            <button type="button" class="btn btn-sm btn-danger btn-remove-q flex-shrink-0" onclick="removeQuestionFromSelected(${id})" title="Remove Question">
                <i class="fas fa-trash-alt me-1"></i> Remove
            </button>
        `;

        selectedList.appendChild(cardElem);
        initCardDragEvents(cardElem);
        updateStateAndLimit();
    };

    // 3. Remove Question Function
    window.removeQuestionFromSelected = function(id) {
        if (!selectedList) return;
        const selCard = selectedList.querySelector(`.sq-selected-card[data-id="${id}"]`);
        if (selCard) {
            selCard.remove();
        }

        if (bankList) {
            const bankCard = bankList.querySelector(`.sq-bank-card[data-id="${id}"]`);
            if (bankCard) {
                bankCard.classList.remove('is-selected');
                const addBtn = bankCard.querySelector('.btn-add-q');
                if (addBtn) {
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="fas fa-plus"></i> <span class="btn-text">Add</span>';
                }
            }
        }

        updateStateAndLimit();
    };

    // 4. Update Orders & Limits
    function updateStateAndLimit() {
        if (!selectedList) return;
        const cards = selectedList.querySelectorAll('.sq-selected-card');
        const count = cards.length;

        cards.forEach((card, index) => {
            const orderNum = index + 1;
            const orderBadge = card.querySelector('.sq-order-badge');
            const orderInput = card.querySelector('.sq-order-input');

            if (orderBadge) orderBadge.textContent = orderNum;
            if (orderInput) orderInput.value = orderNum;

            if (hasSwitchOption && count > 0) {
                const isLast = (index === count - 1);
                const switchInput = card.querySelector('.sq-switch-input');
                if (switchInput) {
                    switchInput.value = isLast ? 1 : 0;
                }

                const qText = card.querySelector('.sq-q-text');
                let badge = card.querySelector('.sq-switch-badge');

                if (isLast) {
                    card.classList.add('is-switch-card');
                    if (qText && !badge) {
                        badge = document.createElement('span');
                        badge.className = 'sq-switch-badge badge me-1';
                        badge.style.cssText = 'background:#8b5cf6; color:#fff; font-size:11px;';
                        badge.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Switch Question';
                        qText.insertBefore(badge, qText.firstChild);
                    }
                } else {
                    card.classList.remove('is-switch-card');
                    if (badge) badge.remove();
                }

                const switchToggle = card.querySelector('.sq-switch-pill-toggle');
                if (switchToggle) {
                    const buttons = switchToggle.querySelectorAll('.sq-pill-btn');
                    buttons.forEach(b => b.classList.remove('active-switch', 'active-no'));
                    if (isLast && buttons[1]) {
                        buttons[1].classList.add('active-switch');
                    } else if (buttons[0]) {
                        buttons[0].classList.add('active-no');
                    }
                }
            }
        });

        if (selectedCountSpan) selectedCountSpan.textContent = count;

        if (emptyState) {
            emptyState.style.display = count === 0 ? 'block' : 'none';
        }

        if (limitVal > 0 && count >= limitVal) {
            if (limitBanner) limitBanner.style.display = 'flex';
            if (bankWrapper) bankWrapper.classList.add('limit-reached');
            
            if (typeof showAlert === 'function' && !window._limitAlertShown) {
                showAlert('warning', 'Limit Reached', 'Question limit reached (' + limitVal + ' max). Question bank has been faded.');
                window._limitAlertShown = true;
            }
        } else {
            if (limitBanner) limitBanner.style.display = 'none';
            if (bankWrapper) bankWrapper.classList.remove('limit-reached');
            window._limitAlertShown = false;
        }
    }

    // 5. Drag Events
    function initCardDragEvents(card) {
        card.addEventListener('dragstart', function(e) {
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            updateStateAndLimit();
        });
    }

    if (selectedList) {
        selectedList.querySelectorAll('.sq-selected-card').forEach(initCardDragEvents);

        selectedList.addEventListener('dragover', function(e) {
            e.preventDefault();
            const draggingCard = selectedList.querySelector('.sq-selected-card.dragging');
            if (!draggingCard) return;

            const afterElement = getDragAfterElement(selectedList, e.clientY);
            if (afterElement == null) {
                selectedList.appendChild(draggingCard);
            } else {
                selectedList.insertBefore(draggingCard, afterElement);
            }
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.sq-selected-card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    updateStateAndLimit();
});
</script>

 {{-- *************image show script start********** --}}
<script>
    // --- MODAL LOGIC ---
    function openImageModal(src) {
        var modal = document.getElementById("sqImageModal");
        var modalImg = document.getElementById("sqLargerImage");
        modal.style.display = "flex"; // Flex use kiya centering ke liye
        modalImg.src = src;
    }

    function closeImageModal(event) {
        // Sirf tab close ho jab background ya close button par click ho, image par nahi
        if (event.target.id === "sqImageModal" || event.target.classList.contains("sq-modal-close")) {
            document.getElementById("sqImageModal").style.display = "none";
        }
    }

  
    // --- AUDIO LOGIC (FIXED) ---
    function toggleAudio(btn) {
        // 1. Pehle parent .audio-box dhoondo, phir uske andar audio tag
        var audioBox = btn.closest('.audio-box');
        var audio = audioBox.querySelector('.hidden-audio');
        
        // Safety check: agar audio nahi mila toh ruk jao
        if (!audio) {
            console.error("Audio element not found!");
            return;
        }

        var iconPlay = btn.querySelector('.icon-play');
        var iconPause = btn.querySelector('.icon-pause');
        var btnText = btn.querySelector('.btn-text');

        // 2. Page ke baaki saare audios ko band kar do (ek time pe ek hi baje)
        document.querySelectorAll('.hidden-audio').forEach(function(a) {
            if (a !== audio) {
                a.pause();
                a.currentTime = 0;
                
                // Unke buttons ko reset karo
                var otherAudioBox = a.closest('.audio-box');
                var otherBtn = otherAudioBox.querySelector('.audio-play-btn');
                
                if (otherBtn) {
                    otherBtn.classList.remove('playing');
                    otherBtn.querySelector('.icon-play').style.display = 'inline';
                    otherBtn.querySelector('.icon-pause').style.display = 'none';
                    otherBtn.querySelector('.btn-text').innerText = 'Play';
                }
            }
        });

        // 3. Current audio ko play/pause karo
        if (audio.paused) {
            audio.play();
            btn.classList.add('playing');
            iconPlay.style.display = 'none';
            iconPause.style.display = 'inline';
            btnText.innerText = 'Playing...';
        } else {
            audio.pause();
            btn.classList.remove('playing');
            iconPlay.style.display = 'inline';
            iconPause.style.display = 'none';
            btnText.innerText = 'Play';
        }

        // 4. Jab audio khatam ho jaye, button ko wapas normal kar do
        audio.onended = function() {
            btn.classList.remove('playing');
            iconPlay.style.display = 'inline';
            iconPause.style.display = 'none';
            btnText.innerText = 'Play';
        };
    }
</script>

@endpush