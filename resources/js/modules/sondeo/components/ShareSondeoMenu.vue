<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    /** URL canónica del sondeo (ej. https://votolibre.factosysperu.com/) */
    url: string;
    title: string;
    description?: string | null;
}>();

const open = ref(false);
const copied = ref(false);
const rootRef = ref<HTMLElement | null>(null);

const shareText = computed(() => {
    const d = props.description?.trim();
    const line = d ? `${props.title} — ${d}` : props.title;
    return `${line}\n${props.url}`;
});

const encodedUrl = computed(() => encodeURIComponent(props.url));
const encodedText = computed(() => encodeURIComponent(shareText.value));

const channels = computed(() => [
    {
        id: 'whatsapp',
        label: 'WhatsApp',
        sub: 'Mensaje o estado',
        href: `https://wa.me/?text=${encodedText.value}`,
        bg: 'bg-[#25D366]',
        icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
    },
    {
        id: 'facebook',
        label: 'Facebook',
        sub: 'Publicar o mensaje',
        href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl.value}&quote=${encodeURIComponent(props.title)}`,
        bg: 'bg-[#1877F2]',
        icon: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    },
    {
        id: 'x',
        label: 'X',
        sub: 'Antes Twitter',
        href: `https://twitter.com/intent/tweet?url=${encodedUrl.value}&text=${encodeURIComponent(props.title)}`,
        bg: 'bg-zinc-900 dark:bg-zinc-100',
        icon: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    },
    {
        id: 'telegram',
        label: 'Telegram',
        sub: 'Canal o chat',
        href: `https://t.me/share/url?url=${encodedUrl.value}&text=${encodeURIComponent(props.title)}`,
        bg: 'bg-[#26A5E4]',
        icon: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z',
    },
    {
        id: 'linkedin',
        label: 'LinkedIn',
        sub: 'Red profesional',
        href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl.value}`,
        bg: 'bg-[#0A66C2]',
        icon: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
    },
    {
        id: 'tiktok',
        label: 'TikTok',
        sub: 'Copia y pega en la app',
        href: '#copy-tiktok',
        bg: 'bg-gradient-to-br from-[#00f2ea] to-[#ff0050]',
        icon: 'M12.525.02c1.31-.02 2.61-.01 3.918-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
    },
]);

function close() {
    open.value = false;
    copied.value = false;
}

function toggle() {
    open.value = !open.value;
    if (!open.value) copied.value = false;
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2200);
    } catch {
        copied.value = false;
    }
}

async function copyForTikTok() {
    const msg = `${shareText.value}\n\n(Pégalo en descripción o comentario de TikTok)`;
    try {
        await navigator.clipboard.writeText(msg);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2200);
    } catch {
        copied.value = false;
    }
}

async function tryNativeShare() {
    if (typeof navigator === 'undefined' || !navigator.share) return false;
    try {
        await navigator.share({
            title: props.title,
            text: shareText.value,
            url: props.url,
        });
        close();
        return true;
    } catch (e) {
        if ((e as Error).name === 'AbortError') return true;
        return false;
    }
}

function onDocClick(e: MouseEvent) {
    if (!open.value || !rootRef.value) return;
    const t = e.target as Node;
    if (!rootRef.value.contains(t)) close();
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') close();
}

onMounted(() => {
    document.addEventListener('click', onDocClick, true);
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    document.removeEventListener('click', onDocClick, true);
    document.removeEventListener('keydown', onKey);
});

function openChannel(c: (typeof channels.value)[0], e: Event) {
    if (c.id === 'tiktok') {
        e.preventDefault();
        copyForTikTok();
        return;
    }
    close();
}
</script>

<template>
    <div ref="rootRef" class="relative">
        <!-- Misma escala que el CTA «Cambiar voto» del header: rounded-lg px-4 py-2 text-sm font-semibold shadow -->
        <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-900 shadow transition hover:border-violet-400 hover:bg-violet-100 active:scale-[0.98] dark:border-violet-800 dark:bg-violet-950/60 dark:text-violet-100 dark:hover:bg-violet-900/70 max-sm:aspect-square max-sm:min-w-[2.5rem] max-sm:px-0 sm:min-w-[11.5rem] sm:px-4"
            aria-haspopup="true"
            :aria-expanded="open"
            aria-label="Compartir sondeo"
            @click.stop="toggle"
        >
            <svg class="size-[1.125rem] shrink-0 text-violet-700 dark:text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <span class="hidden sm:inline">Compartir</span>
        </button>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute right-0 top-full z-50 mt-2 w-[min(100vw-1.5rem,20rem)] origin-top-right rounded-2xl border border-zinc-200/90 bg-white p-3 shadow-xl ring-1 ring-black/5 dark:border-zinc-700 dark:bg-zinc-900 dark:ring-white/10 sm:w-[22rem] sm:p-4"
                role="dialog"
                aria-label="Opciones para compartir"
                @click.stop
            >
                <p class="mb-3 border-b border-zinc-100 pb-2 text-center text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Compartir Voto Libre
                </p>

                <button
                    v-if="typeof navigator !== 'undefined' && typeof navigator.share === 'function'"
                    type="button"
                    class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 py-3 text-sm font-bold text-white shadow-md transition hover:from-violet-500 hover:to-fuchsia-500"
                    @click="tryNativeShare()"
                >
                    <svg class="size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Compartir del sistema
                </button>

                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <template v-for="c in channels" :key="c.id">
                        <a
                            v-if="c.id !== 'tiktok'"
                            :href="c.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex flex-col items-center gap-1 rounded-xl border border-zinc-100 bg-zinc-50/80 p-2.5 text-center transition hover:border-zinc-200 hover:bg-white hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:bg-zinc-800"
                            @click="openChannel(c, $event)"
                        >
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-full text-white shadow-md transition group-active:scale-95 sm:h-12 sm:w-12"
                                :class="c.bg"
                            >
                                <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="c.icon" />
                                </svg>
                            </span>
                            <span class="max-w-full truncate text-[10px] font-bold leading-tight text-zinc-800 dark:text-zinc-100">{{ c.label }}</span>
                            <span class="line-clamp-2 min-h-[1.25rem] text-[9px] leading-tight text-zinc-500 dark:text-zinc-400">{{ c.sub }}</span>
                        </a>
                        <button
                            v-else
                            type="button"
                            class="group flex flex-col items-center gap-1 rounded-xl border border-zinc-100 bg-zinc-50/80 p-2.5 text-center transition hover:border-zinc-200 hover:bg-white hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:bg-zinc-800"
                            @click="openChannel(c, $event)"
                        >
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-full text-white shadow-md transition group-active:scale-95 sm:h-12 sm:w-12"
                                :class="c.bg"
                            >
                                <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="c.icon" />
                                </svg>
                            </span>
                            <span class="max-w-full truncate text-[10px] font-bold text-zinc-800 dark:text-zinc-100">{{ c.label }}</span>
                            <span class="text-[9px] text-zinc-500 dark:text-zinc-400">{{ c.sub }}</span>
                        </button>
                    </template>
                </div>

                <button
                    type="button"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 py-2.5 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                    @click="copyLink"
                >
                    <svg v-if="!copied" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg v-else class="size-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ copied ? '¡Enlace copiado!' : 'Copiar enlace' }}
                </button>
                <p class="mt-2 text-center text-[10px] text-zinc-400 dark:text-zinc-500">
                    {{ url.replace(/^https?:\/\//, '') }}
                </p>
            </div>
        </Transition>
    </div>
</template>
