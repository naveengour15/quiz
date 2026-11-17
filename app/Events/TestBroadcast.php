<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quizId;
    public $message;
    public $server_now_ms;

    public function __construct($quizId, $message, $server_now_ms)
    {
        $this->quizId = $quizId;
        $this->message = $message;
        $this->server_now_ms = $server_now_ms;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("quiz.{$this->quizId}");
    }

    public function broadcastWith()
    {
        return [
            'quizId' => $this->quizId,
            'message' => $this->message,
            'server_now_ms' => $this->server_now_ms,
        ];
    }
}
