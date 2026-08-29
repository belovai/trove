<script setup lang="ts">
import { ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import type { TagSuggestion } from '@/types/inertia';

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        warnings?: string[];
        error?: string;
    }>(),
    { warnings: () => [], error: undefined },
);

const emit = defineEmits<{ 'update:modelValue': [string[]] }>();

const { t } = useTranslations();

const draft = ref('');
const suggestions = ref<TagSuggestion[]>([]);
// -1 means "nothing picked yet": Enter then commits what was typed, so a new
// tag whose prefix matches an existing one is not silently replaced by it.
const highlighted = ref(-1);

let debounce: ReturnType<typeof setTimeout> | undefined;

const commit = (raw: string): void => {
    // A paste of five space-separated tags adds five tags, which is how the
    // existing input already behaves.
    const names = raw
        .split(/\s+/)
        .map((name) => name.trim())
        .filter((name) => name !== '' && !props.modelValue.includes(name));

    if (names.length > 0) {
        emit('update:modelValue', [...props.modelValue, ...names]);
    }

    draft.value = '';
    suggestions.value = [];
    highlighted.value = -1;
};

const remove = (name: string): void => {
    emit(
        'update:modelValue',
        props.modelValue.filter((entry) => entry !== name),
    );
};

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === ' ' || event.key === 'Enter') {
        if (event.key === 'Enter' && highlighted.value >= 0 && suggestions.value.length > 0) {
            event.preventDefault();
            commit(suggestions.value[highlighted.value].name);

            return;
        }

        event.preventDefault();
        commit(draft.value);

        return;
    }

    if (event.key === 'Backspace' && draft.value === '' && props.modelValue.length > 0) {
        remove(props.modelValue[props.modelValue.length - 1]);

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, suggestions.value.length - 1);
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, -1);
    }

    if (event.key === 'Escape') {
        suggestions.value = [];
        highlighted.value = -1;
    }
};

watch(draft, (value) => {
    clearTimeout(debounce);
    highlighted.value = -1;

    // The prefix is stripped for lookup: "artist:jo" should still suggest.
    const query = value.includes(':') ? value.slice(value.indexOf(':') + 1) : value;

    if (query.trim() === '') {
        suggestions.value = [];

        return;
    }

    debounce = setTimeout(async () => {
        const response = await fetch(`/tags/autocomplete?q=${encodeURIComponent(query)}`, {
            headers: { Accept: 'application/json' },
        });

        suggestions.value = response.ok ? ((await response.json()) as TagSuggestion[]) : [];
    }, 150);
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-wrap items-center gap-1 rounded-md border border-divider bg-panel p-2 focus-within:[box-shadow:var(--ring)]"
        >
            <span
                v-for="name in props.modelValue"
                :key="name"
                class="inline-flex items-center gap-1 rounded-sm bg-surface px-2 py-0.5 text-sm text-text"
            >
                {{ name }}
                <button type="button" class="text-muted hover:text-danger" @click="remove(name)">&times;</button>
            </span>

            <input
                v-model="draft"
                type="text"
                class="min-w-[8rem] flex-1 bg-transparent text-sm text-text focus:outline-none focus-visible:shadow-none"
                autocomplete="off"
                @keydown="onKeydown"
                @blur="commit(draft)"
            />
        </div>

        <ul
            v-if="suggestions.length > 0"
            class="max-h-64 overflow-y-auto rounded-md border border-divider bg-panel text-sm"
        >
            <li
                v-for="(suggestion, index) in suggestions"
                :key="suggestion.name"
                class="flex cursor-pointer items-center gap-2 px-2 py-1"
                :class="index === highlighted ? 'bg-surface' : ''"
                @mousedown.prevent="commit(suggestion.name)"
            >
                <span
                    :style="suggestion.color === null ? undefined : { color: suggestion.color }"
                    :class="suggestion.color === null ? 'text-text' : ''"
                >
                    {{ suggestion.name }}
                </span>
                <!-- An alias hit shows what was typed, so nothing is silently substituted. -->
                <span v-if="suggestion.matched !== suggestion.name" class="text-xs text-muted">
                    &larr; {{ suggestion.matched }}
                </span>
                <span class="ml-auto text-xs text-muted">{{ suggestion.usage_count }}</span>
            </li>
        </ul>

        <p class="text-xs text-muted">{{ t('tag::tag.tag_input_hint') }}</p>

        <p v-for="warning in props.warnings" :key="warning" class="text-xs text-warning">
            {{ warning }}
        </p>

        <p v-if="props.error" class="text-xs text-danger-strong">{{ props.error }}</p>
    </div>
</template>
