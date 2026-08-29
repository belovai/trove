<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{ files: [File[]] }>();

const input = ref<HTMLInputElement | null>(null);
const over = ref(false);

const pick = (event: Event): void => {
    const target = event.target as HTMLInputElement;

    emit('files', Array.from(target.files ?? []));

    // Cleared so dropping the same file twice in a row still fires `change`.
    target.value = '';
};

const drop = (event: DragEvent): void => {
    over.value = false;

    emit('files', Array.from(event.dataTransfer?.files ?? []));
};
</script>

<template>
    <div
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed px-6 py-12 text-center transition"
        :class="over ? 'border-primary bg-surface' : 'border-divider'"
        role="button"
        tabindex="0"
        @click="input?.click()"
        @keydown.enter.prevent="input?.click()"
        @keydown.space.prevent="input?.click()"
        @dragover.prevent="over = true"
        @dragleave.prevent="over = false"
        @drop.prevent="drop"
    >
        <slot />

        <input ref="input" type="file" multiple class="hidden" @change="pick" />
    </div>
</template>
