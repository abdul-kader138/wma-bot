<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(
        Request $request,
        string $category,
        string $action,
        ?int $ownerId = null,
        ?string $subjectPath = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $request->user()?->id,
            'owner_id' => $ownerId,
            'category' => $category,
            'action' => $action,
            'subject_path' => $subjectPath,
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1024),
            'created_at' => now(),
        ]);
    }
}
