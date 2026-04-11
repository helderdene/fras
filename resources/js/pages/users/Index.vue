<script setup lang="ts">
defineProps<{
    users: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
}>();
</script>

<template>
    <div>
        <h1>Users</h1>
    </div>
</template>
