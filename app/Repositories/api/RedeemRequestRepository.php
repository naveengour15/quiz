<?php

namespace App\Repositories\api;

use App\Models\State;
use App\Models\SimNetwork;
use App\Models\RedeemRequest;
use App\Models\ElectricityZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Interfaces\api\RedeemRequestRepositoryInterface;

class RedeemRequestRepository implements RedeemRequestRepositoryInterface
{
    public function createRedeemRequest($user, array $data): array
    {
        try {
            return DB::transaction(function () use ($user, $data) {

                $amount = (float) $data['redeem_amount'];

                /*
                 * Get latest wallet balance from database.
                 */
                $user->refresh();

                $availableBalance = (float) $user->wallet_balance;

                /*
                 * Check available balance.
                 */
                if ($amount > $availableBalance) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient wallet balance.',
                        'data' => [
                            'available_balance' => $availableBalance,
                            'redeem_amount'     => $amount,
                        ],
                    ];
                }

                /*
                 * Create redeem request.
                 *
                 * Wallet balance is NOT deducted here
                 * because request is still pending.
                 */
                $redeemRequest = RedeemRequest::create([
                    'tenant_id'       => $user->tenant_id,
                    'user_id'         => $user->id,
                    'request_type'    => $data['request_type'],
                    'zone_id'         => $data['zone_id'],
                    'company'         => $data['company'] ?? null,
                    'post_prepaid'    => $data['post_prepaid'] ?? null,
                    'mobile'          => $data['mobile'] ?? null,
                    'redeem_amount'   => $amount,
                    'consumer_number' => $data['consumer_number'] ?? null,
                    'status'          => 'pending',
                    'user_note'       => $data['user_note'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'Redeem request submitted successfully.',
                    'data' => [
                        'id'                => $redeemRequest->id,
                        'request_type'      => $redeemRequest->request_type,
                        'company'           => $redeemRequest->company,
                        'post_prepaid'      => $redeemRequest->post_prepaid,
                        'mobile'            => $redeemRequest->mobile,
                        'consumer_number'   => $redeemRequest->consumer_number,
                        'redeem_amount'     => $redeemRequest->redeem_amount,
                        'status'            => $redeemRequest->status,
                        'available_balance' => $availableBalance,
                        'created_at'        => $redeemRequest->created_at,
                    ],
                ];
            });

        } catch (\Throwable $e) {

            Log::error('Redeem request failed', [
                'user_id' => $user->id ?? null,
                'data'    => $data,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to create redeem request.',
                'data'    => [],
            ];
        }
    }

    public function getRedeemRequests($user, array $filters = []): array
    {
        try {
            $query = RedeemRequest::where('user_id', $user->id)
                ->with('state:id,name')
                ->latest('id');

            if (!empty($filters['request_type'])) {
                $query->where(
                    'request_type',
                    $filters['request_type']
                );
            }

            if (!empty($filters['status'])) {
                $query->where(
                    'status',
                    $filters['status']
                );
            }

            $requests = $query->get();


            $electricityZones = ElectricityZone::with('state')
                ->where('status', 'active')
                ->orderBy('zone', 'asc')
                ->get()
                ->map(function ($electricityZone) {
                    $electricityZone->state_name = $electricityZone->state?->name;
                    unset($electricityZone->state);

                    return $electricityZone;
                });

            $states = State::orderBy('name', 'asc')->get();

            $simNetwork = SimNetwork::orderBy('name', 'asc')->get();
    
            return [
                'success' => true,
                'message' => 'Redeem requests fetched successfully.',
                'data'    => $requests,
                'states' => $states,
                'electricity_zones' => $electricityZones,
                'sim_network' => $simNetwork
            ];

        } catch (\Throwable $e) {

            Log::error('Redeem request listing failed', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch redeem requests.',
                'data'    => [],
            ];
        }
    }
}