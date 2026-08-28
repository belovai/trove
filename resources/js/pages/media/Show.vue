<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import MediaViewer from '@/components/MediaViewer.vue';
import TagChip from '@/components/TagChip.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    media: MediaDetail;
    can: { update: boolean; delete: boolean };
}>();

const { t } = useTranslations();

const removal = useForm({});

const destroy = (): void => {
    if (confirm(t('media::media.delete_confirm'))) {
        removal.delete(`/m/${props.media.hash_id}`);
    }
};
</script>

<template>
    <Head :title="props.media.title ?? props.media.hash_id" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <!-- The image keeps its place while the sidebar scrolls. -->
        <div class="lg:sticky lg:top-8">
            <MediaViewer :media="props.media" />
        </div>

        <aside class="flex flex-col gap-4">
            <div v-if="props.media.tags.length > 0" class="flex flex-wrap gap-1">
                <TagChip v-for="tag in props.media.tags" :key="tag.name" :tag="tag" />
            </div>

            <div v-if="props.media.title || props.media.description || props.media.source" class="flex flex-col gap-2">
                <h1 v-if="props.media.title" class="text-lg font-semibold">{{ props.media.title }}</h1>
                <p v-if="props.media.description" class="text-sm">{{ props.media.description }}</p>
                <p v-if="props.media.source" class="text-sm text-gray-500">
                    {{ t('media::media.source') }}: {{ props.media.source }}
                </p>
            </div>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <dt class="text-gray-500">{{ t('media::media.uploaded_by') }}</dt>
                <dd>{{ props.media.uploader ?? t('media::media.anonymous_uploader') }}</dd>
                <dt class="text-gray-500">{{ t('media::media.dimensions') }}</dt>
                <dd>{{ props.media.width }} &times; {{ props.media.height }}</dd>
                <dt class="text-gray-500">{{ t('media::media.filesize') }}</dt>
                <dd>{{ Math.round(props.media.filesize / 1024) }} KB</dd>
            </dl>

            <div class="flex items-center gap-4">
                <Link v-if="props.can.update" :href="`/m/${props.media.hash_id}/edit`">
                    {{ t('media::media.edit') }}
                </Link>
                <Button v-if="props.can.delete" type="button" @click="destroy">{{ t('media::media.delete') }}</Button>
            </div>
        </aside>
    </div>
</template>
