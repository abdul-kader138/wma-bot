<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MariaContact extends Model
{
    protected $table = 'maria_contacts';

    protected $fillable = [
        'user_id', 'full_name', 'role', 'organization', 'email', 'categories', 'tier',
        'domain_relevance', 'why_matters', 'verification_source', 'warm_path',
        'stage', 'last_interaction_at', 'next_action', 'follow_up_at',
        'consent_preferences', 'private_contact_data', 'notes',
    ];

    protected $hidden = ['private_contact_data'];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'domain_relevance' => 'array',
            'private_contact_data' => 'encrypted',
            'last_interaction_at' => 'datetime',
            'follow_up_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relationshipRecommendations(): HasMany
    {
        return $this->hasMany(RelationshipRecommendation::class);
    }
}
