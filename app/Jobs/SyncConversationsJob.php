<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\WhatsAppAccount;
use App\Services\WhatsAppGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncConversationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WhatsAppAccount $account,
    ) {
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        if (! $this->account->session_ref) {
            return;
        }

        $remoteConversations = $gateway->syncConversations($this->account);

        foreach ($remoteConversations as $remote) {
            Conversation::updateOrCreate(
                [
                    'whatsapp_account_id' => $this->account->id,
                    'chat_provider_id' => $remote['id'],
                ],
                [
                    'display_name' => $remote['name'] ?? null,
                    'phone' => $remote['phone'] ?? null,
                    'last_message_at' => $remote['last_message_at'] ?? null,
                ],
            );
        }
    }
}

