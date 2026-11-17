<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\AdminRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected AdminRepositoryInterface $adminRepository;

    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /** POST /api/admin/auth/login */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'   => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->adminRepository->login($validator->validated());

        return response()->json($result, $result['success'] ? 200 : 401);
    }

    /** GET /api/admin/users/list */
    public function list(Request $request)
    {
        return response()->json(
            $this->adminRepository->list($request->user()->tenant_id ?? 1)
        );
    }

    /** POST /api/admin/users/create */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:191',
            'email'   => 'nullable|email|max:191',
            'mobile'  => 'required|digits_between:10,15',
            'role_id' => 'required|integer',
            'password'=> 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data              = $validator->validated();
        $data['tenant_id'] = $request->user()->tenant_id ?? 1;

        return response()->json($this->adminRepository->create($data));
    }

    /** GET /api/admin/roles */
    public function rolesList()
    {
        return response()->json($this->adminRepository->rolesList());
    }

    /** POST /api/admin/roles/create */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:191',
            'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data           = $validator->validated();
        $data['status'] = 'active';

        return response()->json($this->adminRepository->createRole($data));
    }

    /** GET /api/superadmin/tenants */
    public function tenantsList()
    {
        return response()->json($this->adminRepository->tenantsList());
    }

    /** POST /api/superadmin/tenants/{id}/status */
    public function updateTenantStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->adminRepository->updateTenantStatus($id, $request->input('status'))
        );
    }

    /** GET /api/admin/audit-logs */
    public function auditLogs(Request $request)
    {
        return response()->json($this->adminRepository->auditLogs($request->only(['module'])));
    }
}
