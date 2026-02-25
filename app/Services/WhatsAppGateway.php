<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\WhatsAppAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppGateway
{
    protected string $baseUrl;

    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp_node.base_url');
        $this->webhookSecret = config('services.whatsapp_node.webhook_secret');
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(10);
    }

    public function startSession(WhatsAppAccount $account): array
    {
        $response = $this->client()->post('/sessions/start', [
            'account_id' => $account->id,
            'session_ref' => $account->session_ref,
            'webhook_secret' => $this->webhookSecret,
        ]);

        $response->throw();

        return $response->json();
    }

    public function getSessionStatus(WhatsAppAccount $account): ?array
    {
        if (! $account->session_ref) {
            return null;
        }

        $response = $this->client()->get('/sessions/status', [
            'session_ref' => $account->session_ref,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function stopSession(WhatsAppAccount $account): void
    {
        if (! $account->session_ref) {
            return;
        }

        $this->client()->post('/sessions/stop', [
            'session_ref' => $account->session_ref,
        ])->throw();
    }

    public function fetchQrCode(WhatsAppAccount $account): ?string
    {
        if (! $account->session_ref) {
            return null;
        }

        $response = $this->client()->get("/sessions/{$account->session_ref}/qr");

        if ($response->status() === 204) {
            return null;
        }

        $response->throw();

        $data = $response->json();

        return $data['qr'] ?? null;
    }

    public function syncConversations(WhatsAppAccount $account): array
    {
        $response = $this->client()->get('/conversations', [
            'session_ref' => $account->session_ref,
        ]);

        $response->throw();

        return $response->json('conversations') ?? [];
    }

    public function syncMessages(Conversation $conversation, int $limit = 50): array
    {
        $response = $this->client()->get("/conversations/{$conversation->chat_provider_id}/messages", [
            'session_ref' => $conversation->whatsappAccount->session_ref,
            'limit' => $limit,
        ]);

        $response->throw();

        return $response->json('messages') ?? [];
    }
}

