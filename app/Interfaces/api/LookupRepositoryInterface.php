<?php

namespace App\Interfaces\api;

interface LookupRepositoryInterface
{ 
    public function countries(): array;
    public function states(?int $countryId): array;
    public function cities(?int $stateId): array;
    public function categories(?int $parentId): array;
    public function subcategories(?int $categoryId = null): array;
    public function levels(): array;
    public function quizTypes(): array;
}
