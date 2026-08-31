<?php

namespace App\Services\Maria\Tools;

use App\Models\AssistantMemory;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RememberFactTool extends BaseAssistantTool
{
    public function name(): string
    {
        return 'remember_fact';
    }

    public function description(): string
    {
        return 'Store a durable fact or standing preference about the owner so it is available in future conversations '
            .'(e.g. "prefers morning meetings", "assistant name is Marta"). Use a short stable key. Re-using a key overwrites it. '
            .'Internal only; contacts nobody.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [
            'key' => ['type' => 'string', 'description' => 'Short stable identifier, e.g. "meeting_time_preference".'],
            'value' => ['type' => 'string', 'description' => 'The fact itself, one sentence.'],
        ], 'required' => ['key', 'value']];
    }

    public function execute(User $owner, array $input): array
    {
        $data = Validator::make($input, [
            'key' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:2000'],
        ])->validate();

        $key = Str::of($data['key'])->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        if ($key === '') {
            return ['error' => 'The key must contain at least one letter or digit.'];
        }

        $memory = AssistantMemory::updateOrCreate(
            ['user_id' => $owner->id, 'key' => $key],
            ['value' => $data['value'], 'source' => 'chat'],
        );

        return ['key' => $memory->key, 'value' => $memory->value, 'stored' => true];
    }
}
