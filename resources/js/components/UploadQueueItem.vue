<script setup lang="ts">
import { watch } from 'vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
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
            'border-divider': item.status === 'pending' || item.status === 'uploading',
            'border-success': item.status === 'done',
            'border-warning': item.status === 'duplicate',
            'border-danger': item.status === 'error',
        }"
    >
        <img :src="item.preview" :alt="item.file.name" class="h-24 w-24 shrink-0 rounded-md object-cover" />

        <div class="flex min-w-0 flex-1 flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
                <p class="truncate text-sm font-medium text-text">{{ item.file.name }}</p>

                <button
                    type="button"
                    class="shrink-0 px-1 text-sm text-muted hover:text-text"
                    :aria-label="t('media::media.remove')"
                    @click="emit('remove')"
                >
                    &times;
                </button>
            </div>

            <template v-if="item.status !== 'done'">
                <div class="flex flex-wrap items-center gap-4 text-sm text-text">
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
                    <AppSelect
                        :id="`visibility_${item.id}`"
                        v-model="item.visibility"
                        :aria-label="t('media::media.visibility')"
                        :disabled="item.status === 'uploading'"
                        class="w-auto"
                    >
                        <option v-for="value in visibilities" :key="value" :value="value">
                            {{ t(`media::visibility.${value}`) }}
                        </option>
                    </AppSelect>

                    <AppCheckbox
                        :id="`anonymous_${item.id}`"
                        v-model="item.is_anonymous"
                        :label="t('media::media.anonymous')"
                        :disabled="item.visibility === 'private' || item.status === 'uploading'"
                    />
                </div>

                <TagInput v-model="item.tags" />
            </template>

            <div v-if="item.status === 'uploading'" class="h-1 w-full rounded-full bg-surface">
                <div
                    class="h-1 rounded-full bg-primary transition-all"
                    :style="{ width: `${Math.round(item.progress * 100)}%` }"
                ></div>
            </div>

            <p v-if="item.status === 'done'" class="text-sm text-success">
                {{ t('media::media.uploaded') }}
            </p>

            <p v-if="item.status === 'error'" class="text-sm text-danger">{{ item.error }}</p>

            <div v-if="item.status === 'duplicate'" class="flex flex-col gap-2 text-sm">
                <p class="text-warning">{{ item.error }}</p>

                <!-- Opened in a new tab on purpose: leaving the page would
                     discard the whole upload queue. -->
                <a
                    v-if="item.duplicate"
                    :href="`/m/${item.duplicate.hash_id}`"
                    target="_blank"
                    rel="noopener"
                    class="text-accent hover:text-accent-hover"
                >
                    {{ item.duplicate.title ?? item.duplicate.hash_id }}
                </a>

                <div class="flex gap-2">
                    <AppButton type="button" variant="danger" size="sm" @click="emit('confirmDuplicate')">
                        {{ t('media::media.upload_anyway') }}
                    </AppButton>

                    <AppButton type="button" variant="secondary" size="sm" @click="emit('remove')">
                        {{ t('media::media.skip') }}
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
