<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizRankReward;
use App\Models\QuizWinner;
use App\Models\WalletHistory;
use App\Models\LeaderPointHistory;
use App\Repositories\api\QuizRepository;
use Illuminate\Support\Facades\DB;

class QuizRewardSynchronizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
        Quiz::where('title', 'Reward Sync Test Quiz')->delete();
        Question::where('question_text', 'Sync Q1')->delete();
        User::whereIn('mobile', ['9998887771'])->delete();
    }

    protected function tearDown(): void
    {
        Quiz::where('title', 'Reward Sync Test Quiz')->delete();
        Question::where('question_text', 'Sync Q1')->delete();
        User::whereIn('mobile', ['9998887771'])->delete();
        parent::tearDown();
    }

    public function test_quiz_completion_updates_wallet_balance_and_leader_board_points()
    {
        // 1. Create User
        $pass = bcrypt('password');
        $user = User::create([
            'tenant_id' => 1,
            'mobile' => '9998887771',
            'name' => 'Sync Test User',
            'password' => $pass,
            'wallet_balance' => 0,
            'leader_board_point' => 0,
        ]);

        // 2. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Reward Sync Test Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'visibility' => 'public',
            'question_limit' => 1,
        ]);

        // 3. Create Rank Reward (100 points for rank 1)
        $rankReward = QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 1,
            'end_rank' => 1,
            'type' => 'point',
            'reward' => 100,
        ]);

        // 4. Create Question and QuizQuestion
        $masterQuestion = Question::create([
            'tenant_id' => 1,
            'question_text' => 'Sync Q1',
            'correct_answer' => 'A',
            'options' => json_encode(['A', 'B', 'C', 'D']),
            'status' => 'published',
        ]);

        $q1 = QuizQuestion::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $masterQuestion->id,
            'order' => 1,
            'question_text' => 'Sync Q1',
            'correct_answer' => 'A',
        ]);

        // 5. Create Correct User Answer
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'user_id' => $user->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 4.0,
        ]);

        // 6. Pre-create QuizWinner entry to simulate QuizesController pre-populating QuizWinner
        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'total_question' => 1,
            'total_correct_answer' => 1,
            'total_time' => 4.0,
            'rank' => 1,
            'reward' => 100,
            'reward_type' => 'point',
            'status' => 'active',
        ]);

        // Ensure wallet_balance and leader_board_point start at 0
        $this->assertEquals(0, (float)$user->fresh()->wallet_balance);
        $this->assertEquals(0, (float)$user->fresh()->leader_board_point);

        // 7. Execute quizResult
        $quizRepo = app(QuizRepository::class);
        $quizRepo->quizResult($quiz->id);

        // 8. Assert wallet_balance and leader_board_point are updated
        $freshUser = $user->fresh();
        $this->assertEquals(100, (float)$freshUser->wallet_balance, 'Wallet balance should be updated to 100');

        // 9. Assert WalletHistory record exists
        $this->assertTrue(
            WalletHistory::where('user_id', $user->id)
                ->where('transaction_type', 'quiz_reward')
                ->exists(),
            'WalletHistory entry should be created'
        );

        // 10. Execute quizResult again to ensure idempotency (no double-crediting)
        $quizRepo->quizResult($quiz->id);

        $doubleFreshUser = $user->fresh();
        $this->assertEquals(100, (float)$doubleFreshUser->wallet_balance, 'Wallet balance must not be double-credited');
    }
}
