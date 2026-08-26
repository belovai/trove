<script setup lang="ts">
withDefaults(
    defineProps<{
        id: string;
        type?: 'text' | 'password' | 'email';
        autocomplete?: string;
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    { type: 'text', autocomplete: undefined, invalid: false, disabled: false },
);

const model = defineModel<string>({ required: true });
</script>

<template>
    <input
        :id="id"
        v-model="model"
        :type="type"
        :autocomplete="autocomplete"
        :aria-invalid="invalid"
        :disabled="disabled"
        class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 disabled:opacity-60 dark:bg-gray-900"
        :class="
            invalid
                ? 'border-red-500 focus-visible:ring-red-500'
                : 'border-gray-300 focus-visible:ring-gray-500 dark:border-gray-700'
        "
    />
</template>
