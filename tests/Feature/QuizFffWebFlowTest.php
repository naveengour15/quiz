<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizQuestion;
use App\Models\QuizUser;
use App\Models\QuizSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuizFffWebFlowTest extends TestCase
{
    protected $user;
    protected $quiz;
    protected $questions = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');

        User::where('mobile', '8887776666')->delete();
        Quiz::where('title', 'PHPUnit FFF Quiz Test')->delete();

        // 1. Create Test User
        $this->user = User::create([
            'tenant_id' => 1,
            'name' => 'FFF Tester',
            'mobile' => '8887776666',
            'password' => bcrypt('123456'),
            'is_active' => 1,
            'status' => 1,
        ]);

        // 2. Create FFF Quiz
        $this->quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'PHPUnit FFF Quiz Test',
            'visibility' => 'fastest_finger_first',
            'status' => 'scheduled',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'scheduled_at' => Carbon::now()->addMinutes(5),
            'availability_start' => Carbon::now()->addMinutes(5),
            'availability_end' => Carbon::now()->addMinutes(30),
            'question_limit' => 1,
            'per_que_time_limit_sec' => 60,
        ]);

        // 3. Question
        $q = Question::create([
            'tenant_id' => 1,
            'question_text' => 'FFF Test Q1?',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_option' => 'option_a',
            'status' => 1,
        ]);
        QuizQuestion::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'question_id' => $q->id,
            'order' => 1,
        ]);
        $this->questions[] = $q;
    }

    protected function tearDown(): void
    {
        if ($this->quiz) {
            QuizQuestion::where('quiz_id', $this->quiz->id)->delete();
            QuizUser::where('quiz_id', $this->quiz->id)->delete();
            $this->quiz->delete();
        }
        foreach ($this->questions as $q) {
            $q->delete();
        }
        if ($this->user) {
            $this->user->delete();
        }
        parent::tearDown();
    }

    protected function actAsFsrUser()
    {
        return $this->withSession([
            'fsr_authenticated' => true,
            'fsr_user_id' => $this->user->id,
            'fsr_tenant_id' => 1,
        ])->actingAs($this->user);
    }

    /**
     * Test: Unjoined user is redirected to home.
     */
    public function test_fff_unjoined_user_is_redirected()
    {
        $resp = $this->actAsFsrUser()->get(route('fsrapp.fff.waiting', ['id' => $this->quiz->id]));
        $this->assertTrue(
            $resp->isRedirect(route('fsrapp.home')) || $resp->isRedirect('/fsrapp/home')
        );
    }

    /**
     * Test: Joined user can access FFF stages and APIs.
     */
    public function test_fff_joined_user_can_access_views()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        // Waiting View
        $waitResp = $this->actAsFsrUser()->get(route('fsrapp.fff.waiting', ['id' => $this->quiz->id]));
        $waitResp->assertStatus(200);

        // Questions API
        $qResp = $this->actAsFsrUser()->get(route('fsrapp.fff.api.questions', ['id' => $this->quiz->id]));
        $qResp->assertStatus(200);

        // Fast forward to active time and submit answer
        $this->quiz->update([
            'scheduled_at' => Carbon::now()->subSeconds(20),
            'availability_start' => Carbon::now()->subSeconds(20),
        ]);
        Cache::forget("quiz_model_{$this->quiz->id}");

        $ansResp = $this->actAsFsrUser()->post(route('fsrapp.fff.api.submit-answer', ['id' => $this->quiz->id]), [
            'question_id' => $this->questions[0]->id,
            'answer' => 'option_a',
            'time_taken' => 4.5,
        ]);
        $ansResp->assertStatus(200);

        // Leaderboard endpoint
        $lbResp = $this->actAsFsrUser()->get(route('fsrapp.fff.leaderboard', ['id' => $this->quiz->id]));
        $lbResp->assertStatus(200);
    }
}

