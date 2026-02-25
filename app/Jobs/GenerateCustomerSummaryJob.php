<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\CustomerSummary;
use App\Models\User;
use App\Services\OllamaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCustomerSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ?User $actor = null,
        public int $window = 50,
    ) {
    }

    public function handle(OllamaClient $ollama): void
    {
        $conversation = $this->conversation->fresh(['customer', 'messages']);

        if (! $conversation->customer) {
            return;
        }

        $summaryText = $ollama->summarizeConversation(
            conversation: $conversation,
            messages: $conversation->messages,
            window: $this->window,
        );

        $model = config('services.ollama.model', 'llama3.1');
        $promptVersion = config('services.ollama.prompt_version', 'v1');

        $summary = CustomerSummary::create([
            'customer_id' => $conversation->customer->id,
            'conversation_id' => $conversation->id,
            'model' => $model,
            'prompt_version' => $promptVersion,
            'source_window' => $this->window,
            'created_by' => $this->actor?->id,
            'summary' => $summaryText,
        ]);

        $conversation->customer->forceFill([
            'ai_summary' => $summary->summary,
            'ai_summary_updated_at' => now(),
        ])->save();
    }
}

