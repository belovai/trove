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

        <AppSection :title="t('user::account.block_avatar')">
            <AppCard>
                <AppCardRow :label="t('user::account.block_avatar')" :description="t('user::ui.not_available_yet')">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface text-sm font-semibold text-muted"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </span>
                        <AppButton variant="secondary" size="sm" disabled>
                            {{ t('user::account.avatar_upload') }}
                        </AppButton>
                    </div>
                </AppCardRow>
            </AppCard>
        </AppSection>
    </SettingsLayout>
</template>
