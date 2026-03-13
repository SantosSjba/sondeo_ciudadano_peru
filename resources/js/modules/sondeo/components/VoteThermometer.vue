<script setup lang="ts">
export type CandidateBar = {
    id: number;
    name: string;
    party_name: string | null;
    short_label: string | null;
    photo_url: string | null;
    party_logo_url: string | null;
    votes: number;
    percent: number;
};

withDefaults(
    defineProps<{ candidates: CandidateBar[]; total: number; loading?: boolean }>(),
    { loading: false },
);

const barColors = [
    'from-red-500 to-rose-400',
    'from-sky-500 to-blue-400',
    'from-emerald-500 to-teal-400',
    'from-violet-500 to-purple-400',
    'from-amber-500 to-yellow-400',
    'from-pink-500 to-fuchsia-400',
    'from-cyan-500 to-sky-400',
    'from-orange-500 to-amber-400',
    'from-teal-500 to-emerald-400',
    'from-indigo-500 to-violet-400',
    'from-rose-500 to-pink-400',
    'from-lime-500 to-green-400',
];
const dotColors = [
    'bg-red-500', 'bg-sky-500', 'bg-emerald-500', 'bg-violet-500',
    'bg-amber-500', 'bg-pink-500', 'bg-cyan-500', 'bg-orange-500',
    'bg-teal-500', 'bg-indigo-500', 'bg-rose-500', 'bg-lime-500',
];

function barGradient(i: number) { return barColors[i % barColors.length]; }
function dotColor(i: number) { return dotColors[i % dotColors.length]; }

function nameShort(name: string) {
    const parts = name.split(' ').filter(Boolean);
    if (parts.length <= 2) return name;
    return `${parts[0]} ${parts[1]}`;
}

const medals = ['🥇', '🥈', '🥉'];
</script>

<template>
    <section :aria-busy="loading">
        <!-- Cabecera -->
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                    Termómetro ciudadano
                </h2>
                <p class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400">
                    Ranking por votos · 🥇 🥈 🥉 primeros tres
                </p>
            </div>
            <span class="flex shrink-0 items-center gap-1.5 text-[11px] text-zinc-400 dark:text-zinc-500 sm:pt-5" aria-live="polite">
                <span class="inline-block size-1.5 rounded-full bg-emerald-500" aria-hidden="true" />
                <span v-if="loading" class="animate-pulse">actualizando…</span>
                <span v-else>{{ total.toLocaleString('es-PE') }} votos</span>
            </span>
        </div>

        <!-- Skeleton loader -->
        <template v-if="loading && candidates.length === 0">
            <ul class="space-y-2.5">
                <li v-for="n in 10" :key="n" class="flex items-center gap-2">
                    <div class="h-7 w-7 shrink-0 animate-pulse rounded-full bg-zinc-200 dark:bg-zinc-700" />
                    <div class="flex-1 space-y-1">
                        <div class="flex justify-between gap-2">
                            <div class="h-2.5 w-24 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700" />
                            <div class="h-2.5 w-8 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700" />
                        </div>
                        <div class="h-2 w-full animate-pulse rounded-full bg-zinc-200 dark:bg-zinc-700" />
                    </div>
                </li>
            </ul>
        </template>

        <!-- Sin datos -->
        <p v-else-if="!loading && total === 0"
            class="rounded-xl bg-zinc-100 py-6 text-center text-sm text-zinc-500 dark:bg-zinc-800">
            Sé el primero en participar.<br />
            <span class="text-xs text-zinc-400">Los resultados aparecerán aquí en tiempo real.</span>
        </p>

        <!-- Lista de candidatos -->
        <ul v-else class="space-y-2" aria-label="Resultados del sondeo">
            <li
                v-for="(c, i) in candidates"
                :key="c.id"
                class="flex items-center gap-2.5 rounded-xl transition-colors"
                :class="i < 3 ? 'bg-zinc-50/80 px-2 py-1 dark:bg-zinc-800/40' : ''"
            >
                <!-- Avatar + logo -->
                <div class="relative shrink-0">
                    <img
                        v-if="c.photo_url"
                        :src="c.photo_url"
                        :alt="c.name"
                        class="h-8 w-8 rounded-full border-2 object-cover object-top"
                        :class="i < 3 ? 'border-zinc-300 dark:border-zinc-500' : 'border-zinc-200 dark:border-zinc-600'"
                        loading="lazy"
                    />
                    <span
                        v-else
                        class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 text-[9px] font-bold text-zinc-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300"
                    >{{ c.name[0] }}</span>
                    <img
                        v-if="c.party_logo_url"
                        :src="c.party_logo_url"
                        alt=""
                        class="absolute -right-0.5 -bottom-0.5 h-4 w-4 rounded-full border-[1.5px] border-white bg-white object-contain dark:border-zinc-800"
                        loading="lazy"
                    />
                    <!-- medalla top 3 -->
                    <span
                        v-if="i < 3"
                        class="absolute -top-1 -left-1 text-[11px] leading-none"
                        aria-hidden="true"
                    >{{ medals[i] }}</span>
                </div>

                <!-- Barra + texto -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-1">
                        <span
                            class="truncate text-[11px] font-semibold leading-tight"
                            :class="i < 3 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300'"
                        >{{ nameShort(c.name) }}</span>
                        <span class="shrink-0 tabular-nums text-[11px] font-bold"
                            :class="i === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400'"
                        >{{ c.percent }}%</span>
                    </div>
                    <div
                        class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
                        role="progressbar"
                        :aria-valuenow="c.percent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`${c.name}: ${c.percent}%`"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r transition-[width] duration-700"
                            :class="barGradient(i)"
                            :style="{ width: `${Math.max(c.percent > 0 ? 2 : 0, Math.min(100, c.percent))}%` }"
                        />
                    </div>
                    <div class="mt-0.5 flex items-center gap-1">
                        <span :class="['inline-block size-1.5 shrink-0 rounded-full', dotColor(i)]" aria-hidden="true" />
                        <p class="truncate text-[9px] text-zinc-400 dark:text-zinc-500">{{ c.party_name }}</p>
                    </div>
                </div>

                <!-- Conteo -->
                <span class="w-9 shrink-0 text-right text-[10px] tabular-nums text-zinc-400 dark:text-zinc-500">
                    {{ c.votes > 999 ? `${(c.votes/1000).toFixed(1)}k` : c.votes.toLocaleString('es-PE') }}
                </span>
            </li>
        </ul>
    </section>
</template>
