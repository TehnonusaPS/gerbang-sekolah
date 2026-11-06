<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $senderId;
    public string $readerId;

    /**
     * Create a new event instance.
     * 
     * @param string $senderId  The user who sent the message (needs to be notified)
     * @param string $readerId  The recipient who received/downloaded the message
     */
    public function __construct(string $senderId, string $readerId)
    {
        $this->senderId = $senderId;
        $this->readerId = $readerId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
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
            'sender_id' => $this->senderId,
            'reader_id' => $this->readerId,
            'status'    => 'delivered',
        ];
    }
}

