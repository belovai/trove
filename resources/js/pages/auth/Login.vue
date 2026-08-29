<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
import FormField from '@/components/ui/FormField.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

defineProps<{
    canRegister: boolean;
    canResetPassword: boolean;
}>();

const { t } = useTranslations();

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = (): void => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="t('auth::login.title')" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-text">{{ t('auth::login.title') }}</h2>

        <FormField id="username" :label="t('auth::login.username')" :error="form.errors.username">
            <TextInput
                id="username"
                v-model="form.username"
                autocomplete="username"
                :invalid="Boolean(form.errors.username)"
            />
        </FormField>

        <FormField id="password" :label="t('auth::login.password')" :error="form.errors.password">
            <TextInput
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                :invalid="Boolean(form.errors.password)"
            />
        </FormField>

        <AppCheckbox id="remember" v-model="form.remember" :label="t('auth::login.remember')" />

        <Link
            v-if="canResetPassword"
            href="/forgot-password"
            class="text-center text-sm text-accent hover:text-accent-hover"
        >
            {{ t('auth::password.forgot_link') }}
        </Link>

        <AppButton type="submit" block :loading="form.processing">{{ t('auth::login.submit') }}</AppButton>

        <p v-if="canRegister" class="text-center text-sm text-muted">
            {{ t('auth::login.no_account') }}
            <Link href="/register" class="text-accent hover:text-accent-hover">{{ t('auth::register.submit') }}</Link>
        </p>
    </form>
</template>
