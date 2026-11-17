<?php

namespace App\Repositories;

use App\Models\ActionTrail;
use App\Interfaces\ActionTrailRepositoryInterface;

class ActionTrailRepository implements ActionTrailRepositoryInterface
{
    public function create(array $data)
    {
        $authUser = auth()->user();
        $authUserId = $authUser?->id ?? 1;
        $roleId     = $authUser?->role_id ?? 1;
        
        return ActionTrail::create([
            'tenant_id'          => $data['tenant_id'] ?? null,
            'quiz_id'            => $data['quiz_id'] ?? null,
            'user_id'            => $data['user_id'] ?? ($data['updated_by'] ?? $authUserId),
            'survey_id'          => $data['survey_id'] ?? null,
            'table_name'         => $data['table_name'] ?? null,
            'reference_id'       => $data['reference_id'] ?? null,
            'action'             => $data['action'] ?? null,
            'updated_by'         => $data['updated_by'] ?? $authUserId,
            'updated_by_role_id' => $data['updated_by_role_id'] ?? $roleId,
            'old_details'        => $data['old_details'] ?? null,
            'details'            => $data['details'] ?? null,
            'status'             => $data['status'] ?? 'success',
        ]);
    }
}