{{-- <form method="POST" action="{{ route('admin.quizzes.questions.store', $editing) }}"> --}}
    @php
    use App\Models\Quiz;
    $quizId = $editing->id
        ?? request()->route('quiz')
        ?? request()->query('editing');

          $questionLimit = 0;

    if($quizId){
        $questionLimit = Quiz::where('id',$quizId)->value('question_limit') ?? 0;
    }
@endphp
@if($quizId)
    <form method="POST" action="{{ route('admin.quizzes.questions.store', $quizId) }}">
    @csrf
    <input type="hidden" name="quiz_id" value="{{ $quizId }}">
  <input type="hidden" id="questionLimit" value="{{ $questionLimit }}">

        <div class="card mt-4">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h4>Select Questions - {{$quizId}}</h4>

                <input type="text" id="questionSearch" class="form-control" style="width:300px ; height: 30px;"; placeholder="Search Question">
                <th width="70">
                    Select All <input type="checkbox" id="selectAll">
                </th>
            </div>

            <div class="card-body p-0">

                <table class="table table-bordered table-hover mb-0" id="questionTable">

                    <thead class="table-light">

                    <tr>

                        <th width="70">Select</th>

                        <th width="28%">Question</th>

                        <th>Option A</th>

                        <th>Option B</th>

                        <th>Option C</th>

                        <th>Option D</th>

                        <th width="180">Correct Answer</th>
                        <th>Bot Correct Answer </th>
                        <th> Order</th>

                    </tr>

                    </thead>

                    <tbody>

                    @forelse($questionBank as $question)

                        @php

                            $options = $question->options;

                            if(is_string($options)){
                                $options = json_decode($options,true);
                            }

                            $options = $options ?? [];

                        @endphp

                        <tr>

                            <td class="text-center">
                            <input type="checkbox" class="question-checkbox" name="question_ids[]" value="{{ $question->id }}" {{ $selectedQuestions->has($question->id) ? 'checked' : '' }}>
                            </td>

                            <td>
                                {{ $question->question_text }}
                            </td>

                            <td>
                                {{ $options[0] ?? '-' }}
                            </td>

                            <td>
                                {{ $options[1] ?? '-' }}
                            </td>

                            <td>
                                {{ $options[2] ?? '-' }}
                            </td>

                            <td>
                                {{ $options[3] ?? '-' }}
                            </td>

                            <td class="text-success fw-bold">
                                {{ $question->correct_answer }}
                            </td>
                    

                            @php
                                $selected = $selectedQuestions->get($question->id);
                            @endphp

                            <td>
                                <select name="bot_correct_answer[{{ $question->id }}]" class="form-select">

                                    <option value="no"
                                        {{ optional(optional($selected)->pivot)->bot_correct_answer == 'no' ? 'selected' : '' }}>
                                        No
                                    </option>

                                    <option value="yes"
                                        {{ optional(optional($selected)->pivot)->bot_correct_answer == 'yes' ? 'selected' : '' }}>
                                        Yes
                                    </option>

                                </select>
                            </td>


                            <td>
                                <input
                                    type="number"
                                    min="1"
                                    name="order[{{ $question->id }}]"
                                    class="form-control"
                                    value="{{ optional(optional($selected)->pivot)->order }}">
                            </td>
                        </tr>

                    @empty

                        <tr>

                            <td colspan="7" class="text-center">

                                No Questions Found

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

            <div class="card-footer text-end">

                <button type="submit"
                        class="btn btn-primary">

                    Add Selected Questions

                </button>

            </div>

        </div>
    </form>
@else
<div class="alert alert-warning">
    Please save the quiz first, then add questions.
</div>
@endif


<script>
const QUESTION_LIMIT = Number(document.getElementById('questionLimit').value);
</script>
<script>

document.getElementById('questionSearch').addEventListener('keyup',function(){

    let value=this.value.toLowerCase();

    document.querySelectorAll('#questionTable tbody tr').forEach(function(row){

        row.style.display=row.innerText.toLowerCase().includes(value)
            ? ''
            : 'none';

    });

});

const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.question-checkbox');

function checkedCount() {
    return document.querySelectorAll('.question-checkbox:checked').length;
}

function updateSelectAll() {
    selectAll.checked = (checkedCount() === checkboxes.length);
}

checkboxes.forEach(function (checkbox) {

    checkbox.addEventListener('change', function () {

        if (
            this.checked &&
            QUESTION_LIMIT > 0 &&
            checkedCount() > QUESTION_LIMIT
        ) {

            this.checked = false;

           showAlert('warning', 'Question Limit', 'You can select maximum ' + QUESTION_LIMIT + ' questions.');

            return;
        }

        updateSelectAll();

    });

});

selectAll.addEventListener('change', function () {

    if (this.checked) {

        let count = 0;

        checkboxes.forEach(function (checkbox) {

            if (QUESTION_LIMIT == 0 || count < QUESTION_LIMIT) {

                checkbox.checked = true;
                count++;

            } else {

                checkbox.checked = false;

            }

        });

        if (QUESTION_LIMIT > 0 && checkboxes.length > QUESTION_LIMIT) {

           showAlert('warning', 'Question Limit','You can select maximum ' + QUESTION_LIMIT + ' questions have been selected.');

        }

    } else {

        checkboxes.forEach(function (checkbox) {

            checkbox.checked = false;

        });

    }

    updateSelectAll();

});

updateSelectAll();
</script>