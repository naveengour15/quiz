<?php

namespace App\Exports;

use App\Models\QuizNotification;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuizNotificationLeadsExport implements FromCollection, WithHeadings
{
    protected $quizId;
    protected $category;
    protected $leadtype;

    public function __construct($quizId, $category, $leadtype)
    {
        $this->quizId = $quizId;
        $this->category = $category;
        $this->leadtype = $leadtype;
    }

    public function collection(): Collection
    {
        $notifications = QuizNotification::with(['user', 'quiz'])
            ->where('quiz_id', $this->quizId)
            ->get();

        return $notifications
            ->filter(function ($notification) {
                return $notification->user;
            })
            ->map(function ($notification) {

                $quizName = $notification->quiz->title
                    ?? $notification->quiz_name
                    ?? '';

                return [
                    'business_name' => $quizName,

                    'Quiz_name' => $quizName,

                    'contact_name' => $notification->user->name
                        ?? $notification->user_name
                        ?? '',

                    'email' => '',

                    'phone' => $notification->user->mobile ?? '',

                    'website' => '',

                    'address' => '',

                    'city' => '',

                    'category' => $this->category,

                    'leadtype' => $this->leadtype,

                    'quiz_time' => $notification->quiz_schedule_time
                        ? \Carbon\Carbon::parse(
                            $notification->quiz_schedule_time
                        )->format('h:i A')
                        : '',

                    'quiz_url' => $notification->quiz_id . '?auto_join=1',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'business_name',
            'Quiz_name',
            'contact_name',
            'email',
            'phone',
            'website',
            'address',
            'city',
            'category',
            'leadtype',
            'quiz_time',
            'quiz_url',
        ];
    }
}