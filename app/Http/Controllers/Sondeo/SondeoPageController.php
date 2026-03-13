<?php

namespace App\Http\Controllers\Sondeo;

use App\Http\Controllers\Controller;
use App\Models\SondeoCampaign;
use Inertia\Inertia;
use Inertia\Response;

final class SondeoPageController extends Controller
{
    public function __invoke(): Response
    {
        $slug = config('sondeo.default_campaign_slug');
        $campaign = SondeoCampaign::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $campaign) {
            return Inertia::render('sondeo/Sondeo', [
                'campaign' => null,
                'candidates' => [],
            ]);
        }

        $candidates = $campaign->candidates()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'party_name', 'short_label', 'photo_url', 'party_logo_url']);

        return Inertia::render('sondeo/Sondeo', [
            'campaign' => [
                'id' => $campaign->id,
                'slug' => $campaign->slug,
                'title' => $campaign->title,
                'description' => $campaign->description,
            ],
            'candidates' => $candidates,
        ]);
    }
}
