<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: GuestLayout });

const props = defineProps<{ email: string | null }>();

const { t } = useTranslations();

const resend = (): void => {
    router.post('/email/verification-notification');
};
</script>

<template>
    <Head :title="t('auth::verification.title')" />

    <div class="flex flex-col gap-4">
        <h2 class="text-lg font-semibold text-text">{{ t('auth::verification.title') }}</h2>

        <p v-if="props.email" class="text-sm text-muted">
            {{ t('auth::verification.body', { email: props.email }) }}
        </p>
        <p v-else class="text-sm text-muted">{{ t('auth::verification.no_email') }}</p>

        <AppButton v-if="props.email" type="button" block @click="resend">
            {{ t('auth::verification.resend') }}
        </AppButton>

        <Link href="/settings/account" class="text-center text-sm text-accent hover:text-accent-hover">
            {{ t('user::account.section_account') }}
        </Link>
    </div>
</template>
