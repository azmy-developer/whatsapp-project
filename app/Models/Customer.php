<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phones',
        'primary_phone',
        'email',
        'tags',
        'notes',
        'ai_summary',
        'ai_summary_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'phones' => 'array',
            'ai_summary_updated_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(Message::class, Conversation::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(CustomerSummary::class);
    }
}

