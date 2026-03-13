<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

const JNE_VOTO_INFORMADO = 'https://votoinformado.jne.gob.pe/presidente-vicepresidentes';

const open = ref(false);
const dismissedKey = 'sondeo_legal_notice_dismissed_v1';

onMounted(() => {
    if (typeof sessionStorage !== 'undefined' && !sessionStorage.getItem(dismissedKey)) {
        open.value = true;
    }
});

watch(open, (v) => {
    if (!v && typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem(dismissedKey, '1');
    }
});

function close() {
    open.value = false;
}
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 rounded-full border border-amber-300/80 bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-900 shadow-sm hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100 dark:hover:bg-amber-950"
        aria-haspopup="dialog"
        :aria-expanded="open"
        @click="open = true"
    >
        <span class="text-amber-600 dark:text-amber-400" aria-hidden="true">ⓘ</span>
        Aviso legal
    </button>

    <Teleport to="body">
        <div
            v-show="open"
            class="fixed inset-0 z-[100] flex items-end justify-center p-3 sm:items-center sm:p-4"
            role="presentation"
            @click.self="close"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1px]" aria-hidden="true" @click="close" />
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="legal-notice-title"
                class="relative z-10 w-full max-w-sm rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-600 dark:bg-zinc-900"
                @click.stop
            >
                <div class="flex max-h-[min(85vh,480px)] flex-col">
                    <div class="flex shrink-0 items-start justify-between gap-2 border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
                        <h2 id="legal-notice-title" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Sondeo ciudadano en línea (no oficial)
                        </h2>
                        <button
                            type="button"
                            class="rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                            aria-label="Cerrar"
                            @click="close"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto px-3 py-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                        <p>
                            Los resultados se basan solo en la participación voluntaria de quienes usan este sitio.
                            <strong class="text-zinc-800 dark:text-zinc-200">No constituyen encuesta científica ni intención de voto de toda la población</strong>,
                            ni tienen validez electoral. El objetivo es un indicador público del pulso entre participantes de internet.
                        </p>
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50/90 p-2.5 dark:border-emerald-900 dark:bg-emerald-950/40">
                            <strong class="text-emerald-900 dark:text-emerald-100">Compromiso ciudadano (ética)</strong><br />
                            Te pedimos <strong>no repetir votos</strong> desde otros dispositivos ni intentar falsear el sondeo: <strong>una participación por dispositivo/red</strong>.
                            El sistema bloquea nuevos intentos si ya registramos tu participación, para que el termómetro refleje mejor la voluntad de quienes participan con honestidad.
                            Gracias por cuidar la transparencia de esta herramienta.
                        </p>
                        <p class="mt-3 rounded-lg border border-sky-200 bg-sky-50/80 p-2.5 dark:border-sky-900 dark:bg-sky-950/40">
                            <strong class="text-zinc-800 dark:text-zinc-200">Información sobre candidatos</strong><br />
                            Puedes informarte en el sitio oficial del JNE
                            <a
                                :href="JNE_VOTO_INFORMADO"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold text-red-700 underline decoration-red-300 underline-offset-2 hover:text-red-800 dark:text-red-400"
                            >Voto informado — Presidente y vicepresidentes</a>
                            (datos y propuestas según el Jurado Nacional de Elecciones).
                        </p>
                    </div>
                    <div class="shrink-0 border-t border-zinc-100 px-3 py-2 dark:border-zinc-800">
                        <button
                            type="button"
                            class="w-full rounded-lg bg-zinc-900 py-2 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                            @click="close"
                        >
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
