<?php

namespace App\Interfaces\api;

interface QuizAdminRepositoryInterface
{
    public function list(array $filters): array;
    public function detail(int $quizId): array;
    public function create(array $data): array;
    public function update(int $quizId, array $data): array;
    public function delete(int $quizId): array;
    public function setQuestions(int $quizId, array $questionIds): array;
    public function removeQuestion(int $quizId, int $questionId): array;
    public function setRankRewards(int $quizId, array $reward): array;
    public function setFilters(int $quizId, array $filters): array;
    public function publish(int $quizId, array $data): array;
    public function approve(int $quizId, string $status): array;
    public function stats(int $quizId): array;
}
