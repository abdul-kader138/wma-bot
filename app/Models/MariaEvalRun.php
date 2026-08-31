<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The recorded outcome of one `php artisan maria:eval --live` run against a
 * specific prompt version's content. Used to gate activation of a new prompt.
 *
 * @property float $pass_rate
 * @property string $prompt_content_hash
 */
class MariaEvalRun extends Model
{
    protected $fillable = [
        'prompt_type', 'prompt_version', 'prompt_content_hash',
        'total', 'passed', 'failed', 'pass_rate', 'results', 'triggered_by',
    ];

    protected function casts(): array
    {
        return ['results' => 'array', 'pass_rate' => 'decimal:1'];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
