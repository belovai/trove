<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ProfileMenu from './ProfileMenu.vue';
import AppLinkButton from '@/components/ui/AppLinkButton.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';

interface NavItem {
    href: string;
    label: string;
}

const page = usePage();
const { isAuthenticated, can } = useAuth();
const { t } = useTranslations();

const items = computed<NavItem[]>(() => {
    const list: NavItem[] = [
        { href: '/posts', label: t('user::ui.nav_browse') },
        { href: '/tags', label: t('user::ui.nav_tags') },
    ];

    if (isAuthenticated.value && can('media.upload')) {
        list.push({ href: '/upload', label: t('user::ui.nav_upload') });
    }

    return list;
});

const isActive = (href: string): boolean => page.url === href || page.url.startsWith(`${href}/`);
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-divider bg-panel">
        <div class="mx-auto flex w-full max-w-7xl items-center gap-4 px-4 py-3 md:px-6">
            <Link href="/" class="text-base font-semibold text-text">Trove</Link>

            <!-- From md up the navigation joins this row, immediately left of
                 the profile menu; below md it drops to its own row. -->
            <nav class="ml-auto hidden items-center gap-1 md:flex">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-md px-2.5 py-1.5 text-sm transition-colors"
                    :class="isActive(item.href) ? 'bg-surface font-medium text-text' : 'text-muted hover:text-text'"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="ml-auto flex items-center md:ml-0">
                <ProfileMenu v-if="isAuthenticated" />
                <AppLinkButton v-else href="/login" variant="primary" size="sm">
                    {{ t('auth::login.submit') }}
                </AppLinkButton>
            </div>
        </div>

        <nav class="flex justify-end gap-1 overflow-x-auto px-4 pb-2 md:hidden">
            <Link
                v-for="item in items"
                :key="item.href"
                :href="item.href"
                class="shrink-0 rounded-md px-2.5 py-1.5 text-sm transition-colors"
                :class="isActive(item.href) ? 'bg-surface font-medium text-text' : 'text-muted'"
            >
                {{ item.label }}
            </Link>
        </nav>
    </header>
</template>
