<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'conversation_id',
        'model',
        'prompt_version',
        'source_window',
        'created_by',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'source_window' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

