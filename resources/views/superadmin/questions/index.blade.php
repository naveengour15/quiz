@extends('superadmin.layouts.app')
@section('title', 'Questions')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')
<!-- Add this at the top, before your existing content -->
@include('superadmin.questions.import')

<style>
/* Question card (blue border wala box) */
/* .q-card{
    background: #fbfcfe;
    border: 2px solid #4a89f3;
    border-radius: 10px;
    padding: 14px 16px;
    min-width: 280px;
} */

/* Question text */
.q-text{
    font-weight: 700;
    color: #1c2430;
    font-size: 15px;
    margin-bottom: 12px;
}

/* Options: ek ke baad ek box me */
.q-options{
    display: flex;
    flex-wrap: wrap;      /* zyada options ho to niche wrap honge */
    gap: 10px;
}

/* Option box */
.q-option{
    display: inline-block;
    padding: 8px 16px;
    background: #e9edf1;
    border: 1px solid #c6cdd5;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #2b3440;
    white-space: nowrap;
}

/* Correct answer: green box */
.q-correct{
    background: #d8f6e4;
    border: 1px solid #3ecf7a;
    color: #149a54;
}
/* .q-card{
    border: none;
    padding: 0;
    background: transparent;
} */

.question-upload-input-box{
     display: grid; 
     grid-template-columns: 1fr 1fr; 
     gap: 20px; 
     align-items: start;
}

.media-inline-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 10px;
    margin-bottom: 12px;
}

.audio-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.audio-play-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    background: #f8fafc;
    color: #0f172a;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    line-height: 1;
    transition: all 0.2s ease;
}

.audio-play-btn:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.12);
}

.audio-play-btn.playing {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}

.hidden-audio {
    display: none;
}

.media-box {
    position: relative;
    width: 92px;
    height: 92px;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    overflow: hidden;
    background: #f8fafc;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.media-box:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(59, 130, 246, 0.12);
}

.sq-q-thumb {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.zoom-hint {
    position: absolute;
    right: 6px;
    bottom: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.7);
    font-size: 11px;
    line-height: 1;
}

 @media(max-width:575px){
    .q-card {
        min-width: 100%;
    }
    .question-upload-input-box{
     grid-template-columns: 1fr; 
    }
    .media-inline-row {
        gap: 10px;
    }
    .media-box {
        width: 82px;
        height: 82px;
    }
 }

/* Image Thumbnail */
.sq-image-modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:99999;
    background:rgba(0,0,0,.75);
    align-items:center;
    justify-content:center;
    padding:20px;
}

.modal-inner-container{
    position:relative;
    max-width:90vw;
    max-height:90vh;
}

.sq-modal-content{
    display:block;
    max-width:90vw;
    max-height:85vh;
    width:auto;
    height:auto;
    object-fit:contain;
    border-radius:10px;
    background:#fff;
}

.sq-modal-close{
    position:absolute;
    top:-40px;
    right:0;
    color:#fff;
    font-size:32px;
    line-height:1;
    cursor:pointer;
}
</style>

    <div class="panel active">

        <div class="page-head">
            <div>
                <h1>Questions</h1>
                <p>The master question bank — manually authored, AI-generated, and imported content.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="white-space: nowrap" onclick="openImportModal()">
                    📥 Import
                </button>
                <button type="button" class="btn btn-primary" style="white-space: nowrap" onclick="openModal()">
                    Add Question
                </button>
            </div>
            {{-- <button type="button" class="btn btn-primary" onclick="openModal()">Add Question</button> --}}
        </div>

        <div class="search-filter-box">
            <form method="GET" class="toolbar">
                 <div class="question-search-filter-box">
                    <div class="question-search-filter-wrapper">
                        <div class="input-field">
                            <label class="lbl" for="search">Search</label>
                            <input class="tb-input" name="search" value="{{ request('search') }}" placeholder="Search question text…">
                        </div>

                        <div class="input-field">
                             <label class="lbl" for="search">Category</label>
                            <select class="tb-select" name="category_id" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="input-field">
                             <label class="lbl" for="search">Type</label>
                            <select class="tb-select" name="type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                @foreach (['mcq' => 'MCQ',  'image' => 'Image-based', 'audio' => 'Audio-based', 'video' => 'Video-based' , 'image_audio' => 'Image-Audio-based', 'fastest_finger_first' => 'Fastest Finger First'] as $val => $label)
                                    <option value="{{ $val }}" @selected(request('type') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="input-field">
                             <label class="lbl" for="search">Status</label>
                            <select class="tb-select" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                @foreach (['draft', 'published', 'archived'] as $s)
                                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="question-search-filter-btn">
                        <div class="tb-spacer"></div>
                        <button class="btn btn-primary" type="submit">Filter</button>
                    </div>
                 </div>
            </form>
        </div>

        <div class="table-wrap question-table-info">
            <div class="question-table-box">
            <table class="question-table-wrapper">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Sub-ategory</th>
                        <th>Type</th>
                        <th>Difficulty</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                        <tr>
                            {{-- <td data-label="Question">{{ str($q->question_text)->limit(60) }}</td> --}}

                            {{-- **********quesiton and answer start*********** --}}

                                @php
                                    $type = strtolower(trim((string) $q->type));

                                    /* English Data*/
                                    $options = $q->options;

                                    if (is_string($options)) {
                                        $options = json_decode($options, true);
                                    }

                                    $options = is_array($options) ? $options : [];
                                    $correct = $q->correct_answer ?? $q->bot_correct_answer;

                                    /* Hindi Data  */
                                    $hindiOptions = $q->hindi_options;

                                    if (is_string($hindiOptions)) {
                                        $hindiOptions = json_decode($hindiOptions, true);
                                    }

                                    $hindiOptions = is_array($hindiOptions) ? $hindiOptions : [];

                                    $hindiCorrect = $q->hindi_correct_answer;

                                    $hasEnglish = !empty(trim((string) $q->question_text));
                                    $hasHindi = !empty(trim((string) $q->hindi_question));
                                @endphp

                            <td data-label="Sr. No.">{{ $questions->firstItem() + $loop->index }}</td>

                            <td data-label="Question">
                        
                                <div class="q-card">

                                 {{-- Question Audio + Image --}}
                                    <div class="media-inline-row">
                                          {{-- Image Thumbnail --}}
                                        @if(!empty($q->media_url))
                                            @php
                                                // $imageUrl = Storage::disk('s3')->url($q->media_url);
                                                $imageUrl = $q->media_url;
                                            @endphp

                                            <div class="media-box"
                                                onclick="openImageModal(@js($imageUrl))">

                                                <img src="{{ $imageUrl }}"
                                                    alt="Question Image"
                                                    class="sq-q-thumb">

                                                <div class="zoom-hint">🔍</div>
                                            </div>
                                        @endif

                                        {{-- Audio Icon --}}
                                        @if(!empty($q->question_audio))
                                            <div class="audio-box">
                                                <button type="button" class="audio-play-btn" onclick="toggleAudio(this)">
                                                    <span class="icon-play">▶️</span>
                                                    <span class="icon-pause" style="display:none;">⏸️</span>
                                                    <span class="btn-text">Play Audio</span>
                                                </button>

                                                <audio class="hidden-audio" preload="none">
                                                    {{-- <source src="{{ Storage::disk('s3')->url($q->question_audio) }}"> --}}
                                                    <source src="{{ $q->question_audio }}">
                                                </audio>
                                            </div>
                                        @endif

                                      

                                    </div>

                                             
                                    {{-- ==========================================================  ENGLISH QUESTION  =========================================================== --}}
                                    @if($hasEnglish)
                                        <div class="q-text">
                                            {{ str($q->question_text)->limit(100) }}
                                        </div>
                                        
                                        {{-- English MCQ Options --}}
                                        {{-- @if($type === 'mcq' && count($options)) --}}
                                        @if(in_array($type, ['mcq','true_false','image','audio','video','puzzle','fastest_finger_first','image_audio']) && count($options))
                                            <div class="q-options">
                                                @foreach($options as $key => $option)
                                                    @php
                                                        $optionText = is_array($option)
                                                            ? ($option['text']
                                                                ?? $option['option']
                                                                ?? $option['title']
                                                                ?? json_encode($option))
                                                            : $option;

                                                        $letter = is_numeric($key)
                                                            ? chr(65 + (int) $key)
                                                            : strtoupper((string) $key);
                                                        $isCorrect = false;

                                                        if ($correct !== null && $correct !== '') {
                                                            $correctStr = trim((string) $correct);
                                                            // $isCorrect =
                                                            //     $correctStr === trim((string) $optionText)
                                                            //     || $correctStr === trim((string) $key)
                                                            //     || $correctStr === $letter;

                                                            $isCorrect = $correctStr === trim((string) $optionText);
                                                        }
                                                    @endphp
                                                    <span class="q-option {{ $isCorrect ? 'q-correct' : '' }}">
                                                        {{ $letter }}: {{ $optionText }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif(in_array($type, ['true_false', 'true-false', 'truefalse', 'boolean'], true))
                                            <div class="q-options">
                                                <span class="q-option q-correct">
                                                    {{ $correct ?: '-' }}
                                                </span>
                                            </div>
                                        @endif
                                    @endif


                                    {{-- ==========================================================  HINDI QUESTION=========================================================== --}}
                                    @if($hasHindi)

                                        <div style="margin-top:14px; padding-top:12px; border-top:1px solid #e5e7eb; ">

                                            {{-- <div style="font-size:11px;font-weight:700; color:#7c3aed; margin-bottom:5px; text-transform:uppercase;">
                                                Hindi
                                            </div> --}}

                                            <div class="q-text">
                                                {{ str($q->hindi_question)->limit(100) }}
                                            </div>
                                            {{-- Hindi MCQ Options --}}
                                            {{-- @if($type === 'mcq' && count($hindiOptions)) --}}
                                             @if(in_array($type, ['mcq','true_false','image','audio','video','puzzle','fastest_finger_first','image_audio']) && count($hindiOptions))
                                                <div class="q-options">
                                                    @foreach($hindiOptions as $key => $option)
                                                        @php
                                                            $optionText = is_array($option)
                                                                ? ($option['text']
                                                                    ?? $option['option']
                                                                    ?? $option['title']
                                                                    ?? json_encode($option))
                                                                : $option;
                                                            $letter = is_numeric($key)
                                                                ? chr(65 + (int) $key)
                                                                : strtoupper((string) $key);
                                                            $isHindiCorrect = false;
                                                            if ($hindiCorrect !== null && $hindiCorrect !== '') {
                                                                $hindiCorrectStr = trim((string) $hindiCorrect);
                                                                // $isHindiCorrect =
                                                                //     $hindiCorrectStr === trim((string) $optionText)
                                                                //     || $hindiCorrectStr === trim((string) $key)
                                                                //     || $hindiCorrectStr === $letter;
                                                                $isHindiCorrect = $hindiCorrectStr === trim((string) $optionText);
                                                            }
                                                        @endphp
                                                        <span class="q-option {{ $isHindiCorrect ? 'q-correct' : '' }}">
                                                            {{ $letter }}: {{ $optionText }}
                                                        </span>
                                                    @endforeach
                                                </div>

                                            @elseif(in_array($type, ['true_false', 'true-false', 'truefalse', 'boolean'], true))
                                                <div class="q-options">
                                                    <span class="q-option q-correct">
                                                        {{ $hindiCorrect ?: '-' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- ========================================================== ONLY HINDI DATA =========================================================== --}}
                                    @if(!$hasEnglish && !$hasHindi)
                                        <div class="q-text">
                                            —
                                        </div>
                                    @endif
                                </div>

                               
                            </td>
                            {{-- ******************************end code********** --}}

                            <td data-label="Category">{{ $q->category->name ?? '—' }}</td>
                            <td data-label="Sub Category">{{ $q->subCategory->name ?? '—' }}</td>
                            <td data-label="Type">{{ str($q->type)->replace('_', ' ')->title() }}</td>
                            <td data-label="Difficulty">
                                @php $diffClass = ['easy'=>'success','medium'=>'warn','hard'=>'danger','adaptive'=>'info'][$q->difficulty] ?? 'neutral'; @endphp
                                <span class="badge {{ $diffClass }}">{{ ucfirst($q->difficulty) }}</span>
                            </td>
                            <td data-label="Source">{{ str($q->source)->replace('_', ' ')->title() }}</td>
                            {{-- <td>
                                @php $stClass = ['draft'=>'neutral','published'=>'success','archived'=>'neutral'][$q->status] ?? 'neutral'; @endphp
                                <span class="badge {{ $stClass }}">{{ ucfirst($q->status) }}</span>
                                @if ($q->moderation_status === 'pending')<span class="badge warn">Flagged</span>@endif
                            </td> --}}

                            <td data-label="Status">
                                <select class="status-change" data-id="{{ $q->id }}">
                                    <option value="draft" {{ $q->status == 'draft' ? 'selected' : '' }}>
                                        Draft
                                    </option>

                                    <option value="published" {{ $q->status == 'published' ? 'selected' : '' }}>
                                        Published
                                    </option>

                                    <option value="archived" {{ $q->status == 'archived' ? 'selected' : '' }}>
                                        Archived
                                    </option>
                                </select>
                            </td>
                            <td data-label="Actions" class="row-actions">
                                <div style="display:flex;gap:10px;padding: 16px 0px;">
                                    <a class="icon-btn"
                                        href="{{ route('superadmin.questions.index', ['edit' => $q->id]) }}">✎</a>
                                    <form method="POST" action="{{ route('superadmin.questions.destroy', $q) }}">
                                        @csrf @method('DELETE')
                                        <button class="icon-btn danger" type="submit">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--ink-faint);">No questions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            {{-- <div class="pagination pagination-section">
                <span>Showing {{ $questions->firstItem() }}–{{ $questions->lastItem() }} of
                    {{ $questions->total() }} questions
                </span>
                    {{ $questions->links() }}
            </div> --}}
            <div class="pagination">
                @include('common.pagination.superadmin_pagination', ['pageData' => $questions])
            </div>
        </div>

        <div id="questionModal" class="modal {{ $editing || $errors->any() ? 'active' : '' }}">
            <div class="modal-backdrop" onclick="closeModal()"></div>
            <div class="modal-content form-card" style="margin-bottom:0; max-width:700px;">
                <div class="fh">
                    <div>
                        <h3 style="display:inline-block; margin-right: 10px;">
                            {{ $editing ? 'Edit Question' : 'Add Question' }}</h3>
                        <span>AI-generated fields are editable before publish</span>
                    </div>
                    <button type="button" class="icon-btn" onclick="closeModal()"
                        style="border:none;background:transparent;box-shadow:none;">✕</button>
                </div>
                <form method="POST" enctype="multipart/form-data"
                    action="{{ $editing ? route('superadmin.questions.update', $editing) : route('superadmin.questions.store') }}">
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    @php
                        $selectedQuestionType = old('type', $editing->type ?? 'mcq');
                        $isTypeLocked = (bool) $editing;
                    @endphp

                    <div class="field full">
                        <label>Question Type <span class="req">*</span></label>
                        <div class="type-choice-list" id="questionType" role="radiogroup" aria-label="Question Type">
                            @foreach ([
                                'mcq' => 'MCQ',
                                'fastest_finger_first' => 'Fastest Finger First',
                                'image_audio' => 'Image + Audio',
                                'image' => 'Image-based',
                                'audio' => 'Audio-based',
                            ] as $val => $label)
                                <label class="type-option" data-type="{{ $val }}">
                                    <input type="radio" name="type" value="{{ $val }}"
                                        @checked($selectedQuestionType === $val)
                                        @disabled($isTypeLocked && $selectedQuestionType !== $val)
                                        required>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-grid form-grid-02">
                        <div class="field full">
                            <label>Question Text </label>
                            <textarea name="question_text" placeholder="Type the question exactly as it should appear…">{{ $editing->question_text ?? old('question_text') }}</textarea>
                        </div>


                        {{-- *************audio text start************** --}}
       
                        <div class="field full" id="questionAudioField" style="display:none; margin-top: 15px;">
                            <label style="font-weight: 700; margin-bottom: 8px; display: block; font-size: 14px;">
                                🎵 Question Audio <span class="hint">— (Optional) Attach an audio file</span>
                            </label>

                            <div class="question-upload-input-box">
                                
                                {{-- LEFT SIDE: Upload Input --}}
                                <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                                    <input type="file" 
                                        name="question_audio" 
                                        id="question_audio" 
                                        accept="audio/*" 
                                        style="width: 100%; font-size: 13px;">
                                    
                                    <p style="font-size: 11px; color: #64748b; margin-top: 8px;">
                                        Supported: MP3, WAV, OGG, M4A (Max 10MB)
                                    </p>
                                    
                                    @if($editing && !empty($editing->question_audio))
                                        <p style="font-size: 11px; color: #ef4444; margin-top: 8px; font-style: italic; font-weight: 600;">
                                            ⚠️ Uploading a new file will replace the current one.
                                        </p>
                                    @endif
                                </div>

                                {{-- RIGHT SIDE: Preview Player --}}
                                <div style="background: #f1f5f9; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 80px; display: flex; align-items: center; justify-content: center;">
                                    
                                    @if($editing && !empty($editing->question_audio))
                                        {{-- Edit Mode: Existing S3 Audio --}}
                                        <audio id="audioPreviewPlayer" controls style="width: 100%;">
                                            {{-- <source src="{{ Storage::disk('s3')->url($editing->question_audio) }}" type="audio/mpeg"> --}}
                                            <source src="{{ $editing->question_audio }}" type="audio/mpeg">
                                            Your browser does not support audio playback.
                                        </audio>
                                    @else
                                        {{-- Add Mode: Hidden initially, shown via JS --}}
                                        <audio id="audioPreviewPlayer" controls style="width: 100%; display: none;"></audio>
                                        <span id="audioEmptyState" style="color: #94a3b8; font-size: 13px; font-weight: 500;">
                                            🔇 No audio selected yet
                                        </span>
                                    @endif

                                </div>
                            </div>
                        </div>

                         {{-- **********quesiton and answer start*********** --}}
                        @php
                            $optionCount = in_array($selectedQuestionType, ['fastest_finger_first'], true) ? 2 : 4;
                        @endphp
                        <div class="field full">
                            <label>Answer Options <span id="englishOptionHint" class="hint">— {{ $optionCount }} options required for this type</span></label>
                            <div class="opt-list opt-list-grid-box" id="options-container">
                                @php
                                    $rawOpts = old('options', $editing->options ?? array_fill(0, $optionCount, ''));
                                    $opts = is_array($rawOpts) ? $rawOpts : array_fill(0, $optionCount, '');
                                    $opts = array_pad($opts, $optionCount, '');
                                    $currentCorrect = $editing->correct_answer ?? old('correct_answer');
                                @endphp

                                @foreach ($opts as $i => $opt)
                                    @if ($i >= $optionCount)
                                        @continue
                                    @endif
                                    <div class="opt-row" style="display: flex; align-items: center; gap: 8px;">
                                        <input type="radio" name="correct_option_radio" class="correct-radio-btn" data-index="{{ $i }}" value="{{ $opt }}"
                                            {{ $currentCorrect === $opt && $opt !== '' ? 'checked' : '' }}
                                            style="width:16px; height:16px; margin:0;cursor:pointer; flex-shrink:0;accent-color:#10b981;">
                                        <input type="text" name="options[]" class="option-input-field" value="{{ $opt }}" placeholder="Option {{ $i + 1 }}" style="flex: 1; width: 100%;">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="field full">
                            <label>Correct Answer  <span class="hint">— must match one option exactly</span></label>
                            <input type="text" name="correct_answer"  id="correct_answer_field" value="{{ $editing->correct_answer ?? old('correct_answer') }}">
                        </div>

                        {{-- Add Hindi --}}
                        <div class="field full">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" id="addHindiCheckbox" style="width:17px;height:17px;cursor:pointer;" {{ old('hindi_question', $editing->hindi_question ?? '') ? 'checked' : '' }}>
                                <span style="font-weight:600;">Add Hindi</span>
                            </label>
                        </div>


                        {{-- Hindi Question Section --}}
                        <div class="field full" id="hindiQuestionSection" style="{{ old('hindi_question', $editing->hindi_question ?? '') ? '' : 'display:none;' }}">

                            <div style=" border:1px solid #dfe3e8; border-radius:10px;padding:16px;background:#f8fafc;">

                                {{-- Hindi Question --}}
                                <div class="field full" style="margin-bottom: 12px;">
                                    <label> Hindi Question </label>
                                    <textarea name="hindi_question" id="hindi_question" placeholder="Hindi question enter kare...">{{ old('hindi_question', $editing->hindi_question ?? '') }}</textarea>
                                </div>

                                {{-- Hindi Options --}}
                                <div class="field full">
                                    @php
                                        $hindiOptionCount = in_array($selectedQuestionType, ['fastest_finger_first'], true) ? 2 : 4;
                                        $hindiOpts = old('hindi_options', $editing->hindi_options ?? array_fill(0, $hindiOptionCount, ''));
                                        $hindiOpts = is_array($hindiOpts) ? $hindiOpts : array_fill(0, $hindiOptionCount, '');
                                        $hindiOpts = array_pad($hindiOpts, $hindiOptionCount, '');
                                        $hindiCorrect = old('hindi_correct_answer', $editing->hindi_correct_answer ?? '');
                                    @endphp

                                    <label>Hindi Answer Options <span id="hindiOptionHint" class="hint">— {{ $hindiOptionCount }} options required</span> </label>
                                    <div class="opt-list opt-list-grid-box" id="hindi-options-container">
                                        @foreach ($hindiOpts as $i => $opt)
                                            @if ($i >= $hindiOptionCount)
                                                @continue
                                            @endif

                                            <div class="opt-row" style="display:flex;align-items:center;gap:8px;">
                                                <input type="radio" name="hindi_correct_option_radio" data-index="{{ $i }}" class="hindi-correct-radio-btn" value="{{ $opt }}"
                                                    {{ $hindiCorrect === $opt && $opt !== '' ? 'checked' : '' }}
                                                    style="width:16px; height:16px; margin:0;cursor:pointer; flex-shrink:0;accent-color:#10b981;">
                                                <input type="text" name="hindi_options[]" class="hindi-option-input-field" value="{{ $opt }}" placeholder="Hindi Option {{ $i + 1 }}" style="flex:1;width:100%;">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Hindi Correct Answer --}}
                                <div class="field full" style="margin-top: 12px;">
                                    <label> Hindi Correct Answer <span class="hint">— must match one Hindi option exactly</span> </label>
                                    <input type="text"  name="hindi_correct_answer" id="hindi_correct_answer_field" value="{{ old('hindi_correct_answer', $editing->hindi_correct_answer ?? '') }}">
                                </div>
                            </div>
                        </div>

                        {{-- MEDIA UPLOAD-}}

                        {{-- IMAGE --}}
                        <div class="field" id="imageUploadField" style="display:none;">
                            <label> Image <span class="hint">— Upload image</span></label>

                            <input style="margin-top: 6px;" type="file" name="image_file"id="image_file"accept="image/*">
                             <small class="text-muted">
                                Allowed: JPG, JPEG, PNG, WEBP | Maximum size: 1 MB
                            </small>
                            {{-- Image Preview --}}
                            <div id="imagePreview" style="margin-top:10px; display:none;">
                                <img id="imagePreviewImg" src=""  alt="Image Preview"style="max-width:250px; max-height:180px; border-radius:8px; border:1px solid #ddd;">
                            </div>

                            {{-- @if($editing && $editing->type === 'image' && $editing->media_url)
                                <div style="margin-top:8px;font-size:12px;color:#64748b;">
                                    Existing image is already uploaded.
                                </div>
                            @endif --}}

                            @if($editing && $editing->type === 'image' && $editing->media_url)
                                <div id="imagePreview" style="margin-top:10px;">
                                    <img id="imagePreviewImg"
                                        {{-- src="{{ Storage::disk('s3')->url($editing->media_url) }}" --}}
                                        src="{{ $editing->media_url }}"
                                        alt="Image Preview"style="max-width:250px; max-height:180px; border-radius:8px; border:1px solid #ddd;">
                                </div>
                            @else
                                <div id="imagePreview" style="margin-top:10px; display:none;">
                                    <img id="imagePreviewImg"  src="" alt="Image Preview" style="max-width:250px; max-height:180px; border-radius:8px; border:1px solid #ddd;">
                                </div>
                            @endif


                        @error('image_file')
                            <div style="color:#dc2626; font-size:13px; margin-top:6px; font-weight:600;">
                                {{ $message }}
                            </div>
                        @enderror
                        </div>


                        {{-- AUDIO --}}
                        {{-- <div class="field" id="audioUploadField" style="display:none;">
                            <label>
                                Audio
                                <span class="hint">— Upload audio</span>
                            </label>

                            <input style="margin-top: 6px;"
                                type="file"
                                name="audio_file"
                                id="audio_file"
                                accept="audio/*"
                            >

                          
                            <div id="audioPreview" style="margin-top:10px;">

                                @if($editing && $editing->type === 'audio' && $editing->audio_file)

                                    <audio
                                        id="audioPreviewPlayer"
                                        controls
                                        preload="metadata"
                                        style="width:100%;"
                                    >
                                        <source
                                            src="{{ Storage::disk('s3')->url($editing->audio_file) }}"
                                            type="audio/mpeg"
                                        >
                                        Your browser does not support audio playback.
                                    </audio>

                                @else

                                    <audio
                                        id="audioPreviewPlayer"
                                        controls
                                        style="width:100%; display:none;"
                                    ></audio>

                                @endif

                            </div>
                        </div> --}}


                        {{-- VIDEO --}}
                        <div class="field full" id="videoUploadField" style="display:none;">
                            <label>
                                Video
                                <span class="hint">— Upload video</span>
                            </label>

                            <input
                                type="file"
                                name="video_file"
                                id="video_file"
                                accept="video/*"
                            >

                            {{-- Video Preview --}}
                            <div id="videoPreview" style="margin-top:10px;">

                                @if($editing && $editing->type === 'video' && $editing->media_url)

                                    <video
                                        id="videoPreviewPlayer"
                                        controls
                                        preload="metadata"
                                        style="max-width:100%; max-height:250px; border-radius:8px;"
                                    >
                                        <source
                                            src="{{ Storage::disk('s3')->url($editing->media_url) }}"
                                        >
                                        Your browser does not support video playback.
                                    </video>

                                @else

                                    <video
                                        id="videoPreviewPlayer"
                                        controls
                                        style="max-width:100%; max-height:250px; border-radius:8px; display:none;"
                                    ></video>

                                @endif

                            </div>
                        </div>

                        {{-- **********quesiton and answer end*********** --}}
                        
                        <div class="field full">
                            <label>Hint Explanation </label>
                            <textarea name="explanation" >{{ $editing->explanation ?? old('explanation') }}</textarea>
                        </div>

                        <div class="field">
                            <label>Category<span class="req">*</span></label>
                            <select name="category_id" id="question_category">
                                <option value="">Select Category</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" @selected(($editing->category_id ?? '') == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Sub Category</label>
                            <select name="sub_category" id="sub_category">
                                <option value="">Select Sub Category</option>
                            </select>
                        </div>

                        {{-- <div class="field"><label>Sub-category</label><input type="text" name="sub_category" value="{{ $editing->sub_category ?? old('sub_category') }}"></div> --}}
                        <div class="field">
                            <label>Difficulty <span class="req">*</span></label>
                            <select name="difficulty" required>
                                @foreach (['easy', 'medium', 'hard', 'adaptive'] as $d)
                                    <option value="{{ $d }}" @selected(($editing->difficulty ?? 'medium') === $d)>{{ ucfirst($d) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Language <span class="req">*</span></label>
                            <select name="language" required>
                                <option @selected(($editing->language ?? 'English') === 'English')>English</option>
                                <option @selected(($editing->language ?? '') === 'Hindi')>Hindi</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Time Limit (seconds) <span class="req">*</span></label>
                            <input type="number" name="time_limit_seconds" value="{{ $editing->time_limit_seconds ?? 30 }}" required>
                        </div>

                        {{-- <div class="field">
                            <label>XP Value <span class="req">*</span></label>
                            <input type="number" name="xp_value" value="{{ $editing->xp_value ?? 4 }}" required>
                        </div> --}}

                        {{-- <div class="field">
                            <label>Negative Marking</label>
                            <input type="number" step="0.25" name="negative_marking" value="{{ $editing->negative_marking ?? 0 }}">
                        </div> --}}

                        <div class="field">
                            <label>Status <span class="req">*</span></label>
                            <select name="status" required>
                                @foreach (['published', 'draft', 'archived'] as $s)
                                    <option value="{{ $s }}" @selected(($editing->status ?? 'published') === $s)>{{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label>Tags <span class="hint">(comma-separated)</span></label>
                            <input type="text" name="tags" value="{{ $editing->tags ?? old('tags') }}">
                        </div>

                        {{-- <div class="field"><label>Moderation Status <span class="req">*</span></label>
                            <select name="moderation_status" required>
                            @foreach (['pending' => 'Pending Review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
                                <option value="{{ $val }}" @selected(($editing->moderation_status ?? 'approved') === $val)>{{ $label }}</option>
                            @endforeach
                            </select>
                        </div> --}}

                        <div class="field">
                            <label>Source <span class="req">*</span></label>
                            <select name="source" required>
                                <option value="manual" @selected(($editing->source ?? 'manual') == 'manual')>Manual</option>
                                <option value="ai_generated" @selected(($editing->source ?? '') == 'ai_generated')>AI Generated</option>
                                <option value="imported" @selected(($editing->source ?? '') == 'imported')>Imported</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-foot">
                        <button class="btn btn-ghost" type="button" onclick="closeModal()">Cancel</button>
                        <button class="btn btn-primary"
                            type="submit">{{ $editing ? 'Update Question' : 'Publish Question' }}</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- IMAGE MODAL --}}
    <div id="sqImageModal"
        class="sq-image-modal"
        onclick="closeImageModal(event)">

        <div class="modal-inner-container">

            <span class="sq-modal-close"
                onclick="closeImageModal(event)">
                &times;
            </span>

            <img class="sq-modal-content"
                id="sqLargerImage"
                src=""
                alt="Question Image">

        </div>
    </div>

    <div id="alert-container"></div>
    @push('styles')
        <style>
            .modal {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 1000;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .modal.active {
                display: flex;
                animation: fadeIn 0.2s ease;
            }

            .modal-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(16, 19, 43, 0.4);
                backdrop-filter: blur(3px);
            }

            .modal-content {
                position: relative;
                z-index: 1001;
                width: 100%;
                max-width: 550px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.98);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            .status-change {

                min-width: 140px;
                padding: 8px 12px;

                border: 1px solid #dcdfe6;
                border-radius: 8px;

                background: #fff;

                font-size: 14px;
                font-weight: 600;

                color: #374151;

                outline: none;
                cursor: pointer;

                transition: .25s;
            }

            .status-change:hover {
                border-color: #2563eb;
            }

            .status-change:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
            }

            /* --- ✨ CREATIVE TOP-RIGHT NOTIFICATION ENGINE --- */
            #alert-container {
                position: fixed;
                top: 24px;
                right: 24px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                /* Niche ki taraf naye alerts badhenge */
                gap: 14px;
                max-width: 380px;
                width: 100%;
            }

            .alert-toast {
                background: rgba(30, 41, 59, 0.85);
                /* Slick semi-transparent dark background */
                backdrop-filter: blur(8px);
                color: #ffffff;
                padding: 16px;
                border-radius: 12px;
                display: flex;
                align-items: flex-start;
                gap: 14px;
                border: 1px solid rgba(255, 255, 255, 0.05);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
                position: relative;
                overflow: hidden;
                animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }

            /* Type Specific Colors & Glow Effects */
            .alert-toast.success {
                border-left: 5px solid #10b981;
                box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
            }

            .alert-toast.warning {
                border-left: 5px solid #f59e0b;
                box-shadow: 0 0 20px rgba(245, 158, 11, 0.15);
            }

            .alert-toast.error {
                border-left: 5px solid #ef4444;
                box-shadow: 0 0 20px rgba(239, 68, 68, 0.15);
            }

            .alert-toast.info {
                border-left: 5px solid #3b82f6;
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.15);
            }

            /* Typography & Structure */
            .alert-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                margin-top: 2px;
            }

            .alert-content {
                flex-grow: 1;
            }

            .alert-title {
                font-weight: 700;
                font-size: 14px;
                margin-bottom: 3px;
                text-transform: capitalize;
            }

            .alert-toast.success .alert-title {
                color: #34d399;
            }

            .alert-toast.warning .alert-title {
                color: #fbbf24;
            }

            .alert-toast.error .alert-title {
                color: #f87171;
            }

            .alert-toast.info .alert-title {
                color: #60a5fa;
            }

            .alert-message {
                font-size: 13px;
                color: #cbd5e1;
                line-height: 1.4;
            }

            .alert-close {
                background: rgba(255, 255, 255, 0.05);
                border: none;
                color: #94a3b8;
                font-size: 14px;
                cursor: pointer;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }

            .alert-close:hover {
                background: rgba(255, 255, 255, 0.15);
                color: #ffffff;
            }

            /* Timed Progress Bar Animation */
            .progress-bar {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                width: 100%;
                transform-origin: left;
                animation: shrinkProgress 4s linear forwards;
            }

            .alert-toast.success .progress-bar {
                background: #10b981;
            }

            .alert-toast.warning .progress-bar {
                background: #f59e0b;
            }

            .alert-toast.error .progress-bar {
                background: #ef4444;
            }

            .alert-toast.info .progress-bar {
                background: #3b82f6;
            }

            /* Animations */
            @keyframes slideInRight {
                from {
                    transform: translateX(120%);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes fadeOutUp {
                to {
                    transform: translateY(-20px);
                    opacity: 0;
                }
            }

            @keyframes shrinkProgress {
                from {
                    transform: scaleX(1);
                }

                to {
                    transform: scaleX(0);
                }
            }
        </style>
        <style>
            /* Grid layout ko maintain karna */
            .opt-list-grid-box {
                display: grid;
                grid-template-columns: 1fr 1fr; /* 2 columns */
                gap: 12px;
            }

            /* Har option row */
            .opt-list-grid-box .opt-row {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0;
                border: none;
                background: transparent;
            }

            /* Radio button styling */
            .opt-list-grid-box .correct-radio-btn {
                width: 16px;
                height: 16px;
                margin: 0;
                cursor: pointer;
                flex-shrink: 0;
                accent-color: #10b981;
            }

            /* Input field - full width within grid cell */
            .opt-list-grid-box .option-input-field {
                flex: 1;
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 14px;
                outline: none;
                background: #fff;
            }

            .opt-list-grid-box .option-input-field:focus {
                border-color: #4F46E5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }

            .type-choice-list {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 12px;
                margin-top: 8px;
                  margin-bottom: 20px;
            }

            .type-option {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                /* min-height: 74px; */
                padding: 12px 14px;
                border: 1px solid #E5E3F2;
                border-radius: 12px;
                background: #FFFFFF;
                cursor: pointer;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
                text-align: center;
                box-shadow: 0 1px 0 rgba(17, 24, 39, 0.02);
            }

            .type-option span {
                font-size: 12px;
                line-height: 1.3;
                font-weight: 600;
                color: #1F2433;
                display: inline-block;
            }

            .type-option input {
                appearance: none;
                -webkit-appearance: none;
                width: 16px;
                height: 16px;
                border: 2px solid #AFB6D0;
                border-radius: 50%;
                background: #fff;
                margin: 0;
                position: relative;
                flex-shrink: 0;
                display: none
            }

            .type-option input:checked {
                background: #4F46E5;
                border-color: #4F46E5;
                box-shadow: inset 0 0 0 4px #fff;
            }

            .type-option.selected {
                border-color: #4F46E5;
                background: #4F46E5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
            }

            .type-option.selected span {
                color: #fff;
            }

            .type-option input:disabled,
            .type-option:has(input:disabled) {
                cursor: not-allowed;
                opacity: 0.5;
                filter: grayscale(0.15);
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function openModal() {
                document.getElementById('questionModal').classList.add('active');
            }

            function closeModal() {
                document.getElementById('questionModal').classList.remove('active');
                if (window.location.search.includes('edit=')) {
                    window.location.href = "{{ route('superadmin.questions.index') }}";
                }
            }
        </script>

        <script>
            const allCategories = @json($allCategories);

            const categoryDropdown = document.getElementById('question_category');
            const subCategoryDropdown = document.getElementById('sub_category');

            function loadSubCategories(parentId, selected = '') {
                subCategoryDropdown.innerHTML = '<option value="">Select Sub Category</option>';

                if (!parentId) {
                    return;
                }

                allCategories.forEach(function(item) {

                    if (parseInt(item.parent_id) === parseInt(parentId)) {

                        let option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;

                        if (parseInt(selected) === parseInt(item.id)) {
                            option.selected = true;
                        }

                        subCategoryDropdown.appendChild(option);
                    }

                });
            }

            categoryDropdown.addEventListener('change', function() {
                loadSubCategories(this.value);
            });

            document.addEventListener('DOMContentLoaded', function() {

                loadSubCategories(
                    "{{ old('category_id', $editing->category_id ?? '') }}",
                    "{{ old('sub_category', $editing->sub_category ?? '') }}"
                );

            });
        </script>

        <script>
            document.addEventListener('change', function(e) {

                if (!e.target.classList.contains('status-change')) {
                    return;
                }

                const id = e.target.dataset.id;
                const status = e.target.value;

                fetch("{{ route('superadmin.questions.changeStatus') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            id: id,
                            status: status
                        })
                    })
                    .then(res => res.json())


                    .then(data => {

                        if (data.success) {

                            showToast(
                                'success',
                                'Status Updated',
                                'Question status updated successfully.'
                            );

                        } else {

                            showToast(
                                'error',
                                'Update Failed',
                                'Unable to update question status.'
                            );

                        }

                    })
                    .catch(() => {

                        showToast(
                            'error',
                            'Server Error',
                            'Something went wrong. Please try again.'
                        );

                    });

            });


            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            function showToast(type, title, message) {

                const container = document.getElementById('alert-container');

                const toast = document.createElement('div');

                toast.className = 'alert-toast ' + type;

                toast.innerHTML = `
            <div class="alert-icon">${icons[type]}</div>

            <div class="alert-content">
                <div class="alert-title">${title}</div>
                <div class="alert-message">${message}</div>
            </div>

            <button class="alert-close">&times;</button>
            <div class="progress-bar"></div>
                 `;

                container.appendChild(toast);

                function removeToast() {
                    toast.style.animation = 'fadeOut .3s forwards';

                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }

                toast.querySelector('.alert-close').onclick = removeToast;

                setTimeout(removeToast, 4000);
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const correctAnswerField = document.getElementById('correct_answer_field');
                const radioButtons = document.querySelectorAll('.correct-radio-btn');
                const optionInputs = document.querySelectorAll('.option-input-field');
                
                // 1. Radio button click par
                radioButtons.forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            const row = this.closest('.opt-row');
                            const inputField = row.querySelector('.option-input-field');
                            correctAnswerField.value = inputField.value;
                        }
                    });
                });
                
                // 2. Option text change par
                optionInputs.forEach(function(input) {
                    input.addEventListener('input', function() {
                        const row = this.closest('.opt-row');
                        const radio = row.querySelector('.correct-radio-btn');
                        
                        if (radio && radio.checked) {
                            correctAnswerField.value = this.value;
                            radio.value = this.value;
                        }
                    });
                });
            });
        </script>

       <script>
        document.addEventListener('DOMContentLoaded', function () {

            const checkbox = document.getElementById('addHindiCheckbox');
            const hindiSection = document.getElementById('hindiQuestionSection');

            if (!checkbox || !hindiSection) {
                return;
            }

            // The checkbox listener is handled below in the main DOMContentLoaded block
            // Hindi radio -> Correct Answer
            const hindiCorrectField =
                document.getElementById('hindi_correct_answer_field');

            document.querySelectorAll('.hindi-correct-radio-btn')
                .forEach(function (radio) {

                    radio.addEventListener('change', function () {

                        const row = this.closest('.opt-row');

                        const input =
                            row.querySelector('.hindi-option-input-field');

                        if (input) {
                            hindiCorrectField.value = input.value;
                        }

                    });

                });


            // Hindi option change
            document.querySelectorAll('.hindi-option-input-field')
                .forEach(function (input) {

                    input.addEventListener('input', function () {

                        const row = this.closest('.opt-row');

                        const radio =
                            row.querySelector('.hindi-correct-radio-btn');

                        if (radio && radio.checked) {

                            hindiCorrectField.value = this.value;
                            radio.value = this.value;

                        }

                    });

                });

        });
        </script>

    
   <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* ========================================================= ELEMENTS ========================================================= */

        const checkbox = document.getElementById('addHindiCheckbox');
        const hindiSection = document.getElementById('hindiQuestionSection');
        const hindiQuestion = document.getElementById('hindi_question');
        const hindiCorrectField = document.getElementById('hindi_correct_answer_field');
        const englishQuestion =document.querySelector('textarea[name="question_text"]');
        const englishCorrect = document.getElementById('correct_answer_field');

        /* =========================================================HINDI CHECKBOX ========================================================= */

        if (checkbox && hindiSection) {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    hindiSection.style.display = 'block';
                    
                    // Only trigger translation if there's no Hindi text yet
                    if (hindiQuestion && hindiQuestion.value.trim() === '') {
                        if (typeof window.autoTranslateHindi === 'function') {
                            window.autoTranslateHindi();
                        }
                    }
                } else {
                    hindiSection.style.display = 'none';
                }
            });
        }


        /* ========================================================= HINDI RADIO Radio select -> Hindi Correct Answer========================================================= */

        // function bindHindiRadio() {
        //     document
        //         .querySelectorAll('.hindi-correct-radio-btn')
        //         .forEach(function (radio) {

        //             radio.addEventListener('change', function () {

        //                 const row = this.closest('.opt-row');
        //                 const input = row?.querySelector(
        //                         '.hindi-option-input-field'
        //                     );

        //                 if (input && hindiCorrectField) {
        //                     hindiCorrectField.value = input.value;
        //                 }
        //             });
        //         });
        // }
        // bindHindiRadio();

        /* =========================================================HINDI OPTION CHANGE ========================================================= */

        document
            .querySelectorAll('.hindi-option-input-field')
            .forEach(function (input) {
                input.addEventListener('input', function () {
                    const row = this.closest('.opt-row');
                    const radio = row?.querySelector(
                            '.hindi-correct-radio-btn'
                        );

                    if (
                        radio &&
                        radio.checked &&
                        hindiCorrectField
                    ) {

                        radio.value = this.value;
                        hindiCorrectField.value = this.value;
                    }
                });
            });

        /* ========================================================= ENGLISH RADIO   English option select -> Correct Answer ========================================================= */

        // function bindEnglishRadio() {

        //     document
        //         .querySelectorAll('.correct-radio-btn')
        //         .forEach(function (radio) {
        //             radio.addEventListener('change', function () {
        //                 const row = this.closest('.opt-row');
        //                 const input = row?.querySelector(
        //                         '.option-input-field'
        //                     );

        //                 if (input && englishCorrect) {
        //                     englishCorrect.value = input.value;

        //                     /** English correct answer change hua, * isliye Hindi translation update karo. */

        //                     clearTimeout(translateTimer);
        //                     translateTimer =
        //                         setTimeout(
        //                             autoTranslateHindi,
        //                            1200
        //                         );
        //                 }
        //             });
        //         });
        // }


        /* ========================================================= TRANSLATION TIMER ========================================================= */

        let translateTimer = null;
        let translationInProgress = false;
         let lastTranslationPayload = '';

        /* =========================================================AUTO TRANSLATE HINDI ========================================================= */

        window.autoTranslateHindi = function () {

            /* * Elements dobara fetch kar rahe hain taaki * undefined variable ka issue na aaye. */

            const englishQuestionInput = document.querySelector('textarea[name="question_text"]' );
            const englishOptionInputs = document.querySelectorAll('.option-input-field');
            const englishCorrectInput = document.getElementById( 'correct_answer_field' );

            if (!englishQuestionInput) {
                return;
            }

            /* =====================================================ENGLISH DATA ===================================================== */
            const question = englishQuestionInput.value.trim();

            const options = Array.from(
                    englishOptionInputs
                ).map(function (input) {
                    return input.value.trim();
                });

            const correctAnswer = englishCorrectInput
                    ? englishCorrectInput.value.trim()
                    : '';

            /* =====================================================EMPTY CHECK  ===================================================== */

            if (
                question === '' &&
                options.every(function (value) {
                    return value === '';
                }) &&
                correctAnswer === ''
            ) {
                return;
            }

            /* ===================================================== AUTO OPEN HINDI ===================================================== */

            if (checkbox) {
                checkbox.checked = true;
            }

            if (hindiSection) {
                hindiSection.style.display = 'block';
            }

            /* =====================================================API REQUEST ===================================================== */

            fetch(
                "{{ route('superadmin.questions.autoTranslateHindi') }}",
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN':document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content')
                            || '{{ csrf_token() }}',

                        'Accept':'application/json'
                    },

                    body: JSON.stringify({
                        question: question,
                        options: options,
                        correct_answer:correctAnswer
                    })
                }
            )
            .then(function (response) {
                return response.json()
                    .then(function (data) {
                        if (!response.ok) {
                            throw new Error(
                                data.message ||
                                'Translation request failed.'
                            );
                        }
                        return data;
                    });
            })

            .then(function (data) {
                // console.log(
                //     'Hindi translation response:',
                //     data
                // );

                /* =================================================TRANSLATION FAILED================================================= */

                if (!data.success) {
                    console.error(
                        'Hindi translation failed:',
                        data.message
                    );
                    return;
                }

                /* ================================================= HINDI QUESTION================================================= */

                if (
                    hindiQuestion &&
                    typeof data.question !== 'undefined'
                ) {

                    hindiQuestion.value = data.question || '';
                }


                /* =================================================HINDI OPTIONS ================================================= */

                const hindiOptionInputs = document.querySelectorAll(
                        '.hindi-option-input-field'
                    );

                const hindiRadios = document.querySelectorAll(
                        '.hindi-correct-radio-btn'
                    );

                if (Array.isArray(data.options)) {

                    data.options.forEach(
                        function (value, index) {
                            const hindiValue = value || '';
                            /* * Hindi input */
                            if (
                                hindiOptionInputs[index]
                            ) {
                                hindiOptionInputs[index]
                                    .value =
                                    hindiValue;

                            }

                            /* * Hindi radio ki value*/

                            if (
                                hindiRadios[index]
                            ) {
                                hindiRadios[index]
                                    .value =
                                    hindiValue;

                            }
                        }
                    );
                }

                /* ================================================= HINDI CORRECT ANSWER ================================================= */
                const translatedCorrectAnswer = data.correct_answer || '';


                if (hindiCorrectField) {
                    hindiCorrectField.value = translatedCorrectAnswer;
                }

                /* ================================================= HINDI CORRECT RADIO ================================================= */
                hindiRadios.forEach(
                    function (radio) {
                        radio.checked = false;
                    }
                );


                if (translatedCorrectAnswer !== '') {
                    hindiRadios.forEach(
                        function (radio) {
                            if (
                                radio.value.trim() ===
                                translatedCorrectAnswer.trim()
                            ) {
                                radio.checked = true;
                            }
                        }
                    );
                }
            })
            .catch(function (error) {
                // console.error(
                //     'Hindi translation error:',
                //     error
                // );
            });
        };

    
        /* ========================================================= ENGLISH QUESTION TYPING========================================================= */

        if (englishQuestion) {
            englishQuestion.addEventListener(
                'input',
                function () {
                    clearTimeout(
                        translateTimer
                    );
                    translateTimer =
                        setTimeout(
                            function () {
                                autoTranslateHindi();
                            },
                            1200
                        );
                }
            );
        }

        /* ========================================================= ENGLISH OPTIONS TYPING ========================================================= */

        document.querySelectorAll('.option-input-field')
            .forEach(function (input) {
                input.addEventListener(
                    'input',
                    function () {
                        clearTimeout(
                            translateTimer
                        );
                        translateTimer =
                            setTimeout(
                                function () {
                                    autoTranslateHindi();
                                },
                                1200
                            );
                    }
                );
            });

        /* ========================================================= ENGLISH CORRECT ANSWER TYPING ========================================================= */

        if (englishCorrect) {
            englishCorrect.addEventListener(
                'input',
                function () {
                    clearTimeout(
                        translateTimer
                    );
                    translateTimer =
                        setTimeout(
                            function () {
                                autoTranslateHindi();
                            },
                            1200
                        );
                }
            );
        }

    });
    </script>
    
    <script>
    document.querySelectorAll('.option-input-field').forEach(function (input) {
        input.addEventListener('input', function () {
            const row = this.closest('.opt-row');
            const radio = row?.querySelector(
                '.correct-radio-btn'
            );

            if (radio) {
                radio.value = this.value;
                if (radio.checked) {
                    const correctField = document.getElementById(
                            'correct_answer_field'
                        );
                    if (correctField) {
                        correctField.value = this.value;
                    }
                }
            }
        });
    });
    </script>

  
   
    {{-- <script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeDropdown = document.getElementById('questionType');
        const imageField = document.getElementById('imageUploadField');
        const audioField = document.getElementById('audioUploadField');
        const videoField = document.getElementById('videoUploadField');
        const imageInput = document.getElementById('image_file');
        const audioInput = document.getElementById('audio_file');
        const videoInput = document.getElementById('video_file');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewImg = document.getElementById('imagePreviewImg');
        const audioPreview = document.getElementById('audioPreview');
       // const audioPreviewPlayer = document.getElementById('audioPreviewPlayer');
        const videoPreview = document.getElementById('videoPreview');
        const videoPreviewPlayer = document.getElementById('videoPreviewPlayer');

        if (!typeDropdown) {
            return;
        }

        /* SHOW / HIDE MEDIA FIELD */
        function updateMediaField() {
            const type = typeDropdown.value;
            // Sab hide
            imageField.style.display = 'none';
            audioField.style.display = 'none';
            videoField.style.display = 'none';

            // Selected type show
            if (type === 'image') {
                imageField.style.display = 'block';
            }

            if (type === 'audio') {
                audioField.style.display = 'block';
            }

            if (type === 'video') {
                videoField.style.display = 'block';
            }
        }

        /*  IMAGE PREVIEW*/
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) {
                    imagePreview.style.display = 'none';
                    imagePreviewImg.src = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreviewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }

        /* AUDIO PREVIEW */
         if (audioInput) {
            audioInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) {
                    audioPreview.style.display = 'none';
                    audioPreviewPlayer.src = '';
                    return;
                }
                const url = URL.createObjectURL(file);
                audioPreviewPlayer.src = url;
                audioPreview.style.display = 'block';
            });
        } 


        /*  VIDEO PREVIEW */
        if (videoInput) {
            videoInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) {
                    videoPreview.style.display = 'none';
                    videoPreviewPlayer.src = '';
                    return;
                }

                const url = URL.createObjectURL(file);
                videoPreviewPlayer.src = url;
                videoPreview.style.display = 'block';
            });
        }

        /* DROPDON CHANGE  */
        typeDropdown.addEventListener('change', updateMediaField);
        /* PAGE LOAD / EDIT
        ===================================================== */
        updateMediaField();

    });
    </script>  --}}

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const typeRadios = document.querySelectorAll('input[name="type"]');
        const questionAudioField = document.getElementById('questionAudioField');
        const imageField = document.getElementById('imageUploadField');
        const videoField = document.getElementById('videoUploadField');
        const imageInput = document.getElementById('image_file');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewImg = document.getElementById('imagePreviewImg');
        const questionAudioInput = document.getElementById('question_audio');
        const questionAudioPlayer = document.getElementById('audioPreviewPlayer');
        const questionAudioEmptyState = document.getElementById('audioEmptyState');
        const englishOptionRows = document.querySelectorAll('#options-container .opt-row');
        const hindiOptionRows = document.querySelectorAll('#hindi-options-container .opt-row');
        const englishOptionHint = document.getElementById('englishOptionHint');
        const hindiOptionHint = document.getElementById('hindiOptionHint');

        function syncTypeSelectionState() {
            document.querySelectorAll('.type-option').forEach(function(option) {
                const checked = option.querySelector('input')?.checked;
                option.classList.toggle('selected', !!checked);
            });
        }

        function syncOptionRows() {
            const type = document.querySelector('input[name="type"]:checked')?.value || 'mcq';
            const count = type === 'fastest_finger_first' ? 2 : 4;

            englishOptionRows.forEach(function(row, index) {
                row.style.display = index < count ? 'flex' : 'none';
            });

            hindiOptionRows.forEach(function(row, index) {
                row.style.display = index < count ? 'flex' : 'none';
            });

            if (englishOptionHint) {
                englishOptionHint.textContent = '— ' + count + ' options required for this type';
            }

            if (hindiOptionHint) {
                hindiOptionHint.textContent = '— ' + count + ' options required';
            }
        }

        function updateMediaField() {
            const type = document.querySelector('input[name="type"]:checked')?.value || 'mcq';

            if (questionAudioField) {
                questionAudioField.style.display = (type === 'audio' || type === 'image_audio') ? 'block' : 'none';
            }

            if (imageField) {
                imageField.style.display = (type === 'image' || type === 'image_audio') ? 'block' : 'none';
            }

            if (videoField) {
                videoField.style.display = type === 'video' ? 'block' : 'none';
            }

            syncOptionRows();
            syncTypeSelectionState();
        }

        if (typeRadios.length) {
            typeRadios.forEach(function(radio) {
                radio.addEventListener('change', updateMediaField);
            });
            updateMediaField();
        }

        if (imageInput) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) {
                    imagePreview.style.display = 'none';
                    imagePreviewImg.src = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreviewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }

        if (questionAudioInput && questionAudioPlayer) {
            questionAudioInput.addEventListener('change', function () {
                const file = this.files[0];

                if (file) {
                    const objectUrl = URL.createObjectURL(file);
                    questionAudioPlayer.src = objectUrl;
                    questionAudioPlayer.style.display = 'block';

                    if (questionAudioEmptyState) {
                        questionAudioEmptyState.style.display = 'none';
                    }

                    questionAudioPlayer.load();
                } else {
                    questionAudioPlayer.pause();
                    questionAudioPlayer.removeAttribute('src');
                    questionAudioPlayer.style.display = 'none';

                    if (questionAudioEmptyState) {
                        questionAudioEmptyState.style.display = 'block';
                    }
                }
            });
        }
    });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const englishRadios = document.querySelectorAll('.correct-radio-btn');
    const hindiRadios = document.querySelectorAll('.hindi-correct-radio-btn');
    const englishCorrectField = document.getElementById('correct_answer_field');
    const hindiCorrectField = document.getElementById('hindi_correct_answer_field');

    // 1. English Radio select hone par → Hindi Radio turant select hoga
    englishRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const index = this.getAttribute('data-index');
                const row = this.closest('.opt-row');
                const englishInput = row.querySelector('.option-input-field');
                
                // English correct field update
                if (englishInput && englishCorrectField) {
                    englishCorrectField.value = englishInput.value;
                    this.value = englishInput.value;
                }

                // Hindi radio turant select karo (same index wala)
                const hindiRadio = document.querySelector(`.hindi-correct-radio-btn[data-index="${index}"]`);
                if (hindiRadio) {
                    hindiRadio.checked = true;
                    const hindiRow = hindiRadio.closest('.opt-row');
                    const hindiInput = hindiRow.querySelector('.hindi-option-input-field');
                    
                    // Hindi correct field bhi turant update karo
                    if (hindiInput && hindiCorrectField) {
                        hindiCorrectField.value = hindiInput.value;
                        hindiRadio.value = hindiInput.value;
                    }
                }
            }
        });
    });

    // 2. Hindi Radio select hone par → English Radio turant select hoga
    hindiRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const index = this.getAttribute('data-index');
                const row = this.closest('.opt-row');
                const hindiInput = row.querySelector('.hindi-option-input-field');
                
                // Hindi correct field update
                if (hindiInput && hindiCorrectField) {
                    hindiCorrectField.value = hindiInput.value;
                    this.value = hindiInput.value;
                }

                // English radio turant select karo (same index wala)
                const englishRadio = document.querySelector(`.correct-radio-btn[data-index="${index}"]`);
                if (englishRadio) {
                    englishRadio.checked = true;
                    const englishRow = englishRadio.closest('.opt-row');
                    const englishInput = englishRow.querySelector('.option-input-field');
                    
                    // English correct field bhi turant update karo
                    if (englishInput && englishCorrectField) {
                        englishCorrectField.value = englishInput.value;
                        englishRadio.value = englishInput.value;
                    }
                }
            }
        });
    });

    // 3. Typing karte waqt radio ki value bhi update hoti rahegi (English)
    document.querySelectorAll('.option-input-field').forEach(function(input) {
        input.addEventListener('input', function() {
            const row = this.closest('.opt-row');
            const radio = row.querySelector('.correct-radio-btn');
            if (radio) {
                radio.value = this.value;
                if (radio.checked && englishCorrectField) {
                    englishCorrectField.value = this.value;
                }
            }
        });
    });

    // 4. Typing karte waqt radio ki value bhi update hoti rahegi (Hindi)
    document.querySelectorAll('.hindi-option-input-field').forEach(function(input) {
        input.addEventListener('input', function() {
            const row = this.closest('.opt-row');
            const radio = row.querySelector('.hindi-correct-radio-btn');
            if (radio) {
                radio.value = this.value;
                if (radio.checked && hindiCorrectField) {
                    hindiCorrectField.value = this.value;
                }
            }
        });
    });
});
</script>

<script>
    // --- MODAL LOGIC ---
     function openImageModal(src) {

        var modal = document.getElementById("sqImageModal");
        var modalImg = document.getElementById("sqLargerImage");

        if (!modal || !modalImg) {
            console.error("Image modal elements not found.");
            return;
        }

        modalImg.src = src;
        modal.style.display = "flex";
    }

    function closeImageModal(event) {

        if (
            event.target.id === "sqImageModal" ||
            event.target.classList.contains("sq-modal-close")
        ) {
            var modal = document.getElementById("sqImageModal");

            if (modal) {
                modal.style.display = "none";
            }
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

@endsection
