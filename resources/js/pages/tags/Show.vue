<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import ImplicationTree from '@/components/ImplicationTree.vue';
import MediaCard from '@/components/MediaCard.vue';
import MediaFilterBar from '@/components/MediaFilterBar.vue';
import TagChip from '@/components/TagChip.vue';
import TagEditDrawer from '@/components/TagEditDrawer.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaCardData, MediaFilters, TagSummary } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tag: {
        name: string;
        description: string | null;
        category: string | null;
        color: string | null;
        usage_count: number;
        aliases: string[];
        ancestors: string[];
        descendants: string[];
    };
    related: (Omit<TagSummary, 'usage_count'> & { shared: number })[];
    media: MediaCardData[];
    filters: MediaFilters;
    categories: { id: number; name: string }[];
    can: { manage: boolean };
}>();

const { t } = useTranslations();

const editing = ref(false);
</script>

<template>
    <Head :title="props.tag.name" />

    <div class="flex flex-col gap-6">
        <header class="flex flex-wrap items-baseline gap-3">
            <h1 class="text-2xl font-medium" :style="props.tag.color === null ? undefined : { color: props.tag.color }">
                {{ props.tag.name }}
            </h1>
            <span class="text-sm text-gray-500">
                {{ props.tag.category ?? t('tag::tag.uncategorized') }} ·
                {{ t('tag::tag.usages', { count: props.tag.usage_count }) }}
            </span>

            <Button v-if="props.can.manage" type="button" variant="secondary" class="ml-auto" @click="editing = true">
                {{ t('tag::tag.edit') }}
            </Button>
        </header>

        <p class="text-sm" :class="props.tag.description === null ? 'text-gray-500' : ''">
            {{ props.tag.description ?? t('tag::tag.no_description') }}
        </p>

        <div v-if="props.tag.aliases.length > 0" class="flex flex-wrap items-baseline gap-2 text-sm">
            <span class="text-xs text-gray-500">{{ t('tag::tag.aliases') }}</span>
            <span v-for="alias in props.tag.aliases" :key="alias">{{ alias }}</span>
        </div>

        <ImplicationTree
            :name="props.tag.name"
            :ancestors="props.tag.ancestors"
            :descendants="props.tag.descendants"
        />

        <section v-if="props.related.length > 0" class="flex flex-col gap-2">
            <h2 class="text-xs text-gray-500">{{ t('tag::tag.related') }}</h2>
            <div class="flex flex-wrap gap-2">
                <TagChip
                    v-for="related in props.related"
                    :key="related.name"
                    :tag="{ ...related, usage_count: related.shared }"
                />
            </div>
        </section>

        <!-- The tag page is an entry point into browsing, not a data sheet. -->
        <section class="flex flex-col gap-2">
            <h2 class="text-xs text-gray-500">{{ t('tag::tag.samples') }}</h2>

            <!-- Rendered even with nothing to show, or a filter that hid
                 everything would take its own way back out with it. -->
            <MediaFilterBar
                :filters="props.filters"
                :url="`/tags/${props.tag.name}`"
                :show-untagged="false"
                class="mb-2"
            />

            <div v-if="props.media.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <MediaCard v-for="item in props.media" :key="item.hash_id" :media="item" />
            </div>

            <p v-else class="text-sm text-gray-500">{{ t('media::media.empty') }}</p>
        </section>
    </div>

    <TagEditDrawer
        v-if="editing && props.can.manage"
        :tag="props.tag"
        :categories="props.categories"
        @close="editing = false"
    />
</template>
