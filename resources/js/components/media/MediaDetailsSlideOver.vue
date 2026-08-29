<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import AppSlideOver from '@/components/ui/AppSlideOver.vue';
import ModalFooter from '@/components/ui/ModalFooter.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaDetail } from '@/types/inertia';

const props = defineProps<{ media: MediaDetail; visibilities: string[] }>();

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();
const page = usePage();

// Details only: the tag set is edited in place on the page, so it is not part
// of this payload.
const form = useForm({
    title: props.media.title ?? '',
    description: props.media.description ?? '',
    source: props.media.source ?? '',
    visibility: props.media.visibility,
    safety_rating: props.media.safety_rating,
    is_anonymous: props.media.is_anonymous,
});

const submit = (): void => {
    form.patch(`/m/${props.media.hash_id}`, { onSuccess: () => emit('close') });
};
</script>

<template>
    <AppSlideOver :title="t('media::media.edit_details')" @close="emit('close')">
        <form id="media-details-form" class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField id="media-title" :label="t('media::media.title')" :error="form.errors.title">
                <TextInput id="media-title" v-model="form.title" />
            </FormField>

            <FormField
                id="media-description"
                :label="t('media::media.description')"
                :error="form.errors.description"
            >
                <AppTextarea id="media-description" v-model="form.description" :rows="5" />
            </FormField>

            <FormField id="media-source" :label="t('media::media.source')" :error="form.errors.source">
                <TextInput id="media-source" v-model="form.source" />
            </FormField>

            <FormField
                id="media-visibility"
                :label="t('media::media.visibility')"
                :error="form.errors.visibility"
            >
                <AppSelect id="media-visibility" v-model="form.visibility">
                    <option v-for="value in props.visibilities" :key="value" :value="value">
                        {{ t(`media::visibility.${value}`) }}
                    </option>
                </AppSelect>
            </FormField>

            <FormField
                id="media-safety"
                :label="t('media::media.safety_rating')"
                :error="form.errors.safety_rating"
            >
                <AppSelect id="media-safety" v-model="form.safety_rating">
                    <option v-for="rating in page.props.safety_ratings" :key="rating" :value="rating">
                        {{ t(`media::safety.${rating}`) }}
                    </option>
                </AppSelect>
            </FormField>

            <AppCheckbox
                id="media-anonymous"
                v-model="form.is_anonymous"
                :label="t('media::media.anonymous')"
            />
            <p v-if="form.errors.is_anonymous" class="text-xs text-danger-strong">
                {{ form.errors.is_anonymous }}
            </p>
        </form>

        <template #footer>
            <ModalFooter>
                <AppButton variant="secondary" @click="emit('close')">{{ t('user::ui.cancel') }}</AppButton>
                <AppButton type="submit" form="media-details-form" :loading="form.processing">
                    {{ t('user::ui.save') }}
                </AppButton>
            </ModalFooter>
        </template>
    </AppSlideOver>
</template>
