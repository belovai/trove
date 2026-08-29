<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { PencilSquareIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppTable from '@/components/ui/AppTable.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppLinkButton from '@/components/ui/AppLinkButton.vue';
import AppBadge from '@/components/ui/AppBadge.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import TextInput from '@/components/ui/TextInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import UserFormSlideOver from '@/components/settings/UserFormSlideOver.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { AdminUser, Paginated, SettingsSection, UserRank } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    sections: SettingsSection[];
    current: string;
    users: Paginated<AdminUser>;
    filters: { search: string | null; rank: string | null; status: string | null };
    ranks: UserRank[];
}>();

const { t } = useTranslations();

const search = ref(props.filters.search ?? '');
const rank = ref(props.filters.rank ?? '');
const status = ref(props.filters.status ?? '');

const applyFilters = (): void => {
    router.get(
        '/settings/users',
        { search: search.value || undefined, rank: rank.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true },
    );
};

const isFormOpen = ref(false);
const editing = ref<AdminUser | null>(null);

const add = (): void => {
    editing.value = null;
    isFormOpen.value = true;
};

const edit = (user: AdminUser): void => {
    editing.value = user;
    isFormOpen.value = true;
};

const initial = (user: AdminUser): string => user.display_name.charAt(0).toUpperCase();

const formatDate = (iso: string | null): string => {
    if (iso === null) {
        return t('user::account.never');
    }

    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(iso));
};
</script>

<template>
    <Head :title="t('user::account.users_title')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <AppSection :title="t('user::account.users_title')">
            <template #actions>
                <AppButton size="sm" @click="add">{{ t('user::account.add_user') }}</AppButton>
            </template>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <TextInput
                    id="users-search"
                    v-model="search"
                    :placeholder="t('user::ui.search')"
                    class="sm:max-w-xs"
                    @keyup.enter="applyFilters"
                />
                <AppSelect id="users-filter-rank" v-model="rank" class="sm:w-48" @change="applyFilters">
                    <option value="">{{ t('user::account.all_ranks') }}</option>
                    <option v-for="item in props.ranks" :key="item" :value="item">{{ t(`user::rank.${item}`) }}</option>
                </AppSelect>
                <AppSelect id="users-filter-status" v-model="status" class="sm:w-48" @change="applyFilters">
                    <option value="">{{ t('user::account.all_statuses') }}</option>
                    <option value="active">{{ t('user::account.status_active') }}</option>
                    <option value="banned">{{ t('user::account.status_banned') }}</option>
                </AppSelect>
            </div>

            <AppCard :padded="false">
                <AppTable v-if="props.users.data.length > 0">
                    <template #head>
                        <tr>
                            <th>{{ t('user::account.username') }}</th>
                            <th class="hidden md:table-cell">{{ t('user::account.display_name') }}</th>
                            <th>{{ t('user::account.rank') }}</th>
                            <th>{{ t('user::account.filter_status') }}</th>
                            <th class="hidden md:table-cell">{{ t('user::account.registered') }}</th>
                            <th class="hidden sm:table-cell">{{ t('user::account.uploads') }}</th>
                            <th><span class="sr-only">{{ t('user::ui.edit') }}</span></th>
                        </tr>
                    </template>

                    <tr v-for="user in props.users.data" :key="user.username">
                        <td>
                            <span class="flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-surface text-xs font-semibold text-muted"
                                    aria-hidden="true"
                                >
                                    {{ initial(user) }}
                                </span>
                                <span class="text-text">{{ user.username }}</span>
                            </span>
                        </td>
                        <td class="hidden text-muted md:table-cell">{{ user.display_name }}</td>
                        <td><AppBadge variant="accent">{{ t(`user::rank.${user.rank}`) }}</AppBadge></td>
                        <td>
                            <AppBadge :variant="user.is_banned ? 'danger' : 'success'">
                                {{ user.is_banned ? t('user::account.status_banned') : t('user::account.status_active') }}
                            </AppBadge>
                        </td>
                        <td class="hidden text-muted md:table-cell">{{ formatDate(user.registered_at) }}</td>
                        <td class="hidden text-muted sm:table-cell">{{ user.uploads }}</td>
                        <td>
                            <span class="flex justify-end">
                                <AppButton variant="ghost" size="icon" :aria-label="t('user::ui.edit')" @click="edit(user)">
                                    <PencilSquareIcon class="h-5 w-5" aria-hidden="true" />
                                </AppButton>
                            </span>
                        </td>
                    </tr>
                </AppTable>

                <AppEmptyState v-else :title="t('user::account.no_users')" />
            </AppCard>

            <div v-if="props.users.data.length > 0" class="flex items-center justify-center gap-3 text-sm text-muted">
                <AppLinkButton v-if="props.users.prev_page_url" :href="props.users.prev_page_url" variant="ghost" size="sm">
                    &larr;
                </AppLinkButton>
                <span v-else class="px-2 opacity-40">&larr;</span>

                <span>{{ props.users.current_page }} / {{ props.users.last_page }}</span>

                <AppLinkButton v-if="props.users.next_page_url" :href="props.users.next_page_url" variant="ghost" size="sm">
                    &rarr;
                </AppLinkButton>
                <span v-else class="px-2 opacity-40">&rarr;</span>
            </div>
        </AppSection>

        <UserFormSlideOver v-if="isFormOpen" :user="editing" :ranks="props.ranks" @close="isFormOpen = false" />
    </SettingsLayout>
</template>
