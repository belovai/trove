<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import AppButton from '@/components/ui/AppButton.vue';
import MediaThumbnail from '@/components/MediaThumbnail.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';
import { useUnsafeContentReveal } from '@/composables/useUnsafeContentReveal';
import type { MediaCardData } from '@/types/inertia';

const props = defineProps<{ media: MediaCardData }>();

const { t } = useTranslations();
const { user } = useAuth();
const { requestShowUnsafeContent } = useUnsafeContentReveal();

// Local to this mount: revealing one item never persists past a reload, and
// never affects any other item. Whether the item reaches the list at all is
// the rating filter's job — this only decides whether it's covered here.
const revealed = ref(false);

const isCovered = computed(
    () => props.media.safety_rating === 'unsafe' && !user.value?.show_unsafe_content && !revealed.value,
);
</script>

<template>
    <div class="relative rounded-md">
        <Link
            :href="`/m/${props.media.hash_id}`"
            class="block rounded-md"
            :class="props.media.tag_count === 0 ? 'ring-2 ring-danger' : ''"
            :title="props.media.tag_count === 0 ? t('media::media.untagged') : undefined"
        >
            <MediaThumbnail :media="props.media" size="thumb" :covered="isCovered" />
        </Link>

        <!--
            A sibling of the link, not a child: a button nested inside an <a>
            still lets the browser navigate on click regardless of the
            button's own stopPropagation/preventDefault. Pointer-events does
            the split instead — none on the transparent wrapper so a click on
            the blurred image still reaches the link underneath, auto on the
            button panel so those clicks land here instead.
        -->
        <div v-if="isCovered" class="pointer-events-none absolute inset-0 flex items-center justify-center p-2">
            <div class="pointer-events-auto flex flex-col items-center gap-2 rounded-md bg-panel/70 p-2 text-center">
                <EyeSlashIcon class="h-6 w-6 text-text" aria-hidden="true" />
                <div class="flex flex-col gap-1">
                    <AppButton size="sm" @click="revealed = true">
                        <template #icon><EyeIcon class="h-4 w-4" aria-hidden="true" /></template>
                        {{ t('media::media.show_anyway') }}
                    </AppButton>
                    <AppButton size="sm" variant="secondary" @click="requestShowUnsafeContent">
                        {{ t('media::media.show_unsafe') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
