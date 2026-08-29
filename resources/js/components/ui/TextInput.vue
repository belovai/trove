<script setup lang="ts">
withDefaults(
    defineProps<{
        id: string;
        type?: 'text' | 'password' | 'email';
        autocomplete?: string;
        invalid?: boolean;
        disabled?: boolean;
        placeholder?: string;
    }>(),
    { type: 'text', autocomplete: undefined, invalid: false, disabled: false, placeholder: undefined },
);

const model = defineModel<string>({ required: true });
</script>

<template>
    <input
        :id="id"
        v-model="model"
        :type="type"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        :aria-invalid="invalid"
        :disabled="disabled"
        class="w-full rounded-md border bg-panel px-3 py-2 text-sm text-text placeholder:text-muted disabled:opacity-60"
        :class="invalid ? 'border-danger' : 'border-divider'"
    />
</template>
