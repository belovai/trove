<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';
import FormField from '@/components/FormField.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import TextInput from '@/components/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

const props = defineProps<{
    emailPolicy: 'optional' | 'required' | 'off';
}>();

const { t } = useTranslations();

const showsEmail = computed(() => props.emailPolicy !== 'off');

const form = useForm({
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('auth::register.title')" />

    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <h2 class="text-lg font-semibold">{{ t('auth::register.title') }}</h2>

        <FormField id="username" :label="t('auth::register.username')" :error="form.errors.username">
            <TextInput
                id="username"
                v-model="form.username"
                autocomplete="username"
                :invalid="Boolean(form.errors.username)"
            />
        </FormField>

        <FormField
            v-if="showsEmail"
            id="email"
            :label="t('auth::register.email')"
            :hint="emailPolicy === 'optional' ? t('auth::register.email_hint') : undefined"
            :error="form.errors.email"
        >
            <TextInput
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                :invalid="Boolean(form.errors.email)"
            />
        </FormField>

        <FormField id="password" :label="t('auth::register.password')" :error="form.errors.password">
            <TextInput
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                :invalid="Boolean(form.errors.password)"
            />
        </FormField>

        <FormField id="password_confirmation" :label="t('auth::register.password_confirmation')">
            <TextInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
            />
        </FormField>

        <Button type="submit" :disabled="form.processing">{{ t('auth::register.submit') }}</Button>

        <p class="text-center text-sm text-gray-500">
            {{ t('auth::register.have_account') }}
            <Link href="/login" class="underline">{{ t('auth::login.submit') }}</Link>
        </p>
    </form>
</template>
