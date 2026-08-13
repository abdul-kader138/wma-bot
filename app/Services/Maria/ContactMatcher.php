<?php

namespace App\Services\Maria;

use App\Models\MariaContact;
use App\Models\User;

class ContactMatcher
{
    /** @return array{status:string,contact:?MariaContact,candidates:array} */
    public function match(User $owner, ?string $email, ?string $name = null): array
    {
        if (filled($email)) {
            $contact = MariaContact::where('user_id', $owner->id)
                ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->first();
            if ($contact) {
                return ['status' => 'matched', 'contact' => $contact, 'candidates' => []];
            }
        }

        if (! filled($name)) {
            return ['status' => 'unmatched', 'contact' => null, 'candidates' => []];
        }

        $normalized = $this->normalizeName($name);
        $candidates = MariaContact::where('user_id', $owner->id)->get()
            ->filter(fn (MariaContact $contact) => $this->normalizeName($contact->full_name) === $normalized)
            ->values();

        if ($candidates->count() === 1) {
            return ['status' => 'matched', 'contact' => $candidates->first(), 'candidates' => []];
        }

        return [
            'status' => $candidates->count() > 1 ? 'ambiguous' : 'unmatched',
            'contact' => null,
            'candidates' => $candidates->map->only(['id', 'full_name', 'email', 'organization'])->all(),
        ];
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }
}
