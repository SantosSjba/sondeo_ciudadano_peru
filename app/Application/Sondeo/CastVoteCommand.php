<?php

namespace App\Application\Sondeo;

final readonly class CastVoteCommand
{
    public function __construct(
        public int $campaignId,
        public int $candidateId,
        public string $fingerprintHash,
        public ?string $honeypot,
        public int $clientElapsedMs,
    ) {}
}
