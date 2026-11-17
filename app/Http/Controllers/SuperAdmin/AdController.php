<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Repositories\superadmin\AdRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\Ad;

class AdController extends Controller
{
    protected $adRepository;

    public function __construct(AdRepositoryInterface $adRepository)
    {
        $this->adRepository = $adRepository;
    }

      public function index(Request $request)
    {
        $data = $this->adRepository->index($request);

        return view('superadmin.ad.index', $data);
    }

      public function store(Request $request)
    {
    
        return $this->adRepository->store($request);
    }

    public function show($id)
    {
        return $this->adRepository->show($id);
    }

  
    public function update(Request $request, $id)
    {
        return $this->adRepository->update($request, $id);
    }

    
    public function destroy($id)
    {
        return $this->adRepository->destroy($id);
    }

     public function changeStatus(Request $request, Ad $id)
    {
        return $this->adRepository->changeStatus($id, $request);
    }
  
   public function getCities($stateId)
    {
        return response()->json(
            $this->adRepository->getCitiesByState($stateId)
        );
    }

    public function assignQuiz(Ad $ad)
    {
        $data = $this->adRepository->assignQuiz($ad);

        return view('superadmin.ad.assign_quiz', $data);
    }

    public function saveAssignedQuizzes(Request $request, Ad $ad)
    {
        return $this->adRepository->saveAssignedQuizzes($request, $ad);
    }

    public function analysis($id)
    {
        return $this->adRepository->analysis($id);
    }

    public function bannerAnalysis($id)
    {
        return $this->adRepository->bannerAnalysis($id);
    }

}
