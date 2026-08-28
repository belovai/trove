<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import MediaCard from '@/components/MediaCard.vue';
import MediaFilterBar from '@/components/MediaFilterBar.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaCardData, MediaFilters, Paginated } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{ media: Paginated<MediaCardData>; filters: MediaFilters }>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('media::media.browse')" />

    <MediaFilterBar :filters="props.filters" url="/posts" class="mb-4" />

    <p v-if="props.media.data.length === 0" class="text-sm text-gray-500">{{ t('media::media.empty') }}</p>

    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        <MediaCard v-for="item in props.media.data" :key="item.hash_id" :media="item" />
    </div>

    <nav v-if="props.media.last_page > 1" class="flex items-center justify-center gap-4 text-sm">
        <Link v-if="props.media.prev_page_url" :href="props.media.prev_page_url">&larr;</Link>
        <span>{{ props.media.current_page }} / {{ props.media.last_page }}</span>
        <Link v-if="props.media.next_page_url" :href="props.media.next_page_url">&rarr;</Link>
    </nav>
</template>
