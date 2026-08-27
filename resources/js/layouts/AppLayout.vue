<script setup lang="ts">
import Alert from '@/components/Alert.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const { user, isAuthenticated } = useAuth();
const { t } = useTranslations();

const signOut = (): void => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <nav class="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
                <Link href="/" class="font-semibold">Trove</Link>

                <div class="flex items-center gap-4 text-sm">
                    <Link href="/posts">{{ t('media::media.browse') }}</Link>

                    <template v-if="isAuthenticated">
                        <Link v-if="page.props.auth.can['media.upload']" href="/upload">{{ t('media::media.upload') }}</Link>
                        <Link href="/account">{{ user?.display_name }}</Link>
                        <button type="button" class="text-gray-500 hover:underline" @click="signOut">
                            {{ t('auth::login.sign_out') }}
                        </button>
                    </template>
                    <Link v-else href="/login">{{ t('auth::login.submit') }}</Link>
                </div>
            </nav>
        </header>

        <main class="mx-auto flex max-w-5xl flex-col gap-4 px-6 py-8">
            <Alert v-if="page.props.flash.error" variant="error">{{ page.props.flash.error }}</Alert>
            <Alert v-if="page.props.flash.success">{{ page.props.flash.success }}</Alert>

            <slot />
        </main>
    </div>
</template>
