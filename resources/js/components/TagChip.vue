<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';
import type { TagOnMedia, TagSummary } from '@/types/inertia';

const props = withDefaults(
    defineProps<{
        tag: TagSummary | TagOnMedia;
        removable?: boolean;
        linked?: boolean;
    }>(),
    { removable: false, linked: true },
);

defineEmits<{ remove: [] }>();

const { t } = useTranslations();

// Implied tags are dimmed: the reader should be able to tell at a glance what
// someone typed from what the taxonomy filled in.
const implied = computed(() => 'source' in props.tag && props.tag.source === 'implied');

const style = computed(() => (props.tag.color === null ? undefined : { color: props.tag.color }));
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-sm dark:bg-gray-800"
        :class="implied ? 'opacity-60' : ''"
        :title="implied ? t('tag::tag.implied_tag') : undefined"
    >
        <component
            :is="props.linked ? Link : 'span'"
            v-bind="props.linked ? { href: `/tags/${encodeURIComponent(props.tag.name)}` } : {}"
            :style="style"
            class="font-medium"
        >
            {{ props.tag.name }}
        </component>

        <span class="text-xs text-gray-500">{{ props.tag.usage_count }}</span>

        <button
            v-if="props.removable"
            type="button"
            class="text-gray-400 hover:text-red-600"
            :aria-label="t('tag::tag.remove')"
            @click="$emit('remove')"
        >
            &times;
        </button>
    </span>
</template>
