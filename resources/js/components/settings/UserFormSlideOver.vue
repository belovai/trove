<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppSlideOver from '@/components/ui/AppSlideOver.vue';
import ModalFooter from '@/components/ui/ModalFooter.vue';
import AppButton from '@/components/ui/AppButton.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppToggle from '@/components/ui/AppToggle.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { AdminUser, UserRank } from '@/types/inertia';

const props = defineProps<{ user: AdminUser | null; ranks: UserRank[] }>();

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();

// One component, two modes: create adds username and an initial password,
// everything else is shared.
const form = useForm({
    username: '',
    password: '',
    display_name: props.user?.display_name ?? '',
    email: props.user?.email ?? '',
    rank: (props.user?.rank ?? 'regular') as UserRank,
    is_banned: props.user?.is_banned ?? false,
    ban_reason: props.user?.ban_reason ?? '',
});

const setRank = (value: string | number | null): void => {
    form.rank = value as UserRank;
};

const submit = (): void => {
    const options = { onSuccess: () => emit('close') };

    if (props.user === null) {
        form.post('/settings/users', options);

        return;
    }

    form
        .transform((data) => ({
            display_name: data.display_name,
            email: data.email,
            rank: data.rank,
            is_banned: data.is_banned,
            ban_reason: data.ban_reason,
        }))
        .patch(`/settings/users/${props.user.username}`, options);
};
</script>

<template>
    <AppSlideOver
        :title="props.user ? t('user::account.edit_user') : t('user::account.add_user')"
        @close="emit('close')"
    >
        <form id="user-form" class="flex flex-col gap-4" @submit.prevent="submit">
            <template v-if="props.user === null">
                <FormField id="user-username" :label="t('user::account.username')" :error="form.errors.username">
                    <TextInput id="user-username" v-model="form.username" :invalid="Boolean(form.errors.username)" />
                </FormField>

                <FormField
                    id="user-password"
                    :label="t('user::account.initial_password')"
                    :error="form.errors.password"
                >
                    <TextInput
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :invalid="Boolean(form.errors.password)"
                    />
                </FormField>
            </template>

            <FormField
                id="user-display-name"
                :label="t('user::account.display_name')"
                :error="form.errors.display_name"
            >
                <TextInput id="user-display-name" v-model="form.display_name" />
            </FormField>

            <FormField id="user-email" :label="t('user::account.email')" :error="form.errors.email">
                <TextInput id="user-email" v-model="form.email" type="email" :invalid="Boolean(form.errors.email)" />
            </FormField>

            <FormField id="user-rank" :label="t('user::account.rank')" :error="form.errors.rank">
                <AppSelect id="user-rank" :model-value="form.rank" :invalid="Boolean(form.errors.rank)" @update:model-value="setRank">
                    <option v-for="rank in props.ranks" :key="rank" :value="rank">
                        {{ t(`user::rank.${rank}`) }}
                    </option>
                </AppSelect>
            </FormField>

            <div v-if="props.user" class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <label for="user-banned" class="text-sm font-medium text-text">
                        {{ t('user::account.ban_user') }}
                    </label>
                    <AppToggle id="user-banned" v-model="form.is_banned" />
                </div>

                <FormField
                    v-if="form.is_banned"
                    id="user-ban-reason"
                    :label="t('user::account.ban_reason')"
                    :error="form.errors.ban_reason"
                >
                    <AppTextarea id="user-ban-reason" v-model="form.ban_reason" :rows="3" />
                </FormField>
            </div>
        </form>

        <template #footer>
            <ModalFooter>
                <AppButton variant="secondary" @click="emit('close')">{{ t('user::ui.cancel') }}</AppButton>
                <AppButton type="submit" form="user-form" :loading="form.processing">
                    {{ t('user::ui.save') }}
                </AppButton>
            </ModalFooter>
        </template>
    </AppSlideOver>
</template>
