<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\DashboardRepositoryInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function __construct(protected DashboardRepositoryInterface $repository)
    {
    }

    public function dashboard(Request $request)
    {
        return $this->repository->dashboard($request->user());

    }


    public function profile(Request $request)
    {
        return $this->repository->profile($request);

    }

    public function completeProfile(Request $request)
    {
        $response = $this->repository->completeProfile($request);

        return response()->json($response, $response['code']);
    }
    
    public function updateProfile(Request $request)
    {
        $response = $this->repository->updateProfile($request);

        return response()->json($response, $response['code']);
    }


    public function updateAltMobile(Request $request)
    {
        $response = $this->repository->updateAltMobile($request);

        return response()->json($response, $response['code']);
    }


    public function sendVerifyEmail(Request $request)
    {
        $response = $this->repository->sendOtpVerifyEmail($request);

        return response()->json($response, $response['code']);
    }

     public function verifyEmail(Request $request)
    {
        $response = $this->repository->verifyEmail($request);

        return response()->json($response, $response['code']);
    }

    /************************* END *************************/ 
}