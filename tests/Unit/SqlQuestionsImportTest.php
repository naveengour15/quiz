<?php

namespace Tests\Unit;

use App\Imports\SqlQuestionsImport;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SqlQuestionsImportTest extends TestCase
{
    #[Test]
    public function it_parses_both_question_types_and_multiple_rows_without_executing_sql(): void
    {
        $sql = <<<'SQL'
INSERT INTO `questions` (`tenant_id`, `question_text`, `type`, `options`, `correct_answer`, `hindi_question`, `hindi_options`, `hindi_correct_answer`) VALUES
(1, 'Which city is India''s capital?', 'mcq', '["Delhi","Mumbai","Jaipur","Chennai"]', 'Delhi', 'भारत की राजधानी कौन सा शहर है?', '["दिल्ली","मुंबई","जयपुर","चेन्नई"]', 'दिल्ली'),
(1, 'Which comes first?', 'fastest_finger_first', '["A","B"]', 'A', 'पहले कौन आता है?', '["अ","ब"]', 'अ');
SQL;

        $import = new SqlQuestionsImport(1);
        $rows = $import->parse($sql);

        $this->assertCount(2, $rows);
        $this->assertSame("Which city is India's capital?", $rows[0]['question_text']);
        $this->assertSame('fastest_finger_first', $rows[1]['type']);

        $mcq = $import->normalizeQuestionRow($rows[0]);
        $fastFinger = $import->normalizeQuestionRow($rows[1]);

        $this->assertSame('mcq', $mcq['type']);
        $this->assertCount(4, $mcq['options']);
        $this->assertSame(30, $mcq['time_limit_seconds']);
        $this->assertSame('fastest_finger_first', $fastFinger['type']);
        $this->assertCount(2, $fastFinger['options']);
        $this->assertSame(60, $fastFinger['time_limit_seconds']);
        $this->assertSame(1, $fastFinger['tenant_id']);
    }

    #[Test]
    public function it_rejects_non_question_sql_instead_of_executing_it(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only INSERT INTO questions');

        (new SqlQuestionsImport(1))->parse('DROP TABLE questions;');
    }

    #[Test]
    public function it_rejects_rows_with_the_wrong_number_of_options(): void
    {
        $import = new SqlQuestionsImport(1);
        $row = [
            'type' => 'fastest_finger_first',
            'question_text' => 'Question?',
            'options' => '["A","B","C"]',
            'correct_answer' => 'A',
            'hindi_question' => 'प्रश्न?',
            'hindi_options' => '["अ","ब"]',
            'hindi_correct_answer' => 'अ',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly 2 English options');

        $import->normalizeQuestionRow($row);
    }
}
