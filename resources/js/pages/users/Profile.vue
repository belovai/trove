<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import AppBadge from '@/components/ui/AppBadge.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import AppStatTile from '@/components/ui/AppStatTile.vue';
import MediaCard from '@/components/MediaCard.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaCardData, Paginated, ProfileNotices, ProfileSummary } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    profile: ProfileSummary;
    uploads: Paginated<MediaCardData> | null;
    notices: ProfileNotices;
}>();

const { t } = useTranslations();

const initial = computed(() => props.profile.display_name.charAt(0).toUpperCase());

const registered = computed(() =>
    props.profile.registered_at === null ? '—' : new Date(props.profile.registered_at).toLocaleDateString(),
);
</script>

<template>
    <Head :title="props.profile.display_name" />

    <div class="flex flex-col gap-6">
        <header class="flex items-center gap-4">
            <img
                v-if="props.profile.avatar_url"
                :src="props.profile.avatar_url"
                alt=""
                class="h-16 w-16 rounded-xl object-cover"
                aria-hidden="true"
            />
            <span
                v-else
                class="flex h-16 w-16 items-center justify-center rounded-xl bg-surface text-2xl font-semibold text-muted"
                aria-hidden="true"
            >
                {{ initial }}
            </span>

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="truncate text-xl font-semibold text-text">{{ props.profile.display_name }}</h1>
                    <AppBadge v-if="props.profile.is_banned" variant="danger">
                        {{ t('user::profile.banned') }}
                    </AppBadge>
                </div>
                <p class="text-sm text-muted">@{{ props.profile.username }}</p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <AppStatTile :label="t('user::profile.registered')" :value="registered" />
            <AppStatTile :label="t('user::profile.uploads')" :value="String(props.profile.upload_count)" />
            <AppStatTile :label="t('user::profile.rank')" :value="props.profile.rank_label" />
        </div>

        <Alert v-if="props.profile.is_banned" variant="error">{{ t('user::profile.notice_banned') }}</Alert>
        <Alert v-if="props.notices.uploads_hidden" variant="warning">{{ t('user::profile.notice_hidden') }}</Alert>
        <Alert v-if="props.notices.has_anonymous" variant="warning">{{ t('user::profile.notice_anonymous') }}</Alert>

        <AppEmptyState v-if="props.uploads === null" :title="t('user::profile.hidden')" />

        <p v-else-if="props.uploads.data.length === 0" class="text-sm text-muted">{{ t('user::profile.empty') }}</p>

        <template v-else>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <MediaCard v-for="item in props.uploads.data" :key="item.hash_id" :media="item" />
            </div>

            <nav v-if="props.uploads.last_page > 1" class="flex items-center justify-center gap-4 text-sm text-muted">
                <Link
                    v-if="props.uploads.prev_page_url"
                    :href="props.uploads.prev_page_url"
                    class="text-accent hover:text-accent-hover"
                >&larr;</Link>
                <span>{{ props.uploads.current_page }} / {{ props.uploads.last_page }}</span>
                <Link
                    v-if="props.uploads.next_page_url"
                    :href="props.uploads.next_page_url"
                    class="text-accent hover:text-accent-hover"
                >&rarr;</Link>
            </nav>
        </template>
    </div>
</template>
