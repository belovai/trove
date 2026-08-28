<script setup lang="ts">
import { watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import FormField from '@/components/FormField.vue';
import MediaViewer from '@/components/MediaViewer.vue';
import TagInput from '@/components/TagInput.vue';
import TextInput from '@/components/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail, SafetyRating, Visibility } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    media: MediaDetail;
    visibilities: Visibility[];
    safety_ratings: SafetyRating[];
    tags: string[];
}>();

const { t } = useTranslations();

const form = useForm({
    title: props.media.title ?? '',
    description: props.media.description ?? '',
    source: props.media.source ?? '',
    visibility: props.media.visibility,
    safety_rating: props.media.safety_rating,
    is_anonymous: props.media.is_anonymous,
    tags: [...props.tags],
});

watch(
    () => form.visibility,
    (visibility) => {
        if (visibility === 'private') {
            form.is_anonymous = false;
        }
    },
);

const submit = (): void => {
    form.patch(`/m/${props.media.hash_id}`);
};
</script>

<template>
    <Head :title="t('media::media.edit')" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <!-- The image stays put while the form on the right scrolls. -->
        <div class="lg:sticky lg:top-8">
            <MediaViewer :media="props.media" />
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField id="title" :label="t('media::media.title')" :error="form.errors.title">
                <TextInput id="title" v-model="form.title" />
            </FormField>

            <FormField id="description" :label="t('media::media.description')" :error="form.errors.description">
                <textarea
                    id="description"
                    v-model="form.description"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                ></textarea>
            </FormField>

            <FormField id="source" :label="t('media::media.source')" :error="form.errors.source">
                <TextInput id="source" v-model="form.source" />
            </FormField>

            <FormField id="visibility" :label="t('media::media.visibility')" :error="form.errors.visibility">
                <select
                    id="visibility"
                    v-model="form.visibility"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <option v-for="value in props.visibilities" :key="value" :value="value">
                        {{ t(`media::visibility.${value}`) }}
                    </option>
                </select>
            </FormField>

            <FormField id="safety_rating" :label="t('media::media.safety_rating')" :error="form.errors.safety_rating">
                <select
                    id="safety_rating"
                    v-model="form.safety_rating"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <option v-for="value in props.safety_ratings" :key="value" :value="value">
                        {{ t(`media::safety.${value}`) }}
                    </option>
                </select>
            </FormField>

            <FormField id="is_anonymous" :label="t('media::media.anonymous')" :error="form.errors.is_anonymous">
                <input
                    id="is_anonymous"
                    type="checkbox"
                    v-model="form.is_anonymous"
                    :disabled="form.visibility === 'private'"
                />
            </FormField>

            <FormField id="tags" :label="t('tag::tag.add_tags')" :error="form.errors.tags">
                <TagInput v-model="form.tags" :error="form.errors.tags" />
            </FormField>

            <Button type="submit" :disabled="form.processing">{{ t('media::media.save') }}</Button>
        </form>
    </div>
</template>
