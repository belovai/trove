<script setup lang="ts">
import { ref, watch } from 'vue';

withDefaults(defineProps<{ id: string; disabled?: boolean }>(), { disabled: false });

const model = defineModel<string>({ required: true });

// The hex field is edited character by character, so it keeps its own draft and
// only writes back once the draft is a complete colour.
const draft = ref(model.value);

watch(model, (value) => {
    if (value.toLowerCase() !== draft.value.toLowerCase()) {
        draft.value = value;
    }
});

const normalize = (value: string): string | null => {
    const hex = value.trim().replace(/^#/, '').toLowerCase();

    if (/^[0-9a-f]{3}$/.test(hex)) {
        return `#${hex[0]}${hex[0]}${hex[1]}${hex[1]}${hex[2]}${hex[2]}`;
    }

    return /^[0-9a-f]{6}$/.test(hex) ? `#${hex}` : null;
};

const onHexInput = (): void => {
    const normalized = normalize(draft.value);

    if (normalized !== null) {
        model.value = normalized;
    }
};

// Typing an incomplete value and leaving the field snaps back to the colour in use.
const onHexBlur = (): void => {
    draft.value = normalize(draft.value) ?? model.value;
};
</script>

<template>
    <div class="flex items-center gap-2">
        <input :id="id" v-model="model" type="color" :disabled="disabled" class="h-8 w-8 shrink-0" />
        <input
            :id="`${id}-hex`"
            v-model="draft"
            type="text"
            inputmode="text"
            spellcheck="false"
            maxlength="7"
            placeholder="#888888"
            :disabled="disabled"
            :aria-label="`${id}-hex`"
            class="w-24 rounded-md border border-divider bg-panel px-2 py-1 font-mono text-xs text-text disabled:opacity-60"
            @input="onHexInput"
            @blur="onHexBlur"
        />
    </div>
</template>
