<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SondeoCandidate extends Model
{
    protected $table = 'sondeo_candidates';

    protected $fillable = [
        'campaign_id', 'name', 'party_name', 'short_label',
        'photo_url', 'party_logo_url', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SondeoCampaign::class, 'campaign_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SondeoVote::class, 'candidate_id');
    }
}
