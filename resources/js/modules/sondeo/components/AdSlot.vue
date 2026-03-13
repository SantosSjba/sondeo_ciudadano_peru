<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        /** id único para futuros scripts de red publicitaria */
        slotId?: string;
    }>(),
    {
        label: 'Espacio publicitario',
        slotId: 'sondeo-inline-1',
    },
);
</script>

<template>
    <aside
        :data-ad-slot="slotId"
        class="flex min-h-[90px] flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 px-4 py-6 text-center dark:border-zinc-600 dark:bg-zinc-900/50"
        aria-label="Publicidad"
    >
        <span class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
            {{ label }}
        </span>
        <p class="mt-1 max-w-md text-xs text-zinc-400 dark:text-zinc-500">
            Aquí puedes integrar Google Ad Manager, tablero directo u otra red; el tráfico electoral
            puede monetizarse sin afectar el voto anónimo.
        </p>
    </aside>
</template>
