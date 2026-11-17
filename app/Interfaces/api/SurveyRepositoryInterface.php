<?php

namespace App\Interfaces\api;

interface SurveyRepositoryInterface
{
    // Admin
    public function createQuestion(array $data): array;
    public function updateQuestion(int $id, array $data): array;
    public function deleteQuestion(int $id): array;
    public function questionsList(int $surveyId): array;
    public function setRewardRule(int $surveyId, array $data): array;

    // User
    
    
    public function surveyList();
    public function startSurvey(int $surveyId): array;
    public function submitAnswer($user, int $surveyId, array $data): array;
    public function submitBulk($user, int $surveyId, array $answers): array;
    public function myResult($user, int $surveyId): array;
    public function myHistory($user): array;
}
