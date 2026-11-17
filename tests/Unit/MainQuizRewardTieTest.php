<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizRankReward;
use App\Models\QuizWinner;
use App\Repositories\api\QuizRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MainQuizRewardTieTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
    }

    protected function tearDown(): void
    {
        Quiz::where('title', 'Tied Reward Test Quiz')->delete();
        \App\Models\Question::where('question_text', 'Sample Q1')->delete();
        User::whereIn('mobile', ['9876543210', '9876543211', '9876543212', '9876543213'])->delete();
        parent::tearDown();
    }

    public function test_tied_users_receive_equal_reward_points_summed_from_tied_ranks()
    {
        // 1. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Tied Reward Test Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'visibility' => 'public',
            'question_limit' => 2,
        ]);

        // 2. Create Rank Rewards (Rank 1: 100 points, Rank 2: 60 points, Rank 3: 40 points, Rank 4: 20 points)
        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 1,
            'end_rank' => 1,
            'type' => 'point',
            'reward' => 100,
        ]);

        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 2,
            'end_rank' => 2,
            'type' => 'point',
            'reward' => 60,
        ]);

        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 3,
            'end_rank' => 3,
            'type' => 'point',
            'reward' => 40,
        ]);

        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 4,
            'end_rank' => 4,
            'type' => 'point',
            'reward' => 20,
        ]);

        // 3. Create Master Question & QuizQuestion
        $masterQuestion = \App\Models\Question::create([
            'tenant_id' => 1,
            'question_text' => 'Sample Q1',
            'correct_answer' => 'A',
            'options' => json_encode(['A', 'B', 'C', 'D']),
            'status' => 'published',
        ]);

        $q1 = QuizQuestion::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $masterQuestion->id,
            'order' => 1,
            'question_text' => 'Sample Q1',
            'correct_answer' => 'A',
        ]);

        // 4. Create Users
        $pass = bcrypt('password');
        $user1 = User::create(['tenant_id' => 1, 'mobile' => '9876543210', 'name' => 'User 1 (Rank 1 Solo)', 'password' => $pass, 'wallet_balance' => 0]);
        $user2 = User::create(['tenant_id' => 1, 'mobile' => '9876543211', 'name' => 'User 2 (Tied for Rank 2 & 3)', 'password' => $pass, 'wallet_balance' => 0]);
        $user3 = User::create(['tenant_id' => 1, 'mobile' => '9876543212', 'name' => 'User 3 (Tied for Rank 2 & 3)', 'password' => $pass, 'wallet_balance' => 0]);
        $user4 = User::create(['tenant_id' => 1, 'mobile' => '9876543213', 'name' => 'User 4 (Rank 4 Solo)', 'password' => $pass, 'wallet_balance' => 0]);

        foreach ([$user1, $user2, $user3, $user4] as $u) {
            \App\Models\QuizUser::create([
                'tenant_id' => 1,
                'quiz_id' => $quiz->id,
                'user_id' => $u->id,
                'status' => 'active',
            ]);
        }

        // User 1: 1 correct answer, 5 seconds total (Rank 1)
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'user_id' => $user1->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 5.0,
        ]);

        // User 2 & User 3: TIED -> 1 correct answer, 10 seconds total (Occupies Rank 2 and Rank 3)
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'user_id' => $user2->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 10.0,
        ]);

        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'user_id' => $user3->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 10.0,
        ]);

        // User 4: 1 correct answer, 15 seconds total (Rank 4)
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => $q1->id,
            'user_id' => $user4->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 15.0,
        ]);

        // Execute calculateUser
        $quizRepo = app(QuizRepository::class);
        $result = $quizRepo->calculateUser($quiz);

        $this->assertTrue($result);

        // Check Winner Records
        $w1 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $user1->id)->first();
        $w2 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $user2->id)->first();
        $w3 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $user3->id)->first();
        $w4 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $user4->id)->first();

        // User 1 gets Rank 1 reward (100)
        $this->assertEquals(100, (float) $w1->reward);
        $this->assertEquals(100, (float) $user1->fresh()->wallet_balance);

        // Tied ranks occupied by User 2 and User 3: Rank 2 (60 points) + Rank 3 (40 points) = 100 points
        // Distributed equally among 2 tied users: 100 / 2 = 50 points each!
        $this->assertEquals(50, (float) $w2->reward);
        $this->assertEquals(50, (float) $w3->reward);
        $this->assertEquals(50, (float) $user2->fresh()->wallet_balance);
        $this->assertEquals(50, (float) $user3->fresh()->wallet_balance);

        // User 4 gets Rank 4 reward (20)
        $this->assertEquals(20, (float) $w4->reward);
        $this->assertEquals(20, (float) $user4->fresh()->wallet_balance);
    }

    public function test_three_way_tie_distributes_combined_reward_points_equally()
    {
        // 1. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Tied Reward Test Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'visibility' => 'public',
            'question_limit' => 2,
        ]);

        // Rank 1: 100, Rank 2: 60, Rank 3: 40 -> Sum for 3 tied users = 200 -> 200 / 3 = 66.67 each
        QuizRankReward::create(['tenant_id' => 1, 'quiz_id' => $quiz->id, 'start_rank' => 1, 'end_rank' => 1, 'type' => 'point', 'reward' => 100]);
        QuizRankReward::create(['tenant_id' => 1, 'quiz_id' => $quiz->id, 'start_rank' => 2, 'end_rank' => 2, 'type' => 'point', 'reward' => 60]);
        QuizRankReward::create(['tenant_id' => 1, 'quiz_id' => $quiz->id, 'start_rank' => 3, 'end_rank' => 3, 'type' => 'point', 'reward' => 40]);

        $masterQ = \App\Models\Question::create(['tenant_id' => 1, 'question_text' => 'Sample Q1', 'correct_answer' => 'A', 'options' => json_encode(['A']), 'status' => 'published']);
        $q1 = QuizQuestion::create(['tenant_id' => 1, 'quiz_id' => $quiz->id, 'question_id' => $masterQ->id, 'order' => 1, 'question_text' => 'Sample Q1', 'correct_answer' => 'A']);

        $pass = bcrypt('password');
        $u1 = User::create(['tenant_id' => 1, 'mobile' => '9876543210', 'name' => 'Tied 1', 'password' => $pass, 'wallet_balance' => 0]);
        $u2 = User::create(['tenant_id' => 1, 'mobile' => '9876543211', 'name' => 'Tied 2', 'password' => $pass, 'wallet_balance' => 0]);
        $u3 = User::create(['tenant_id' => 1, 'mobile' => '9876543212', 'name' => 'Tied 3', 'password' => $pass, 'wallet_balance' => 0]);

        foreach ([$u1, $u2, $u3] as $user) {
            \App\Models\QuizUser::create([
                'tenant_id' => 1,
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'status' => 'active',
            ]);
            QuizQuestionUserAnswer::create([
                'tenant_id' => 1,
                'quiz_id' => $quiz->id,
                'question_id' => $q1->id,
                'user_id' => $user->id,
                'answer' => 'A',
                'status' => 1,
                'total_time' => 8.0,
            ]);
        }

        $quizRepo = app(QuizRepository::class);
        $result = $quizRepo->calculateUser($quiz);

        $this->assertTrue($result);

        $w1 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $u1->id)->first();
        $w2 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $u2->id)->first();
        $w3 = QuizWinner::where('quiz_id', $quiz->id)->where('user_id', $u3->id)->first();

        // 200 / 3 = 66.67
        $this->assertEquals(66.67, (float) $w1->reward);
        $this->assertEquals(66.67, (float) $w2->reward);
        $this->assertEquals(66.67, (float) $w3->reward);

        $this->assertEquals(66.67, (float) $u1->fresh()->wallet_balance);
        $this->assertEquals(66.67, (float) $u2->fresh()->wallet_balance);
        $this->assertEquals(66.67, (float) $u3->fresh()->wallet_balance);
    }
}
