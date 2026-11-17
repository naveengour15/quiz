<?php

namespace App\Repositories\admin;
use App\Models\Quiz;
use Illuminate\Support\Collection;

interface QuizRepositoryInterface
{
    public function getAll(array $filters = []);

    // public function find(int $id);

    // public function create(array $data);

    // public function update(int $id, array $data);

    // public function delete(int $id);

    //  public function getQuestionBank();
    // ✅ Quiz object optional hai (null ho sakta hai)
    public function getQuestionBank(?Quiz $quiz = null); 

    public function getSelectedQuestions(Quiz $quiz);

    public function syncQuestions(Quiz $quiz, array $questionIds, array $orders = [], array $botAnswers = [], array $isSwitchQuestion = []);

    public function getStates(): Collection;

    public function getCitiesByState(int $stateId): Collection;
}