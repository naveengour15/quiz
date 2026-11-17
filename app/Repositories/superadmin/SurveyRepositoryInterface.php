<?php

namespace App\Repositories\superadmin;

interface SurveyRepositoryInterface
{
    public function index($request);

    public function store(array $data);

    public function update($survey, array $data);

    public function destroy($survey);

    public function report($survey);

    public function reportUsers($survey, int $questionId, string $answer);

    public function changeStatus($survey);

    public function searchTenant($search);
}