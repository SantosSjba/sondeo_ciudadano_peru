<?php

namespace App\Domain\Sondeo\Contracts;

interface VoteRepositoryInterface
{
    public function recordParticipation(int $campaignId, int $candidateId, string $fingerprintHash): void;

    public function hasParticipated(int $campaignId, string $fingerprintHash): bool;

    /**
     * @return array<int, array{id: int, name: string, short_label: string|null, votes: int, percent: float}>
     */
    public function aggregateByCandidate(int $campaignId): array;

    public function totalVotes(int $campaignId): int;
}
