<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizNotification;
use App\Repositories\WhatsAppRepositoryInterface;
use App\Repositories\WhatsAppRepository;
use Illuminate\Support\Facades\DB;
use Mockery;

class QuizWhatsAppNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
        QuizNotification::where('user_name', 'Test Notification User')
            ->orWhere('quiz_name', 'WhatsApp Notification Test Quiz')
            ->forceDelete();
        Quiz::where('title', 'WhatsApp Notification Test Quiz')->forceDelete();
        User::where('mobile', 'like', '999%')->forceDelete();
    }

    protected function tearDown(): void
    {
        QuizNotification::where('user_name', 'Test Notification User')
            ->orWhere('quiz_name', 'WhatsApp Notification Test Quiz')
            ->forceDelete();
        Quiz::where('title', 'WhatsApp Notification Test Quiz')->forceDelete();
        User::where('mobile', 'like', '999%')->forceDelete();
        parent::tearDown();
    }

    public function test_pending_notifications_are_processed_and_status_updated()
    {
        // 1. Create Test User
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        // 2. Create Test Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(15),
        ]);

        // 3. Create Pending QuizNotification
        $notification = QuizNotification::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'quiz_name' => $quiz->title,
            'quiz_schedule_time' => now()->addMinutes(15),
            'notificationactiv_date_time' => now()->subMinute(),
            'status' => 'pending',
            'msg' => null,
            'sent_date_time' => null,
        ]);

        // 4. Mock WhatsAppRepository and verify parameters
        $mockWhatsAppRepo = Mockery::mock(WhatsAppRepository::class)->makePartial();
        $mockWhatsAppRepo->shouldReceive('sendBatchWhatsAppMessages')
            ->atLeast()->once()
            ->andReturnUsing(function ($batchMessages) {
                $res = [];
                foreach ($batchMessages as $msg) {
                    $res[$msg['id']] = [
                        'success' => true,
                        'status' => 'sent',
                        'msg' => 'WhatsApp message sent successfully',
                    ];
                }
                return $res;
            });

        $this->app->instance(WhatsAppRepositoryInterface::class, $mockWhatsAppRepo);

        // 5. Run Artisan Command
        $this->artisan('quiz:send-whatsapp-notifications')
            ->assertExitCode(0);

        // 6. Assert Notification Status updated to 'Sent' and sent_date_time is populated
        $freshNotification = $notification->fresh();
        $this->assertEquals('Sent', $freshNotification->status);
        $this->assertNotNull($freshNotification->sent_date_time);
        $this->assertEquals('WhatsApp message sent successfully', $freshNotification->msg);
    }

    public function test_unauthenticated_access_to_quiz_join_redirects_to_login_with_intended_url()
    {
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(10),
        ]);

        $targetUrl = route('fsrapp.quizes.join', ['id' => $quiz->id, 'auto_join' => 1]);

        $response = $this->get($targetUrl);

        $response->assertStatus(302);
        $response->assertRedirect(route('fsrapp.login', ['redirect' => $targetUrl]));
        $this->assertEquals($targetUrl, session('url.intended'));
        $this->assertEquals($targetUrl, session('redirect_to'));
    }

    public function test_verified_user_redirects_to_intended_quiz_url_after_otp()
    {
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'referral_code_to' => 'SANDE687',
            'is_active' => 1,
        ]);

        // Create referral record
        \App\Models\ReferralCode::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'code' => 'SANDE687',
            'referral_user_id' => $user->id,
            'status' => 'active',
            'used' => 'used',
        ]);

        \App\Models\MobileVerification::create([
            'tenant_id' => 1,
            'mobile_number' => '9991112223',
            'otp' => '1234',
            'token' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(10),
        ]);

        $targetUrl = route('fsrapp.quizes.join', ['id' => $quiz->id, 'auto_join' => 1]);

        $response = $this->withSession([
            'redirect_to' => $targetUrl,
            'url.intended' => $targetUrl,
        ])->post(route('fsrapp.verify-otp'), [
            'mobile' => '9991112223',
            'otp' => ['1', '2', '3', '4'],
            'redirect' => $targetUrl,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect($targetUrl);
        $this->assertSame('web', $user->fresh()->login_type);
    }

    public function test_authenticated_user_direct_quiz_join_with_auto_join()
    {
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(10),
        ]);

        $targetUrl = route('fsrapp.quizes.join', ['id' => $quiz->id, 'auto_join' => 1]);

        $response = $this->actingAs($user)->withSession([
            'fsr_authenticated' => true,
            'fsr_user_id' => $user->id,
            'fsr_user_name' => $user->name,
            'fsr_user_mobile' => $user->mobile,
        ])->get($targetUrl);

        $response->assertStatus(302);
        $response->assertRedirect(route('fsrapp.quizes.waiting', $quiz->id));
    }

    public function test_large_batch_notifications_all_processed_completely()
    {
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(15),
        ]);

        $users = [];
        $notifications = [];
        for ($i = 1; $i <= 250; $i++) {
            $mobile = '999' . str_pad($i, 7, '0', STR_PAD_LEFT);
            $user = User::create([
                'tenant_id' => 1,
                'mobile' => $mobile,
                'name' => 'Test Notification User',
                'password' => bcrypt('password'),
                'is_active' => 1,
            ]);
            $users[] = $user;

            $notifications[] = [
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'quiz_name' => $quiz->title,
                'quiz_schedule_time' => now()->addMinutes(15),
                'notificationactiv_date_time' => now()->subMinute(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        QuizNotification::insert($notifications);

        $mockWhatsAppRepo = Mockery::mock(WhatsAppRepository::class)->makePartial();
        $mockWhatsAppRepo->shouldReceive('sendBatchWhatsAppMessages')
            ->atLeast()->once()
            ->andReturnUsing(function ($batchMessages) {
                $res = [];
                foreach ($batchMessages as $msg) {
                    $res[$msg['id']] = [
                        'success' => true,
                        'status' => 'sent',
                        'msg' => 'WhatsApp message sent successfully',
                    ];
                }
                return $res;
            });

        $this->app->instance(WhatsAppRepositoryInterface::class, $mockWhatsAppRepo);

        $this->artisan('quiz:send-whatsapp-notifications')
            ->assertExitCode(0);

        $pendingCount = QuizNotification::where('quiz_id', $quiz->id)->where('status', 'pending')->count();
        $sentCount = QuizNotification::where('quiz_id', $quiz->id)->where('status', 'Sent')->count();

        $this->assertEquals(0, $pendingCount);
        $this->assertEquals(250, $sentCount);

        // Cleanup
        User::whereIn('id', collect($users)->pluck('id'))->delete();
    }

    public function test_prepare_whatsapp_entries_does_not_create_duplicate_records()
    {
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'scheduled',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'scheduled_at' => now()->addMinutes(40),
        ]);

        $mockWhatsAppRepo = Mockery::mock(WhatsAppRepository::class)->makePartial();
        $mockWhatsAppRepo->shouldReceive('sendBatchWhatsAppMessages')
            ->zeroOrMoreTimes()
            ->andReturn([]);
        $this->app->instance(WhatsAppRepositoryInterface::class, $mockWhatsAppRepo);

        // Run command first time (prepares entries)
        $this->artisan('quiz:send-whatsapp-notifications')
            ->assertExitCode(0);

        $initialCount = QuizNotification::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count();
        $this->assertEquals(1, $initialCount);

        // Run command second time (should NOT create duplicate)
        $this->artisan('quiz:send-whatsapp-notifications')
            ->assertExitCode(0);

        $secondCount = QuizNotification::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count();
        $this->assertEquals(1, $secondCount);
    }

    public function test_toggle_notification_handles_re_enabling_without_duplicate_error()
    {
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'scheduled',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'scheduled_at' => now()->addMinutes(40),
        ]);

        $repo = app(\App\Interfaces\api\QuizRepositoryInterface::class);

        // 1. Toggle ON -> should create notification
        $res1 = $repo->toggleNotificationData($user, $quiz->id);
        $this->assertTrue($res1['success']);
        $this->assertTrue($res1['notified']);
        $this->assertEquals(1, QuizNotification::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count());

        // 2. Toggle OFF -> should soft-delete
        $res2 = $repo->toggleNotificationData($user, $quiz->id);
        $this->assertTrue($res2['success']);
        $this->assertFalse($res2['notified']);
        $this->assertEquals(0, QuizNotification::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count());
        $this->assertEquals(1, QuizNotification::withTrashed()->where('quiz_id', $quiz->id)->where('user_id', $user->id)->count());

        // 3. Toggle ON AGAIN -> should restore without Duplicate Entry 1062 violation!
        $res3 = $repo->toggleNotificationData($user, $quiz->id);
        $this->assertTrue($res3['success']);
        $this->assertTrue($res3['notified']);
        $this->assertEquals(1, QuizNotification::where('quiz_id', $quiz->id)->where('user_id', $user->id)->count());
    }

    public function test_no_duplicate_whatsapp_message_is_sent_when_command_runs_multiple_times()
    {
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9991112223',
            'name' => 'Test Notification User',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'WhatsApp Notification Test Quiz',
            'status' => 'published',
            'scheduled_at' => now()->addMinutes(15),
        ]);

        $notification = QuizNotification::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'quiz_name' => $quiz->title,
            'quiz_schedule_time' => now()->addMinutes(15),
            'notificationactiv_date_time' => now()->subMinute(),
            'status' => 'pending',
            'msg' => null,
            'sent_date_time' => null,
        ]);

        $sentCount = 0;
        $mockWhatsAppRepo = Mockery::mock(WhatsAppRepository::class)->makePartial();
        $mockWhatsAppRepo->shouldReceive('sendBatchWhatsAppMessages')
            ->andReturnUsing(function ($batchMessages) use (&$sentCount, $notification) {
                $res = [];
                foreach ($batchMessages as $msg) {
                    if ($msg['id'] == $notification->id) {
                        $sentCount++;
                    }
                    $res[$msg['id']] = [
                        'success' => true,
                        'status' => 'sent',
                        'msg' => 'WhatsApp message sent successfully',
                    ];
                }
                return $res;
            });

        $this->app->instance(WhatsAppRepositoryInterface::class, $mockWhatsAppRepo);

        // Run command 1st time
        $this->artisan('quiz:send-whatsapp-notifications')->assertExitCode(0);
        $this->assertEquals(1, $sentCount);

        // Run command 2nd time (should NOT send again!)
        $this->artisan('quiz:send-whatsapp-notifications')->assertExitCode(0);
        $this->assertEquals(1, $sentCount);

        // Run command 3rd time (should NOT send again!)
        $this->artisan('quiz:send-whatsapp-notifications')->assertExitCode(0);
        $this->assertEquals(1, $sentCount);

        // Notification must still be Sent
        $fresh = $notification->fresh();
        $this->assertEquals('Sent', $fresh->status);
        $this->assertNotNull($fresh->sent_date_time);
    }
}
