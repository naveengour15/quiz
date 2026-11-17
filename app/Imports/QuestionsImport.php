<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;

class QuestionsImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    public $tenantId;
    public $imported = 0;
    public $skipped = 0;
    public $errors = [];

    // Constructor se tenantId receive karenge
    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    // ⚠️ Yahan ': void' add kiya gaya hai (PHP 8 compatibility ke liye)
    public function onRow(Row $row): void
    {
        $rowData = $row->toArray();
        $rowNumber = $row->getIndex(); // Excel ki actual row number

        try {
            // ═══════════════════════════════════════════════
            // 1. COLUMN MAPPING (WithHeadingRow ki wajah se name se access hoga)
            // ═══════════════════════════════════════════════
            $questionText = trim($rowData['question_text'] ?? '');
            $type         = strtolower(trim($rowData['type'] ?? 'mcq'));
            $optionA      = trim($rowData['option_a'] ?? '');
            $optionB      = trim($rowData['option_b'] ?? '');
            $optionC      = trim($rowData['option_c'] ?? '');
            $optionD      = trim($rowData['option_d'] ?? '');
            $correctAns   = trim($rowData['correct_answer'] ?? '');
            $explanation  = trim($rowData['explanation'] ?? '');
            $categoryName = trim($rowData['category'] ?? '');
            $subCategory  = trim($rowData['sub_category'] ?? '');
            $difficulty   = strtolower(trim($rowData['difficulty'] ?? 'medium'));
            $language     = trim($rowData['language'] ?? 'English');
            $status       = strtolower(trim($rowData['status'] ?? 'published'));

            // ═══════════════════════════════════════════════
            // 2. VALIDATIONS
            // ═══════════════════════════════════════════════
            if (empty($questionText)) {
                throw new \Exception("Question text is required");
            }

            if ($optionA === '' || $optionB === '' || $optionC === '' || $optionD === '') {
                throw new \Exception("All 4 options (A, B, C, D) are required");
            }

            $options = [$optionA, $optionB, $optionC, $optionD];

            if ($correctAns === '') {
                throw new \Exception("Correct answer is required");
            }

            if (!in_array($correctAns, $options)) {
                throw new \Exception("Correct answer '{$correctAns}' must match one of the options");
            }

            // Normalization
            $allowedDifficulties = ['easy', 'medium', 'hard', 'adaptive'];
            $difficulty = in_array($difficulty, $allowedDifficulties) ? $difficulty : 'medium';

            $allowedStatuses = ['draft', 'published', 'archived'];
            $status = in_array($status, $allowedStatuses) ? $status : 'published';

            $allowedLanguages = ['English', 'Hindi'];
            $language = in_array($language, $allowedLanguages) ? $language : 'English';

            $allowedTypes = ['mcq', 'true_false', 'image', 'audio', 'video'];
            $type = in_array($type, $allowedTypes) ? $type : 'mcq';

            // Category ID Lookup
            $categoryId = null;
            if (!empty($categoryName) && $categoryName !== '?' && strtoupper($categoryName) !== 'NULL') {
                $categoryId = $this->getCategoryId($categoryName, $this->tenantId);
            }

            if (empty($subCategory) || $subCategory === '?' || strtoupper($subCategory) === 'NULL') {
                $subCategory = null;
            }

            // ═══════════════════════════════════════════════
            // 3. AUTO HINDI TRANSLATION
            // ═══════════════════════════════════════════════
            $hindiQuestion = null;
            $hindiOptions = null;
            $hindiCorrectAnswer = null;

            if ($language === 'English') {
                $hindiQuestion = $this->translateText($questionText);
                
                $hindiOptions = [];
                foreach ($options as $option) {
                    $hindiOptions[] = $this->translateText($option);
                }
                
                $hindiCorrectAnswer = $this->translateText($correctAns);
            }

            // ═══════════════════════════════════════════════
            // 4. DATABASE INSERT
            // ═══════════════════════════════════════════════
            Question::create([
                'tenant_id'            => $this->tenantId,
                'question_text'        => $questionText,
                'type'                 => $type,
                'options'              => $options, // Ensure $casts = ['options' => 'array'] in Model
                'correct_answer'       => $correctAns,
                'hindi_question'       => $hindiQuestion,
                'hindi_options'        => $hindiOptions,
                'hindi_correct_answer' => $hindiCorrectAnswer,
                'explanation'          => !empty($explanation) ? $explanation : null,
                'quiz_id'              => null,
                'category_id'          => $categoryId,
                'sub_category'         => $subCategory,
                'difficulty'           => $difficulty,
                'language'             => $language,
                'time_limit_seconds'   => 30,
                'xp_value'             => 4,
                'negative_marking'     => 0,
                'status'               => $status,
                'tags'                 => null,
                'source'               => 'imported',
                'moderation_status'    => 'approved',
            ]);

            $this->imported++;

        } catch (\Exception $e) {
            $errorMsg = "Row {$rowNumber}: " . $e->getMessage();
            $this->errors[] = $errorMsg;
            $this->skipped++;

            // ⚠️ ROW-LEVEL LOGGING: Exact data ke sath error log karega
            Log::warning('Excel Import Row Skipped', [
                'row_number' => $rowNumber,
                'error_message' => $e->getMessage(),
                'problematic_data' => $rowData 
            ]);
        }
    }

    /**
     * Translate English text to Hindi
     */
    private function translateText($text): string
    {
        $text = trim((string) $text);
        if ($text === '') return '';

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl'     => 'en',
                    'tl'     => 'hi',
                    'dt'     => 't',
                    'q'      => $text,
                ]);

            if (!$response->successful()) return '';

            $result = $response->json();
            if (!is_array($result) || !isset($result[0]) || !is_array($result[0])) return '';

            $translated = '';
            foreach ($result[0] as $part) {
                if (is_array($part) && isset($part[0])) {
                    $translated .= $part[0];
                }
            }
            return trim($translated);
        } catch (\Throwable $e) {
            Log::error('Translation Error in Import', ['text' => $text, 'message' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Get or find Category ID
     */
    private function getCategoryId($categoryName, $tenantId): ?int
    {
        $category = Category::where('tenant_id', $tenantId)
            ->where('name', 'LIKE', "%{$categoryName}%")
            ->first();

        return $category ? (int)$category->id : null;
    }
}