<?php

namespace App\Http\Controllers\Sondeo;

use App\Application\Sondeo\CastVoteCommand;
use App\Application\Sondeo\CastVoteHandler;
use App\Domain\Sondeo\Services\ParticipantFingerprint;
use App\Http\Controllers\Controller;
use App\Models\SondeoCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class SondeoVoteController extends Controller
{
    public function __invoke(Request $request, CastVoteHandler $handler, ParticipantFingerprint $fingerprint): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => ['required', 'integer', 'min:1'],
            'campaign_slug' => ['required', 'string', 'max:128'],
            'company' => ['nullable', 'string'], // honeypot (oculto); bots lo rellenan
            'client_elapsed_ms' => ['required', 'integer', 'min:0', 'max:600000'],
        ]);

        $campaign = SondeoCampaign::query()
            ->where('slug', $validated['campaign_slug'])
            ->where('is_active', true)
            ->first();
        if (! $campaign) {
            return response()->json(['ok' => false, 'code' => 'campaign_not_found'], 404);
        }

        $hash = $fingerprint->hash($request);
        $cmd = new CastVoteCommand(
            $campaign->id,
            (int) $validated['candidate_id'],
            $hash,
            isset($validated['company']) ? trim((string) $validated['company']) : null,
            (int) $validated['client_elapsed_ms'],
        );

        try {
            $handler->handle($cmd);
        } catch (RuntimeException $e) {
            $code = $e->getMessage();
            $status = match ($code) {
                'already_voted', 'legacy_no_change' => 409,
                'change_too_soon' => 429,
                'too_fast', 'invalid', 'invalid_candidate' => 422,
                default => 422,
            };

            return response()->json(['ok' => false, 'code' => $code], $status);
        }

        return response()->json(['ok' => true]);
    }
}
