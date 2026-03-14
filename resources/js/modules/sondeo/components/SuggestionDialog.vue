<script setup lang="ts">
import { ref } from 'vue';

const open = ref(false);
const message = ref('');
const email = ref('');
const honeypot = ref('');
const sending = ref(false);
const sent = ref(false);
const error = ref<string | null>(null);

function csrf() {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function submit() {
    error.value = null;
    const m = message.value.trim();
    if (m.length < 5) {
        error.value = 'Escribe al menos unas palabras (mín. 5 caracteres).';
        return;
    }
    sending.value = true;
    try {
        const r = await fetch('/api/sondeo/suggestion', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: m,
                contact_email: email.value.trim() || null,
                company: honeypot.value,
            }),
        });
        const data = await r.json().catch(() => ({}));
        if (r.ok && data.ok) {
            sent.value = true;
            message.value = '';
            email.value = '';
        } else if (r.status === 429) {
            error.value = 'Demasiados envíos. Espera un minuto e intenta de nuevo.';
        } else {
            error.value = 'No se pudo enviar. Intenta en unos momentos.';
        }
    } finally {
        sending.value = false;
    }
}

function close() {
    open.value = false;
    setTimeout(() => {
        sent.value = false;
        error.value = null;
    }, 300);
}
</script>

<template>
    <!-- Botón flotante compacto -->
    <button
        type="button"
        class="fixed bottom-24 left-3 z-[35] flex h-11 w-11 items-center justify-center rounded-full border border-violet-300 bg-violet-100 text-violet-800 shadow-lg transition hover:bg-violet-200 active:scale-95 sm:bottom-8 sm:left-4 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-200 dark:hover:bg-violet-900"
        aria-label="Sugerencias de mejora"
        title="Sugerencias"
        @click="open = true"
    >
        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
    </button>

    <Teleport to="body">
        <div
            v-show="open"
            class="fixed inset-0 z-[95] flex items-end justify-center p-3 sm:items-center"
            role="presentation"
            @click.self="close"
        >
            <div class="absolute inset-0 bg-black/45 backdrop-blur-[1px]" aria-hidden="true" @click="close" />
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="sug-title"
                class="relative z-10 w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-4 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                @click.stop
            >
                <div class="mb-3 flex items-start justify-between gap-2">
                    <div>
                        <h2 id="sug-title" class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                            Tu sugerencia
                        </h2>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            Ayúdanos a mejorar el sondeo. Lo leemos con gusto (sin publicar tu mensaje).
                        </p>
                    </div>
                    <button type="button" class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Cerrar" @click="close">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div v-if="sent" class="rounded-xl bg-emerald-50 py-6 text-center text-sm text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                    <p class="font-semibold">¡Gracias!</p>
                    <p class="mt-1 text-xs opacity-90">Tu sugerencia quedó registrada.</p>
                    <button type="button" class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-800" @click="close">
                        Cerrar
                    </button>
                </div>

                <form v-else class="space-y-3" @submit.prevent="submit">
                    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                        <input v-model="honeypot" type="text" name="company" tabindex="-1" autocomplete="off" />
                    </div>
                    <div>
                        <label for="sug-msg" class="sr-only">Sugerencia</label>
                        <textarea
                            id="sug-msg"
                            v-model="message"
                            rows="4"
                            maxlength="2000"
                            placeholder="¿Qué mejorarías? (diseño, claridad, velocidad, accesibilidad…)"
                            class="w-full resize-y rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>
                    <div>
                        <label for="sug-mail" class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Correo (opcional, por si queremos responderte)</label>
                        <input
                            id="sug-mail"
                            v-model="email"
                            type="email"
                            autocomplete="email"
                            placeholder="tu@correo.com"
                            class="mt-1 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>
                    <p v-if="error" class="text-xs text-red-600 dark:text-red-400" role="alert">{{ error }}</p>
                    <button
                        type="submit"
                        :disabled="sending"
                        class="w-full rounded-xl bg-violet-600 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50"
                    >
                        {{ sending ? 'Enviando…' : 'Enviar sugerencia' }}
                    </button>
                </form>
            </div>
        </div>
    </Teleport>
</template>
