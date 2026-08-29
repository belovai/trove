<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ChevronDownIcon, Cog6ToothIcon, ArrowRightStartOnRectangleIcon } from '@heroicons/vue/24/outline';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

const { user } = useAuth();
const { t } = useTranslations();

const isOpen = ref(false);

// Avatars are not implemented yet; the placeholder is the display name's first
// letter, which is also what the settings avatar block shows.
const initial = computed(() => (user.value?.display_name ?? '?').charAt(0).toUpperCase());

const close = (): void => {
    isOpen.value = false;
};

const signOut = (): void => {
    close();
    router.post('/logout');
};
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 hover:bg-surface"
            :aria-expanded="isOpen"
            :aria-label="t('user::ui.nav_profile_menu')"
            @click="isOpen = !isOpen"
        >
            <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-surface text-xs font-semibold text-muted"
                aria-hidden="true"
            >
                {{ initial }}
            </span>
            <span class="hidden text-sm text-text md:inline">{{ user?.display_name }}</span>
            <ChevronDownIcon class="h-4 w-4 text-muted" aria-hidden="true" />
        </button>

        <div v-if="isOpen" class="fixed inset-0 z-10" @click="close" />
        <div
            v-if="isOpen"
            class="absolute top-full right-0 z-20 mt-1.5 w-48 overflow-hidden rounded-lg border border-divider bg-panel shadow-pop"
        >
            <Link
                href="/settings"
                class="flex items-center gap-2 border-b border-divider px-3 py-2.5 text-sm text-text hover:bg-surface"
                @click="close"
            >
                <Cog6ToothIcon class="h-4 w-4" aria-hidden="true" />
                {{ t('user::ui.nav_settings') }}
            </Link>
            <button
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-left text-sm text-text hover:bg-surface"
                @click="signOut"
            >
                <ArrowRightStartOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
                {{ t('auth::login.sign_out') }}
            </button>
        </div>
    </div>
</template>
