<script setup lang="ts">
import { watch } from 'vue';
import Button from '@/components/Button.vue';
import TagInput from '@/components/TagInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { QueueItem } from '@/composables/useUploadQueue';
import type { SafetyRating, Visibility } from '@/types/inertia';

const props = defineProps<{
    item: QueueItem;
    visibilities: Visibility[];
    safetyRatings: SafetyRating[];
}>();

const emit = defineEmits<{ remove: []; confirmDuplicate: [] }>();

const { t } = useTranslations();

// A private item is visible only to its uploader and to admins, so anonymity
// is meaningless. The server rejects the combination; this keeps the user from
// reaching that error in the first place.
watch(
    () => props.item.visibility,
    (visibility) => {
        if (visibility === 'private') {
            props.item.is_anonymous = false;
        }
    },
);
</script>

<template>
    <div
        class="flex gap-4 rounded-md border p-3"
        :class="{
            'border-gray-200 dark:border-gray-800': item.status === 'pending' || item.status === 'uploading',
            'border-green-400': item.status === 'done',
            'border-yellow-400': item.status === 'duplicate',
            'border-red-400': item.status === 'error',
        }"
    >
        <img :src="item.preview" :alt="item.file.name" class="h-24 w-24 shrink-0 rounded-md object-cover" />

        <div class="flex min-w-0 flex-1 flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
                <p class="truncate text-sm font-medium">{{ item.file.name }}</p>

                <button
                    type="button"
                    class="shrink-0 px-1 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-gray-100"
                    :aria-label="t('media::media.remove')"
                    @click="emit('remove')"
                >
                    &times;
                </button>
            </div>

            <template v-if="item.status !== 'done'">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <label v-for="value in safetyRatings" :key="value" class="flex items-center gap-1">
                        <input
                            type="radio"
                            :value="value"
                            v-model="item.safety_rating"
                            :name="`safety_rating_${item.id}`"
                            :disabled="item.status === 'uploading'"
                        />
                        {{ t(`media::safety.${value}`) }}
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <select
                        v-model="item.visibility"
                        :aria-label="t('media::media.visibility')"
                        :disabled="item.status === 'uploading'"
                        class="rounded-md border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option v-for="value in visibilities" :key="value" :value="value">
                            {{ t(`media::visibility.${value}`) }}
                        </option>
                    </select>

                    <label class="flex items-center gap-1">
                        <input
                            type="checkbox"
                            v-model="item.is_anonymous"
                            :disabled="item.visibility === 'private' || item.status === 'uploading'"
                        />
                        {{ t('media::media.anonymous') }}
                    </label>
                </div>

                <TagInput v-model="item.tags" />
            </template>

            <div v-if="item.status === 'uploading'" class="h-1 w-full rounded-full bg-gray-200 dark:bg-gray-800">
                <div
                    class="h-1 rounded-full bg-gray-900 transition-all dark:bg-gray-100"
                    :style="{ width: `${Math.round(item.progress * 100)}%` }"
                ></div>
            </div>

            <p v-if="item.status === 'done'" class="text-sm text-green-700 dark:text-green-400">
                {{ t('media::media.uploaded') }}
            </p>

            <p v-if="item.status === 'error'" class="text-sm text-red-700 dark:text-red-400">{{ item.error }}</p>

            <div v-if="item.status === 'duplicate'" class="flex flex-col gap-2 text-sm">
                <p class="text-yellow-700 dark:text-yellow-400">{{ item.error }}</p>

                <!-- Opened in a new tab on purpose: leaving the page would
                     discard the whole upload queue. -->
                <a
                    v-if="item.duplicate"
                    :href="`/m/${item.duplicate.hash_id}`"
                    target="_blank"
                    rel="noopener"
                    class="underline"
                >
                    {{ item.duplicate.title ?? item.duplicate.hash_id }}
                </a>

                <div class="flex gap-2">
                    <Button type="button" variant="warning" @click="emit('confirmDuplicate')">
                        {{ t('media::media.upload_anyway') }}
                    </Button>

                    <Button type="button" variant="secondary" @click="emit('remove')">
                        {{ t('media::media.skip') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
