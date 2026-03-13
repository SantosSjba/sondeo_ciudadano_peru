<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SondeoVote extends Model
{
    protected $table = 'sondeo_votes';

    protected $fillable = ['campaign_id', 'candidate_id'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SondeoCampaign::class, 'campaign_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(SondeoCandidate::class, 'candidate_id');
    }
}
