<?php

namespace App\Interfaces\api;

interface AdminRepositoryInterface
{
    public function login(array $data): array;
    public function list(int $tenantId): array;
    public function create(array $data): array;
    public function rolesList(): array;
    public function createRole(array $data): array;
    public function tenantsList(): array;
    public function updateTenantStatus(int $tenantId, string $status): array;
    public function auditLogs(array $filters): array;
}
