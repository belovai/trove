<script setup lang="ts">
import { ref } from 'vue';
import type { MediaCardData } from '@/types/inertia';

const props = defineProps<{
    media: MediaCardData;
    size: 'thumb' | 'preview';
}>();

const loaded = ref(false);
</script>

<template>
    <!-- The dominant color stands in until the image arrives, and stays if the
         thumbnail job has not run or failed. -->
    <div
        class="media-thumbnail aspect-square w-full overflow-hidden rounded-md bg-surface"
        :style="{ backgroundColor: props.media.dominant_color ?? undefined }"
    >
        <img
            v-if="props.media.has_thumbnail"
            :src="`/m/${props.media.hash_id}/thumbnail/${props.size}`"
            :alt="props.media.title ?? ''"
            loading="lazy"
            class="h-full w-full object-cover opacity-0 transition-opacity duration-200"
            :class="{ 'opacity-100': loaded }"
            @load="loaded = true"
        />
    </div>
</template>
