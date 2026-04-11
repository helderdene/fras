<script setup lang="ts">
type Props = {
    mqttConnected: boolean;
    reverbConnected: boolean;
    queueDepth: number;
};

defineProps<Props>();
</script>

<template>
    <div
        class="flex h-9 shrink-0 items-center gap-6 border-t border-border bg-muted px-4 dark:bg-muted/90 dark:backdrop-blur-sm"
    >
        <div class="flex items-center gap-1.5">
            <span
                class="size-1.5 shrink-0 rounded-full"
                :class="mqttConnected ? 'bg-emerald-500' : 'bg-red-500'"
                :aria-label="
                    'MQTT: ' + (mqttConnected ? 'connected' : 'disconnected')
                "
            />
            <span class="text-xs text-muted-foreground">MQTT</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span
                class="size-1.5 shrink-0 rounded-full"
                :class="reverbConnected ? 'bg-emerald-500' : 'bg-red-500'"
                :aria-label="
                    'Reverb: ' +
                    (reverbConnected ? 'connected' : 'disconnected')
                "
            />
            <span class="text-xs text-muted-foreground">Reverb</span>
        </div>
        <span class="text-xs text-muted-foreground">
            <span class="font-mono text-xs">{{ queueDepth }}</span> pending
        </span>
    </div>
</template>
