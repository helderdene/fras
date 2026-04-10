<script setup lang="ts">
import { Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        currentPhotoUrl?: string;
        maxSizeBytes?: number;
        accept?: string;
    }>(),
    {
        currentPhotoUrl: undefined,
        maxSizeBytes: 10485760,
        accept: 'image/jpeg,image/png',
    },
);

const preview = ref<string | null>(null);
const error = ref<string | null>(null);
const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const hasNewFile = ref(false);

const displayUrl = computed(() => {
    if (hasNewFile.value && preview.value) {
        return preview.value;
    }

    return props.currentPhotoUrl ?? null;
});

function onDragEnter(e: DragEvent): void {
    e.preventDefault();
    isDragOver.value = true;
}

function onDragOver(e: DragEvent): void {
    e.preventDefault();
}

function onDragLeave(): void {
    isDragOver.value = false;
}

function onDrop(e: DragEvent): void {
    e.preventDefault();
    isDragOver.value = false;
    const file = e.dataTransfer?.files[0];
    handleFile(file);
}

function onClick(): void {
    fileInputRef.value?.click();
}

function onFileSelect(e: Event): void {
    const file = (e.target as HTMLInputElement).files?.[0];
    handleFile(file);
}

function handleFile(file: File | undefined): void {
    if (!file) {
        return;
    }

    if (
        !file.type.startsWith('image/') ||
        !['image/jpeg', 'image/png'].includes(file.type)
    ) {
        error.value = 'Please select a JPEG or PNG image.';
        preview.value = null;
        hasNewFile.value = false;
        resetFileInput();

        return;
    }

    if (file.size > props.maxSizeBytes) {
        error.value = 'File is too large. Maximum upload size is 10MB.';
        preview.value = null;
        hasNewFile.value = false;
        resetFileInput();

        return;
    }

    error.value = null;
    hasNewFile.value = true;

    const reader = new FileReader();
    reader.onload = () => {
        preview.value = reader.result as string;
    };
    reader.readAsDataURL(file);

    const dt = new DataTransfer();
    dt.items.add(file);
    fileInputRef.value!.files = dt.files;
}

function removePhoto(): void {
    preview.value = null;
    hasNewFile.value = false;
    error.value = null;
    resetFileInput();
}

function resetFileInput(): void {
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}
</script>

<template>
    <div class="space-y-1">
        <!-- Dropzone area -->
        <div
            class="relative flex h-48 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed transition-colors"
            :class="{
                'border-primary bg-accent/50': isDragOver,
                'border-destructive': error,
                'border-border hover:border-muted-foreground/50':
                    !isDragOver && !error,
            }"
            @dragenter.prevent="onDragEnter"
            @dragover.prevent="onDragOver"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop"
            @click="onClick"
        >
            <!-- Preview state: show photo thumbnail -->
            <template v-if="displayUrl">
                <img
                    :src="displayUrl"
                    alt="Photo preview"
                    class="h-full w-full object-cover"
                />
                <!-- Replace overlay for edit mode -->
                <div
                    v-if="!hasNewFile && currentPhotoUrl"
                    class="absolute inset-0 flex flex-col items-center justify-center bg-black/50 opacity-0 transition-opacity hover:opacity-100"
                >
                    <Upload class="size-6 text-white" />
                    <span class="mt-1 text-sm text-white">Replace photo</span>
                </div>
                <!-- Remove button for new selections -->
                <button
                    v-if="hasNewFile"
                    type="button"
                    class="absolute top-2 right-2 flex size-6 items-center justify-center rounded-full bg-background/80 hover:bg-background"
                    @click.stop="removePhoto"
                >
                    <X class="size-4" />
                </button>
            </template>

            <!-- Empty state: show upload prompt -->
            <template v-else>
                <div class="flex flex-col items-center gap-2 text-center">
                    <Upload class="size-8 text-muted-foreground/50" />
                    <p class="text-sm text-muted-foreground">
                        Drag and drop a photo here, or click to browse
                    </p>
                </div>
            </template>
        </div>

        <!-- Constraint help text -->
        <p class="text-xs text-muted-foreground">
            JPEG or PNG, max 1MB after processing. Photos are automatically
            resized.
        </p>

        <!-- Inline client-side error -->
        <p v-if="error" class="text-sm text-destructive">
            {{ error }}
        </p>

        <!-- Hidden file input -->
        <input
            ref="fileInputRef"
            type="file"
            :name="name"
            :accept="accept"
            class="hidden"
            @change="onFileSelect"
        />
    </div>
</template>
