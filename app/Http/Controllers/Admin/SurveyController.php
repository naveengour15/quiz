<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\superadmin\SurveyRepositoryInterface;
use App\Models\AuditLog;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{
    protected $surveyRepository;

    public function __construct(SurveyRepositoryInterface $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }

    public function index(Request $request)
    {
        $data = $this->surveyRepository->index($request);

        return view('admin.surveys.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'     => 'nullable|integer',
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|max:255',
            'reward_type'   => 'required|in:point,quiz',
            'point'         => 'nullable|numeric',
            'questions'     => 'required|array|min:1',
            'questions.*'   => 'required|string|max:1000',
            'quiz'          => 'nullable|numeric',
        ]);

        $validated['tenant_id'] = getTenantId();

        $response = $this->surveyRepository->store($validated);

        return redirect()
            ->route('admin.survey.index')
            ->with(
                $response['status'] ? 'success' : 'error',
                $response['message']
            );
    }

    public function show(Survey $survey)
    {
        $survey->load(['tenant', 'questions']);

        return response()->json([
            'success' => true,
            'data' => $survey,
        ]);
    }

    public function report(Survey $survey)
    {
        return response()->json(
            $this->surveyRepository->report($survey)
        );
    }

    public function reportUsers(Request $request, Survey $survey)
    {
        $questionId = (int) $request->query('question_id', 0);
        $answer = $request->query('answer', 'yes');

        if (!in_array($answer, ['yes', 'no'], true)) {
            $answer = 'yes';
        }

        return response()->json(
            $this->surveyRepository->reportUsers($survey, $questionId, $answer)
        );
    }

    public function update(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'tenant_id'     => 'nullable|integer',
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|max:255',
            'reward_type'   => 'required|in:point,quiz',
            'point'         => 'nullable|numeric',
            'questions'     => 'required|array|min:1',
            'questions.*'   => 'required|string|max:1000',
            'quiz'          => 'nullable|numeric',
        ]);

        $validated['tenant_id'] = getTenantId();

        $response = $this->surveyRepository->update($survey, $validated);

        if ($response['status']) {
            AuditLog::log('Updated survey', 'Survey', $survey->name);
        }

        return redirect()
            ->route('admin.survey.index')
            ->with(
                $response['status'] ? 'success' : 'error',
                $response['message']
            );
    }

    public function destroy(Survey $survey)
    {
        $response = $this->surveyRepository->destroy($survey);

        return redirect()
            ->route('admin.survey.index')
            ->with(
                $response['status'] ? 'success' : 'error',
                $response['message']
            );
    }

    public function changeStatus(Survey $survey)
    {
        return response()->json(
            $this->surveyRepository->changeStatus($survey)
        );
    }

    public function searchTenant(Request $request)
    {
        $search = $request->input('q', '');

        return response()->json(
            $this->surveyRepository->searchTenant($search)
        );
    }
}
