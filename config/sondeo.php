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
    | Slug de la campaña activa mostrada en la portada.
    */
    'default_campaign_slug' => env('SONDEO_DEFAULT_CAMPAIGN_SLUG', 'presidencial-peru'),

];
