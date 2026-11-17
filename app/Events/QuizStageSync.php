<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStageSync implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quizId;
    public $stage;
    public $server_now_ms;
    public $payload;

    /**
     * Create a new event instance.
     */
    public function __construct($quizId, $stage, $server_now_ms = null, array $payload = [])
    {
        $this->quizId = $quizId;
        $this->stage = $stage;
        $this->server_now_ms = $server_now_ms ?? (int) round(microtime(true) * 1000);
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [
            new Channel("quiz.{$this->quizId}"),
            new PrivateChannel("quiz.{$this->quizId}")
        ];
    }

    public function broadcastWith()
    {
        return [
            'quizId' => $this->quizId,
            'stage' => $this->stage,
            'server_now_ms' => $this->server_now_ms,
            'payload' => $this->payload,
        ];
    }
}
