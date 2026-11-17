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

class QuizWebFlowTest extends TestCase
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

        // Cleanup any old test records
        User::where('mobile', '8887776665')->delete();
        Quiz::where('title', 'PHPUnit Web Quiz Test')->delete();

        // 1. Create a Test User
        $this->user = User::create([
            'tenant_id' => 1,
            'name' => 'Web Tester',
            'mobile' => '8887776665',
            'password' => bcrypt('123456'),
            'is_active' => 1,
            'status' => 1,
        ]);

        // 2. Create a Test Quiz scheduled 5 minutes in future (within 40 mins window)
        $this->quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'PHPUnit Web Quiz Test',
            'visibility' => 'public',
            'status' => 'scheduled',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'scheduled_at' => Carbon::now()->addMinutes(5),
            'availability_start' => Carbon::now()->addMinutes(5),
            'availability_end' => Carbon::now()->addMinutes(30),
            'question_limit' => 2,
            'per_que_time_limit_sec' => 15,
        ]);

        // 3. Create 2 Questions
        for ($i = 1; $i <= 2; $i++) {
            $q = Question::create([
                'tenant_id' => 1,
                'question_text' => "Web Test Q{$i}?",
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
                'order' => $i,
            ]);
            $this->questions[] = $q;
        }

        QuizSetting::firstOrCreate(['id' => 1], [
            'tenant_id' => 1,
            'warmup_seconds' => 10,
            'per_question_seconds' => 15,
        ]);
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
     * Test 1: User without enrollment trying to access waiting/question page must be redirected.
     */
    public function test_unjoined_user_is_redirected_to_home()
    {
        $response = $this->actAsFsrUser()->get(route('fsrapp.quizes.waiting', ['id' => $this->quiz->id]));

        // Expected: Redirect to home because user hasn't joined yet
        $this->assertTrue(
            $response->isRedirect(route('fsrapp.home')) || $response->isRedirect('/fsrapp/home'),
            'Unjoined user accessing waiting page should redirect to home.'
        );
    }

    /**
     * Test 2: User joins quiz successfully.
     */
    public function test_user_can_join_quiz()
    {
        $response = $this->actAsFsrUser()->post(route('fsrapp.quizes.join.post', ['id' => $this->quiz->id]));

        $this->assertDatabaseHas('quiz_users', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test 3: Joined user accessing waiting page and status API.
     */
    public function test_joined_user_can_access_waiting_page_and_status_api()
    {
        // Enroll user first
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        $waitingResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.waiting', ['id' => $this->quiz->id]));
        $waitingResp->assertStatus(200);

        $statusResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.api.status', ['id' => $this->quiz->id]));
        $statusResp->assertStatus(200);
        $statusResp->assertJsonStructure(['success', 'status', 'server_now_ms']);
    }

    /**
     * Test 4: Questions API returns valid questions for enrolled user.
     */
    public function test_questions_api_returns_quiz_questions()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actAsFsrUser()->get(route('fsrapp.quizes.api.questions', ['id' => $this->quiz->id]));
        $response->assertStatus(200);
        $data = $response->json();

        $this->assertNotEmpty($data['questions'] ?? $data['data'] ?? $data);
    }

    /**
     * Test 5: Submitting answers:
     * - Before start time: rejected with 422
     * - During active play: accepted with 200
     */
    public function test_submitting_answer_timing_rules()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        // 1. Submit before start time -> Expect 422
        $prematureResp = $this->actAsFsrUser()->post(route('fsrapp.quizes.api.submit-answer', ['id' => $this->quiz->id]), [
            'question_id' => $this->questions[0]->id,
            'selected_option' => 'option_a',
            'time_taken' => 5.2,
        ]);
        $prematureResp->assertStatus(422);

        // 2. Fast forward time so quiz is actively running
        $this->quiz->update([
            'scheduled_at' => Carbon::now()->subSeconds(20),
            'availability_start' => Carbon::now()->subSeconds(20),
        ]);
        Cache::forget("quiz_model_{$this->quiz->id}");

        $activeResp = $this->actAsFsrUser()->post(route('fsrapp.quizes.api.submit-answer', ['id' => $this->quiz->id]), [
            'question_id' => $this->questions[0]->id,
            'selected_option' => 'option_a',
            'time_taken' => 5.2,
        ]);
        $activeResp->assertStatus(200);
    }

    /**
     * Test 6: Answer sheet view rules:
     * - Before quiz completion: redirected to home (302)
     * - After quiz answer time finished: renders 200
     */
    public function test_answer_sheet_endpoint()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        // Before quiz finishes: must redirect to home
        $blockedResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.answer-sheet', ['id' => $this->quiz->id]));
        $blockedResp->assertStatus(302);
        $blockedResp->assertRedirect(route('fsrapp.home'));

        // After quiz answer time finishes / completed
        $this->quiz->update([
            'complete_status' => 'completed',
        ]);
        $allowedResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.answer-sheet', ['id' => $this->quiz->id]));
        $allowedResp->assertStatus(200);
    }

    /**
     * Test 7: Review counter and Final counter views.
     */
    public function test_counter_views_render()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        $revResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.review-counter', ['id' => $this->quiz->id]));
        $revResp->assertStatus(200);

        $finalResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.final-counter', ['id' => $this->quiz->id]));
        $finalResp->assertStatus(200);
    }

    /**
     * Test 8: Leaderboard view and API.
     */
    public function test_leaderboard_endpoints()
    {
        QuizUser::create([
            'tenant_id' => 1,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
        ]);

        $viewResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.leaderboard', ['id' => $this->quiz->id]));
        $viewResp->assertStatus(200);

        $apiResp = $this->actAsFsrUser()->get(route('fsrapp.quizes.api.leaderboard', ['id' => $this->quiz->id]));
        $apiResp->assertStatus(200);
    }
}
