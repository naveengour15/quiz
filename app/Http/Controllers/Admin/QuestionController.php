<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QuestionsImport;

class QuestionController extends Controller
{
   
     public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
          $tenantId = getTenantId();
        $questions = Question::where('tenant_id', $tenantId)->with(['category', 'subCategory'])
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($query) use ($search) {
                    $query->where('question_text', 'like', "%{$search}%")
                        ->orWhere('hindi_question', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::where('tenant_id', $tenantId)->whereNull('parent_id')->orderBy('name')->get();
        $subCategories = Category::where('tenant_id', $tenantId)->whereNotNull('parent_id')->orderBy('name')->get();
        $editing = $request->edit ? Question::findOrFail($request->edit) : null;
        $allCategories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('admin.questions.index', compact(  'allCategories','questions', 'categories', 'editing',  'subCategories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        // $data['tenant_id'] = getTenantId();
        $tenantId = getTenantId();

        if (!$tenantId) {
            return back()->with('error', 'Tenant ID not found.');
        }

        $data['tenant_id'] = $tenantId;

        /* Image Upload → S3 → media_url*/
        if (in_array($request->type, ['image', 'image_audio'], true) && $request->hasFile('image_file')) {

            $file = $request->file('image_file');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $imgPath = "questions/images/{$imageName}";

                Storage::disk('s3')->put(
                    $imgPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['media_url'] = $imgPath;
            }
        }

        /* Audio Upload → S3 → audio_file*/

        if ($request->type === 'audio' && $request->hasFile('audio_file')) {

            $file = $request->file('audio_file');

            if ($file && $file->isValid()) {

                $audioName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $audioPath = "questions/audio/{$audioName}";

                Storage::disk('s3')->put(
                    $audioPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['audio_file'] = $audioPath;
            }
        }

         // *****quesiton audio file start***************
        if ($request->hasFile('question_audio')) {

            $file = $request->file('question_audio');

            if ($file && $file->isValid()) {

                $audioName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $audioPath = "questions/audio/{$audioName}";

                Storage::disk('s3')->put(
                    $audioPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['question_audio'] = $audioPath;
            }
        }
        // *****quesiton audio file end***************

        /*Video Upload → S3 → media_url */
        
        /*if ($request->type === 'video' && $request->hasFile('video_file')) {

            $file = $request->file('video_file');

            if ($file && $file->isValid()) {

                $videoName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $videoPath = "questions/videos/{$videoName}";

                Storage::disk('s3')->put(
                    $videoPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['media_url'] = $videoPath;
            }
        }*/

        Question::create($data);
        AuditLog::log('Created question', 'Content', str($data['question_text'])->limit(40));

        return redirect()->route('admin.questions.index')->with('success', 'Question saved.');
    }


     public function update(Request $request, Question $question)
    {
        $data = $this->validated($request);

        //  Tenant ID compulsory (Admin ka tenant_id)
        $tenantId = getTenantId();

        if (!$tenantId) {
            return back()->with('error', 'Tenant ID not found.');
        }

        $data['tenant_id'] = $tenantId;

         /* Image Upload → S3 → media_url*/
        if (in_array($request->type, ['image', 'image_audio'], true) && $request->hasFile('image_file')) {

            $file = $request->file('image_file');

            if ($file && $file->isValid()) {

                // Delete old image
                if ($question->media_url) {
                    try {
                        Storage::disk('s3')->delete($question->media_url);
                    } catch (\Exception $e) {
                        // Ignore if old file doesn't exist
                    }
                }

                $imageName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $imgPath = "questions/images/{$imageName}";

                Storage::disk('s3')->put(
                    $imgPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['media_url'] = $imgPath;
            }
        }

        /* Audio Upload → S3 → audio_file */
        if ($request->type === 'audio' && $request->hasFile('audio_file')) {

            $file = $request->file('audio_file');

            if ($file && $file->isValid()) {

                // Delete old audio
                if ($question->audio_file) {
                    try {
                        Storage::disk('s3')->delete($question->audio_file);
                    } catch (\Exception $e) {
                        // Ignore if old file doesn't exist
                    }
                }

                $audioName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $audioPath = "questions/audio/{$audioName}";

                Storage::disk('s3')->put(
                    $audioPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['audio_file'] = $audioPath;
            }
        }
        // *****quesiton audio file start***************
        if ($request->hasFile('question_audio')) {

            $file = $request->file('question_audio');

            if ($file && $file->isValid()) {

                $audioName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $audioPath = "questions/audio/{$audioName}";

                Storage::disk('s3')->put(
                    $audioPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['question_audio'] = $audioPath;
            }
        }
        // *****quesiton audio file end***************

        /*Video Upload → S3 → media_url 
        if ($request->type === 'video' && $request->hasFile('video_file')) {

            $file = $request->file('video_file');

            if ($file && $file->isValid()) {

                // Delete old video
                if ($question->media_url) {
                    try {
                        Storage::disk('s3')->delete($question->media_url);
                    } catch (\Exception $e) {
                        // Ignore if old file doesn't exist
                    }
                }

                $videoName = uniqid(time(), true) . '.' . $file->getClientOriginalExtension();

                $videoPath = "questions/videos/{$videoName}";

                Storage::disk('s3')->put(
                    $videoPath,
                    file_get_contents($file->getRealPath()),
                    'public'
                );

                $data['media_url'] = $videoPath;
            }
        }*/
      
        $question->update($data);
        AuditLog::log('Edited question', 'Content', 'Q-' . $question->id);

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        AuditLog::log('Deleted question', 'Content', 'Q-' . $question->id);

        return back()->with('success', 'Question deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question_text' => 'nullable|string',
            'type' => 'required|in:mcq,true_false,image,audio,video,puzzle,fastest_finger_first,image_audio',
            'image_file' => ['nullable','file','image',],
            
            'options' => [
                'nullable',
                'array',
                Rule::requiredIf($request->type === 'mcq')
            ],
            
            'options.*' => [
                'nullable',
                'string',
                // Rule::requiredIf($request->type === 'mcq')
            ],


             // Hindi
                'hindi_question' => 'nullable|string',
                'hindi_options' => ['nullable', 'array',],
                'hindi_options.*' => ['nullable','string',],
                'hindi_correct_answer' => 'nullable|string',
            
            'correct_answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category' => 'nullable|exists:categories,id',
            'difficulty' => 'required|in:easy,medium,hard,adaptive',
            'language' => 'required|string',
            'time_limit_seconds' => 'required|integer|min:5',
            'xp_value' => 'nullable|integer|min:0',
            'negative_marking' => 'nullable|numeric',
            'status' => 'required|in:draft,published,archived',
            'tags' => 'nullable|string',
            'source' => 'required|in:manual,ai_generated,imported',
            'audio_file' => ['nullable','file', 'mimes:mp3,wav,ogg,m4a,aac,flac',],
            'question_audio' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a,aac,flac'],

        ]);

        $data['moderation_status'] = 'approved';

        if ($request->has('options') && is_array($request->options)) {
            $cleanOptions = [];
            foreach ($request->options as $value) {
                if (trim((string)$value) !== '') {
                    $cleanOptions[] = $value;
                }
            }
            $data['options'] = $cleanOptions;
        } else {
            $data['options'] = [];
        }

          /*Hindi Options */
        if ($request->has('hindi_options') && is_array($request->hindi_options)) {
            $cleanHindiOptions = [];
            foreach ($request->hindi_options as $value) {
                if (trim((string) $value) !== '') {
                    $cleanHindiOptions[] = trim($value);
                }
            }
            $data['hindi_options'] = $cleanHindiOptions;
        } else {
            $data['hindi_options'] = [];
        }

         /*Hindi Correct Answer */
        if (
            empty($data['hindi_question']) ||
            empty($data['hindi_options'])
        ) {
            $data['hindi_question'] = null;
            $data['hindi_options'] = [];
            $data['hindi_correct_answer'] = null;
        }

        return $data;
    }
    
    public function changeStatus(Request $request)
    {
        $question = Question::find($request->id);

        if (!$question) {
            return response()->json([
                'success' => false
            ]);
        }

        $question->status = $request->status;
        $question->save();

        return response()->json([
            'success' => true
        ]);
    }

   

      public function autoTranslateHindi(Request $request)
    {
        $request->validate([
            'question' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string',
            'correct_answer' => 'nullable|string',
        ]);

        try {

            // NEW CLOUD TRANSLATION API CODE
            $translate = function ($text) {
                $text = trim((string) $text);
                if ($text === '') {
                    return '';
                }

                $apiKey = env('GOOGLE_TRANSLATION_API_KEY');
                if (empty($apiKey)) {
                    throw new \Exception('Google Cloud Translation API key is missing in .env (GOOGLE_TRANSLATION_API_KEY)');
                }

                $response = Http::withoutVerifying()
                    ->timeout(15)
                    ->post(
                        'https://translation.googleapis.com/language/translate/v2?key=' . $apiKey,
                        [
                            'q'      => $text,
                            'target' => 'hi',
                            'source' => 'en',
                            'format' => 'text'
                        ]
                    );

                if (!$response->successful()) {
                    throw new \Exception(
                        'Google Cloud Translation API Error: ' .
                        $response->status() . ' - ' . $response->body()
                    );
                }

                $result = $response->json();

                if (
                    !is_array($result) ||
                    !isset($result['data']['translations'][0]['translatedText'])
                ) {
                    throw new \Exception(
                        'Invalid response received from Google Cloud Translation API.'
                    );
                }

                // Cloud Translation API might return HTML entities (like &#39; for apostrophe)
                return trim(html_entity_decode($result['data']['translations'][0]['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            };

            // English Question
            $hindiQuestion = $translate(
                $request->input('question', '')
            );

            // English Options
            $hindiOptions = [];

            foreach (
                $request->input('options', []) as $option
            ) {

                $option = trim((string) $option);

                if ($option === '') {
                    $hindiOptions[] = '';
                    continue;
                }

                $hindiOptions[] = $translate($option);
            }

            // English Correct Answer
            $correctAnswer = trim(
                (string) $request->input(
                    'correct_answer',
                    ''
                )
            );

            $hindiCorrectAnswer = '';

            if ($correctAnswer !== '') {

                $hindiCorrectAnswer =
                    $translate($correctAnswer);
            }


            return response()->json([
                'success' => true,
                'question' => $hindiQuestion,
                'options' => $hindiOptions,
                'correct_answer' => $hindiCorrectAnswer,
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Hindi Translation Error',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
  
    // ************queistion import code start********
  
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $allowedExtensions = ['xlsx', 'xls', 'csv'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('The file must be Excel (.xlsx, .xls) or CSV format.');
                    }
                }
            ],
        ]);

        $file = $request->file('file');
    
        $tenantId = getTenantId();

        try {
            $import = new QuestionsImport($tenantId);
            
            // Excel import trigger
            Excel::import($import, $file);

            //  SUCCESS SCENARIO
            if ($import->imported > 0) {
                $totalRows = $import->imported + $import->skipped;
                return redirect()->route('admin.questions.index')
                    ->with('success', "Successfully imported {$import->imported} questions.")
                    ->with('import_summary', [
                        'total'    => $totalRows,
                        'imported' => $import->imported,
                        'skipped'  => $import->skipped,
                        'errors'   => $import->errors,
                    ]);
            }

            // ❌ FAILED SCENARIO (0 Imported, but errors exist)
            if ($import->imported === 0 && !empty($import->errors)) {
                // ⚠️ YEH LOG BANAYEGA: Exact reason batayega ki kyu fail hua
                Log::error('Excel Import Failed: 0 Rows Imported', [
                    'tenant_id' => $tenantId,
                    'file_name' => $file->getClientOriginalName(),
                    'total_errors' => count($import->errors),
                    'errors' => $import->errors
                ]);

                return redirect()->route('admin.questions.index')
                    ->with('error', 'Import failed. Please check the file format and column names. Check logs for details.')
                    ->with('import_errors', $import->errors) // Blade mein dikhane ke liye
                    ->withInput();
            }

            // Fallback agar kuch aur hi galat ho gaya
            return redirect()->route('admin.questions.index')
                ->with('error', 'File processed but no valid data found.')
                ->withInput();

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }
            
            Log::error('Excel Validation Exception', ['errors' => $errors]);

            return redirect()->route('admin.questions.index')
                ->with('error', 'Validation failed during import.')
                ->with('import_errors', $errors)
                ->withInput();
                
        } catch (\Exception $e) {
            // ⚠️ GLOBAL ERROR LOG (Jaise WithHeadingRow fail hona, ya file corrupt hona)
            Log::error('Excel Import Global Fatal Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.questions.index')
                ->with('error', 'Failed to process file: ' . $e->getMessage())
                ->withInput();
        }
    }
    
 

    // ... aapka downloadSample() method yahan rahega (unchanged) ...
    public function downloadSample()
    {
        $filename = 'question_import_sample_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            fputcsv($file, [
                'question_text', 'type', 'option_a', 'option_b', 'option_c', 'option_d',
                'correct_answer', 'explanation', 'category', 'sub_category', 'difficulty', 'language', 'status',
            ]);

            fputcsv($file, [
                'capital of mp', 'mcq', 'Indore', 'Bhopal', 'Ujjain', 'Jabalpur',
                'Bhopal', 'Bhopal is the capital of Madhya Pradesh', 'Geography', 'Indian States', 'medium', 'English', 'published',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
