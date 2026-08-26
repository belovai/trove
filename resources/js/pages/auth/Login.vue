<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';
import FormField from '@/components/FormField.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import TextInput from '@/components/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

defineProps<{
    canRegister: boolean;
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
        <h2 class="text-lg font-semibold">{{ t('auth::login.title') }}</h2>

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

        <label class="flex items-center gap-2 text-sm">
            <input v-model="form.remember" type="checkbox" class="rounded border-gray-300" />
            {{ t('auth::login.remember') }}
        </label>

        <Button type="submit" :disabled="form.processing">{{ t('auth::login.submit') }}</Button>

        <p v-if="canRegister" class="text-center text-sm text-gray-500">
            {{ t('auth::login.no_account') }}
            <Link href="/register" class="underline">{{ t('auth::register.submit') }}</Link>
        </p>
    </form>
</template>
