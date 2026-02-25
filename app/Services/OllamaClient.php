<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class OllamaClient
{
    public function summarizeConversation(Conversation $conversation, Collection $messages, int $window = 50): string
    {
        $baseUrl = config('services.ollama.base_url');
        $model = config('services.ollama.model', 'llama3.1');

        $ordered = $messages
            ->sortBy('sent_at')
            ->take($window)
            ->values()
            ->map(function ($message) {
                $direction = $message->direction === 'outbound' ? 'Agent' : 'Customer';

                return sprintf(
                    '[%s][%s] %s',
                    $direction,
                    optional($message->sent_at)->toDateTimeString(),
                    (string) $message->body
                );
            })
            ->implode(PHP_EOL);

        $prompt = $this->buildPrompt($conversation, $ordered);

        try {
            $response = Http::baseUrl($baseUrl)
                ->timeout(60)
                ->post('/api/generate', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.2,
                    ],
                ]);
        } catch (ConnectionException $e) {
            return 'AI summary unavailable (Ollama not reachable).';
        }

        if (! $response->successful()) {
            return 'AI summary unavailable (Ollama error).';
        }

        return (string) $response->json('response', '');
    }

    protected function buildPrompt(Conversation $conversation, string $messages): string
    {
        $customerName = optional($conversation->customer)->name;

        return collect([
            'You are an assistant that creates concise CRM summaries from WhatsApp chats.',
            'Summarize the conversation between an agent and a customer.',
            'Focus on: who the customer is, key needs/issues, decisions/commitments, next suggested actions, and open questions.',
            'If information is missing, explicitly say Unknown.',
            'Do not invent facts.',
            '',
            'Customer name (may be null): '.$customerName,
            'Conversation messages (from oldest to newest):',
            $messages,
        ])->implode(PHP_EOL);
    }
}

