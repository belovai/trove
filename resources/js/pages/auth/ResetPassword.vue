<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

const props = defineProps<{ token: string; email: string }>();

const { t } = useTranslations();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('auth::password.reset_title')" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-text">{{ t('auth::password.reset_title') }}</h2>

        <FormField id="email" :label="t('auth::password.email')" :error="form.errors.email">
            <TextInput
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                :invalid="Boolean(form.errors.email)"
            />
        </FormField>

        <FormField id="password" :label="t('auth::password.password')" :error="form.errors.password">
            <TextInput
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                :invalid="Boolean(form.errors.password)"
            />
        </FormField>

        <FormField id="password_confirmation" :label="t('auth::password.password_confirmation')">
            <TextInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
            />
        </FormField>

        <AppButton type="submit" block :loading="form.processing">{{ t('auth::password.submit') }}</AppButton>
    </form>
</template>
