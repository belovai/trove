<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

const { t } = useTranslations();

const form = useForm({ email: '' });

const submit = (): void => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head :title="t('auth::password.forgot_title')" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-text">{{ t('auth::password.forgot_title') }}</h2>
        <p class="text-sm text-muted">{{ t('auth::password.forgot_body') }}</p>

        <FormField id="email" :label="t('auth::password.email')" :error="form.errors.email">
            <TextInput
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                :invalid="Boolean(form.errors.email)"
            />
        </FormField>

        <AppButton type="submit" block :loading="form.processing">{{ t('auth::password.send') }}</AppButton>

        <Link href="/login" class="text-center text-sm text-accent hover:text-accent-hover">
            {{ t('auth::password.back_to_login') }}
        </Link>
    </form>
</template>
