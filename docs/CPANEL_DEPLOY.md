# Desplegar Laravel en cPanel (HTTP 500 / document root en `public`)

## 1. Estructura obligatoria

El **Document Root** del dominio debe ser la carpeta **`public`** del proyecto, **pero** Laravel necesita **todo el proyecto** una carpeta **arriba** de `public`:

```
tu_cuenta/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← Document Root del dominio (solo esta carpeta es “la web”)
│   ├── index.php
│   └── .htaccess
├── resources/
├── routes/
├── storage/
├── vendor/          ← imprescindible (composer install)
└── .env
```

**Error típico:** subir solo lo que hay dentro de `public/` a `public_html`. Entonces `index.php` busca `../vendor/autoload.php` y **no existe** → **HTTP 500**.

**Soluciones en cPanel:**

- **A)** Subir **todo el proyecto** (por FTP/Git) a algo como `~/sondeo_cuidadano_peru/`, y en cPanel poner el dominio **Document Root** en `.../sondeo_cuidadano_peru/public`.
- **B)** O dejar el proyecto en `~/sondeo_cuidadano_peru/` y crear un **enlace simbólico** desde `public_html` hacia `.../public` (si el hosting lo permite).

## 2. Después de subir archivos

En SSH (o Terminal de cPanel), dentro de la carpeta del proyecto (donde está `artisan`):

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force   # solo si .env no tiene APP_KEY
php artisan migrate --force        # si usas BD en el servidor
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

`.env` en el servidor: `APP_URL=https://votolibre.factosysperu.com`, `APP_DEBUG=false`, base de datos del hosting, etc.

## 3. Si el 500 sigue: ver el error real

- cPanel → **Métricas** → **Errores** (Error Log).
- O archivo `error_log` en `public_html` o en la raíz del dominio.

Causas frecuentes:

| Causa | Qué hacer |
|-------|-----------|
| Falta `vendor/` | `composer install` en el servidor |
| `APP_KEY` vacío | `php artisan key:generate` |
| PHP viejo | cPanel → **Select PHP Version** → PHP 8.2 o 8.3 |
| Permisos | `storage` y `bootstrap/cache` escribibles |
| `.htaccess` | Usar el `.htaccess` mínimo del repo (ya adaptado) |

## 4. PHP en cPanel

- Elegir **PHP 8.2+** para Laravel 11/12.
- Extensiones habituales: `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`.

## 5. SEO e indexación (Google)

En `.env` de producción:

- `APP_URL=https://votolibre.factosysperu.com` (sin barra final o con barra, Laravel lo normaliza en enlaces).
- `APP_NAME=Voto Libre` (o el nombre que quieras en título por defecto).

Tras desplegar:

1. Abre `https://votolibre.factosysperu.com/robots.txt` — debe listar el Sitemap.
2. Abre `https://votolibre.factosysperu.com/sitemap.xml` — debe mostrar la URL de la portada.
3. **Google Search Console** → Añadir propiedad (prefijo de URL) → Verificación → **Sitemaps** → enviar `https://votolibre.factosysperu.com/sitemap.xml`.

Favicon: bandera de Perú en `/favicon.svg`.

**Votar en producción:** `APP_URL` debe ser exactamente el dominio desde el que entra la gente (ej. `https://votolibre.factosysperu.com`). Si usas `www` y `APP_URL` no tiene `www` (o al revés), el middleware de voto puede rechazar el POST. En local (`APP_ENV=local`) ese control no se aplica.

**Termómetro:** el listado se ordena por **número de votos** (mayor arriba). Las medallas son el top 3 real.

**Sugerencias:** tabla `sondeo_suggestions`. Ver últimas: `php artisan sondeo:suggestions --last=50`

## 6. Base de datos remota

Si MySQL está en otro host, en `.env`: `DB_HOST=...` y en el panel MySQL permitir el usuario desde el servidor web si hace falta.
