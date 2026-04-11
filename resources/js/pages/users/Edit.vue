<script setup lang="ts">
defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
    };
}>();
</script>

<template>
    <div>
        <h1>Edit User</h1>
    </div>
</template>
