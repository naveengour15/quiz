<?php

namespace Tests\Feature;

use App\Interfaces\androidapi\AuthRepositoryInterface;
use Mockery;
use Tests\TestCase;

class AndroidLoginTypeTest extends TestCase
{
    public function test_otp_verification_forwards_ios_login_type_to_the_repository(): void
    {
        $repository = Mockery::mock(AuthRepositoryInterface::class);
        $repository->shouldReceive('verifyOtp')
            ->once()
            ->with('9876543210', '1234', 'ios')
            ->andReturn(['status' => true, 'message' => 'OTP verified.']);

        $this->app->instance(AuthRepositoryInterface::class, $repository);

        $this->postJson(route('androidapi.auth.verify-otp'), [
            'mobile' => '9876543210',
            'otp' => '1234',
            'login_type' => 'ios',
        ])->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_otp_verification_defaults_login_type_to_android(): void
    {
        $repository = Mockery::mock(AuthRepositoryInterface::class);
        $repository->shouldReceive('verifyOtp')
            ->once()
            ->with('9876543210', '1234', 'android')
            ->andReturn(['status' => true, 'message' => 'OTP verified.']);

        $this->app->instance(AuthRepositoryInterface::class, $repository);

        $this->postJson(route('androidapi.auth.verify-otp'), [
            'mobile' => '9876543210',
            'otp' => '1234',
        ])->assertOk()
            ->assertJsonPath('status', true);
    }
}
