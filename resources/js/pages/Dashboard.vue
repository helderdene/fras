<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useConnectionStatus } from '@laravel/echo-vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ConnectionBanner from '@/components/ConnectionBanner.vue';
import DashboardTopNav from '@/components/DashboardTopNav.vue';
import StatusBar from '@/components/StatusBar.vue';
import type { Camera, RecognitionEvent } from '@/types';

interface DashboardCamera extends Camera {
    today_recognition_count: number;
}

interface TodayStats {
    recognitions: number;
    critical: number;
    warnings: number;
    enrolled: number;
}

interface MapboxConfig {
    token: string;
    darkStyle: string;
    lightStyle: string;
}

type Props = {
    cameras: DashboardCamera[];
    todayStats: TodayStats;
    recentEvents: RecognitionEvent[];
    mapbox: MapboxConfig;
};

defineProps<Props>();

const leftRailOpen = ref(true);
const rightFeedOpen = ref(true);
const queueDepth = ref(0);

const connectionStatus = useConnectionStatus();
const isReverbConnected = computed(
    () => connectionStatus.value === 'connected',
);
const isMqttConnected = isReverbConnected;

let queuePollInterval: ReturnType<typeof setInterval> | null = null;

async function fetchQueueDepth() {
    try {
        const res = await fetch('/api/queue-depth');
        const data = await res.json();
        queueDepth.value = data.depth;
    } catch {
        /* silently ignore */
    }
}

onMounted(() => {
    fetchQueueDepth();
    queuePollInterval = setInterval(fetchQueueDepth, 30000);
});

onUnmounted(() => {
    if (queuePollInterval) {
        clearInterval(queuePollInterval);
    }
});
</script>

<template>
    <Head title="Command Center" />
    <DashboardTopNav
        :left-rail-open="leftRailOpen"
        :right-feed-open="rightFeedOpen"
        @toggle-left-rail="leftRailOpen = !leftRailOpen"
        @toggle-right-feed="rightFeedOpen = !rightFeedOpen"
    />
    <ConnectionBanner :visible="!isReverbConnected" />
    <div class="flex flex-1 overflow-hidden">
        <!-- Left rail placeholder (Plan 03 fills this) -->
        <aside
            v-show="leftRailOpen"
            class="w-[280px] shrink-0 overflow-y-auto border-r border-border bg-muted/50 transition-all duration-200"
        >
            <!-- CameraRail will go here in Plan 03 -->
        </aside>
        <!-- Center map placeholder (Plan 02 fills this) -->
        <main class="flex-1 overflow-hidden">
            <!-- DashboardMap will go here in Plan 02 -->
        </main>
        <!-- Right alert feed placeholder (Plan 03 fills this) -->
        <aside
            v-show="rightFeedOpen"
            class="w-[360px] shrink-0 overflow-y-auto border-l border-border transition-all duration-200"
        >
            <!-- DashboardAlertFeed will go here in Plan 03 -->
        </aside>
    </div>
    <StatusBar
        :mqtt-connected="isMqttConnected"
        :reverb-connected="isReverbConnected"
        :queue-depth="queueDepth"
    />
</template>
