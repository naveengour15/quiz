<?php

namespace App\Repositories\api;

use App\Interfaces\api\QuestionRepositoryInterface;
use App\Models\Question;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function list(array $filters): array
    {
        $query = Question::query();

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['moderation_status'])) {
            $query->where('moderation_status', $filters['moderation_status']);
        }

        return [
            'success' => true,
            'data'    => $query->latest('id')->paginate(20),
        ];
    }

    public function create(array $data): array
    {
        $question = Question::create($data);

        return [
            'success'  => true,
            'message'  => 'Question created successfully',
            'question' => $question,
        ];
    }

    public function update(int $id, array $data): array
    {
        $question = Question::find($id);

        if (! $question) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $question->fill($data);
        $question->save();

        return [
            'success'  => true,
            'message'  => 'Question updated successfully',
            'question' => $question,
        ];
    }

    public function delete(int $id): array
    {
        $question = Question::find($id);

        if (! $question) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $question->delete();

        return ['success' => true, 'message' => 'Question deleted successfully'];
    }

    public function moderate(int $id, string $status): array
    {
        $question = Question::find($id);

        if (! $question) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $question->moderation_status = $status;
        $question->save();

        return ['success' => true, 'message' => 'Moderation status updated'];
    }
}
