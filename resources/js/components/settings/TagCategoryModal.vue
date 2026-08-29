<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/components/ui/AppModal.vue';
import ModalFooter from '@/components/ui/ModalFooter.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import ColorInput from '@/components/ui/ColorInput.vue';
import { useTranslations } from '@/composables/useTranslations';

interface Category {
    id: number;
    name: string;
    color: string;
    sort_order: number;
}

const props = defineProps<{ category: Category | null }>();

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();

const form = useForm({
    name: props.category?.name ?? '',
    color: props.category?.color ?? '#888888',
    sort_order: props.category?.sort_order ?? 0,
});

const submit = (): void => {
    const options = { onSuccess: () => emit('close') };

    if (props.category === null) {
        form.post('/admin/tags/categories', options);

        return;
    }

    form.patch(`/admin/tags/categories/${props.category.id}`, options);
};
</script>

<template>
    <AppModal
        :title="props.category ? t('tag::tag.edit_category') : t('tag::tag.add_category')"
        @close="emit('close')"
    >
        <form id="tag-category-form" class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField id="category-name" :label="t('tag::tag.name')" :error="form.errors.name">
                <TextInput id="category-name" v-model="form.name" :invalid="Boolean(form.errors.name)" />
            </FormField>

            <FormField id="category-color" :label="t('tag::tag.color')" :error="form.errors.color">
                <ColorInput id="category-color" v-model="form.color" />
            </FormField>

            <FormField id="category-sort" :label="t('tag::tag.sort_order')" :error="form.errors.sort_order">
                <input
                    id="category-sort"
                    v-model.number="form.sort_order"
                    type="number"
                    class="w-24 rounded-md border border-divider bg-panel px-3 py-2 text-sm text-text"
                />
            </FormField>
        </form>

        <template #footer>
            <ModalFooter>
                <AppButton variant="secondary" @click="emit('close')">{{ t('user::ui.cancel') }}</AppButton>
                <AppButton type="submit" form="tag-category-form" :loading="form.processing">
                    {{ t('user::ui.save') }}
                </AppButton>
            </ModalFooter>
        </template>
    </AppModal>
</template>
