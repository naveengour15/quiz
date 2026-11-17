<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach ([804, 802, 800, 799, 797, 796] as $qid) {
    $qu = \App\Models\QuizUser::where('quiz_id', $qid)->pluck('user_id')->toArray();
    $qw = \App\Models\QuizWinner::where('quiz_id', $qid)->pluck('user_id')->toArray();
    $qa = \App\Models\QuizQuestionUserAnswer::where('quiz_id', $qid)->distinct('user_id')->pluck('user_id')->toArray();
    $diff = array_diff($qa, $qu);
    echo "Quiz $qid: QuizUsers=" . count($qu) . ", Winners=" . count($qw) . ", AnswerUsers=" . count($qa) . ", InAnswersButNotInQuizUsers=[" . implode(',', $diff) . "]\n";
}
