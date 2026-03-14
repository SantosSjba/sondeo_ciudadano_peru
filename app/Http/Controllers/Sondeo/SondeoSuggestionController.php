<?php

namespace App\Http\Controllers\Sondeo;

use App\Domain\Sondeo\Services\ParticipantFingerprint;
use App\Http\Controllers\Controller;
use App\Models\SondeoSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SondeoSuggestionController extends Controller
{
    public function __invoke(Request $request, ParticipantFingerprint $fingerprint): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        if (filled($validated['company'] ?? null)) {
            return response()->json(['ok' => false, 'code' => 'invalid'], 422);
        }

        SondeoSuggestion::query()->create([
            'message' => trim($validated['message']),
            'contact_email' => isset($validated['contact_email']) ? trim((string) $validated['contact_email']) ?: null : null,
            'fingerprint_hash' => $fingerprint->hash($request),
        ]);

        return response()->json(['ok' => true]);
    }
}
