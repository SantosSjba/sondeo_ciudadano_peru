<?php

namespace App\Http\Controllers\Sondeo;

use App\Application\Sondeo\GetResultsQuery;
use App\Http\Controllers\Controller;
use App\Models\SondeoCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SondeoResultsController extends Controller
{
    public function __invoke(Request $request, GetResultsQuery $query): JsonResponse
    {
        $slug = $request->query('campaign', config('sondeo.default_campaign_slug'));
        $campaign = SondeoCampaign::query()->where('slug', $slug)->where('is_active', true)->first();
        if (! $campaign) {
            return response()->json(['error' => 'campaign_not_found'], 404);
        }

        return response()->json($query->execute($campaign->id));
    }
}
