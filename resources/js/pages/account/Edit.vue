<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import FormField from '@/components/FormField.vue';
import TextInput from '@/components/TextInput.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: AppLayout });

defineProps<{
    locales: string[];
}>();

const { t } = useTranslations();
const { user } = useAuth();

const profile = useForm({
    display_name: user.value?.display_name ?? '',
    locale: user.value?.locale ?? '',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const removal = useForm({
    current_password: '',
});

const saveProfile = (): void => {
    profile.patch('/account', { preserveScroll: true });
};

const changePassword = (): void => {
    password.patch('/account/password', {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });
};

const deleteAccount = (): void => {
    removal.delete('/account');
};
</script>

<template>
    <Head :title="t('user::account.title')" />

    <div class="flex flex-col gap-8">
        <section class="flex max-w-md flex-col gap-4">
            <h2 class="text-lg font-semibold">{{ t('user::account.profile') }}</h2>

            <FormField id="username" :label="t('user::account.username')" :hint="t('user::account.username_hint')">
                <TextInput id="username" :model-value="user?.username ?? ''" disabled />
            </FormField>

            <FormField
                id="display_name"
                :label="t('user::account.display_name')"
                :hint="t('user::account.display_name_hint')"
                :error="profile.errors.display_name"
            >
                <TextInput
                    id="display_name"
                    v-model="profile.display_name"
                    :invalid="Boolean(profile.errors.display_name)"
                />
            </FormField>

            <FormField id="locale" :label="t('user::account.locale')" :error="profile.errors.locale">
                <select
                    id="locale"
                    v-model="profile.locale"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <option value="">{{ t('user::account.locale_default') }}</option>
                    <option v-for="code in locales" :key="code" :value="code">{{ code }}</option>
                </select>
            </FormField>

            <Button type="button" :disabled="profile.processing" @click="saveProfile">
                {{ t('user::account.save') }}
            </Button>
        </section>

        <section class="flex max-w-md flex-col gap-4">
            <h2 class="text-lg font-semibold">{{ t('user::account.password') }}</h2>

            <FormField
                id="current_password"
                :label="t('user::account.current_password')"
                :error="password.errors.current_password"
            >
                <TextInput
                    id="current_password"
                    v-model="password.current_password"
                    type="password"
                    autocomplete="current-password"
                    :invalid="Boolean(password.errors.current_password)"
                />
            </FormField>

            <FormField id="new_password" :label="t('user::account.new_password')" :error="password.errors.password">
                <TextInput
                    id="new_password"
                    v-model="password.password"
                    type="password"
                    autocomplete="new-password"
                    :invalid="Boolean(password.errors.password)"
                />
            </FormField>

            <FormField id="new_password_confirmation" :label="t('user::account.new_password_confirmation')">
                <TextInput
                    id="new_password_confirmation"
                    v-model="password.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </FormField>

            <Button type="button" :disabled="password.processing" @click="changePassword">
                {{ t('user::account.change_password') }}
            </Button>
        </section>

        <section class="flex max-w-md flex-col gap-4">
            <h2 class="text-lg font-semibold text-red-700 dark:text-red-400">{{ t('user::account.danger') }}</h2>
            <p class="text-sm text-gray-500">{{ t('user::account.danger_hint') }}</p>

            <FormField
                id="delete_password"
                :label="t('user::account.current_password')"
                :error="removal.errors.current_password"
            >
                <TextInput
                    id="delete_password"
                    v-model="removal.current_password"
                    type="password"
                    autocomplete="current-password"
                    :invalid="Boolean(removal.errors.current_password)"
                />
            </FormField>

            <Button type="button" :disabled="removal.processing" @click="deleteAccount">
                {{ t('user::account.delete') }}
            </Button>
        </section>
    </div>
</template>
