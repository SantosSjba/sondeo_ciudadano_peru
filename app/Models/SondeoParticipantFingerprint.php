<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SondeoParticipantFingerprint extends Model
{
    protected $table = 'sondeo_participant_fingerprints';

    protected $fillable = ['campaign_id', 'fingerprint_hash'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SondeoCampaign::class, 'campaign_id');
    }
}
