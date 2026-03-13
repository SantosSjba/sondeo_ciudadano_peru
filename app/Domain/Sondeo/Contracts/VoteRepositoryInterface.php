<?php

namespace App\Domain\Sondeo\Contracts;

interface VoteRepositoryInterface
{
    /** Primer voto (huella nueva en esta campaña). */
    public function recordParticipation(int $campaignId, int $candidateId, string $fingerprintHash): void;

    /** Cambio de opción: una sola fila por huella. */
    public function updateParticipation(int $campaignId, int $candidateId, string $fingerprintHash): void;

    public function findVoteRowByFingerprint(int $campaignId, string $fingerprintHash): ?object;

    public function hasLegacyFingerprintOnly(int $campaignId, string $fingerprintHash): bool;

    public function hasParticipated(int $campaignId, string $fingerprintHash): bool;

    /**
     * @return array<int, array{id: int, name: string, short_label: string|null, votes: int, percent: float}>
     */
    public function aggregateByCandidate(int $campaignId): array;

    public function totalVotes(int $campaignId): int;
}
