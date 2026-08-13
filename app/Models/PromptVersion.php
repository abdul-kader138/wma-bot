<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    protected $fillable = [
        'created_by', 'prompt_type', 'version', 'content', 'output_schema',
        'content_hash', 'is_active', 'change_notes',
    ];

    protected function casts(): array
    {
        return ['output_schema' => 'array', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
