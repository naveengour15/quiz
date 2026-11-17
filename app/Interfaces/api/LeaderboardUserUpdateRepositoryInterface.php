<?php

namespace App\Interfaces\api;

interface LeaderboardUserUpdateRepositoryInterface
{
  public function leaderboardUserUpdate(?int $tenantId = null): void;
}
