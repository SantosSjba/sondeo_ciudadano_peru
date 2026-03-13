<?php

namespace App\Application\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;

final class GetResultsQuery
{
    public function __construct(
        private VoteRepositoryInterface $votes,
    ) {}

    /**
     * @return array{candidates: array, total: int, updated_at: string}
     */
    public function execute(int $campaignId): array
    {
        $candidates = $this->votes->aggregateByCandidate($campaignId);
        $total = $this->votes->totalVotes($campaignId);

        return [
            'candidates' => $candidates,
            'total' => $total,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
