<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\RedeemRequestRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RedeemRequestController extends Controller
{
    public function __construct(
        protected RedeemRequestRepositoryInterface $redeemRepository
    ) {
    }

    /**
     * Create redeem request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'request_type' => [
                'required',
                'string',
                'in:recharge,electricity_bill',
            ],

            'zone_id' => [
                'nullable',
                'integer',
                'exists:states,id',
            ],

            'company' => [
                'nullable',
                'string',
                'max:191',
            ],

            'post_prepaid' => [
                'nullable',
                'string',
                'max:191',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'redeem_amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'consumer_number' => [
                'nullable',
                'string',
                'max:191',
            ],

            'user_note' => [
                'nullable',
                'string',
            ],
        ]);

        /*
         * Additional validation based on request type.
         */

        $validator->sometimes(
            'mobile',
            'required',
            function ($input) {
                return $input->request_type === 'phone_recharge';
            }
        );

        $validator->sometimes(
            'consumer_number',
            'required',
            function ($input) {
                return $input->request_type === 'electricity_bill';
            }
        );

        $validator->sometimes(
            'company',
            'required',
            function ($input) {
                return in_array(
                    $input->request_type,
                    [
                        'phone_recharge',
                        'electricity_bill',
                    ]
                );
            }
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $result = $this->redeemRepository->createRedeemRequest(
            $user,
            $validator->validated()
        );

        return response()->json(
            $result,
            $result['success'] ? 200 : 422
        );
    }

    /**
     * Get user's redeem requests.
     */
    public function index(Request $request)
    {

           $validator = Validator::make($request->all(), [
            'request_type' => [
                'nullable',
                'string',
                'in:phone_recharge,electricity_bill',
            ],
            'status' => [
                'nullable',
                'string',
                'in:pending,approved,rejected,cancelled',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->redeemRepository->getRedeemRequests(
            $request->user(),
            $validator->validated()
        );

        return response()->json($result);
    }
}