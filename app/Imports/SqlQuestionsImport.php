<?php

namespace App\Imports;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Exception;
use InvalidArgumentException;

class SqlQuestionsImport
{
    private const ALLOWED_COLUMNS = [
        'tenant_id',
        'question_text',
        'type',
        'options',
        'correct_answer',
        'hindi_question',
        'hindi_options',
        'hindi_correct_answer',
        'sub_category',
        'difficulty',
        'language',
        'time_limit_seconds',
        'xp_value',
        'negative_marking',
        'status',
        'tags',
        'source',
        'moderation_status',
    ];

    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function __construct(private readonly int $tenantId)
    {
    }

    public function import(string $sql): void
    {
        $rows = $this->parse($sql);

        if ($rows === []) {
            throw new InvalidArgumentException('No INSERT INTO questions ... VALUES statements were found.');
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                Question::create($this->normalizeQuestionRow($row));
                $this->imported++;
            } catch (Exception $exception) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: {$exception->getMessage()}";

                Log::warning('SQL question import row skipped', [
                    'row_number' => $rowNumber,
                    'error_message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Parse question INSERT statements without executing uploaded SQL.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $sql): array
    {
        $sql = $this->stripComments($sql);
        $rows = [];

        foreach ($this->splitStatements($sql) as $statementNumber => $statement) {
            if (trim($statement) === '') {
                continue;
            }

            if (!preg_match(
                '/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+(?:(?:`?[A-Za-z0-9_]+`?)\s*\.\s*)?`?questions`?\s*\((.*?)\)\s*VALUES\s*(.*?)\s*$/is',
                $statement,
                $matches
            )) {
                throw new InvalidArgumentException(
                    'Only INSERT INTO questions ... VALUES statements are supported (statement '.($statementNumber + 1).').'
                );
            }

            $columns = array_map(
                static fn (string $column): string => strtolower(trim($column, " \t\n\r\0\x0B`")),
                $this->splitTopLevel(rtrim(trim($matches[1]), ','))
            );

            if ($columns === [] || in_array('', $columns, true)) {
                throw new InvalidArgumentException('The questions INSERT statement has an invalid column list.');
            }

            if (count($columns) !== count(array_unique($columns))) {
                throw new InvalidArgumentException('The questions INSERT statement contains duplicate columns.');
            }

            foreach ($columns as $column) {
                if (!in_array($column, self::ALLOWED_COLUMNS, true)) {
                    throw new InvalidArgumentException("Unsupported questions column: {$column}.");
                }
            }

            foreach ($this->parseTuples($matches[2]) as $tuple) {
                $values = $this->splitTopLevel($tuple);

                if (count($values) !== count($columns)) {
                    throw new InvalidArgumentException('The number of values does not match the questions column list.');
                }

                $row = [];
                foreach ($columns as $index => $column) {
                    $row[$column] = $this->parseLiteral($values[$index]);
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function normalizeQuestionRow(array $row): array
    {
        $options = $this->decodeOptions($row['options'] ?? null, 'options');
        $hindiOptions = $this->decodeOptions($row['hindi_options'] ?? null, 'hindi_options');
        $type = strtolower(trim((string) ($row['type'] ?? '')));

        if ($type === '') {
            $type = count($options) === 2 ? 'fastest_finger_first' : 'mcq';
        }

        $expectedOptionCount = match ($type) {
            'mcq' => 4,
            'fastest_finger_first' => 2,
            default => throw new InvalidArgumentException('Type must be mcq or fastest_finger_first.'),
        };

        if (count($options) !== $expectedOptionCount) {
            throw new InvalidArgumentException("{$type} questions require exactly {$expectedOptionCount} English options.");
        }

        if (count($hindiOptions) !== $expectedOptionCount) {
            throw new InvalidArgumentException("{$type} questions require exactly {$expectedOptionCount} Hindi options.");
        }

        $questionText = trim((string) ($row['question_text'] ?? ''));
        $hindiQuestion = trim((string) ($row['hindi_question'] ?? ''));
        $correctAnswer = trim((string) ($row['correct_answer'] ?? ''));
        $hindiCorrectAnswer = trim((string) ($row['hindi_correct_answer'] ?? ''));

        if ($questionText === '' || $hindiQuestion === '') {
            throw new InvalidArgumentException('English and Hindi question text are required.');
        }

        if (!in_array($correctAnswer, $options, true)) {
            throw new InvalidArgumentException('correct_answer must match one of the English options.');
        }

        if (!in_array($hindiCorrectAnswer, $hindiOptions, true)) {
            throw new InvalidArgumentException('hindi_correct_answer must match one of the Hindi options.');
        }

        $difficulty = strtolower(trim((string) ($row['difficulty'] ?? 'easy')));
        if (!in_array($difficulty, ['easy', 'medium', 'hard', 'adaptive'], true)) {
            throw new InvalidArgumentException('Difficulty must be easy, medium, hard, or adaptive.');
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'published')));
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            throw new InvalidArgumentException('Status must be draft, published, or archived.');
        }

        $source = strtolower(trim((string) ($row['source'] ?? 'manual')));
        if (!in_array($source, ['manual', 'ai_generated', 'imported'], true)) {
            throw new InvalidArgumentException('Source must be manual, ai_generated, or imported.');
        }

        $moderationStatus = strtolower(trim((string) ($row['moderation_status'] ?? 'approved')));
        if (!in_array($moderationStatus, ['pending', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Moderation status must be pending, approved, or rejected.');
        }

        $timeLimit = $this->positiveInteger(
            $row['time_limit_seconds'] ?? ($type === 'fastest_finger_first' ? 60 : 30),
            'time_limit_seconds'
        );
        $xpValue = $this->nonNegativeInteger($row['xp_value'] ?? 5, 'xp_value');
        $negativeMarking = $row['negative_marking'] ?? 0;
        if (!is_numeric($negativeMarking) || (float) $negativeMarking < 0) {
            throw new InvalidArgumentException('negative_marking must be a non-negative number.');
        }

        return [
            'tenant_id' => $this->tenantId,
            'question_text' => $questionText,
            'type' => $type,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'hindi_question' => $hindiQuestion,
            'hindi_options' => $hindiOptions,
            'hindi_correct_answer' => $hindiCorrectAnswer,
            'sub_category' => trim((string) ($row['sub_category'] ?? 'India')) ?: 'India',
            'difficulty' => $difficulty,
            'language' => trim((string) ($row['language'] ?? 'English')) ?: 'English',
            'time_limit_seconds' => $timeLimit,
            'xp_value' => $xpValue,
            'negative_marking' => $negativeMarking,
            'status' => $status,
            'tags' => isset($row['tags']) ? trim((string) $row['tags']) : 'gk,india',
            'source' => $source,
            'moderation_status' => $moderationStatus,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function decodeOptions(mixed $value, string $field): array
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("{$field} must be a JSON array.");
        }

        try {
            $options = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException("{$field} must contain valid JSON.", previous: $exception);
        }

        if (!is_array($options) || !array_is_list($options)) {
            throw new InvalidArgumentException("{$field} must be a JSON array.");
        }

        foreach ($options as $option) {
            if (!is_string($option) || trim($option) === '') {
                throw new InvalidArgumentException("Every {$field} entry must be a non-empty string.");
            }
        }

        return array_map('trim', $options);
    }

    private function positiveInteger(mixed $value, string $field): int
    {
        if (!is_numeric($value) || (int) $value < 1 || (string) (int) $value !== (string) $value) {
            throw new InvalidArgumentException("{$field} must be a positive whole number.");
        }

        return (int) $value;
    }

    private function nonNegativeInteger(mixed $value, string $field): int
    {
        if (!is_numeric($value) || (int) $value < 0 || (string) (int) $value !== (string) $value) {
            throw new InvalidArgumentException("{$field} must be a non-negative whole number.");
        }

        return (int) $value;
    }

    /**
     * @return array<int, string>
     */
    private function parseTuples(string $values): array
    {
        $tuples = [];
        $length = strlen($values);
        $position = 0;

        while ($position < $length) {
            while ($position < $length && (ctype_space($values[$position]) || $values[$position] === ',')) {
                $position++;
            }

            if ($position >= $length) {
                break;
            }

            if ($values[$position] !== '(') {
                throw new InvalidArgumentException('Invalid VALUES list in questions INSERT statement.');
            }

            [$tuple, $position] = $this->readParenthesized($values, $position);
            $tuples[] = $tuple;

            while ($position < $length && ctype_space($values[$position])) {
                $position++;
            }

            if ($position < $length && $values[$position] !== ',') {
                throw new InvalidArgumentException('Invalid separator between question rows.');
            }
        }

        if ($tuples === []) {
            throw new InvalidArgumentException('The questions INSERT statement has no value rows.');
        }

        return $tuples;
    }

    /**
     * @return array{string, int}
     */
    private function readParenthesized(string $input, int $start): array
    {
        $depth = 0;
        $quote = null;
        $length = strlen($input);

        for ($position = $start; $position < $length; $position++) {
            $character = $input[$position];

            if ($quote !== null) {
                if ($character === '\\') {
                    $position++;
                    continue;
                }
                if ($character === $quote) {
                    if (($input[$position + 1] ?? null) === $quote) {
                        $position++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')' && --$depth === 0) {
                return [substr($input, $start + 1, $position - $start - 1), $position + 1];
            }
        }

        throw new InvalidArgumentException('Unclosed value row in questions INSERT statement.');
    }

    /**
     * @return array<int, string>
     */
    private function splitTopLevel(string $input): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $quote = null;
        $length = strlen($input);

        for ($position = 0; $position < $length; $position++) {
            $character = $input[$position];

            if ($quote !== null) {
                if ($character === '\\') {
                    $position++;
                    continue;
                }
                if ($character === $quote) {
                    if (($input[$position + 1] ?? null) === $quote) {
                        $position++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $parts[] = trim(substr($input, $start, $position - $start));
                $start = $position + 1;
            }
        }

        $parts[] = trim(substr($input, $start));

        return $parts;
    }

    private function parseLiteral(string $literal): mixed
    {
        $literal = trim($literal);

        if (strcasecmp($literal, 'NULL') === 0) {
            return null;
        }

        if (preg_match('/^([\'"])(.*)\1$/s', $literal, $matches)) {
            $quote = $matches[1];
            $content = $matches[2];
            $decoded = '';

            for ($position = 0, $length = strlen($content); $position < $length; $position++) {
                $character = $content[$position];

                if ($character === $quote && ($content[$position + 1] ?? null) === $quote) {
                    $decoded .= $quote;
                    $position++;
                } elseif ($character === '\\' && $position + 1 < $length) {
                    $position++;
                    $decoded .= match ($content[$position]) {
                        '0' => "\0",
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        'Z' => "\x1a",
                        default => $content[$position],
                    };
                } else {
                    $decoded .= $character;
                }
            }

            return $decoded;
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $literal)) {
            return $literal;
        }

        throw new InvalidArgumentException('Only quoted strings, numbers, and NULL values are supported.');
    }

    private function stripComments(string $sql): string
    {
        $result = '';
        $quote = null;
        $length = strlen($sql);

        for ($position = 0; $position < $length; $position++) {
            $character = $sql[$position];
            $next = $sql[$position + 1] ?? '';

            if ($quote !== null) {
                $result .= $character;
                if ($character === '\\' && $position + 1 < $length) {
                    $result .= $sql[++$position];
                } elseif ($character === $quote) {
                    if (($sql[$position + 1] ?? null) === $quote) {
                        $result .= $sql[++$position];
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
                $result .= $character;
            } elseif (($character === '-' && $next === '-' && ctype_space($sql[$position + 2] ?? ' '))
                || $character === '#') {
                while ($position < $length && $sql[$position] !== "\n") {
                    $position++;
                }
                $result .= "\n";
            } elseif ($character === '/' && $next === '*') {
                $end = strpos($sql, '*/', $position + 2);
                if ($end === false) {
                    throw new InvalidArgumentException('Unclosed SQL comment.');
                }
                $result .= ' ';
                $position = $end + 1;
            } else {
                $result .= $character;
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $start = 0;
        $quote = null;
        $length = strlen($sql);

        for ($position = 0; $position < $length; $position++) {
            $character = $sql[$position];

            if ($quote !== null) {
                if ($character === '\\') {
                    $position++;
                } elseif ($character === $quote) {
                    if (($sql[$position + 1] ?? null) === $quote) {
                        $position++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === ';') {
                $statements[] = substr($sql, $start, $position - $start);
                $start = $position + 1;
            }
        }

        if ($quote !== null) {
            throw new InvalidArgumentException('Unclosed quoted value in SQL file.');
        }

        $statements[] = substr($sql, $start);

        return $statements;
    }
}
