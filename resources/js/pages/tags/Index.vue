<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TagChip from '@/components/TagChip.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated, TagSummary } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tags: Paginated<TagSummary>;
    filters: { q: string };
}>();

const { t } = useTranslations();

const query = ref(props.filters.q);

let debounce: ReturnType<typeof setTimeout> | undefined;

watch(query, (value) => {
    clearTimeout(debounce);

    debounce = setTimeout(() => {
        router.get('/tags', { q: value }, { preserveState: true, replace: true });
    }, 250);
});
</script>

<template>
    <Head :title="t('tag::tag.tags')" />

    <div class="flex flex-col gap-4">
        <TextInput id="tag-search" v-model="query" :placeholder="t('tag::tag.search_tags')" />

        <p v-if="props.tags.data.length === 0" class="text-sm text-muted">{{ t('tag::tag.no_results') }}</p>

        <div v-else class="flex flex-wrap gap-2">
            <TagChip v-for="tag in props.tags.data" :key="tag.name" :tag="tag" />
        </div>

        <nav v-if="props.tags.last_page > 1" class="flex items-center justify-center gap-4 text-sm text-muted">
            <Link v-if="props.tags.prev_page_url" :href="props.tags.prev_page_url" class="text-accent hover:text-accent-hover">&larr;</Link>
            <span>{{ props.tags.current_page }} / {{ props.tags.last_page }}</span>
            <Link v-if="props.tags.next_page_url" :href="props.tags.next_page_url" class="text-accent hover:text-accent-hover">&rarr;</Link>
        </nav>
    </div>
</template>
