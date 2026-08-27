<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MediaThumbnail from '@/components/MediaThumbnail.vue';
import type { MediaCardData } from '@/types/inertia';

const props = defineProps<{ media: MediaCardData }>();
</script>

<template>
    <Link :href="`/m/${props.media.hash_id}`" class="block">
        <MediaThumbnail :media="props.media" size="thumb" />
    </Link>
</template>
