<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TagChip from '@/components/TagChip.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated, TagCategorySummary, TagSummary } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tags: Paginated<TagSummary>;
    categories: TagCategorySummary[];
    filters: { q: string; category: string | null };
}>();

const { t } = useTranslations();

const query = ref(props.filters.q);

let debounce: ReturnType<typeof setTimeout> | undefined;

watch(query, (value) => {
    clearTimeout(debounce);

    debounce = setTimeout(() => {
        router.get('/tags', { q: value, category: props.filters.category }, { preserveState: true, replace: true });
    }, 250);
});

function selectCategory(category: string | null): void {
    router.get('/tags', { q: query.value, category }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="t('tag::tag.tags')" />

    <div class="flex flex-col gap-4">
        <TextInput id="tag-search" v-model="query" class="max-w-xs" :placeholder="t('tag::tag.search_tags')" />

        <div v-if="props.categories.length > 0" class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full border px-3 py-1 text-sm font-medium"
                :class="
                    props.filters.category === null
                        ? 'border-accent bg-accent/10 text-accent'
                        : 'border-divider text-muted hover:text-text'
                "
                @click="selectCategory(null)"
            >
                {{ t('tag::tag.all_categories') }}
            </button>

            <button
                v-for="category in props.categories"
                :key="category.name"
                type="button"
                class="rounded-full border px-3 py-1 text-sm font-medium"
                :class="
                    props.filters.category === category.name
                        ? 'border-accent bg-accent/10 text-accent'
                        : 'border-divider text-muted hover:text-text'
                "
                :style="props.filters.category === category.name ? undefined : { color: category.color }"
                @click="selectCategory(category.name)"
            >
                {{ category.name }} <span class="text-xs text-muted">{{ category.tags_count }}</span>
            </button>
        </div>

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
