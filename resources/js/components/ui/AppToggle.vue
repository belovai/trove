<script setup lang="ts">
const props = withDefaults(defineProps<{ id: string; disabled?: boolean }>(), { disabled: false });

const model = defineModel<boolean>({ required: true });

const toggle = (): void => {
    if (props.disabled) {
        return;
    }

    model.value = !model.value;
};
</script>

<template>
    <button
        :id="id"
        type="button"
        role="switch"
        :aria-checked="model"
        :disabled="disabled"
        class="inline-flex h-6 w-10 shrink-0 cursor-pointer items-center rounded-xl border border-divider transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        :class="model ? 'bg-primary' : 'bg-surface'"
        @click="toggle"
    >
        <span
            class="mx-0.5 h-4.5 w-4.5 rounded-xl bg-panel transition-transform"
            :class="model ? 'translate-x-4' : 'translate-x-0'"
        />
    </button>
</template>
