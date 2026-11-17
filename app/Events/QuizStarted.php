<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quizId;
    public $start_time_ms;
    public $duration_ms;
    public $server_now_ms;

    /**
     * Create a new event instance.
     */
    public function __construct($quizId, $start_time_ms, $duration_ms, $server_now_ms)
    {
        $this->quizId = $quizId;
        $this->start_time_ms = $start_time_ms;
        $this->duration_ms = $duration_ms;
        $this->server_now_ms = $server_now_ms;
    }

    /**
     * Get the channels the event should broadcast on.
     * Using a public channel named quiz.{id}. Switch to PrivateChannel if auth is required.
     */
    public function broadcastOn()
    {
        // Broadcast on both public and private channels for maximum client compatibility
        return [
            new Channel("quiz.{$this->quizId}"),
            new PrivateChannel("quiz.{$this->quizId}")
        ];
    }

    public function broadcastWith()
    {
        return [
            'quizId' => $this->quizId,
            'start_time_ms' => $this->start_time_ms,
            'duration_ms' => $this->duration_ms,
            'server_now_ms' => $this->server_now_ms,
            'warmup_seconds' => 10,
            'per_question_seconds' => 15,
            'hold_seconds' => 5,
            'answer_seconds' => 10,
            'anssheet_seconds' => 10,
            'leaderboard_wait_seconds' => 30,
        ];
    }
}
