<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn('hover:bg-accent/50 data-[state=selected]:bg-muted border-b border-border/50 transition-colors odd:bg-muted/20 dark:odd:bg-muted/20', props.class)"
  >
    <slot />
  </tr>
</template>
