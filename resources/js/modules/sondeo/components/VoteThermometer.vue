<script setup lang="ts">
export type CandidateBar = {
    id: number;
    name: string;
    short_label: string | null;
    votes: number;
    percent: number;
};

const props = defineProps<{
    candidates: CandidateBar[];
    total: number;
    loading?: boolean;
}>();

const colors = [
    'bg-red-500',
    'bg-sky-500',
    'bg-emerald-500',
    'bg-violet-500',
    'bg-amber-500',
    'bg-pink-500',
    'bg-cyan-500',
    'bg-orange-500',
];
</script>

<template>
    <section aria-live="polite" :aria-busy="loading">
        <div class="mb-3 flex items-baseline justify-between gap-2">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                Termómetro ciudadano
            </h2>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ loading ? '…' : '' }} {{ total.toLocaleString('es-PE') }} participaciones
            </span>
        </div>
        <ul class="space-y-3">
            <li
                v-for="(c, i) in candidates"
                :key="c.id"
                class="rounded-lg bg-zinc-100/80 p-3 dark:bg-zinc-800/80"
            >
                <div class="mb-1 flex justify-between gap-2 text-sm">
                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{
                        c.short_label || c.name
                    }}</span>
                    <span class="tabular-nums text-zinc-600 dark:text-zinc-400">
                        {{ c.percent }}% · {{ c.votes.toLocaleString('es-PE') }}
                    </span>
                </div>
                <div
                    class="h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
                    role="progressbar"
                    :aria-valuenow="c.percent"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full min-w-0 rounded-full transition-[width] duration-500 ease-out"
                        :class="colors[i % colors.length]"
                        :style="{ width: `${Math.min(100, c.percent)}%` }"
                    />
                </div>
                <p v-if="c.short_label" class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-500">
                    {{ c.name }}
                </p>
            </li>
        </ul>
        <p v-if="!loading && total === 0" class="mt-4 text-center text-sm text-zinc-500">
            Aún no hay participaciones. Sé el primero en opinar (de forma anónima).
        </p>
    </section>
</template>
