<?php

namespace Tests\Unit;

use App\Models\Quiz;
use App\Models\TempQuizQuestionUserAnswer;
use App\Models\User;
use App\Services\FffQuizService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FffQuizExpiryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
        Quiz::where('title', 'Test FFF Expiry Quiz')->delete();
        User::whereIn('mobile', ['9997771112'])->delete();
    }

    protected function tearDown(): void
    {
        Quiz::where('title', 'Test FFF Expiry Quiz')->delete();
        User::whereIn('mobile', ['9997771112'])->delete();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fff_quiz_expiry_time_calculation()
    {
        $service = app(FffQuizService::class);
        $schedTime = Carbon::parse('2026-09-28 10:00:00');
        $quiz = new Quiz(['scheduled_at' => $schedTime]);

        $expiry = $service->getFffQuizExpiryTime($quiz);

        // schedule_time + 20 minutes buffer to prevent premature expiration
        $this->assertEquals('2026-09-28 10:20:00', $expiry->format('Y-m-d H:i:s'));
    }

    public function test_fff_quiz_is_not_expired_before_expiry_time()
    {
        Carbon::setTestNow('2026-09-28 10:05:00');
        $service = app(FffQuizService::class);
        $schedTime = Carbon::parse('2026-09-28 10:00:00');
        $quiz = new Quiz(['id' => 999, 'scheduled_at' => $schedTime]);

        $this->assertFalse($service->isFffQuizExpired($quiz));
    }

    public function test_fff_quiz_is_expired_after_expiry_time_when_no_answers_submitted()
    {
        Carbon::setTestNow('2026-09-28 10:20:15');
        $service = app(FffQuizService::class);
        $schedTime = Carbon::parse('2026-09-28 10:00:00');
        $quiz = new Quiz(['id' => 999, 'scheduled_at' => $schedTime]);

        $user = new User(['id' => 123]);

        $this->assertTrue($service->isFffQuizExpired($quiz, $user));
    }

    public function test_fff_quiz_is_not_expired_after_70_seconds_when_user_has_submitted_answer()
    {
        Carbon::setTestNow('2026-09-28 10:01:15');
        $service = app(FffQuizService::class);
        $schedTime = Carbon::parse('2026-09-28 10:00:00');
        $quiz = Quiz::create([
            'tenant_id'    => 1,
            'title'        => 'Test FFF Expiry Quiz',
            'scheduled_at' => $schedTime,
            'status'       => 'published',
            'approve_status' => 'approved',
        ]);

        $user = User::create([
            'tenant_id' => 1,
            'name'      => 'Test Expiry User',
            'mobile'    => '9997771112',
            'password'  => bcrypt('password'),
        ]);

        TempQuizQuestionUserAnswer::create([
            'tenant_id'   => 1,
            'quiz_id'     => $quiz->id,
            'question_id' => 1,
            'user_id'     => $user->id,
            'quiz_type'   => 'fff',
            'answer'      => 'A',
            'status'      => true,
            'show_time'   => '10:00:15',
            'ans_time'    => '10:00:18',
            'total_time'  => 3.0,
        ]);

        $this->assertFalse($service->isFffQuizExpired($quiz, $user));

        TempQuizQuestionUserAnswer::where('quiz_id', $quiz->id)->delete();
        $quiz->delete();
        $user->delete();
    }
}
