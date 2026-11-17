@extends('superadmin.layouts.app')

@section('title', 'Assign Quiz')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
@section('content')

{{-- <style>
    .assign-quiz-page {
        padding: 20px;
    }

    .assign-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .assign-header h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }

    .assign-header p {
        margin: 5px 0 0;
        color: #777;
    }

    .count-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    .count-card {
        background: #fff;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 4px 15px rgba(0,0,0,.08);
        border: 1px solid #eee;
    }

    .count-card-title {
        font-size: 14px;
        color: #777;
        margin-bottom: 8px;
    }

    .count-card-value {
        font-size: 28px;
        font-weight: 700;
    }

    .quiz-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        background: #fff;
        padding: 15px 18px;
        border-radius: 10px;
        border: 1px solid #eee;
    }

    .all-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .quiz-table-wrap {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eee;
    }

    .quiz-table {
        width: 100%;
        border-collapse: collapse;
    }

    .quiz-table th,
    .quiz-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }

    .quiz-table th {
        background: #f8f9fb;
        font-size: 13px;
        font-weight: 700;
    }

    .quiz-table td {
        font-size: 14px;
    }

    .quiz-select {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .select-quiz-btn {
        border: 1px solid #ddd;
        background: #fff;
        padding: 7px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }

    .select-quiz-btn.selected {
        background: var(--indigo);
        color: #fff;
        border-color: var(--indigo);
    }

    .submit-btn {
        border: none;
        background: #0d6efd;
        color: #fff;
        padding: 9px 20px;
        border-radius: 7px;
        cursor: pointer;
        font-weight: 600;
    }

    .submit-btn:hover {
        background: #0b5ed7;
    }

    .back-btn {
        text-decoration: none;
        color: #555;
        margin-right: 10px;
    }

    @media(max-width: 768px) {
        .count-cards {
            grid-template-columns: 1fr;
        }

        .quiz-table-wrap {
            overflow-x: auto;
        }

        .quiz-table {
            min-width: 750px;
        }
    }
</style> --}}

<style>
    /* .assign-quiz-page {
        padding: 20px;
    } */

    .assign-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .assign-header h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }

    .assign-header p {
        margin: 5px 0 0;
        color: #777;
    }

    .count-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    .count-card {
        background: #fff;
        border-radius: 12px;
        padding: 22px;
        border: 1px solid var(--line);
    }

    .count-card-title {
        font-size: 14px;
        color: #777;
        margin-bottom: 8px;
    }

    .count-card-value {
        font-size: 28px;
        font-weight: 700;
    }

    .quiz-table-wrap {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eee;
    }

    .quiz-table {
        width: 100%;
        border-collapse: collapse;
    }

    /* .quiz-table th,
    .quiz-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        text-align: left;
    } */

    /* .quiz-table th {
        background: #f8f9fb;
        font-size: 13px;
        font-weight: 700;
    } */

    .quiz-table td {
        font-size: 14px;
    }

    /*
     * NOT SELECTED
     * Green background + white text
     */
    .select-quiz-btn {
        border: 1px solid var(--indigo);
        background: var(--indigo);
        color: #fff;
        padding: 7px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        min-width: 80px;
    }

    /*
     * SELECTED
     * White background + black text
     */
    .select-quiz-btn.selected {
        background: #fff;
        color: #000;
        border-color: var(--indigo);
    }

    .select-quiz-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .back-btn {
        text-decoration: none;
        color: #555;
        margin-right: 10px;
    }

    .ad-assign-quiz-table-box {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .ad-assign-quiz-table-wrapper {
        width: 100%;
        min-width: 750px;
        border-collapse: collapse;
    }

    @media (max-width: 991px) {
    .ad-assign-quiz-table-box {
        width: 100%;
        overflow: visible;
         border: none;
        background: none;
    }

    .ad-assign-quiz-table-wrapper {
        width: 100%;
        min-width: 0 !important;
        border-collapse: separate;
    }

    /* Hide table header */
    .ad-assign-quiz-table-wrapper thead {
        display: none;
    }

    /* Row = Card */
    .ad-assign-quiz-table-wrapper tbody tr {
        display: block;
        width: 100%;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        box-sizing: border-box;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 10px;
    }

    /* Every TD */
    .ad-assign-quiz-table-wrapper tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 0;
        border: none;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
        box-sizing: border-box;
        text-align: right;
    }

    /* Last item no border */
    .ad-assign-quiz-table-wrapper tbody td:last-child {
        border-bottom: none;
        padding-bottom: 2px;
    }

    /* Label from data-label */
    .ad-assign-quiz-table-wrapper tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280;
        font-size: 12px;
        text-align: left;
        margin-right: 15px;
    }

    /* Select button */
    .ad-assign-quiz-table-wrapper .select-quiz-btn {
        padding: 7px 18px;
        border-radius: 7px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }
}

    @media(max-width: 768px) {
        .count-cards {
            grid-template-columns: 1fr;
        }

        .quiz-table-wrap {
            overflow-x: auto;
        }

        .quiz-table {
            min-width: 700px;
        }


    }
</style>


<div class="panel active assign-quiz-page">

    {{-- HEADER --}}
    <div class="assign-header">
        <div>
            <h1>Assign Quiz</h1>
            <p>
                Assign quizzes to:
                <strong>{{ $ad->campaign_name }}</strong>
            </p>
        </div>

        <div>
            <a href="{{ route('superadmin.ad.index') }}"
               class="btn btn-ghost">
                ← Back
            </a>
        </div>

    </div>


    {{-- COUNT CARDS --}}
    <div class="count-cards">

        <div class="count-card">

            <div class="count-card-title">
                Approved / Total Users
            </div>

            <div class="count-card-value"
                 id="totalUsersCount">
                {{ $selectedQuizTotalPlayers ?? 0 }}
            </div>

        </div>


        <div class="count-card">

            <div class="count-card-title">
                Visited Users
            </div>

            <div class="count-card-value"
                 id="visitedUsersCount">
                0
            </div>

        </div>

    </div>


    {{-- FORM --}}
    <form method="POST" action="{{ route('superadmin.ad.saveAssignedQuizzes', $ad->id) }}" id="assignQuizForm">
        @csrf
        {{-- TOOLBAR --}}
        {{-- <div class="quiz-toolbar">
             <label class="all-checkbox"><input type="checkbox" id="selectAll"> <span>All</span> </label> 
            <button type="submit"  class="submit-btn"> Submit </button>
        </div> --}}

        {{-- QUIZ TABLE --}}
        <div class="quiz-table-wrap ad-assign-quiz-table-box">
            <table class="quiz-table ad-assign-quiz-table-wrapper">
                <thead>
                    <tr>
                       
                        <th>Quiz Name </th>
                        <th>Quiz Type</th>
                        <th> Number of Players </th>
                        <th> Visibility</th>
                        <th width="120">Action </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($quizzes as $quiz)

                        @php
                            $isSelected = in_array(
                                $quiz->id,
                                $selectedQuizIds
                            );
                        @endphp

                        <tr>
                            {{-- <td>
                                <input type="checkbox" class="quiz-checkbox"  name="quiz_ids[]" value="{{ $quiz->id }}" {{ $isSelected ? 'checked' : '' }} >
                            </td> --}}

                            <td data-label="Quiz Name"> {{ $quiz->title ?? '-' }} </td>

                            <td data-label="Quiz Type">
                                @if($quiz->quizType)
                                    {{ $quiz->quizType->name }}
                                @else
                                    {{ $quiz->type ?? '-' }}
                                @endif
                            </td>

                            <td data-label="Number of Players">{{ $quiz->no_of_players ?? 0 }}</td>

                            <td data-label="Visibility"> - </td>

                            <td data-label="Action">
                                {{-- <button type="button" class="select-quiz-btn {{ $isSelected ? 'selected' : '' }}" data-quiz-id="{{ $quiz->id }}">
                                 {{ $isSelected ? 'Selected' : 'Select' }}
                                </button> --}}

                                 <button type="button" class="select-quiz-btn {{ $isSelected ? 'selected' : '' }}" data-quiz-id="{{ $quiz->id }}" data-players="{{ $quiz->no_of_players ?? 0 }}" >
                                  {{ $isSelected ? 'Select' : 'Selected' }}
                                 </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:30px;">
                                No quizzes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

    </form>

</div>


{{-- <script>

    document.addEventListener('DOMContentLoaded', function () {

        const selectAll = document.getElementById('selectAll');
        const quizCheckboxes = document.querySelectorAll('.quiz-checkbox');
        const selectButtons = document.querySelectorAll('.select-quiz-btn');
        const totalUsersCount = document.getElementById('totalUsersCount');
        const visitedUsersCount = document.getElementById('visitedUsersCount');

        /** Existing selected quizzes */
        updateSelectAllState();
        updateUserCounts();

        /* ALL CHECKBOX */
        selectAll.addEventListener('change', function () {
            quizCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateSelectButtons();
            updateUserCounts();
        });


        /** INDIVIDUAL CHECKBOX*/
        quizCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                updateSelectAllState();
                updateSelectButtons();
                updateUserCounts();
            });
        });


        /** SELECT BUTTON*/
        selectButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const quizId = this.dataset.quizId;
                const checkbox = document.querySelector(
                        '.quiz-checkbox[value="' + quizId + '"]'
                    );

                if (!checkbox) {
                    return;
                }

                checkbox.checked =  !checkbox.checked;
                updateSelectAllState();
                updateSelectButtons();
                updateUserCounts();
            });

        });

        /* UPDATE SELECT BUTTON TEXT*/
        function updateSelectButtons() {
            selectButtons.forEach(function (button) {
                const quizId = button.dataset.quizId;
                const checkbox = document.querySelector(
                        '.quiz-checkbox[value="' + quizId + '"]'
                    );

                if (!checkbox) {
                    return;
                }

                if (checkbox.checked) {
                    button.classList.add('selected');
                    button.innerText = 'Selected';
                } else {
                    button.classList.remove('selected');
                    button.innerText = 'Select';
                }
            });
        }

        /* * UPDATE ALL CHECKBOX */
        function updateSelectAllState() {
            const total = quizCheckboxes.length;
            const checked = document.querySelectorAll(
                    '.quiz-checkbox:checked'
                ).length;

            selectAll.checked =
                total > 0 && total === checked;

        }


        /* * GET SELECTED QUIZ IDS*/
        function getSelectedQuizIds() {
            return Array.from(
                document.querySelectorAll(
                    '.quiz-checkbox:checked'
                )
            ).map(function (checkbox) {
                return checkbox.value;
            });
        }


        /* * UPDATE USER COUNTS */
        function updateUserCounts() {
            const quizIds = getSelectedQuizIds();

            if (quizIds.length === 0) {
                totalUsersCount.innerText = '0';
                visitedUsersCount.innerText = '0';
                return;
            }

            fetch("{{ route('superadmin.ad.quizUserCounts') }}", {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':'{{ csrf_token() }}',
                    'Accept':'application/json'
                },

                body: JSON.stringify({
                    quiz_ids: quizIds
                })
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    return;
                }
                totalUsersCount.innerText = data.total_users ?? 0;

                visitedUsersCount.innerText = data.visited_users ?? 0;

            })

            .catch(function (error) {
                console.error(
                    'Quiz user count error:',
                    error
                );
            });
        }
    });

</script> --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const selectButtons = document.querySelectorAll('.select-quiz-btn');
    const totalUsersCount = document.getElementById('totalUsersCount');

    let selectedQuizIds = @json($selectedQuizIds ?? []).map(function (id) {
        return String(id);
    });


    function calculateTotalUsers() {
        let total = 0;
        selectButtons.forEach(function (button) {
            const quizId = String(button.dataset.quizId);
            if (selectedQuizIds.includes(quizId)) {
                total += parseInt(
                    button.dataset.players || 0
                );
            }
        });
        totalUsersCount.innerText = total;
    }

    function updateButton(button, selected) {

        if (selected) {
            // SELECTED = WHITE + BLACK
            button.classList.add('selected');
            button.innerText = 'Selected';

        } else {
            // NOT SELECTED = GREEN + WHITE
            button.classList.remove('selected');
            button.innerText = 'Select';
        }
    }

    // Page load par DB ke selected quizzes ke buttons update karo
    selectButtons.forEach(function (button) {
        const quizId = String(button.dataset.quizId);
        updateButton(
            button,
            selectedQuizIds.includes(quizId)
        );
    });

    // Page load par total calculate
    calculateTotalUsers();

    // SELECT / UNSELECT
    selectButtons.forEach(function (button) {
        button.addEventListener('click', function () {

            const clickedButton = this;
            const quizId = String(clickedButton.dataset.quizId);
            const isCurrentlySelected = selectedQuizIds.includes(quizId);
            const newSelectedState = !isCurrentlySelected;

            clickedButton.disabled = true;

            fetch(
                "{{ route('superadmin.ad.saveAssignedQuizzes', $ad->id) }}",
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },

                    body: JSON.stringify({
                        quiz_id: quizId,
                        selected: newSelectedState
                    })
                }
            )
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    alert(
                        data.message ||
                        'Something went wrong.'
                    );

                    return;
                }

                // DB se returned IDs
                selectedQuizIds =
                    (data.quiz_ids || []).map(function (id) {
                        return String(id);
                    });

                // Button update
                updateButton(
                    clickedButton,
                    selectedQuizIds.includes(quizId)
                );
                // Total players update
                calculateTotalUsers();

            })

            .catch(function (error) {
                console.error(
                    'Quiz assignment error:',
                    error
                );

                alert( 'Unable to save quiz selection.'  );
            })
            .finally(function () {
                clickedButton.disabled = false;
            });
        });
    });
});

</script>

@endsection