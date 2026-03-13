<?php

namespace App\Infrastructure\Sondeo;

use App\Domain\Sondeo\Contracts\VoteRepositoryInterface;
use App\Models\SondeoCandidate;
use App\Models\SondeoParticipantFingerprint;
use App\Models\SondeoVote;
use Illuminate\Support\Facades\DB;

final class EloquentVoteRepository implements VoteRepositoryInterface
{
    public function recordParticipation(int $campaignId, int $candidateId, string $fingerprintHash): void
    {
        DB::transaction(function () use ($campaignId, $candidateId, $fingerprintHash): void {
            SondeoParticipantFingerprint::query()->firstOrCreate([
                'campaign_id' => $campaignId,
                'fingerprint_hash' => $fingerprintHash,
            ]);
            SondeoVote::query()->create([
                'campaign_id' => $campaignId,
                'candidate_id' => $candidateId,
                'fingerprint_hash' => $fingerprintHash,
            ]);
        });
    }

    public function updateParticipation(int $campaignId, int $candidateId, string $fingerprintHash): void
    {
        SondeoVote::query()
            ->where('campaign_id', $campaignId)
            ->where('fingerprint_hash', $fingerprintHash)
            ->update([
                'candidate_id' => $candidateId,
                'updated_at' => now(),
            ]);
    }

    public function findVoteRowByFingerprint(int $campaignId, string $fingerprintHash): ?object
    {
        return SondeoVote::query()
            ->where('campaign_id', $campaignId)
            ->where('fingerprint_hash', $fingerprintHash)
            ->first();
    }

    public function hasLegacyFingerprintOnly(int $campaignId, string $fingerprintHash): bool
    {
        $hasFp = SondeoParticipantFingerprint::query()
            ->where('campaign_id', $campaignId)
            ->where('fingerprint_hash', $fingerprintHash)
            ->exists();

        $hasRow = SondeoVote::query()
            ->where('campaign_id', $campaignId)
            ->where('fingerprint_hash', $fingerprintHash)
            ->exists();

        return $hasFp && ! $hasRow;
    }

    public function hasParticipated(int $campaignId, string $fingerprintHash): bool
    {
        return SondeoParticipantFingerprint::query()
            ->where('campaign_id', $campaignId)
            ->where('fingerprint_hash', $fingerprintHash)
            ->exists();
    }

    public function aggregateByCandidate(int $campaignId): array
    {
        $total = $this->totalVotes($campaignId);
        $candidates = SondeoCandidate::query()
            ->where('campaign_id', $campaignId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $counts = SondeoVote::query()
            ->where('campaign_id', $campaignId)
            ->selectRaw('candidate_id, COUNT(*) as c')
            ->groupBy('candidate_id')
            ->pluck('c', 'candidate_id');

        $out = [];
        foreach ($candidates as $c) {
            $votes = (int) ($counts[$c->id] ?? 0);
            $percent = $total > 0 ? round(100 * $votes / $total, 2) : 0.0;
            $out[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'party_name' => $c->party_name,
                'short_label' => $c->short_label,
                'photo_url' => $c->photo_url,
                'party_logo_url' => $c->party_logo_url,
                'votes' => $votes,
                'percent' => $percent,
            ];
        }

        return $out;
    }

    public function totalVotes(int $campaignId): int
    {
        return (int) SondeoVote::query()->where('campaign_id', $campaignId)->count();
    }
}
