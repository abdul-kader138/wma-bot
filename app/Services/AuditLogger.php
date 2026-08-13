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
        return $this->recordContext(
            category: $category,
            action: $action,
            actorId: $request->user()?->id,
            ownerId: $ownerId,
            subjectPath: $subjectPath,
            metadata: $metadata,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    public function recordContext(
        string $category,
        string $action,
        ?int $actorId = null,
        ?int $ownerId = null,
        ?string $subjectPath = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actorId,
            'owner_id' => $ownerId,
            'category' => $category,
            'action' => $action,
            'subject_path' => $subjectPath,
            'metadata' => $metadata ?: null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1024) : null,
            'created_at' => now(),
        ]);
    }
}
