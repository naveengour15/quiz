<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizRankReward;
use App\Models\QuizWinner;
use App\Models\WalletHistory;
use App\Models\QuizStatus;
use App\Repositories\api\QuizWinnerRepository;
use App\Interfaces\ActionTrailRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class QuizZeroCorrectWinnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        // Create schemas in memory sqlite
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('password')->nullable();
            $table->string('profile_photo')->nullable();
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->decimal('leader_board_point', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('title');
            $table->string('status')->default('published');
            $table->string('approve_status')->default('approved');
            $table->string('complete_status')->default('pending');
            $table->string('visibility')->default('public');
            $table->integer('question_limit')->default(10);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('availability_end')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_question_user_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('question_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->string('answer')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('ans_time')->nullable();
            $table->decimal('total_time', 10, 3)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('total_question')->default(0);
            $table->integer('total_correct_answer')->default(0);
            $table->decimal('total_time', 10, 3)->default(0);
            $table->decimal('per_ans_average_time', 10, 3)->default(0);
            $table->integer('rank')->default(0);
            $table->string('level')->nullable();
            $table->string('reward_type')->default('point');
            $table->decimal('reward', 12, 2)->default(0);
            $table->unsignedBigInteger('reward_id')->nullable();
            $table->unsignedBigInteger('voucher_history_id')->nullable();
            $table->string('reward_detail')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_rank_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->integer('start_rank')->default(1);
            $table->integer('end_rank')->default(1);
            $table->decimal('reward', 12, 2)->default(0);
            $table->string('type')->default('point');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->json('voucher_histories_id')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('question_id')->default(1);
            $table->boolean('is_switch_question')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('wallet_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('old_wallet_balance', 12, 2)->default(0);
            $table->decimal('new_wallet_balance', 12, 2)->default(0);
            $table->string('type')->default('credit');
            $table->string('transaction_type')->default('quiz_reward');
            $table->string('reference_id')->nullable();
            $table->string('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('leader_point_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->decimal('old_point', 12, 2)->default(0);
            $table->decimal('point', 12, 2)->default(0);
            $table->decimal('new_point', 12, 2)->default(0);
            $table->string('type')->default('credit');
            $table->string('remark')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_enroll_user')->default(0);
            $table->unsignedInteger('total_played_user')->default(0);
            $table->unsignedInteger('total_complete_quiz_user')->default(0);
            $table->unsignedInteger('total_correct_ans')->default(0);
            $table->unsignedInteger('total_intrested_user')->default(0);
            $table->decimal('correct_answer_average_time', 10, 3)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_cron_job_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('quiz_id');
            $table->string('cron_type')->default('quiz_winner_leaderboard');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function test_user_with_one_wrong_answer_is_included_in_quiz_winners_and_gets_reward_and_wallet_update()
    {
        // 1. Create User
        $userA = User::create([
            'tenant_id' => 1,
            'name' => 'User A (1 Correct, 10s)',
            'mobile' => '9990000001',
            'wallet_balance' => 0,
            'leader_board_point' => 0,
        ]);

        $userB = User::create([
            'tenant_id' => 1,
            'name' => 'User B (0 Correct, 1 Answer Submitted, 5s)',
            'mobile' => '9990000002',
            'wallet_balance' => 10,
            'leader_board_point' => 0,
        ]);

        // 2. Create Quiz
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Test 1 Answer Submission Quiz',
            'status' => 'published',
            'approve_status' => 'approved',
            'complete_status' => 'processing',
            'visibility' => 'public',
            'question_limit' => 1,
            'scheduled_at' => now(),
        ]);

        // 3. Create Rank Rewards: Rank 1 -> 50 points, Rank 2 -> 20 points
        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 1,
            'end_rank' => 1,
            'reward' => 50,
            'type' => 'point',
            'status' => 'active',
        ]);

        QuizRankReward::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'start_rank' => 2,
            'end_rank' => 2,
            'reward' => 20,
            'type' => 'point',
            'status' => 'active',
        ]);

        // 4. Create User Answers
        // User A answered 1 question correctly in 10s
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => 1,
            'user_id' => $userA->id,
            'answer' => 'A',
            'status' => 1,
            'total_time' => 10.0,
        ]);

        // User B submitted 1 answer (wrong, 0 correct) in 5s
        QuizQuestionUserAnswer::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'question_id' => 1,
            'user_id' => $userB->id,
            'answer' => 'B',
            'status' => 0,
            'total_time' => 5.0,
        ]);

        // 5. Run QuizWinnerRepository::quizUpdatePlayData
        $mockActionTrail = $this->createMock(ActionTrailRepositoryInterface::class);
        $mockActionTrail->method('create')->willReturn((object)['id' => 1]);
        $winnerRepo = new QuizWinnerRepository($mockActionTrail);

        $stats = $winnerRepo->calculateQuizQuestionUserAnswer($quiz->id, 1);
        $this->assertNotEmpty($stats, 'Stats should not be empty');

        $result = $winnerRepo->quizUpdatePlayData($quiz);
        $this->assertTrue($result['success'] ?? false, $result['message'] ?? 'Failed');

        // 6. Assert both users have entries in quiz_winners
        $winners = QuizWinner::where('quiz_id', $quiz->id)->orderBy('rank', 'asc')->get();
        $this->assertCount(2, $winners);

        // User A should be Rank 1 with 1 correct answer and 50 points reward
        $winnerA = $winners->firstWhere('user_id', $userA->id);
        $this->assertNotNull($winnerA);
        $this->assertEquals(1, $winnerA->rank);
        $this->assertEquals(1, $winnerA->total_correct_answer);
        $this->assertEquals(50, (float) $winnerA->reward);

        // User B should be Rank 2 with 0 correct answer and 20 points reward
        $winnerB = $winners->firstWhere('user_id', $userB->id);
        $this->assertNotNull($winnerB);
        $this->assertEquals(2, $winnerB->rank);
        $this->assertEquals(0, $winnerB->total_correct_answer);
        $this->assertEquals(1, $winnerB->total_question);
        $this->assertEquals(20, (float) $winnerB->reward);

        // 7. Assert users.wallet_balance is updated
        $userA->refresh();
        $userB->refresh();
        $this->assertEquals(50, (float) $userA->wallet_balance, 'User A wallet balance should be 50');
        $this->assertEquals(30, (float) $userB->wallet_balance, 'User B wallet balance should be 10 + 20 = 30');

        // 8. Assert WalletHistory entries exist for both users
        $historyA = WalletHistory::where('user_id', $userA->id)->where('quiz_id', $quiz->id)->first();
        $this->assertNotNull($historyA);
        $this->assertEquals(50, (float) $historyA->amount);

        $historyB = WalletHistory::where('user_id', $userB->id)->where('quiz_id', $quiz->id)->first();
        $this->assertNotNull($historyB);
        $this->assertEquals(20, (float) $historyB->amount);
    }

    public function test_recalculate_ranks_preserves_and_ranks_zero_correct_participants()
    {
        $quiz = Quiz::create([
            'tenant_id' => 1,
            'title' => 'Recalculate Ranks Test Quiz',
            'status' => 'published',
            'scheduled_at' => now(),
        ]);

        $user1 = User::create(['tenant_id' => 1, 'name' => 'User 1 (2 correct, 10s)']);
        $user2 = User::create(['tenant_id' => 1, 'name' => 'User 2 (0 correct, 8s)']);
        $user3 = User::create(['tenant_id' => 1, 'name' => 'User 3 (0 correct, 12s)']);

        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $user1->id,
            'total_question' => 2,
            'total_correct_answer' => 2,
            'total_time' => 10.0,
            'rank' => 1,
            'status' => 'active',
        ]);

        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $user2->id,
            'total_question' => 2,
            'total_correct_answer' => 0,
            'total_time' => 8.0,
            'rank' => 1,
            'status' => 'active',
        ]);

        QuizWinner::create([
            'tenant_id' => 1,
            'quiz_id' => $quiz->id,
            'user_id' => $user3->id,
            'total_question' => 2,
            'total_correct_answer' => 0,
            'total_time' => 12.0,
            'rank' => 1,
            'status' => 'active',
        ]);

        QuizWinner::recalculateRanks($quiz->id);

        $winners = QuizWinner::where('quiz_id', $quiz->id)->orderBy('rank', 'asc')->get();
        $this->assertCount(3, $winners);

        // User 1 (2 correct) -> Rank 1
        $this->assertEquals($user1->id, $winners[0]->user_id);
        $this->assertEquals(1, $winners[0]->rank);

        // User 2 (0 correct, 8s) -> Rank 2 (faster than User 3)
        $this->assertEquals($user2->id, $winners[1]->user_id);
        $this->assertEquals(2, $winners[1]->rank);

        // User 3 (0 correct, 12s) -> Rank 3
        $this->assertEquals($user3->id, $winners[2]->user_id);
        $this->assertEquals(3, $winners[2]->rank);
    }
}
