<script setup lang="ts">
import { computed, ref } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import AppButton from '@/components/ui/AppButton.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';
import { useUnsafeContentReveal } from '@/composables/useUnsafeContentReveal';
import type { MediaDetail } from '@/types/inertia';

const props = defineProps<{ media: MediaDetail }>();

const { t } = useTranslations();
const { user } = useAuth();
const { requestShowUnsafeContent } = useUnsafeContentReveal();

const storageKey = `reveal:${props.media.hash_id}`;

// The safety rating is a display filter, so the choice to look is the viewer's
// and is remembered only for this browser tab.
const revealed = ref(sessionStorage.getItem(storageKey) === '1');

const reveal = (): void => {
    revealed.value = true;
    sessionStorage.setItem(storageKey, '1');
};

const isCovered = computed(
    () => props.media.safety_rating === 'unsafe' && !user.value?.show_unsafe_content && !revealed.value,
);
</script>

<template>
    <div class="relative overflow-hidden rounded-md" :class="isCovered ? 'aspect-video' : ''">
        <img
            :src="`/m/${props.media.hash_id}/file`"
            :alt="props.media.title ?? ''"
            class="w-full"
            :class="isCovered ? 'h-full scale-110 object-cover blur-2xl' : 'max-h-[80vh] object-contain'"
        />

        <div
            v-if="isCovered"
            class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-panel/40 p-4 text-center"
        >
            <EyeSlashIcon class="h-8 w-8 text-text" aria-hidden="true" />
            <p class="text-sm text-text">{{ t('media::media.hidden_by_rating') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <AppButton type="button" @click="reveal">
                    <template #icon><EyeIcon class="h-4 w-4" aria-hidden="true" /></template>
                    {{ t('media::media.show_anyway') }}
                </AppButton>
                <AppButton type="button" variant="secondary" @click="requestShowUnsafeContent">
                    {{ t('media::media.show_unsafe') }}
                </AppButton>
            </div>
        </div>
    </div>
</template>
