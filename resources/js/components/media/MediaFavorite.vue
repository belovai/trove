<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { HeartIcon as HeartIconOutline } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartIconSolid } from '@heroicons/vue/24/solid';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    hashId: string;
    isFavorited: boolean;
    canFavorite: boolean;
    isGuest: boolean;
}>();

const { t } = useTranslations();

/**
 * Local mirror of the server value, same pattern as MediaVote.vue: a click
 * moves it immediately, the watch below re-seeds it from the fresh props the
 * redirect brings back.
 */
const favorited = ref(props.isFavorited);
const inFlight = ref(false);

watch(
    () => props.isFavorited,
    (next) => {
        favorited.value = next;
    },
);

const label = () => (favorited.value ? t('media::media.favorite_remove') : t('media::media.favorite_add'));

const reason = () => {
    if (props.canFavorite) {
        return undefined;
    }

    return props.isGuest
        ? t('media::media.favorite_blocked_guest')
        : t('media::media.favorite_blocked_restricted');
};

const toggle = (): void => {
    if (!props.canFavorite || inFlight.value) {
        return;
    }

    const previous = favorited.value;
    const next = !previous;

    favorited.value = next;
    inFlight.value = true;

    const options = {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            favorited.value = previous;
        },
        onFinish: () => {
            inFlight.value = false;
        },
    } as const;

    if (next) {
        router.post(`/m/${props.hashId}/favorite`, {}, options);
    } else {
        router.delete(`/m/${props.hashId}/favorite`, options);
    }
};
</script>

<template>
    <button
        type="button"
        :disabled="!props.canFavorite || inFlight"
        :aria-label="label()"
        :aria-pressed="favorited"
        :title="reason()"
        class="cursor-pointer rounded-md p-1 disabled:cursor-not-allowed disabled:opacity-40"
        :class="favorited ? 'text-danger' : 'text-muted hover:text-text'"
        @click="toggle"
    >
        <HeartIconSolid v-if="favorited" class="h-5 w-5" aria-hidden="true" />
        <HeartIconOutline v-else class="h-5 w-5" aria-hidden="true" />
    </button>
</template>
