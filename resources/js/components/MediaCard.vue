<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MediaThumbnail from '@/components/MediaThumbnail.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaCardData } from '@/types/inertia';

const props = defineProps<{ media: MediaCardData }>();

const { t } = useTranslations();
</script>

<template>
    <Link
        :href="`/m/${props.media.hash_id}`"
        class="block rounded-md"
        :class="props.media.tag_count === 0 ? 'ring-2 ring-danger' : ''"
        :title="props.media.tag_count === 0 ? t('media::media.untagged') : undefined"
    >
        <MediaThumbnail :media="props.media" size="thumb" />
    </Link>
</template>
