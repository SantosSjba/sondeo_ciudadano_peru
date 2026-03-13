<?php

namespace App\Application\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;

final class GetResultsQuery
{
    public function __construct(
        private VoteRepositoryInterface $votes,
    ) {}

    /**
     * @return array{candidates: array, total: int, updated_at: string, my_candidate_id: int|null, can_change_vote: bool, legacy_locked: bool}
     */
    public function execute(int $campaignId, string $fingerprintHash): array
    {
        $candidates = $this->votes->aggregateByCandidate($campaignId);
        $total = $this->votes->totalVotes($campaignId);

        $row = $this->votes->findVoteRowByFingerprint($campaignId, $fingerprintHash);
        $myCandidateId = $row ? (int) $row->candidate_id : null;
        $legacyLocked = $this->votes->hasLegacyFingerprintOnly($campaignId, $fingerprintHash);
        $canChangeVote = $myCandidateId !== null;

        return [
            'candidates' => $candidates,
            'total' => $total,
            'updated_at' => now()->toIso8601String(),
            'my_candidate_id' => $myCandidateId,
            'can_change_vote' => $canChangeVote,
            'legacy_locked' => $legacyLocked,
        ];
    }
}
