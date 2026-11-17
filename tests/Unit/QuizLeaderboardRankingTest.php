<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizWinner;
use App\Repositories\api\QuizRepository;
use App\Http\Controllers\fsrapp\QuizesController;
use App\Http\Controllers\fsrapp\HistoryController;
use Illuminate\Support\Facades\DB;

class QuizLeaderboardRankingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'fsr_quiz',
        ]);
        DB::purge('mysql');
        Quiz::where('title', 'Leaderboard Ranking Test Quiz')->delete();
        User::whereIn('mobile', ['9991110001', '9991110002', '9991110003', '9991110004'])->delete();
    }

    protected function tearDown(): void
    {
        Quiz::where('title', 'Leaderboard Ranking Test Quiz')->delete();
        User::whereIn('mobile', ['9991110001', '9991110002', '9991110003', '9991110004'])->delete();
        parent::tearDown();
    }

    public function test_correct_answers_strictly_prioritized_over_total_time()
    {
        // 1. Create 4 Users
        $pass = bcrypt('password');
        $userB = User::create(['tenant_id' => 1, 'mobile' => '9991110001', 'name' => 'User B (7 correct, 80.125s)', 'password' => $pass]);
        $userC = User::create(['tenant_id' => 1, 'mobile' => '9991110002', 'name' => 'User C (7 correct, 70.000s)', 'password' => $pass]);
        $userA = User::create(['tenant_id' => 1, 'mobile' => '9991110003', 'name' => 'User A (8 correct, 100.120s)', 'password' => $pass]);
        $userD = User::create(['tenant_id' => 1, 'mobile' => '9991110004', 'name' => 'User D (8 correct, 90.000s)', 'password' => $pass]);

        // 2. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Leaderboard Ranking Test Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'visibility' => 'public',
            'question_limit' => 10,
        ]);

        foreach ([$userA, $userB, $userC, $userD] as $u) {
            \App\Models\QuizUser::create([
                'tenant_id' => 1,
                'quiz_id' => $quiz->id,
                'user_id' => $u->id,
                'status' => 'active',
            ]);
        }

        // 3. Create Question master & QuizQuestions
        for ($i = 1; $i <= 10; $i++) {
            $q = Question::create([
                'tenant_id' => 1,
                'question_text' => "Rank Q{$i}",
                'correct_answer' => 'A',
                'options' => json_encode(['A', 'B', 'C', 'D']),
                'status' => 'published',
            ]);

            QuizQuestion::create([
                'tenant_id' => 1,
                'quiz_id' => $quiz->id,
                'question_id' => $q->id,
                'order' => $i,
                'question_text' => "Rank Q{$i}",
                'correct_answer' => 'A',
            ]);
        }

        // Helper to insert user answers
        $insertAnswers = function ($userId, $correctCount, $totalTime) use ($quiz) {
            $perQuestionTime = $totalTime / 10.0;
            for ($i = 1; $i <= 10; $i++) {
                QuizQuestionUserAnswer::create([
                    'tenant_id' => 1,
                    'quiz_id' => $quiz->id,
                    'question_id' => $i,
                    'user_id' => $userId,
                    'answer' => $i <= $correctCount ? 'A' : 'B',
                    'status' => $i <= $correctCount ? 1 : 0,
                    'total_time' => $perQuestionTime,
                ]);
            }
        };

        // User B finishes 1st (7 correct, 80.125s)
        $insertAnswers($userB->id, 7, 80.125);
        // User C finishes 2nd (7 correct, 70.000s)
        $insertAnswers($userC->id, 7, 70.000);
        // User A finishes 3rd (8 correct, 100.120s)
        $insertAnswers($userA->id, 8, 100.120);
        // User D finishes 4th (8 correct, 90.000s)
        $insertAnswers($userD->id, 8, 90.000);

        // 4. Calculate Quiz Winners using QuizRepository
        $quizRepo = app(QuizRepository::class);
        $quizRepo->quizResult($quiz->id);

        // Fetch winners from QuizWinner table
        $winners = QuizWinner::where('quiz_id', $quiz->id)
            ->orderBy('rank', 'asc')
            ->get();

        $this->assertCount(4, $winners);

        // Assert ranking order:
        // Rank 1: User D (8 correct, 90s)
        // Rank 2: User A (8 correct, 100.120s)
        // Rank 3: User C (7 correct, 70s)
        // Rank 4: User B (7 correct, 80.125s)
        $this->assertEquals($userD->id, $winners[0]->user_id, 'User D (8 correct, 90s) should be Rank 1');
        $this->assertEquals(1, (int)$winners[0]->rank);

        $this->assertEquals($userA->id, $winners[1]->user_id, 'User A (8 correct, 100.120s) should be Rank 2 (above 7-correct users)');
        $this->assertEquals(2, (int)$winners[1]->rank);

        $this->assertEquals($userC->id, $winners[2]->user_id, 'User C (7 correct, 70s) should be Rank 3');
        $this->assertEquals(3, (int)$winners[2]->rank);

        $this->assertEquals($userB->id, $winners[3]->user_id, 'User B (7 correct, 80.125s) should be Rank 4');
        $this->assertEquals(4, (int)$winners[3]->rank);
    }

    public function test_get_leaderboard_data_orders_8_correct_above_7_correct_even_with_prepopulated_ranks()
    {
        // 1. Create Users
        $pass = bcrypt('password');
        $userB = User::create(['tenant_id' => 1, 'mobile' => '9991110001', 'name' => 'User B (7 correct, 80.125s)', 'password' => $pass]);
        $userA = User::create(['tenant_id' => 1, 'mobile' => '9991110003', 'name' => 'User A (8 correct, 100.120s)', 'password' => $pass]);

        // 2. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Leaderboard Ranking Test Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'pending',
            'visibility' => 'public',
            'question_limit' => 10,
        ]);

        // 3. Insert User B first with 7 correct (80.125s) and pre-populate QuizWinner with rank 1
        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $userB->id,
            'total_question' => 10,
            'total_correct_answer' => 7,
            'total_time' => 80.125,
            'per_ans_average_time' => 8.0125,
            'rank' => 1,
            'reward' => 70,
            'status' => 'active',
        ]);

        // 4. Insert User A second with 8 correct (100.120s) and pre-populate QuizWinner with rank 1 (simulating race condition)
        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $userA->id,
            'total_question' => 10,
            'total_correct_answer' => 8,
            'total_time' => 100.120,
            'per_ans_average_time' => 10.012,
            'rank' => 1,
            'reward' => 80,
            'status' => 'active',
        ]);

        // 5. Call QuizesController getLeaderboardData
        $controller = app(QuizesController::class);
        $response = $controller->getLeaderboardData($quiz->id);
        $json = $response->getData(true);

        $this->assertTrue($json['success']);
        $leaderboard = $json['data'];

        $this->assertCount(2, $leaderboard);
        // User A (8 correct, 100.120s) MUST BE FIRST in leaderboard response
        $this->assertEquals($userA->id, $leaderboard[0]['user_id'], 'User A (8 correct, 100.120s) MUST rank above User B (7 correct, 80.125s)');
        $this->assertEquals(1, (int)$leaderboard[0]['rank']);
        $this->assertEquals(8, (int)$leaderboard[0]['correct']);

        // User B (7 correct, 80.125s) MUST BE SECOND
        $this->assertEquals($userB->id, $leaderboard[1]['user_id'], 'User B (7 correct, 80.125s) MUST rank below User A');
        $this->assertEquals(2, (int)$leaderboard[1]['rank']);
        $this->assertEquals(7, (int)$leaderboard[1]['correct']);
    }
}
