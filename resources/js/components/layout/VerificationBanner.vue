<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

const { user } = useAuth();
const { t } = useTranslations();

/** Only meaningful for a signed-in account that has an address to confirm. */
const show = computed(() => user.value !== null && user.value.email !== null && !user.value.email_verified);

const resend = (): void => {
    router.post('/email/verification-notification', {}, { preserveScroll: true });
};
</script>

<template>
    <div v-if="show" class="border-b border-divider bg-warning-soft px-4 py-2 text-sm text-text md:px-6">
        <div class="mx-auto flex w-full max-w-7xl items-center gap-3">
            <p class="flex-1">{{ t('auth::verification.banner') }}</p>
            <AppButton type="button" variant="secondary" size="sm" @click="resend">
                {{ t('auth::verification.resend') }}
            </AppButton>
        </div>
    </div>
</template>
