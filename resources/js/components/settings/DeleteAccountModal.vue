<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppModal from '@/components/ui/AppModal.vue';
import ModalFooter from '@/components/ui/ModalFooter.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();

const form = useForm({ current_password: '' });

const submit = (): void => {
    form.delete('/account');
};
</script>

<template>
    <AppModal
        :title="t('user::account.delete_confirm_title')"
        :description="t('user::account.delete_confirm_hint')"
        @close="emit('close')"
    >
        <form id="delete-account-form" @submit.prevent="submit">
            <FormField
                id="delete-account-password"
                :label="t('user::account.current_password')"
                :error="form.errors.current_password"
            >
                <TextInput
                    id="delete-account-password"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    :invalid="Boolean(form.errors.current_password)"
                />
            </FormField>
        </form>

        <template #footer>
            <ModalFooter>
                <AppButton variant="secondary" @click="emit('close')">{{ t('user::ui.cancel') }}</AppButton>
                <AppButton
                    type="submit"
                    form="delete-account-form"
                    variant="danger"
                    :loading="form.processing"
                >
                    {{ t('user::account.delete') }}
                </AppButton>
            </ModalFooter>
        </template>
    </AppModal>
</template>
