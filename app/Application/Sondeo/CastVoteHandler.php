<?php

namespace App\Application\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;
use App\Models\SondeoCandidate;
use RuntimeException;

final class CastVoteHandler
{
    public function __construct(
        private VoteRepositoryInterface $votes,
    ) {}

    /**
     * @throws RuntimeException mensaje clave para front (traducible)
     */
    public function handle(CastVoteCommand $cmd): void
    {
        if ($cmd->honeypot !== null && $cmd->honeypot !== '') {
            throw new RuntimeException('invalid');
        }

        // Bots suelen enviar al instante; humanos > ~2s
        if ($cmd->clientElapsedMs < 2000) {
            throw new RuntimeException('too_fast');
        }

        $candidate = SondeoCandidate::query()
            ->where('id', $cmd->candidateId)
            ->where('campaign_id', $cmd->campaignId)
            ->where('is_active', true)
            ->first();
        if (! $candidate) {
            throw new RuntimeException('invalid_candidate');
        }

        if ($this->votes->hasParticipated($cmd->campaignId, $cmd->fingerprintHash)) {
            throw new RuntimeException('already_voted');
        }

        $this->votes->recordParticipation($cmd->campaignId, $cmd->candidateId, $cmd->fingerprintHash);
    }
}
