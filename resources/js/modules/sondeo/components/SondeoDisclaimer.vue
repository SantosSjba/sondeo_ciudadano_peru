<script setup lang="ts">
defineProps<{
    compact?: boolean;
}>();
</script>

<template>
    <div
        class="rounded-xl border border-amber-200/80 bg-amber-50/90 p-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
        role="note"
    >
        <p class="font-semibold">Sondeo ciudadano en línea (no oficial)</p>
        <p v-if="!compact" class="mt-2 leading-relaxed">
            Los resultados se basan solo en la participación voluntaria de quienes usan este sitio.
            <strong>No constituyen encuesta científica ni intención de voto de toda la población</strong>,
            ni tienen validez electoral. El objetivo es un indicador público del pulso entre
            participantes de internet.
        </p>
        <p v-else class="mt-1 text-xs opacity-90">
            Voluntario y no representativo de todo el país.
        </p>
    </div>
</template>
