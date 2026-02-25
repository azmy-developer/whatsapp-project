<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $limit = 50,
    ) {
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $messages = $gateway->syncMessages($this->conversation, $this->limit);

        foreach ($messages as $remote) {
            Message::updateOrCreate(
                [
                    'provider_message_id' => $remote['id'],
                ],
                [
                    'conversation_id' => $this->conversation->id,
                    'direction' => $remote['direction'] ?? 'inbound',
                    'body' => $remote['body'] ?? null,
                    'sent_at' => $remote['sent_at'] ?? null,
                    'meta' => $remote,
                ],
            );
        }
    }
}

