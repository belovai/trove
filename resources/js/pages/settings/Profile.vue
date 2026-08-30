<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppCardRow from '@/components/ui/AppCardRow.vue';
import AppButton from '@/components/ui/AppButton.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';
import type { SettingsSection } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{ sections: SettingsSection[]; current: string }>();

const { t } = useTranslations();
const { user } = useAuth();

const initial = computed(() => (user.value?.display_name ?? '?').charAt(0).toUpperCase());

const form = useForm({ display_name: user.value?.display_name ?? '' });

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarForm = useForm<{ source: 'upload' | 'letter' | 'gravatar'; avatar: File | null }>({
    source: 'upload',
    avatar: null,
});

const hasEmail = computed(() => Boolean(user.value?.email));

const pickAvatar = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    target.value = '';

    if (file === null) {
        return;
    }

    avatarForm.source = 'upload';
    avatarForm.avatar = file;
    avatarForm.patch('/account/avatar', { preserveScroll: true, forceFormData: true });
};

const useGravatar = (): void => {
    avatarForm.source = 'gravatar';
    avatarForm.avatar = null;
    avatarForm.patch('/account/avatar', { preserveScroll: true });
};

const useLetterAvatar = (): void => {
    avatarForm.source = 'letter';
    avatarForm.avatar = null;
    avatarForm.patch('/account/avatar', { preserveScroll: true });
};

const justSaved = ref(false);
let savedTimeout: ReturnType<typeof setTimeout> | undefined;

const submit = (): void => {
    form.patch('/account', {
        preserveScroll: true,
        onSuccess: () => {
            justSaved.value = true;
            clearTimeout(savedTimeout);
            savedTimeout = setTimeout(() => {
                justSaved.value = false;
            }, 3000);
        },
    });
};

const showActions = computed(() => form.isDirty || justSaved.value);

onUnmounted(() => {
    clearTimeout(savedTimeout);
});
</script>

<template>
    <Head :title="t('user::account.section_profile')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <AppSection :title="t('user::account.block_profile')" :description="t('user::account.block_profile_hint')">
            <AppCard :padded="false">
                <form @submit.prevent="submit">
                    <AppCardRow :label="t('user::account.display_name')" :description="t('user::account.display_name_hint')">
                        <div class="sm:w-72">
                            <TextInput
                                id="profile-display-name"
                                v-model="form.display_name"
                                :invalid="Boolean(form.errors.display_name)"
                            />
                            <p v-if="form.errors.display_name" class="mt-1 text-xs text-danger-strong">
                                {{ form.errors.display_name }}
                            </p>
                        </div>
                    </AppCardRow>

                    <div
                        class="grid transition-all duration-150 ease-out"
                        :class="showActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                        :inert="!showActions"
                    >
                        <div class="overflow-hidden">
                            <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                <p v-if="justSaved" class="mr-auto text-xs text-muted">{{ t('user::ui.saved') }}</p>
                                <AppButton
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    :disabled="!form.isDirty"
                                    @click="form.reset()"
                                >
                                    {{ t('user::ui.reset') }}
                                </AppButton>
                                <AppButton type="submit" size="sm" :disabled="!form.isDirty" :loading="form.processing">
                                    {{ t('user::ui.save') }}
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </form>
            </AppCard>
        </AppSection>

        <AppSection :title="t('user::account.block_avatar')" :description="t('user::account.block_avatar_hint')">
            <AppCard>
                <AppCardRow :label="t('user::account.block_avatar')">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="user?.avatar_url"
                            :src="user.avatar_url"
                            alt=""
                            class="h-9 w-9 rounded-xl object-cover"
                            aria-hidden="true"
                        />
                        <span
                            v-else
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface text-sm font-semibold text-muted"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </span>

                        <AppButton
                            variant="secondary"
                            size="sm"
                            :loading="avatarForm.processing && avatarForm.source === 'upload'"
                            @click="avatarInput?.click()"
                        >
                            {{ t('user::account.avatar_upload') }}
                        </AppButton>
                        <AppButton
                            v-if="hasEmail && user?.avatar_source !== 'gravatar'"
                            variant="secondary"
                            size="sm"
                            :loading="avatarForm.processing && avatarForm.source === 'gravatar'"
                            @click="useGravatar"
                        >
                            {{ t('user::account.avatar_use_gravatar') }}
                        </AppButton>
                        <AppButton
                            v-if="user?.avatar_source !== 'letter'"
                            variant="secondary"
                            size="sm"
                            :loading="avatarForm.processing && avatarForm.source === 'letter'"
                            @click="useLetterAvatar"
                        >
                            {{ t('user::account.avatar_use_letter') }}
                        </AppButton>

                        <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="pickAvatar" />
                    </div>
                    <p v-if="avatarForm.errors.source" class="mt-1 text-xs text-danger-strong">
                        {{ avatarForm.errors.source }}
                    </p>
                    <p v-if="avatarForm.errors.avatar" class="mt-1 text-xs text-danger-strong">
                        {{ avatarForm.errors.avatar }}
                    </p>
                </AppCardRow>
            </AppCard>
        </AppSection>
    </SettingsLayout>
</template>
