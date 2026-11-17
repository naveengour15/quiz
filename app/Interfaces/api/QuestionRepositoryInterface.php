<?php

namespace App\Interfaces\api;

interface QuestionRepositoryInterface
{
    public function list(array $filters): array;
    public function create(array $data): array;
    public function update(int $id, array $data): array;
    public function delete(int $id): array;
    public function moderate(int $id, string $status): array;
}
