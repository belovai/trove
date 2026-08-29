<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/layouts/AppLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import MediaViewer from '@/components/MediaViewer.vue';
import TagChip from '@/components/TagChip.vue';
import TagInput from '@/components/TagInput.vue';
import MediaDetailsSlideOver from '@/components/media/MediaDetailsSlideOver.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    media: MediaDetail;
    can: { update: boolean; delete: boolean };
    visibilities: string[];
}>();

const { t } = useTranslations();
const { confirm } = useConfirm();

const isDetailsOpen = ref(false);
const isEditingTags = ref(false);

// Only human tags are editable: implied ones follow from them, and are
// recomputed server-side by SyncMediaTags.
const tagForm = useForm<{ tags: string[] }>({
    tags: props.media.tags.filter((tag) => tag.source === 'human').map((tag) => tag.name),
});

const startTagEdit = (): void => {
    tagForm.tags = props.media.tags.filter((tag) => tag.source === 'human').map((tag) => tag.name);
    isEditingTags.value = true;
};

const saveTags = (): void => {
    tagForm.patch(`/m/${props.media.hash_id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditingTags.value = false;
        },
    });
};

const destroy = async (): Promise<void> => {
    const accepted = await confirm({
        message: t('media::media.delete_confirm'),
        confirmLabel: t('user::ui.delete'),
        variant: 'danger',
    });

    if (accepted) {
        router.delete(`/m/${props.media.hash_id}`);
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

        <aside class="flex flex-col divide-y divide-divider rounded-lg border border-divider bg-panel">
            <div class="flex items-start justify-between gap-3 px-4 py-3">
                <h1 class="min-w-0 text-base font-semibold text-text">
                    {{ props.media.title ?? props.media.hash_id }}
                </h1>
                <div v-if="props.can.update || props.can.delete" class="flex shrink-0 gap-1">
                    <AppButton
                        v-if="props.can.update"
                        variant="ghost"
                        size="icon"
                        :aria-label="t('media::media.edit_details')"
                        @click="isDetailsOpen = true"
                    >
                        <PencilSquareIcon class="h-5 w-5" aria-hidden="true" />
                    </AppButton>
                    <AppButton
                        v-if="props.can.delete"
                        variant="ghost"
                        size="icon"
                        class="text-danger"
                        :aria-label="t('media::media.delete')"
                        @click="destroy"
                    >
                        <TrashIcon class="h-5 w-5" aria-hidden="true" />
                    </AppButton>
                </div>
            </div>

            <div v-if="props.media.description || props.media.source" class="flex flex-col gap-2 px-4 py-3">
                <p v-if="props.media.description" class="text-sm text-text">{{ props.media.description }}</p>
                <p v-if="props.media.source" class="text-xs text-muted">
                    {{ t('media::media.source') }}: {{ props.media.source }}
                </p>
            </div>

            <div class="flex flex-col gap-2 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xs font-semibold tracking-wide text-muted uppercase">
                        {{ t('media::media.section_tags') }}
                    </h2>
                    <AppButton v-if="props.can.update && !isEditingTags" variant="ghost" size="sm" @click="startTagEdit">
                        {{ t('media::media.edit_tags') }}
                    </AppButton>
                </div>

                <div v-if="!isEditingTags" class="flex flex-wrap gap-1">
                    <TagChip v-for="tag in props.media.tags" :key="tag.name" :tag="tag" />
                    <p v-if="props.media.tags.length === 0" class="text-xs text-muted">
                        {{ t('user::ui.nothing_here') }}
                    </p>
                </div>

                <!-- Edited in place, beside the image: a chip list in an overlay would
                     hide the very thing you are tagging, on mobile especially. -->
                <div v-else class="flex flex-col gap-2">
                    <TagInput v-model="tagForm.tags" :error="tagForm.errors.tags" />
                    <div class="flex justify-end gap-2">
                        <AppButton variant="secondary" size="sm" @click="isEditingTags = false">
                            {{ t('user::ui.cancel') }}
                        </AppButton>
                        <AppButton size="sm" :loading="tagForm.processing" @click="saveTags">
                            {{ t('user::ui.save') }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 px-4 py-3 text-sm">
                <dt class="text-muted">{{ t('media::media.uploaded_by') }}</dt>
                <dd class="text-text">
                    <div>{{ props.media.uploader ?? t('media::media.anonymous_uploader') }}</div>
                    <div v-if="props.media.is_anonymous && props.media.uploader" class="text-xs text-muted">
                        {{ t('media::media.anonymous_badge') }}
                    </div>
                </dd>
                <dt class="text-muted">{{ t('media::media.dimensions') }}</dt>
                <dd class="text-text">{{ props.media.width }} &times; {{ props.media.height }}</dd>
                <dt class="text-muted">{{ t('media::media.filesize') }}</dt>
                <dd class="text-text">{{ Math.round(props.media.filesize / 1024) }} KB</dd>
                <dt class="text-muted">{{ t('media::media.visibility') }}</dt>
                <dd class="text-text">{{ t(`media::visibility.${props.media.visibility}`) }}</dd>
                <dt class="text-muted">{{ t('media::media.safety_rating') }}</dt>
                <dd class="text-text">{{ t(`media::safety.${props.media.safety_rating}`) }}</dd>
                <dt class="text-muted">{{ t('media::media.uploaded_at') }}</dt>
                <dd class="text-text">{{ props.media.created_at ?? '—' }}</dd>
            </dl>
        </aside>
    </div>

    <MediaDetailsSlideOver
        v-if="isDetailsOpen"
        :media="props.media"
        :visibilities="props.visibilities"
        @close="isDetailsOpen = false"
    />
</template>
