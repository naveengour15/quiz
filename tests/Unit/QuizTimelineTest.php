<?php

namespace Tests\Unit;

use App\Services\QuizTimeline;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class QuizTimelineTest extends TestCase
{
    public function test_question_index_is_derived_from_the_scheduled_slot(): void
    {
        $scheduledAt = Carbon::parse('2026-09-28 10:00:00');

        $this->assertNull(QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:09'),
            10
        ));
        $this->assertSame(0, QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:10'),
            10
        ));
        $this->assertSame(1, QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:27'),
            10
        ));
        $this->assertNull(QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:00'),
            10
        ));
    }

    public function test_answer_sheet_release_includes_only_scheduled_question_slots_and_review(): void
    {
        $scheduledAt = Carbon::parse('2026-09-28 10:00:00');

        $this->assertSame(
            '2026-09-28 10:03:27',
            QuizTimeline::answerSheetAt($scheduledAt, 11)->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2026-09-28 10:03:44',
            QuizTimeline::answerSheetAt($scheduledAt, 12)->format('Y-m-d H:i:s')
        );
        $this->assertSame(10, QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:00'),
            11
        ));
        $this->assertNull(QuizTimeline::currentQuestionIndex(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:17'),
            11
        ));
        $this->assertSame('2026-09-28 10:00:00', $scheduledAt->format('Y-m-d H:i:s'));
    }

    public function test_answer_sheet_countdown_tracks_remaining_question_slots(): void
    {
        $scheduledAt = Carbon::parse('2026-09-28 10:00:00');
        $questionStart = QuizTimeline::questionStartAt($scheduledAt);
        $releaseAt = QuizTimeline::answerSheetAt($scheduledAt, 12);

        $afterTenAnswers = $questionStart->copy()->addSeconds((10 * QuizTimeline::QUESTION_SLOT_SECONDS) - 3);
        $afterElevenAnswers = $questionStart->copy()->addSeconds((11 * QuizTimeline::QUESTION_SLOT_SECONDS) - 3);

        $this->assertSame(47, $releaseAt->getTimestamp() - $afterTenAnswers->getTimestamp());
        $this->assertSame(30, $releaseAt->getTimestamp() - $afterElevenAnswers->getTimestamp());
    }

    public function test_main_quiz_answer_sheet_countdown_matches_question_count(): void
    {
        $this->assertSame(47, QuizTimeline::answerSheetCountdownSeconds(10));
        $this->assertSame(30, QuizTimeline::answerSheetCountdownSeconds(11));
    }

    public function test_answer_submission_allows_a_short_network_grace_after_the_question_slot(): void
    {
        $scheduledAt = Carbon::parse('2026-09-28 10:00:00');

        $this->assertTrue(QuizTimeline::isQuestionAnswerable(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:27'),
            2,
            0
        ));
        $this->assertTrue(QuizTimeline::isQuestionAnswerable(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:30'),
            2,
            0
        ));
        $this->assertFalse(QuizTimeline::isQuestionAnswerable(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:30.001'),
            2,
            0
        ));
        $this->assertFalse(QuizTimeline::isQuestionAnswerable(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:09'),
            2,
            0
        ));
    }

    public function test_answer_submission_window_matches_the_client_randomized_question_order(): void
    {
        $scheduledAt = Carbon::parse('2026-09-28 10:00:00');

        $this->assertTrue(QuizTimeline::isAnswerSubmissionWindowOpen(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:15'),
            10
        ));
        $this->assertTrue(QuizTimeline::isAnswerSubmissionWindowOpen(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:00'),
            10
        ));
        $this->assertTrue(QuizTimeline::isAnswerSubmissionWindowOpen(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:03'),
            10
        ));
        $this->assertFalse(QuizTimeline::isAnswerSubmissionWindowOpen(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:00:09'),
            10
        ));
        $this->assertFalse(QuizTimeline::isAnswerSubmissionWindowOpen(
            $scheduledAt,
            Carbon::parse('2026-09-28 10:03:03.001'),
            10
        ));
    }
}
