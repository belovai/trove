<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/Button.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail } from '@/types/inertia';

const props = defineProps<{ media: MediaDetail }>();

const { t } = useTranslations();

const storageKey = `reveal:${props.media.hash_id}`;

// The safety rating is a display filter, so the choice to look is the viewer's
// and is remembered only for this browser tab.
const revealed = ref(sessionStorage.getItem(storageKey) === '1');

const reveal = (): void => {
    revealed.value = true;
    sessionStorage.setItem(storageKey, '1');
};
</script>

<template>
    <div
        v-if="props.media.safety_rating !== 'safe' && !revealed"
        class="flex flex-col items-center justify-center gap-3 rounded-md p-12 text-center"
        :style="{ backgroundColor: props.media.dominant_color ?? undefined }"
    >
        <p>{{ t('media::media.hidden_by_rating') }}</p>
        <Button type="button" @click="reveal">{{ t('media::media.show_anyway') }}</Button>
    </div>

    <img
        v-else
        :src="`/m/${props.media.hash_id}/file`"
        :alt="props.media.title ?? ''"
        class="max-h-[80vh] w-full rounded-md object-contain"
    />
</template>
