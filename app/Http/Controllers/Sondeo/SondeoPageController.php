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

        $seo = self::seoPayload($campaign?->title, $campaign?->description);

        if (! $campaign) {
            return Inertia::render('sondeo/Sondeo', [
                'campaign' => null,
                'candidates' => [],
                'seo' => $seo,
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
            'seo' => self::seoPayload($campaign->title, $campaign->description),
        ]);
    }

    /**
     * @return array{siteName: string, title: string, description: string, canonical: string, jsonLd: string}
     */
    private static function seoPayload(?string $campaignTitle, ?string $campaignDescription): array
    {
        $base = rtrim(config('app.url'), '/');
        $siteName = (string) config('sondeo.seo_site_name');
        $title = $campaignTitle
            ? "{$campaignTitle} | {$siteName}"
            : "{$siteName} — Sondeo ciudadano Perú";
        $description = $campaignDescription
            ? strip_tags($campaignDescription).' '.config('sondeo.seo_description')
            : (string) config('sondeo.seo_description');
        $description = mb_substr(preg_replace('/\s+/', ' ', $description) ?? '', 0, 320);

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $base.'/#website',
                    'url' => $base.'/',
                    'name' => $siteName,
                    'description' => config('sondeo.seo_description'),
                    'inLanguage' => 'es-PE',
                    'publisher' => ['@id' => $base.'/#organization'],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => $base.'/#organization',
                    'name' => 'Factosys Perú',
                    'url' => 'https://factosysperu.com',
                    'sameAs' => ['https://factosysperu.com'],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $base.'/#webpage',
                    'url' => $base.'/',
                    'name' => $title,
                    'description' => $description,
                    'isPartOf' => ['@id' => $base.'/#website'],
                    'inLanguage' => 'es-PE',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return [
            'siteName' => $siteName,
            'title' => $title,
            'description' => $description,
            'canonical' => $base.'/',
            'jsonLd' => $jsonLd ?: '{}',
        ];
    }
}
