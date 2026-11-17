<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuizType;
use Illuminate\Http\Request;

class QuizTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $quizTypes = QuizType::latest()->paginate($perPage)->withQueryString();

        $editing = $request->edit
            ? QuizType::findOrFail($request->edit)
            : null;

        return view('admin.quiz-types.index', compact('quizTypes', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $data['tenant_id'] = getTenantId();

        QuizType::create($data);
        AuditLog::log('Created Quiz Type', 'Content', $data['name']);

        return redirect()->route('admin.quiz-types.index')->with('success', 'Quiz Type created successfully.');
    }

    public function update(Request $request, QuizType $quizType)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $data['tenant_id'] = getTenantId();
        $quizType->update($data);
        AuditLog::log('Updated Quiz Type', 'Content', $quizType->name);

        return redirect()->route('admin.quiz-types.index')->with('success', 'Quiz Type updated successfully.');
    }

    public function destroy(QuizType $quizType)
    {
        AuditLog::log('Deleted Quiz Type', 'Content', $quizType->name);
        $quizType->delete();

        return back()->with('success', 'Quiz Type deleted successfully.');
    }

    public function updateStatus(QuizType $quizType)
    {
        $quizType->status = $quizType->status == 'active' ? 'inactive' : 'active';
        $quizType->save();

        AuditLog::log(
            'Updated Quiz Type Status',
            'Content',
            $quizType->name . ' (' . $quizType->status . ')'
        );

        return response()->json([
            'success' => true,
            'status'  => $quizType->status
        ]);
    }
}
