/**
 * Huella de navegador construida enteramente en el cliente.
 * No se usa ninguna librería externa. Combina señales pasivas que
 * diferencian navegadores reales de bots/headless/scripts.
 *
 * IMPORTANTE: este hash NO se almacena en BD ni se usa como clave
 * de unicidad; es una señal secundaria de confianza que el servidor
 * evalúa junto con IP + UA. Nunca contiene datos personales.
 */
export async function buildBrowserFingerprint(): Promise<string> {
    const parts: string[] = [];

    // ── 1. Pantalla ──────────────────────────────────────────────
    try {
        parts.push(`s:${screen.width}x${screen.height}x${screen.colorDepth}x${screen.pixelDepth ?? 0}`);
        parts.push(`av:${screen.availWidth}x${screen.availHeight}`);
    } catch { /* ignore */ }

    // ── 2. Zona horaria ──────────────────────────────────────────
    try {
        parts.push(`tz:${Intl.DateTimeFormat().resolvedOptions().timeZone}`);
        parts.push(`tzo:${new Date().getTimezoneOffset()}`);
    } catch { /* ignore */ }

    // ── 3. Idiomas del navegador ─────────────────────────────────
    try {
        parts.push(`lang:${(navigator.languages ?? [navigator.language]).join(',')}`);
    } catch { /* ignore */ }

    // ── 4. Hardware (concurrency, memoria, touch) ────────────────
    try {
        parts.push(`hw:${navigator.hardwareConcurrency ?? 0}`);
        // deviceMemory no está en todas las versiones del tipo pero existe en Chrome
        const mem = (navigator as unknown as Record<string, unknown>).deviceMemory;
        if (mem) parts.push(`mem:${mem}`);
        parts.push(`touch:${'ontouchstart' in window ? 1 : 0}`);
        parts.push(`maxtp:${navigator.maxTouchPoints ?? 0}`);
    } catch { /* ignore */ }

    // ── 5. Platform / OS ────────────────────────────────────────
    try {
        parts.push(`plat:${navigator.platform ?? ''}`);
        parts.push(`vendor:${navigator.vendor ?? ''}`);
    } catch { /* ignore */ }

    // ── 6. Canvas 2D ────────────────────────────────────────────
    try {
        const canvas = document.createElement('canvas');
        canvas.width = 220; canvas.height = 30;
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#d00';
            ctx.fillRect(100, 1, 80, 22);
            ctx.fillStyle = '#1a56db';
            ctx.font = 'italic 14px "Arial", sans-serif';
            ctx.fillText('Voto🗳️Perú2026', 2, 18);
            ctx.fillStyle = 'rgba(102,204,0,0.75)';
            ctx.font = '11px "Times New Roman", serif';
            ctx.fillText('sondeo ciudadano', 6, 26);
            parts.push(`c2d:${canvas.toDataURL('image/png').slice(-80)}`);
        }
    } catch { /* ignore */ }

    // ── 7. WebGL renderer ────────────────────────────────────────
    try {
        const gl = document.createElement('canvas').getContext('webgl') as WebGLRenderingContext | null;
        if (gl) {
            const dbg = gl.getExtension('WEBGL_debug_renderer_info');
            if (dbg) {
                const renderer = gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL) as string;
                const vendor = gl.getParameter(dbg.UNMASKED_VENDOR_WEBGL) as string;
                parts.push(`gl:${renderer.slice(0, 60)}`);
                parts.push(`glv:${vendor.slice(0, 40)}`);
            }
        }
    } catch { /* ignore */ }

    // ── 8. Características de CSS / entorno ──────────────────────
    try {
        parts.push(`dpr:${Math.round((window.devicePixelRatio ?? 1) * 100)}`);
        parts.push(`cw:${document.documentElement.clientWidth}`);
    } catch { /* ignore */ }

    // ── 9. Hash SHA-256 en el navegador ─────────────────────────
    const raw = parts.filter(Boolean).join('|');
    try {
        const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(raw));
        return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('');
    } catch {
        // Fallback: hash rápido (no criptográfico)
        let h = 0;
        for (let i = 0; i < raw.length; i++) {
            h = Math.imul(31, h) + raw.charCodeAt(i) | 0;
        }
        return (h >>> 0).toString(16).padStart(8, '0');
    }
}

/**
 * Puntaje de interacción humana (0-100).
 * Se acumula a medida que el usuario mueve el ratón / toca la pantalla.
 * Un bot que lanza la petición sin interacción obtiene 0.
 */
export function createInteractionTracker(): { score: () => number; listen: () => void; destroy: () => void } {
    let score = 0;
    let moveCount = 0;
    let touchCount = 0;
    let scrollCount = 0;

    const onMove = () => { moveCount = Math.min(moveCount + 1, 50); };
    const onTouch = () => { touchCount = Math.min(touchCount + 1, 30); };
    const onScroll = () => { scrollCount = Math.min(scrollCount + 1, 20); };

    return {
        score: () => {
            const s = Math.min(100, moveCount * 1 + touchCount * 2 + scrollCount * 2);
            return s;
        },
        listen: () => {
            window.addEventListener('mousemove', onMove, { passive: true });
            window.addEventListener('touchstart', onTouch, { passive: true });
            window.addEventListener('scroll', onScroll, { passive: true });
        },
        destroy: () => {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('touchstart', onTouch);
            window.removeEventListener('scroll', onScroll);
        },
    };
}
