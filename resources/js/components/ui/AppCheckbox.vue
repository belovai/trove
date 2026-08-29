<script setup lang="ts">
withDefaults(defineProps<{ id: string; label: string; disabled?: boolean }>(), { disabled: false });

const model = defineModel<boolean>({ required: true });
</script>

<template>
    <label :for="id" class="inline-flex cursor-pointer items-center gap-2 text-sm text-text">
        <input
            :id="id"
            v-model="model"
            type="checkbox"
            :disabled="disabled"
            class="h-4 w-4 rounded-sm border-divider accent-primary disabled:opacity-60"
        />
        {{ label }}
    </label>
</template>
