<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from '@/components/ui/AppButton.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

const { user } = useAuth();
const { t } = useTranslations();

/** Only meaningful for a signed-in account that has an address to confirm. */
const show = computed(() => user.value !== null && user.value.email !== null && !user.value.email_verified);

const RESEND_COOLDOWN_SECONDS = 60;
const COOLDOWN_STORAGE_KEY = 'trove.verification.resend_until';
const cooldown = ref(0);
let cooldownInterval: ReturnType<typeof setInterval> | undefined;

const tickFrom = (until: number): void => {
    clearInterval(cooldownInterval);
    const update = (): void => {
        const remaining = Math.ceil((until - Date.now()) / 1000);
        cooldown.value = Math.max(remaining, 0);
        if (cooldown.value <= 0) {
            clearInterval(cooldownInterval);
            localStorage.removeItem(COOLDOWN_STORAGE_KEY);
        }
    };
    update();
    cooldownInterval = setInterval(update, 1000);
};

onMounted(() => {
    const stored = Number(localStorage.getItem(COOLDOWN_STORAGE_KEY));
    if (stored > Date.now()) {
        tickFrom(stored);
    }
});

onUnmounted(() => clearInterval(cooldownInterval));

const resend = (): void => {
    router.post(
        '/email/verification-notification',
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const until = Date.now() + RESEND_COOLDOWN_SECONDS * 1000;
                localStorage.setItem(COOLDOWN_STORAGE_KEY, String(until));
                tickFrom(until);
            },
        },
    );
};
</script>

<template>
    <div v-if="show" class="border-b border-divider bg-warning-soft px-4 py-2 text-sm text-text md:px-6">
        <div class="mx-auto flex w-full max-w-7xl items-center gap-3">
            <p class="flex-1">{{ t('auth::verification.banner') }}</p>
            <AppButton type="button" variant="secondary" size="sm" :disabled="cooldown > 0" @click="resend">
                {{ cooldown > 0 ? t('auth::verification.resend_cooldown', { seconds: cooldown }) : t('auth::verification.resend') }}
            </AppButton>
        </div>
    </div>
</template>
