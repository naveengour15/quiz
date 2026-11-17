<?php

namespace App\Repositories\superadmin;
use Illuminate\Support\Collection;
use App\Models\Ad;
interface AdRepositoryInterface
{
    public function index($request);

    public function store($request);

    public function update($request, $id);

    public function destroy($id);

    public function show($id);

    public function changeStatus($id, $request);
    public function getCitiesByState(int $stateId): Collection;
    public function getCities($stateId);

    public function assignQuiz(Ad $ad);

    public function saveAssignedQuizzes($request, Ad $ad);
    public function analysis($id);
    public function bannerAnalysis($id);
  
}