<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import LegalNoticeDialog from '@/modules/sondeo/components/LegalNoticeDialog.vue';
import ShareSondeoMenu from '@/modules/sondeo/components/ShareSondeoMenu.vue';
import SuggestionDialog from '@/modules/sondeo/components/SuggestionDialog.vue';
import VoteThermometer, { type CandidateBar } from '@/modules/sondeo/components/VoteThermometer.vue';
import { buildBrowserFingerprint, createInteractionTracker } from '@/utils/browserFingerprint';

type Candidate = {
    id: number;
    name: string;
    party_name: string | null;
    short_label: string | null;
    photo_url: string | null;
    party_logo_url: string | null;
};

const props = defineProps<{
    campaign: { id: number; slug: string; title: string; description: string | null } | null;
    candidates: Candidate[];
    seo: {
        siteName: string;
        title: string;
        description: string;
        canonical: string;
        jsonLd: string;
    };
}>();

/* ── estado ─────────────────────────────────────────────────────── */
const pageLoadAt = ref(Date.now());
const selectedId = ref<number | null>(null);
const honeypot = ref('');
const honeypot2 = ref('');
const browserFp = ref('');
const interactTracker = createInteractionTracker();
const submitting = ref(false);
/** Ya votó (persistido vía API por huella): recarga / otro día sigue viendo “Cambiar voto” */
const votedOk = ref(false);
/** Participó con esquema antiguo: sin cambio de voto */
const legacyLocked = ref(false);
const lastSubmitAt = ref(0);
const errorCode = ref<string | null>(null);
const showModal = ref(false);
const candidateSearch = ref('');
/** Relleno inferior en móvil cuando el teclado tapa la lista */
const modalKeyboardPad = ref(0);
let vvResizeHandler: (() => void) | null = null;

function updateModalKeyboardPad() {
    if (typeof window === 'undefined' || !window.visualViewport) {
        modalKeyboardPad.value = 0;
        return;
    }
    const vv = window.visualViewport;
    const hidden = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
    modalKeyboardPad.value = Math.min(hidden + 24, 320);
}

function onSearchFocus(e: Event) {
    const el = e.target as HTMLElement;
    setTimeout(() => {
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        updateModalKeyboardPad();
    }, 280);
}

/** Alerta global (toast) para que el ciudadano siempre vea el mensaje */
const alertBanner = ref<{
    type: 'error' | 'warning' | 'info';
    title: string;
    message: string;
} | null>(null);
let alertAutoClose: ReturnType<typeof setTimeout> | null = null;

function dismissAlert() {
    if (alertAutoClose) {
        clearTimeout(alertAutoClose);
        alertAutoClose = null;
    }
    alertBanner.value = null;
}

function showAlertBanner(type: 'error' | 'warning' | 'info', title: string, message: string) {
    dismissAlert();
    alertBanner.value = { type, title, message };
    alertAutoClose = setTimeout(() => {
        alertBanner.value = null;
        alertAutoClose = null;
    }, 14000);
}

const results = ref<{ candidates: CandidateBar[]; total: number }>({ candidates: [], total: 0 });
const resultsLoading = ref(true);
const updatedAt = ref('');
/** Usuarios con la página abierta (presencia vía polling ~10 s) */
const onlineCount = ref(0);
let pollTimer: ReturnType<typeof setInterval> | null = null;

/* ── helpers ─────────────────────────────────────────────────────── */
function initials(name: string) {
    return name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
}
function csrf() {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

/* Bloquea scroll del body cuando el modal está abierto */
watch(showModal, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
    if (v) {
        candidateSearch.value = '';
        modalKeyboardPad.value = 0;
        if (typeof window !== 'undefined' && window.visualViewport) {
            vvResizeHandler = () => updateModalKeyboardPad();
            window.visualViewport.addEventListener('resize', vvResizeHandler);
            window.visualViewport.addEventListener('scroll', vvResizeHandler);
        }
    } else {
        modalKeyboardPad.value = 0;
        if (vvResizeHandler && typeof window !== 'undefined' && window.visualViewport) {
            window.visualViewport.removeEventListener('resize', vvResizeHandler);
            window.visualViewport.removeEventListener('scroll', vvResizeHandler);
            vvResizeHandler = null;
        }
    }
});

/* ── API ─────────────────────────────────────────────────────────── */
async function fetchResults() {
    if (!props.campaign) { resultsLoading.value = false; return; }
    try {
        const r = await fetch(
            `/api/sondeo/results?campaign=${encodeURIComponent(props.campaign.slug)}`,
            { headers: { Accept: 'application/json' } },
        );
        if (!r.ok) return;
        const data = await r.json();
        results.value = { candidates: data.candidates ?? [], total: data.total ?? 0 };
        updatedAt.value = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        onlineCount.value = Math.max(0, Number(data.online_count) || 0);

        // Misma huella que al votar: restaurar “ya participé” tras F5 o volver más tarde
        const slug = props.campaign.slug;
        const storageKey = `sondeo_voto_${slug}`;

        if (data.legacy_locked) {
            legacyLocked.value = true;
            votedOk.value = false;
            sessionStorage.removeItem(storageKey);
        } else {
            legacyLocked.value = false;
            if (data.my_candidate_id != null && Number.isFinite(Number(data.my_candidate_id))) {
                votedOk.value = true;
                selectedId.value = Number(data.my_candidate_id);
                lastSubmitAt.value = Date.now();
                sessionStorage.setItem(storageKey, String(data.my_candidate_id));
            } else {
                const cached = sessionStorage.getItem(storageKey);
                if (cached != null && Number.isFinite(Number(cached))) {
                    votedOk.value = true;
                    selectedId.value = Number(cached);
                    lastSubmitAt.value = Date.now();
                } else {
                    votedOk.value = false;
                }
            }
        }
    } finally {
        resultsLoading.value = false;
    }
}

async function submitVote() {
    if (!props.campaign || selectedId.value == null) return;
    errorCode.value = null;
    submitting.value = true;
    const elapsed = votedOk.value
        ? Math.max(2000, Date.now() - lastSubmitAt.value)
        : Date.now() - pageLoadAt.value;
    try {
        const r = await fetch('/api/sondeo/vote', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                candidate_id: selectedId.value,
                campaign_slug: props.campaign.slug,
                company: honeypot.value,
                website: honeypot2.value,
                client_elapsed_ms: elapsed,
                browser_fp: browserFp.value || '',
                interact_score: interactTracker.score(),
            }),
        });
        const data = await r.json().catch(() => ({}));
        if (r.ok && data.ok) {
            votedOk.value = true;
            lastSubmitAt.value = Date.now();
            if (props.campaign && selectedId.value != null) {
                sessionStorage.setItem(`sondeo_voto_${props.campaign.slug}`, String(selectedId.value));
            }
            showModal.value = false;
            await fetchResults();
        } else {
            errorCode.value = data.code ?? 'error';
            const code = data.code ?? 'error';
            const titles: Record<string, string> = {
                legacy_no_change: 'No se puede cambiar el voto',
                change_too_soon: 'Espera un momento',
                already_voted: 'Participación registrada',
                too_fast: 'Demasiado rápido',
                bot_ua: 'Acceso no válido',
                bot_origin: 'Acceso no válido',
                bot_headers: 'Acceso no válido',
                ip_abuse: 'Demasiados intentos',
                invalid: 'Error al registrar',
                invalid_candidate: 'Candidato no válido',
                error: 'No se pudo completar',
            };
            const type: 'error' | 'warning' | 'info' =
                code === 'legacy_no_change' ? 'warning'
                : code === 'change_too_soon' || code === 'too_fast' ? 'info'
                : 'error';
            showAlertBanner(
                type,
                titles[code] ?? 'Aviso',
                errorMessageForCode(code),
            );
        }
    } finally {
        submitting.value = false;
    }
}

function errorMessageForCode(code: string | null): string {
    switch (code) {
        case 'already_voted':
            return 'Ya registramos tu participación en este sondeo. Solo se cuenta una por dispositivo.';
        case 'change_too_soon':
            return 'Debes esperar unos segundos entre cada cambio de opción (protección anti-abuso). Vuelve a intentar en breve.';
        case 'legacy_no_change':
            return 'Tu participación se registró con una versión anterior del sitio y no permite cambiar el voto desde aquí. Gracias por haber participado.';
        case 'too_fast':
            return 'Espera al menos unos segundos en la página antes de enviar (protección anti-bots). En el primer voto suele pedir ~4 s.';
        case 'bot_ua':
        case 'bot_origin':
        case 'bot_headers':
            return 'No se pudo validar el envío desde este entorno. Abre el sitio en el navegador (Chrome, Safari, etc.) y vuelve a intentar.';
        case 'ip_abuse':
            return 'Demasiados intentos desde tu red en poco tiempo. Espera unos minutos e inténtalo de nuevo.';
        case 'invalid':
        case 'invalid_candidate':
            return 'No se pudo registrar tu voto. Recarga la página e intenta de nuevo.';
        default:
            return 'No se pudo registrar tu participación. Intenta de nuevo en unos momentos.';
    }
}

const errorMessage = computed(() => (errorCode.value ? errorMessageForCode(errorCode.value) : null));

const selectedCandidate = computed(() => props.candidates.find((c) => c.id === selectedId.value) ?? null);

/* Candidatos ordenados: seleccionado al inicio si ya votó */
const sortedCandidates = computed(() => {
    if (!votedOk.value || selectedId.value == null) return props.candidates;
    return [
        ...props.candidates.filter((c) => c.id === selectedId.value),
        ...props.candidates.filter((c) => c.id !== selectedId.value),
    ];
});

/** Búsqueda por nombre de candidato, partido o etiqueta corta */
const filteredCandidates = computed(() => {
    const q = candidateSearch.value.trim().toLowerCase().normalize('NFD').replace(/\p{M}/gu, '');
    if (!q) return sortedCandidates.value;
    return sortedCandidates.value.filter((c) => {
        const name = (c.name ?? '').toLowerCase().normalize('NFD').replace(/\p{M}/gu, '');
        const party = (c.party_name ?? '').toLowerCase().normalize('NFD').replace(/\p{M}/gu, '');
        const short = (c.short_label ?? '').toLowerCase();
        return name.includes(q) || party.includes(q) || short.includes(q);
    });
});

function injectJsonLd() {
    document.getElementById('sondeo-schema-jsonld')?.remove();
    if (!props.seo?.jsonLd) return;
    const el = document.createElement('script');
    el.id = 'sondeo-schema-jsonld';
    el.type = 'application/ld+json';
    el.textContent = props.seo.jsonLd;
    document.head.appendChild(el);
}

onMounted(() => {
    fetchResults();
    pollTimer = setInterval(fetchResults, 10000);
    interactTracker.listen();
    buildBrowserFingerprint().then(fp => { browserFp.value = fp; }).catch(() => {});
    injectJsonLd();
});
onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    if (alertAutoClose) clearTimeout(alertAutoClose);
    interactTracker.destroy();
    document.body.style.overflow = '';
    document.getElementById('sondeo-schema-jsonld')?.remove();
});
</script>

<template>
    <Head :title="props.seo.title">
        <meta name="description" :content="props.seo.description" />
        <meta name="keywords" content="sondeo Perú, intención de voto, elecciones, candidatos presidenciales, termómetro ciudadano, participación anónima, Voto Libre" />
        <link rel="canonical" :href="props.seo.canonical" />

        <meta property="og:title" :content="props.seo.title" />
        <meta property="og:description" :content="props.seo.description" />
        <meta property="og:url" :content="props.seo.canonical" />
        <meta property="og:locale" content="es_PE" />
        <meta property="og:image" :content="`${props.seo.canonical.replace(/\/$/, '')}/favicon.svg`" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="props.seo.title" />
        <meta name="twitter:description" :content="props.seo.description" />
    </Head>

    <!-- Alerta global: siempre visible (encima del modal) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="alertBanner"
                class="fixed left-0 right-0 top-0 z-[100] px-3 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-4"
                role="alert"
                aria-live="assertive"
            >
                <div
                    class="mx-auto flex max-w-lg gap-3 rounded-2xl border-2 p-4 shadow-2xl sm:max-w-xl"
                    :class="{
                        'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/95 dark:text-red-100': alertBanner.type === 'error',
                        'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950/95 dark:text-amber-100': alertBanner.type === 'warning',
                        'border-sky-300 bg-sky-50 text-sky-950 dark:border-sky-800 dark:bg-sky-950/95 dark:text-sky-100': alertBanner.type === 'info',
                    }"
                >
                    <div class="shrink-0 pt-0.5" aria-hidden="true">
                        <svg v-if="alertBanner.type === 'error'" class="size-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg v-else-if="alertBanner.type === 'warning'" class="size-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg v-else class="size-8 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold leading-tight sm:text-base">{{ alertBanner.title }}</p>
                        <p class="mt-1.5 text-sm leading-snug opacity-95 sm:text-[15px]">{{ alertBanner.message }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-xl px-3 py-2 text-sm font-semibold underline decoration-2 underline-offset-2 hover:opacity-80"
                        @click="dismissAlert"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>

    <div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white text-zinc-900 dark:from-zinc-950 dark:to-zinc-900 dark:text-zinc-100">

        <!-- ═══ HEADER ═══════════════════════════════════════════════════ -->
        <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/95 shadow-sm backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <!-- Logo / título -->
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <span class="flex h-7 shrink-0 overflow-hidden rounded shadow-sm" aria-hidden="true">
                        <span class="w-2.5 bg-red-600" /><span class="w-4 bg-white" /><span class="w-2.5 bg-red-600" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold tracking-widest text-red-700 uppercase dark:text-red-400">
                            Sondeo ciudadano · Perú 2026
                        </p>
                        <h1 class="truncate text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-100 sm:text-base">
                            {{ campaign?.title ?? 'Sondeo ciudadano' }}
                        </h1>
                    </div>
                </div>

                <!-- Acciones header -->
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <LegalNoticeDialog v-if="campaign" />
                    <ShareSondeoMenu
                        v-if="campaign"
                        :url="seo.canonical || 'https://votolibre.factosysperu.com/'"
                        :title="seo.title"
                        :description="seo.description"
                    />
                    <!-- CTA principal header (md+) -->
                    <span
                        v-if="campaign && legacyLocked"
                        class="hidden rounded-lg bg-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-600 sm:inline dark:bg-zinc-800 dark:text-zinc-400"
                    >Ya participaste</span>
                    <button
                        v-else-if="campaign"
                        type="button"
                        class="hidden rounded-lg px-4 py-2 text-sm font-semibold text-white shadow transition-colors sm:block"
                        :class="votedOk
                            ? 'bg-amber-600 hover:bg-amber-700'
                            : 'bg-red-600 hover:bg-red-700'"
                        @click="showModal = true"
                    >
                        {{ votedOk ? 'Cambiar voto' : '¡Participar!' }}
                    </button>
                </div>
            </div>
        </header>

        <!-- ═══ MAIN ══════════════════════════════════════════════════════ -->
        <main class="mx-auto max-w-5xl px-4 pb-28 sm:px-6 sm:pb-16">

            <template v-if="!campaign">
                <div class="mt-10 rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-zinc-500">No hay sondeo activo. Ejecuta las migraciones y el seeder.</p>
                </div>
            </template>

            <template v-else>

                <!-- ── Hero contador ───────────────────────────────────── -->
                <div class="mt-5 mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-2xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100 sm:text-3xl">
                            {{ resultsLoading ? '…' : results.total.toLocaleString('es-PE') }}
                            <span class="text-base font-medium text-zinc-500"> participantes</span>
                        </p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-block size-2 animate-pulse rounded-full bg-emerald-500" aria-hidden="true" />
                                <span>En tiempo real · {{ updatedAt || 'cargando…' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 font-medium text-sky-800 dark:border-sky-800 dark:bg-sky-950/60 dark:text-sky-200" title="Quienes tienen esta página abierta ahora (actualización cada pocos segundos)">
                                <svg class="size-3.5 shrink-0 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="tabular-nums">{{ resultsLoading ? '…' : onlineCount.toLocaleString('es-PE') }}</span>
                                <span class="text-[10px] font-normal opacity-90">en línea</span>
                            </span>
                        </p>
                    </div>

                    <!-- Chip de estado del usuario (desktop) -->
                    <div v-if="votedOk && selectedCandidate" class="hidden items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 sm:flex dark:border-emerald-900 dark:bg-emerald-950/40">
                        <div class="relative shrink-0">
                            <img
                                v-if="selectedCandidate.photo_url"
                                :src="selectedCandidate.photo_url"
                                :alt="selectedCandidate.name"
                                class="h-9 w-9 rounded-full border border-zinc-200 object-cover dark:border-zinc-600"
                            />
                            <span v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ initials(selectedCandidate.name) }}
                            </span>
                            <img v-if="selectedCandidate.party_logo_url" :src="selectedCandidate.party_logo_url" alt="" class="absolute -right-0.5 -bottom-0.5 h-4 w-4 rounded-full border border-white bg-white object-contain dark:border-zinc-800" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-emerald-800 dark:text-emerald-200">Tu opción</p>
                            <p class="max-w-[200px] text-[10px] leading-snug text-emerald-800 dark:text-emerald-200">{{ selectedCandidate.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Ética ciudadana (transparencia) ───────────────── -->
                <div
                    class="mb-4 rounded-xl border border-emerald-200/90 bg-emerald-50/95 px-3 py-3 text-[11px] leading-relaxed text-emerald-950 shadow-sm sm:px-4 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100"
                >
                    <p class="font-bold text-emerald-900 dark:text-emerald-100">Compromiso por la transparencia</p>
                    <p class="mt-1 text-emerald-900/95 dark:text-emerald-200/95">
                        Te pedimos <strong>no repetir votos</strong> desde varios dispositivos ni intentar falsear el sondeo.
                        <strong>Una sola participación por dispositivo/red</strong>: si ya votaste, el sistema <strong>bloquea nuevos intentos</strong> (solo podrás <em>cambiar</em> tu opción desde el mismo dispositivo, con límites de tiempo).
                        Así el termómetro refleja mejor la opinión honesta de quien participa. Gracias.
                    </p>
                </div>

                <!-- ── Termómetro ──────────────────────────────────────── -->
                <section
                    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-6 dark:border-zinc-700 dark:bg-zinc-900"
                    aria-label="Resultados en tiempo real"
                >
                    <VoteThermometer
                        :candidates="results.candidates"
                        :total="results.total"
                        :loading="resultsLoading"
                    />
                </section>

                <!-- ── Footer ─────────────────────────────────────────── -->
                <footer class="mt-8 border-t border-zinc-200 pt-6 text-[11px] leading-relaxed text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <div class="mx-auto max-w-2xl space-y-2 text-center">
                        <p>
                            Este sitio es un <strong class="text-zinc-700 dark:text-zinc-300">sondeo ciudadano en línea</strong>:
                            participación voluntaria, resultados no oficiales y sin validez electoral. Datos mostrados en totales anónimos.
                            <strong class="text-zinc-600 dark:text-zinc-400">Un voto por dispositivo</strong>; intentos repetidos quedan bloqueados para cuidar la transparencia.
                        </p>
                        <p>Imágenes de candidatos y logos usados como referencia visual informativa; no vinculado a organismos electorales ni a campañas.</p>
                        <p>
                            Información oficial sobre candidatos:
                            <a href="https://votoinformado.jne.gob.pe/presidente-vicepresidentes" target="_blank" rel="noopener noreferrer" class="font-medium text-red-700 underline underline-offset-2 hover:text-red-800 dark:text-red-400">JNE — Voto informado (presidente y vicepresidentes)</a>.
                        </p>
                        <p class="pt-1 font-medium text-zinc-600 dark:text-zinc-300">
                            Desarrollado por
                            <a href="https://factosysperu.com" target="_blank" rel="noopener noreferrer"
                                class="text-red-700 underline decoration-red-300 underline-offset-2 hover:text-red-800 dark:text-red-400">factosysperu.com</a>
                        </p>
                    </div>
                </footer>
            </template>
        </main>

        <!-- ═══ CTA FLOTANTE MOBILE ═══════════════════════════════════════ -->
        <div
            v-if="campaign && !legacyLocked"
            class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-2xl backdrop-blur sm:hidden dark:border-zinc-800 dark:bg-zinc-950/95"
        >
            <!-- chip candidato (si votó) -->
            <div v-if="votedOk && selectedCandidate" class="mb-2 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="relative shrink-0">
                    <img v-if="selectedCandidate.photo_url" :src="selectedCandidate.photo_url" alt="" class="h-7 w-7 rounded-full border border-zinc-200 object-cover" />
                    <span v-else class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-200 text-[9px] font-bold text-zinc-600">{{ initials(selectedCandidate.name) }}</span>
                    <img v-if="selectedCandidate.party_logo_url" :src="selectedCandidate.party_logo_url" alt="" class="absolute -right-0.5 -bottom-0.5 h-3.5 w-3.5 rounded-full border border-white bg-white object-contain" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[10px] font-semibold text-emerald-800 dark:text-emerald-200">
                        Tu voto: {{ selectedCandidate.name }}
                    </p>
                </div>
                <svg class="size-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold text-white shadow-lg transition-colors active:scale-[0.98]"
                :class="votedOk ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'"
                @click="showModal = true"
            >
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path v-if="!votedOk" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ votedOk ? 'Cambiar mi voto' : 'Participar en el sondeo' }}
            </button>
        </div>

        <!-- Barra legacy (solo móvil): ya votó con sistema antiguo -->
        <div
            v-if="campaign && legacyLocked"
            class="fixed bottom-0 left-0 right-0 z-40 border-t border-amber-200 bg-amber-50 px-4 py-3 text-center text-xs font-medium text-amber-900 sm:hidden dark:border-amber-900 dark:bg-amber-950/80 dark:text-amber-200"
        >
            Ya participaste antes en este dispositivo (registro anterior). No se puede cambiar el voto desde aquí. Gracias.
        </div>

        <!-- ═══ MODAL DE VOTACIÓN ════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showModal && !legacyLocked"
                    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
                    role="presentation"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"
                        aria-hidden="true"
                        @click="showModal = false"
                    />

                    <!-- Hoja / diálogo -->
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                        enter-to-class="translate-y-0 sm:scale-100 sm:opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0 sm:scale-100 sm:opacity-100"
                        leave-to-class="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                    >
                        <div
                            v-if="showModal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="modal-title"
                            class="relative z-10 flex max-h-[100dvh] w-full flex-col rounded-t-3xl border border-zinc-200 bg-white shadow-2xl sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl dark:border-zinc-700 dark:bg-zinc-900"
                            @click.stop
                        >
                            <!-- Handle mobile -->
                            <div class="mx-auto mt-2.5 mb-1 h-1 w-10 shrink-0 rounded-full bg-zinc-300 sm:hidden dark:bg-zinc-600" aria-hidden="true" />

                            <!-- Cabecera modal -->
                            <div class="flex shrink-0 items-center justify-between border-b border-zinc-100 px-4 py-3 sm:px-6 dark:border-zinc-800">
                                <div>
                                    <h2 id="modal-title" class="text-base font-bold text-zinc-900 sm:text-lg dark:text-zinc-100">
                                        {{ votedOk ? 'Cambiar mi voto' : '¿Por quién votarías hoy?' }}
                                    </h2>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ votedOk ? 'Solo cambios desde este dispositivo, con espera entre cambios.' : 'Una participación por dispositivo; no repitas voto desde otros equipos.' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="-mr-1 flex h-9 w-9 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                    aria-label="Cerrar"
                                    @click="showModal = false"
                                >
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Banner "ya votaste" -->
                            <div
                                v-if="votedOk && selectedCandidate"
                                class="mx-4 mt-3 flex shrink-0 items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 sm:mx-6 dark:border-emerald-900 dark:bg-emerald-950/40"
                            >
                                <svg class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                                        Tu voto actual: <span class="font-bold">{{ selectedCandidate.name }}</span>
                                    </p>
                                    <p class="text-[10px] text-emerald-700/80 dark:text-emerald-300/80">Selecciona otro candidato para cambiar.</p>
                                </div>
                            </div>

                            <!-- Buscador: sticky en móvil para que no quede tapado por el teclado al hacer scroll -->
                            <div
                                class="sticky top-0 z-20 mx-4 mt-2 shrink-0 border-b border-zinc-100 bg-white pb-2 pt-1 sm:static sm:z-0 sm:mx-6 sm:border-0 sm:bg-transparent sm:pb-0 sm:pt-0 dark:border-zinc-800 dark:bg-zinc-900 sm:dark:bg-transparent"
                            >
                                <label for="sondeo-buscar" class="sr-only">Buscar candidato o partido</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input
                                        id="sondeo-buscar"
                                        v-model="candidateSearch"
                                        type="search"
                                        enterkeyhint="search"
                                        autocomplete="off"
                                        placeholder="Buscar por nombre o partido…"
                                        class="w-full rounded-xl border-2 border-zinc-200 bg-zinc-50 py-3.5 pl-10 pr-10 text-base font-medium text-zinc-900 placeholder:text-zinc-400 focus:border-red-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 sm:text-sm dark:placeholder:text-zinc-500 dark:focus:border-red-500"
                                        @focus="onSearchFocus"
                                    />
                                    <button
                                        v-if="candidateSearch.trim()"
                                        type="button"
                                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700"
                                        aria-label="Limpiar búsqueda"
                                        @click="candidateSearch = ''"
                                    >
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                                    En móvil, desplaza la lista hacia arriba si el teclado tapa los resultados.
                                </p>
                            </div>

                            <!-- Lista candidatos (scrollable) + padding extra cuando hay teclado -->
                            <form
                                class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 pt-2 pb-2 sm:px-6"
                                :style="{ paddingBottom: modalKeyboardPad ? `${modalKeyboardPad}px` : undefined }"
                                @submit.prevent="submitVote"
                            >
                                <!-- Honeypot -->
                                <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                                    <input v-model="honeypot" type="text" name="company" tabindex="-1" autocomplete="off" />
                                    <input v-model="honeypot2" type="text" name="website" tabindex="-1" autocomplete="off" />
                                </div>

                                <fieldset :disabled="submitting" class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:grid-cols-2">
                                    <legend class="sr-only">Selecciona un candidato</legend>

                                    <p
                                        v-if="filteredCandidates.length === 0"
                                        class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-8 text-center text-sm text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400"
                                        role="status"
                                    >
                                        No hay candidatos que coincidan con «{{ candidateSearch.trim() }}». Prueba otro nombre o partido.
                                    </p>

                                    <label
                                        v-for="c in filteredCandidates"
                                        :key="c.id"
                                        class="relative flex cursor-pointer items-center gap-3 rounded-2xl border p-3 transition-all duration-150 active:scale-[0.98]"
                                        :class="selectedId === c.id
                                            ? 'border-red-500 bg-red-50 shadow-sm ring-1 ring-red-500 dark:border-red-600 dark:bg-red-950/30'
                                            : 'border-zinc-200 hover:border-red-300 hover:bg-red-50/30 dark:border-zinc-700 dark:hover:border-red-700 dark:hover:bg-red-950/10'"
                                    >
                                        <input
                                            v-model.number="selectedId"
                                            type="radio"
                                            name="candidate"
                                            :value="c.id"
                                            class="sr-only"
                                        />

                                        <!-- Avatar + logo (más grandes en móvil) -->
                                        <div class="relative shrink-0">
                                            <img
                                                v-if="c.photo_url"
                                                :src="c.photo_url"
                                                :alt="c.name"
                                                class="h-[4.5rem] w-[4.5rem] rounded-full border-2 object-cover object-top shadow-md sm:h-14 sm:w-14"
                                                :class="selectedId === c.id ? 'border-red-400 ring-2 ring-red-200' : 'border-zinc-200 dark:border-zinc-600'"
                                                loading="lazy"
                                            />
                                            <span
                                                v-else
                                                class="flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-full border-2 border-dashed border-zinc-300 bg-zinc-100 text-sm font-bold text-zinc-500 sm:h-14 sm:w-14 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                                            >{{ initials(c.name) }}</span>
                                            <img
                                                v-if="c.party_logo_url"
                                                :src="c.party_logo_url"
                                                alt=""
                                                class="absolute -right-0.5 -bottom-0.5 h-8 w-8 rounded-full border-2 border-white bg-white object-contain shadow-md sm:h-7 sm:w-7 dark:border-zinc-800 dark:bg-zinc-800"
                                                loading="lazy"
                                            />
                                        </div>

                                        <!-- Nombre + partido -->
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold leading-snug text-zinc-800 dark:text-zinc-200">
                                                {{ c.name }}
                                            </p>
                                            <p class="mt-1 line-clamp-2 text-[10px] leading-tight text-zinc-500 dark:text-zinc-400">
                                                {{ c.party_name }}
                                            </p>
                                        </div>

                                        <!-- Check seleccionado -->
                                        <span
                                            v-if="selectedId === c.id"
                                            class="shrink-0 rounded-full bg-red-500 p-0.5"
                                            aria-hidden="true"
                                        >
                                            <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    </label>
                                </fieldset>

                                <!-- Refuerzo en modal (la alerta arriba es la principal) -->
                                <p
                                    v-if="errorMessage"
                                    class="col-span-full mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200"
                                    role="alert"
                                >
                                    {{ errorMessage }}
                                </p>
                            </form>

                            <!-- Footer fijo modal -->
                            <div class="shrink-0 border-t border-zinc-100 px-4 py-3 sm:px-6 dark:border-zinc-800">
                                <button
                                    type="button"
                                    :disabled="selectedId == null || submitting"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold text-white shadow transition-colors active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="votedOk ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'"
                                    @click="submitVote"
                                >
                                    <svg v-if="submitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{
                                        submitting ? 'Enviando…'
                                        : selectedId == null ? 'Selecciona un candidato'
                                        : votedOk ? 'Guardar cambio de voto'
                                        : 'Enviar mi opinión'
                                    }}
                                </button>
                                <p class="mt-2 text-center text-[10px] text-zinc-400">
                                    Participación anónima · sin registro · sin datos personales
                                </p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
        <SuggestionDialog v-if="campaign" />
    </div>
</template>
