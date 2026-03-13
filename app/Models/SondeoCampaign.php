<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SondeoCampaign extends Model
{
    protected $table = 'sondeo_campaigns';

    protected $fillable = [
        'slug', 'title', 'description', 'is_active', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(SondeoCandidate::class, 'campaign_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SondeoVote::class, 'campaign_id');
    }
}
