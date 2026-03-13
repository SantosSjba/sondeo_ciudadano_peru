<?php

namespace App\Application\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;
use App\Domain\Sondeo\Services\SondeoPresenceTracker;

final class GetResultsQuery
{
    public function __construct(
        private VoteRepositoryInterface $votes,
        private SondeoPresenceTracker $presence,
    ) {}

    /**
     * @return array{candidates: array, total: int, updated_at: string, online_count: int, my_candidate_id: int|null, can_change_vote: bool, legacy_locked: bool}
     */
    public function execute(int $campaignId, string $fingerprintHash): array
    {
        $candidates = $this->votes->aggregateByCandidate($campaignId);
        $total = $this->votes->totalVotes($campaignId);

        $row = $this->votes->findVoteRowByFingerprint($campaignId, $fingerprintHash);
        $myCandidateId = $row ? (int) $row->candidate_id : null;
        $legacyLocked = $this->votes->hasLegacyFingerprintOnly($campaignId, $fingerprintHash);
        $canChangeVote = $myCandidateId !== null;

        $onlineCount = $this->presence->touchAndCount($campaignId, $fingerprintHash);

        return [
            'candidates' => $candidates,
            'total' => $total,
            'updated_at' => now()->toIso8601String(),
            'online_count' => $onlineCount,
            'my_candidate_id' => $myCandidateId,
            'can_change_vote' => $canChangeVote,
            'legacy_locked' => $legacyLocked,
        ];
    }
}
