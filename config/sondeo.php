<?php

return [

    /*
    | Pepper adicional para la huella (además de APP_KEY). Cambiar en producción vía .env.
    */
    'fingerprint_pepper' => env('SONDEO_FINGERPRINT_PEPPER', ''),

    /*
    | Máximo de votos aceptados por minuto por IP (anti-bots básico).
    */
    'vote_throttle_per_minute' => (int) env('SONDEO_VOTE_THROTTLE_PER_MINUTE', 5),

    /*
    | Segundos mínimos entre cambios de voto (misma huella). Anti-bots / flood.
    */
    'vote_change_cooldown_seconds' => (int) env('SONDEO_VOTE_CHANGE_COOLDOWN_SECONDS', 45),

    /*
    | Slug de la campaña activa mostrada en la portada.
    */
    'default_campaign_slug' => env('SONDEO_DEFAULT_CAMPAIGN_SLUG', 'presidencial-peru'),

    /*
    | SEO / Google: descripción por defecto (meta description, Open Graph).
    | APP_URL debe ser https://votolibre.factosysperu.com en producción.
    */
    'seo_site_name' => env('SONDEO_SEO_SITE_NAME', 'Voto Libre'),
    'seo_description' => env(
        'SONDEO_SEO_DESCRIPTION',
        'Sondeo ciudadano en línea: participa de forma anónima y consulta el termómetro de intención de voto presidencial en el Perú. Resultados en tiempo real. No oficial, sin validez electoral.'
    ),
    'seo_keywords' => env(
        'SONDEO_SEO_KEYWORDS',
        'sondeo Perú, intención de voto, elecciones Perú, candidatos presidenciales, termómetro ciudadano, participación ciudadana, factosys'
    ),
];
