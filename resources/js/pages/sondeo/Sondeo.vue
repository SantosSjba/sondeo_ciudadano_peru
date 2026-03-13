<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed } from 'vue';
import SondeoDisclaimer from '@/modules/sondeo/components/SondeoDisclaimer.vue';
import AdSlot from '@/modules/sondeo/components/AdSlot.vue';
import VoteThermometer, {
    type CandidateBar,
} from '@/modules/sondeo/components/VoteThermometer.vue';

const props = defineProps<{
    campaign: {
        id: number;
        slug: string;
        title: string;
        description: string | null;
    } | null;
    candidates: { id: number; name: string; short_label: string | null }[];
}>();

const pageLoadAt = ref(Date.now());
const selectedId = ref<number | null>(null);
const honeypot = ref('');
const submitting = ref(false);
const votedOk = ref(false);
const errorCode = ref<string | null>(null);
const results = ref<{ candidates: CandidateBar[]; total: number }>({
    candidates: [],
    total: 0,
});
const resultsLoading = ref(true);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const csrf = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

async function fetchResults() {
    if (!props.campaign) {
        resultsLoading.value = false;
        return;
    }
    try {
        const r = await fetch(
            `/api/sondeo/results?campaign=${encodeURIComponent(props.campaign.slug)}`,
            { headers: { Accept: 'application/json' } },
        );
        if (!r.ok) return;
        const data = await r.json();
        results.value = {
            candidates: data.candidates ?? [],
            total: data.total ?? 0,
        };
    } finally {
        resultsLoading.value = false;
    }
}

async function submitVote() {
    if (!props.campaign || selectedId.value == null) return;
    errorCode.value = null;
    submitting.value = true;
    const elapsed = Date.now() - pageLoadAt.value;
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
                client_elapsed_ms: elapsed,
            }),
        });
        const data = await r.json().catch(() => ({}));
        if (r.ok && data.ok) {
            votedOk.value = true;
            await fetchResults();
        } else {
            errorCode.value = data.code ?? 'error';
        }
    } finally {
        submitting.value = false;
    }
}

const errorMessage = computed(() => {
    switch (errorCode.value) {
        case 'already_voted':
            return 'Ya registramos una participación desde este entorno. Solo se cuenta una por sondeo.';
        case 'too_fast':
            return 'Espera un momento y vuelve a intentar (protección anti-bots).';
        case 'invalid':
            return 'No se pudo validar el envío.';
        case 'invalid_candidate':
            return 'Opción no válida.';
        default:
            return errorCode.value ? 'No se pudo registrar. Intenta de nuevo.' : null;
    }
});

onMounted(() => {
    fetchResults();
    pollTimer = setInterval(fetchResults, 12000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <Head :title="campaign?.title ?? 'Sondeo ciudadano'" />

    <div
        class="min-h-screen bg-gradient-to-b from-zinc-50 to-white text-zinc-900 dark:from-zinc-950 dark:to-zinc-900 dark:text-zinc-100"
    >
        <header
            class="border-b border-zinc-200/80 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90"
        >
            <div class="mx-auto flex max-w-3xl flex-col gap-2 px-4 py-6 sm:px-6">
                <p class="text-xs font-medium tracking-wider text-red-700 uppercase dark:text-red-400">
                    Perú · participación anónima
                </p>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    {{ campaign?.title ?? 'Sondeo no disponible' }}
                </h1>
                <p
                    v-if="campaign?.description"
                    class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400"
                >
                    {{ campaign.description }}
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <template v-if="!campaign">
                <p class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    No hay un sondeo activo. Configura la campaña en base de datos y ejecuta el seeder.
                </p>
            </template>

            <template v-else>
                <SondeoDisclaimer class="mb-8" />

                <AdSlot slot-id="sondeo-top" class="mb-10" label="Publicidad (superior)" />

                <div class="grid gap-10 lg:grid-cols-2 lg:gap-8">
                    <section
                        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <h2 class="mb-4 text-lg font-semibold">¿Por quién votarías hoy?</h2>
                        <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                            Un voto anónimo por dispositivo/navegador en este sondeo. No pedimos datos
                            personales.
                        </p>

                        <form class="space-y-4" @submit.prevent="submitVote">
                            <div
                                class="absolute -left-[9999px] h-0 w-0 overflow-hidden opacity-0"
                                aria-hidden="true"
                            >
                                <label>
                                    Empresa
                                    <input
                                        v-model="honeypot"
                                        type="text"
                                        name="company"
                                        tabindex="-1"
                                        autocomplete="off"
                                    />
                                </label>
                            </div>

                            <fieldset :disabled="votedOk || submitting" class="space-y-2">
                                <legend class="sr-only">Candidatos</legend>
                                <label
                                    v-for="c in candidates"
                                    :key="c.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 p-3 transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50/50 dark:border-zinc-600 dark:has-[:checked]:border-red-600 dark:has-[:checked]:bg-red-950/30"
                                >
                                    <input
                                        v-model.number="selectedId"
                                        type="radio"
                                        name="candidate"
                                        :value="c.id"
                                        class="size-4 accent-red-600"
                                    />
                                    <span class="text-sm font-medium">{{ c.name }}</span>
                                </label>
                            </fieldset>

                            <p
                                v-if="errorMessage"
                                class="text-sm text-red-600 dark:text-red-400"
                                role="alert"
                            >
                                {{ errorMessage }}
                            </p>
                            <p
                                v-if="votedOk"
                                class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                ¡Gracias! Tu participación quedó registrada de forma anónima.
                            </p>

                            <button
                                type="submit"
                                class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow hover:bg-red-700 disabled:opacity-50 sm:w-auto"
                                :disabled="selectedId == null || votedOk || submitting"
                            >
                                {{ submitting ? 'Enviando…' : 'Enviar mi opinión' }}
                            </button>
                        </form>

                        <SondeoDisclaimer compact class="mt-6" />
                    </section>

                    <section
                        class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <VoteThermometer
                            :candidates="results.candidates"
                            :total="results.total"
                            :loading="resultsLoading"
                        />
                    </section>
                </div>

                <AdSlot slot-id="sondeo-footer" class="mt-10" label="Publicidad (pie)" />

                <footer class="mt-12 border-t border-zinc-200 pt-8 text-center text-xs text-zinc-500 dark:border-zinc-800">
                    <p>
                        Transparencia: solo se almacenan totales por opción y una huella técnica
                        anónima para evitar duplicados (no vendemos datos personales).
                    </p>
                    <p class="mt-2">
                        Fases del producto: (1) voto + termómetro ✓ · (2) tendencias temporales ·
                        (3) refuerzo anti-fraude · (4) reportes para medios.
                    </p>
                </footer>
            </template>
        </main>
    </div>
</template>
