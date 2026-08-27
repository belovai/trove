<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    media: MediaDetail;
    can: { update: boolean; delete: boolean };
}>();

const { t } = useTranslations();

const storageKey = `reveal:${props.media.hash_id}`;

// The safety rating is a display filter, so the choice to look is the viewer's
// and is remembered only for this browser tab.
const revealed = ref(sessionStorage.getItem(storageKey) === '1');

const reveal = (): void => {
    revealed.value = true;
    sessionStorage.setItem(storageKey, '1');
};

const removal = useForm({});

const destroy = (): void => {
    if (confirm(t('media::media.delete_confirm'))) {
        removal.delete(`/m/${props.media.hash_id}`);
    }
};
</script>

<template>
    <Head :title="props.media.title ?? props.media.hash_id" />

    <div class="flex flex-col gap-4">
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
            class="max-h-[75vh] w-full rounded-md object-contain"
        />

        <h1 v-if="props.media.title" class="text-lg font-semibold">{{ props.media.title }}</h1>
        <p v-if="props.media.description" class="text-sm">{{ props.media.description }}</p>
        <p v-if="props.media.source" class="text-sm text-gray-500">
            {{ t('media::media.source') }}: {{ props.media.source }}
        </p>

        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
            <dt class="text-gray-500">{{ t('media::media.uploaded_by') }}</dt>
            <dd>{{ props.media.uploader ?? t('media::media.anonymous_uploader') }}</dd>
            <dt class="text-gray-500">{{ t('media::media.dimensions') }}</dt>
            <dd>{{ props.media.width }} &times; {{ props.media.height }}</dd>
            <dt class="text-gray-500">{{ t('media::media.filesize') }}</dt>
            <dd>{{ Math.round(props.media.filesize / 1024) }} KB</dd>
        </dl>

        <div class="flex items-center gap-4">
            <Link v-if="props.can.update" :href="`/m/${props.media.hash_id}/edit`">{{ t('media::media.edit') }}</Link>
            <Button v-if="props.can.delete" type="button" @click="destroy">{{ t('media::media.delete') }}</Button>
        </div>
    </div>
</template>
