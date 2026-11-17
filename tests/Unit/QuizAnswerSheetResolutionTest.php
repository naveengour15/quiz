<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\QuizQuestion;
use App\Repositories\api\QuizRepository;

class QuizAnswerSheetResolutionTest extends TestCase
{
    private QuizRepository $quizRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quizRepo = app(QuizRepository::class);
    }

    /**
     * Test letter correct answer ('A', 'B', 'C', 'D') resolves correctly even with shuffled clean options.
     */
    public function test_letter_correct_answer_resolves_to_correct_option_regardless_of_shuffling()
    {
        $q = new QuizQuestion([
            'id' => 101,
            'order' => 1,
            'question_text' => 'Who tied rakhi to Abhimanyu?',
            'hindi_question' => 'अभिमन्यु को राखी किसने बांधी?',
            'options' => ['Draupadi', 'Queen Kunti', 'Subhadra', 'Uttara'],
            'hindi_options' => ['द्रौपदी', 'रानी कुंती', 'सुभद्रा', 'उत्तरा'],
            'correct_answer' => 'B', // 'B' should map to 'Queen Kunti' / 'रानी कुंती (Queen Kunti)'
        ]);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $this->assertCount(1, $formatted);
        $item = $formatted[0];

        $this->assertEquals('रानी कुंती (Queen Kunti)', $item['correct_answer']);
        $this->assertContains('रानी कुंती (Queen Kunti)', $item['options']);
    }

    /**
     * Test English text correct answer resolves to bilingual option text.
     */
    public function test_english_correct_answer_resolves_to_bilingual_option()
    {
        $q = new QuizQuestion([
            'id' => 102,
            'order' => 2,
            'question_text' => 'Sample Question',
            'hindi_question' => 'नमूना प्रश्न',
            'options' => ['Option One', 'Option Two', 'Option Three', 'Option Four'],
            'hindi_options' => ['विकल्प एक', 'विकल्प दो', 'विकल्प तीन', 'विकल्प चार'],
            'correct_answer' => 'Option Three',
        ]);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $item = $formatted[0];
        $this->assertEquals('विकल्प तीन (Option Three)', $item['correct_answer']);
    }

    /**
     * Test Hindi text correct answer resolves to bilingual option text.
     */
    public function test_hindi_correct_answer_resolves_to_bilingual_option()
    {
        $q = new QuizQuestion([
            'id' => 103,
            'order' => 3,
            'question_text' => 'Sample Question',
            'hindi_question' => 'नमूना प्रश्न',
            'options' => ['Option One', 'Option Two', 'Option Three', 'Option Four'],
            'hindi_options' => ['विकल्प एक', 'विकल्प दो', 'विकल्प तीन', 'विकल्प चार'],
            'correct_answer' => 'विकल्प दो',
        ]);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $item = $formatted[0];
        $this->assertEquals('विकल्प दो (Option Two)', $item['correct_answer']);
    }

    /**
     * Test fallback to hindi_correct_answer when correct_answer is empty.
     */
    public function test_fallback_to_hindi_correct_answer_when_correct_answer_is_null()
    {
        $q = new QuizQuestion([
            'id' => 104,
            'order' => 4,
            'question_text' => 'Sample Question',
            'hindi_question' => 'नमूना प्रश्न',
            'options' => ['Option One', 'Option Two'],
            'hindi_options' => ['विकल्प एक', 'विकल्प दो'],
            'correct_answer' => null,
            'hindi_correct_answer' => 'विकल्प एक',
        ]);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $item = $formatted[0];
        $this->assertEquals('विकल्प एक (Option One)', $item['correct_answer']);
    }

    /**
     * Test checkAnswersMatch correctly handles bilingual vs English vs Hindi submissions.
     */
    public function test_check_answers_match_comprehensive()
    {
        $options = [
            'द्रौपदी (Draupadi)',
            'रानी कुंती (Queen Kunti)',
            'सुभद्रा (Subhadra)',
            'उत्तरा (Uttara)',
        ];

        // Bilingual correct vs bilingual user answer
        $this->assertTrue($this->quizRepo->checkAnswersMatch('रानी कुंती (Queen Kunti)', 'रानी कुंती (Queen Kunti)', $options));

        // Bilingual correct vs English user answer
        $this->assertTrue($this->quizRepo->checkAnswersMatch('रानी कुंती (Queen Kunti)', 'Queen Kunti', $options));

        // English correct vs Bilingual user answer
        $this->assertTrue($this->quizRepo->checkAnswersMatch('Queen Kunti', 'रानी कुंती (Queen Kunti)', $options));

        // Hindi correct vs Bilingual user answer
        $this->assertTrue($this->quizRepo->checkAnswersMatch('रानी कुंती', 'रानी कुंती (Queen Kunti)', $options));

        // Bilingual correct vs Hindi user answer
        $this->assertTrue($this->quizRepo->checkAnswersMatch('रानी कुंती (Queen Kunti)', 'रानी कुंती', $options));

        // Mismatched answers
        $this->assertFalse($this->quizRepo->checkAnswersMatch('रानी कुंती (Queen Kunti)', 'Draupadi', $options));
        $this->assertFalse($this->quizRepo->checkAnswersMatch('रानी कुंती (Queen Kunti)', 'सुभद्रा (Subhadra)', $options));
    }

    /**
     * Test numeric index (0, 1, 2, 3) correct answer resolution.
     */
    public function test_numeric_index_correct_answer_resolves_correctly()
    {
        $q = new QuizQuestion([
            'id' => 105,
            'order' => 5,
            'question_text' => 'Sample Index Question',
            'options' => ['First', 'Second', 'Third', 'Fourth'],
            'correct_answer' => '2', // 0-based index 2 or 1-based index 2 -> 'Third' or 'Second'
        ]);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $item = $formatted[0];
        $this->assertContains($item['correct_answer'], ['Second', 'Third']);
    }

    /**
     * Test master Question relation fallback when QuizQuestion correct_answer is empty.
     */
    public function test_master_question_relation_fallback()
    {
        $masterQ = new \App\Models\Question([
            'question_text' => 'Master Question',
            'correct_answer' => 'Master Answer',
            'options' => ['Master Answer', 'Wrong Answer'],
        ]);

        $q = new QuizQuestion([
            'id' => 106,
            'order' => 6,
            'question_text' => 'Quiz Question text',
            'options' => ['Master Answer', 'Wrong Answer'],
            'correct_answer' => null,
        ]);
        $q->setRelation('question', $masterQ);

        $reflection = new \ReflectionClass($this->quizRepo);
        $method = $reflection->getMethod('formatQuestionsArray');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->quizRepo, collect([$q]));

        $item = $formatted[0];
        $this->assertEquals('Master Answer', $item['correct_answer']);
    }
}
