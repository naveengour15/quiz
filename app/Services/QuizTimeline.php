<?php

namespace App\Services;

use Carbon\Carbon;

class QuizTimeline
{
    public const START_COUNTER_SECONDS = 10;

    public const QUESTION_SLOT_SECONDS = 17;

    public const ANSWER_SUBMISSION_GRACE_SECONDS = 3;

    public const REVIEW_COUNTER_SECONDS = 10;

    public const MAX_QUESTION_SLOTS = 12;

    public static function answerSheetCountdownSeconds(int $questionCount): int
    {
        $remainingSlots = max(0, self::MAX_QUESTION_SLOTS - max(0, $questionCount));

        return ($remainingSlots * self::QUESTION_SLOT_SECONDS)
            + self::REVIEW_COUNTER_SECONDS
            + self::ANSWER_SUBMISSION_GRACE_SECONDS;
    }

    public static function fromTimestampMilliseconds(int $timestampMs): Carbon
    {
        return Carbon::createFromTimestamp(intdiv($timestampMs, 1000))
            ->addMilliseconds($timestampMs % 1000);
    }

    public static function questionStartAt(Carbon $scheduledAt): Carbon
    {
        return $scheduledAt->copy()->addSeconds(self::START_COUNTER_SECONDS);
    }

    public static function questionEndAt(Carbon $scheduledAt, int $questionCount): Carbon
    {
        return self::questionStartAt($scheduledAt)
            ->addSeconds(max(0, $questionCount) * self::QUESTION_SLOT_SECONDS);
    }

    public static function answerSheetAt(Carbon $scheduledAt, int $questionCount): Carbon
    {
        return self::questionEndAt($scheduledAt, $questionCount)
            ->addSeconds(self::REVIEW_COUNTER_SECONDS);
    }

    public static function currentQuestionIndex(Carbon $scheduledAt, Carbon $now, int $questionCount): ?int
    {
        $questionStart = self::questionStartAt($scheduledAt);
        $questionEnd = self::questionEndAt($scheduledAt, $questionCount);

        if ($now->lt($questionStart) || $now->gte($questionEnd)) {
            return null;
        }

        return (int) floor($questionStart->diffInMilliseconds($now, false) / (self::QUESTION_SLOT_SECONDS * 1000));
    }

    public static function isQuestionAnswerable(
        Carbon $scheduledAt,
        Carbon $now,
        int $questionCount,
        int $questionIndex
    ): bool {
        if ($questionIndex < 0 || $questionIndex >= $questionCount) {
            return false;
        }

        if (self::currentQuestionIndex($scheduledAt, $now, $questionCount) === $questionIndex) {
            return true;
        }

        $questionEnd = self::questionStartAt($scheduledAt)
            ->addSeconds(($questionIndex + 1) * self::QUESTION_SLOT_SECONDS);

        return $now->gte($questionEnd)
            && $now->lte($questionEnd->copy()->addSeconds(self::ANSWER_SUBMISSION_GRACE_SECONDS));
    }

    public static function isAnswerSubmissionWindowOpen(
        Carbon $scheduledAt,
        Carbon $now,
        int $questionCount
    ): bool {
        if ($questionCount <= 0) {
            return false;
        }

        if (self::currentQuestionIndex($scheduledAt, $now, $questionCount) !== null) {
            return true;
        }

        return self::isQuestionAnswerable($scheduledAt, $now, $questionCount, $questionCount - 1);
    }
}
