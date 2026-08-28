<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import TagInput from '@/components/TagInput.vue';
import UploadDropzone from '@/components/UploadDropzone.vue';
import UploadQueueItem from '@/components/UploadQueueItem.vue';
import { useTranslations } from '@/composables/useTranslations';
import { useUploadQueue } from '@/composables/useUploadQueue';
import type { SafetyRating, Visibility } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    visibilities: Visibility[];
    safety_ratings: SafetyRating[];
    max_filesize: number; // KB
    allowed_mimes: string[];
}>();

const { t } = useTranslations();

const { items, uploading, add, remove, uploadAll, confirmDuplicate } = useUploadQueue({
    allowedMimes: props.allowed_mimes,
    maxFilesize: props.max_filesize,
    messages: {
        type: t('media::media.error_type'),
        size: t('media::media.error_size'),
        failed: t('media::media.error_failed'),
    },
});

const pending = computed(() => items.value.filter((item) => item.status === 'pending').length);

const batchTags = ref<string[]>([]);

// Applies to pending items only: a file already uploaded cannot be changed
// from here, and re-tagging it belongs on its own edit page.
const applyBatchTags = (): void => {
    for (const item of items.value) {
        if (item.status === 'pending') {
            item.tags = [...batchTags.value];
        }
    }
};

// Cmd/Ctrl+V anywhere on the page: a screenshot or a copied image lands in the
// queue like a dropped file. Pasted text carries no files, so it is ignored.
const paste = (event: ClipboardEvent): void => {
    const files = Array.from(event.clipboardData?.items ?? [])
        .filter((entry) => entry.kind === 'file' && entry.type.startsWith('image/'))
        .map((entry) => entry.getAsFile())
        .filter((file): file is File => file !== null);

    if (files.length === 0) {
        return;
    }

    event.preventDefault();
    add(files);
};

onMounted(() => document.addEventListener('paste', paste));
onUnmounted(() => document.removeEventListener('paste', paste));

const settle = async (upload: () => Promise<void>): Promise<void> => {
    await upload();

    // Leaving the page would take the failed and duplicate cards with it, and
    // with them the only place to retry. Only a clean batch navigates away.
    if (items.value.length > 0 && items.value.every((item) => item.status === 'done')) {
        router.visit('/posts');
    }
};
</script>

<template>
    <Head :title="t('media::media.upload')" />

    <div class="flex flex-col gap-4">
        <UploadDropzone @files="add">
            <p class="text-lg font-medium">{{ t('media::media.drop_files') }}</p>
            <p class="text-sm text-gray-500">{{ t('media::media.drop_hint') }}</p>
            <p class="text-sm text-gray-500">{{ t('media::media.paste_hint') }}</p>
            <p class="text-xs text-gray-500">{{ props.allowed_mimes.join(', ') }}</p>
        </UploadDropzone>

        <div v-if="items.length > 0" class="flex flex-col gap-2">
            <TagInput v-model="batchTags" />
            <Button type="button" variant="secondary" @click="applyBatchTags">
                {{ t('tag::tag.add_tags') }}
            </Button>
        </div>

        <div v-if="items.length > 0" class="flex items-center gap-4">
            <Button type="button" :disabled="uploading || pending === 0" @click="settle(uploadAll)">
                {{ t('media::media.upload_all') }}
            </Button>
        </div>

        <UploadQueueItem
            v-for="item in items"
            :key="item.id"
            :item="item"
            :visibilities="props.visibilities"
            :safety-ratings="props.safety_ratings"
            @remove="remove(item.id)"
            @confirm-duplicate="settle(() => confirmDuplicate(item.id))"
        />
    </div>
</template>
