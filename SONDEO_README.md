# Sondeo ciudadano (Perú)

Plataforma web de **participación voluntaria y anónima**: termómetro de intención de voto entre usuarios del sitio. **No es encuesta científica ni resultado electoral oficial.**

## Seguridad de credenciales

- **No subas** usuario/clave de MySQL al repositorio.
- Usa solo `.env` (local o servidor). El archivo `.env.example` muestra variables sin secretos.

## Configuración MySQL (ejemplo)

```env
DB_CONNECTION=mysql
DB_HOST=tu_host
DB_PORT=3306
DB_DATABASE=factosys_sondeo
DB_USERNAME=factosys_sondeo
DB_PASSWORD=tu_contraseña_segura

SONDEO_FINGERPRINT_PEPPER=genera_un_string_largo_aleatorio
SONDEO_VOTE_THROTTLE_PER_MINUTE=5
SONDEO_DEFAULT_CAMPAIGN_SLUG=presidencial-peru
```

```bash
php artisan migrate
php artisan db:seed --class=SondeoCampaignSeeder
npm run build
php artisan serve
```

Portada: `/` · Antigua bienvenida Laravel: `/welcome`.

## Arquitectura (MVP)

| Capa | Contenido |
|------|-----------|
| **Dominio** | `ParticipantFingerprint`, `VoteRepositoryInterface` |
| **Aplicación** | `CastVoteHandler`, `GetResultsQuery` |
| **Infraestructura** | `EloquentVoteRepository` |
| **Presentación** | `Sondeo*Controller`, Inertia `sondeo/Sondeo` |
| **Frontend modular** | `resources/js/modules/sondeo/` |

## Anti-manipulación (sin servicios externos)

1. **Una participación por campaña** por huella `sha256(IP + User-Agent + pepper)` — no guardamos IP en claro.
2. **Throttle** por IP en `POST /api/sondeo/vote`.
3. **Honeypot** (campo oculto).
4. **Tiempo mínimo** en página antes de enviar (reduce scripts instantáneos).

## Fases sugeridas

1. **MVP** — Voto + termómetro + avisos + huecos publicitarios ✓  
2. **Tendencias** — Series temporales, gráficos por día/hora.  
3. **Fraude** — Límites adicionales, scoring interno, auditoría de picos.  
4. **Monetización B2B** — Reportes PDF/API para medios (sobre agregados únicamente).

## Fotos y logos

En `sondeo_candidates`: **`photo_url`** (foto perfil candidato) y **`party_logo_url`** (logo partido). Rutas públicas ej.: `/img/candidatos/nombre.jpg`.
