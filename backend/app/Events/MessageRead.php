<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $readerId;
    public $senderId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $readerId, string $senderId)
    {
        $this->readerId = $readerId;
        $this->senderId = $senderId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to the user who sent the messages originally, 
        // notifying them that their messages to $this->readerId have been read.
        return [
            new PrivateChannel('chat.' . $this->senderId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'reader_id' => $this->readerId,
            'sender_id' => $this->senderId,
            'status' => 'read',
        ];
    }
}
