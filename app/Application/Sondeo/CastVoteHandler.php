<?php

namespace App\Application\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;
use App\Domain\Sondeo\Services\VoteAbuseDetector;
use App\Models\SondeoCandidate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use RuntimeException;

final class CastVoteHandler
{
    public function __construct(
        private VoteRepositoryInterface $votes,
        private VoteAbuseDetector $abuse,
    ) {}

    /**
     * @throws RuntimeException mensaje clave para front
     */
    public function handle(CastVoteCommand $cmd, Request $request): void
    {
        // Honeypots: bots suelen rellenar campos ocultos
        if ($cmd->honeypot !== null && $cmd->honeypot !== '') {
            throw new RuntimeException('invalid');
        }
        if ($cmd->honeypot2 !== null && $cmd->honeypot2 !== '') {
            throw new RuntimeException('invalid');
        }

        $candidate = SondeoCandidate::query()
            ->where('id', $cmd->candidateId)
            ->where('campaign_id', $cmd->campaignId)
            ->where('is_active', true)
            ->first();
        if (! $candidate) {
            throw new RuntimeException('invalid_candidate');
        }

        $existing = $this->votes->findVoteRowByFingerprint($cmd->campaignId, $cmd->fingerprintHash);
        $isChange = $existing !== null;

        // Detector de abuso (interacción, huella, diversidad por IP, tiempo)
        $this->abuse->check(
            $request,
            $cmd->fingerprintHash,
            $cmd->browserFp,
            $cmd->interactScore,
            $cmd->clientElapsedMs,
            $isChange,
        );

        // Tiempo mínimo (doble chequeo tras detector)
        $minMs = $isChange ? 2000 : 3500;
        if ($cmd->clientElapsedMs < $minMs) {
            throw new RuntimeException('too_fast');
        }

        if ($existing !== null) {
            if ((int) $existing->candidate_id === $cmd->candidateId) {
                return;
            }
            $cooldown = (int) config('sondeo.vote_change_cooldown_seconds', 45);
            $last = CarbonImmutable::parse($existing->updated_at ?? $existing->created_at);
            if ($last->addSeconds($cooldown)->isFuture()) {
                throw new RuntimeException('change_too_soon');
            }
            $this->votes->updateParticipation($cmd->campaignId, $cmd->candidateId, $cmd->fingerprintHash);

            return;
        }

        if ($this->votes->hasLegacyFingerprintOnly($cmd->campaignId, $cmd->fingerprintHash)) {
            throw new RuntimeException('legacy_no_change');
        }

        if ($this->votes->hasParticipated($cmd->campaignId, $cmd->fingerprintHash)) {
            throw new RuntimeException('already_voted');
        }

        $this->votes->recordParticipation($cmd->campaignId, $cmd->candidateId, $cmd->fingerprintHash);
    }
}
