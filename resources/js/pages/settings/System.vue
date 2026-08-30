<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppCardRow from '@/components/ui/AppCardRow.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppToggle from '@/components/ui/AppToggle.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import Alert from '@/components/ui/Alert.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { SettingsSection } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    sections: SettingsSection[];
    current: string;
    settings: Record<string, string | boolean | string[]>;
    registration_modes: string[];
    email_policies: string[];
    verification_modes: string[];
    visibilities: string[];
}>();

const { t } = useTranslations();

// General block: app.name. Each block is its own form and PATCHes only the
// keys it owns — the endpoint is already partial-write, so blocks save
// independently rather than sharing one bottom action bar.
const generalForm = useForm({
    appName: String(props.settings['app.name'] ?? ''),
});

const appNameError = computed(() => (generalForm.errors as Record<string, string>)['app.name']);

const generalJustSaved = ref(false);
let generalSavedTimeout: ReturnType<typeof setTimeout> | undefined;

const submitGeneral = (): void => {
    generalForm
        .transform((data) => ({ 'app.name': data.appName }))
        .patch('/settings/system', {
            preserveScroll: true,
            onSuccess: () => {
                generalJustSaved.value = true;
                clearTimeout(generalSavedTimeout);
                generalSavedTimeout = setTimeout(() => {
                    generalJustSaved.value = false;
                }, 3000);
            },
        });
};

const showGeneralActions = computed(() => generalForm.isDirty || generalJustSaved.value);

// Media block: default visibility for newly uploaded media.
const mediaForm = useForm({
    defaultVisibility: String(props.settings['media.default_visibility'] ?? 'public'),
});

const mediaJustSaved = ref(false);
let mediaSavedTimeout: ReturnType<typeof setTimeout> | undefined;

const submitMedia = (): void => {
    mediaForm
        .transform((data) => ({ 'media.default_visibility': data.defaultVisibility }))
        .patch('/settings/system', {
            preserveScroll: true,
            onSuccess: () => {
                mediaJustSaved.value = true;
                clearTimeout(mediaSavedTimeout);
                mediaSavedTimeout = setTimeout(() => {
                    mediaJustSaved.value = false;
                }, 3000);
            },
        });
};

const showMediaActions = computed(() => mediaForm.isDirty || mediaJustSaved.value);

// Registration block: mode, email policy, approval.
const registrationForm = useForm({
    registrationMode: String(props.settings['registration.mode'] ?? 'open'),
    registrationEmail: String(props.settings['registration.email'] ?? 'optional'),
    registrationApproval: Boolean(props.settings['registration.approval']),
    registrationVerify: String(props.settings['registration.verify'] ?? 'soft'),
    registrationBlockedNames: (
        (props.settings['registration.blocked_names'] as string[] | undefined) ?? []
    ).join('\n'),
});

/**
 * The backend validates against dotted keys (`registration.mode`, ...) per
 * `UpdateSystemSettingsRequest::KEYS`, so that's how it reports errors back,
 * even though the form's own fields are named without dots (see submitRegistration()).
 */
const registrationModeError = computed(
    () => (registrationForm.errors as Record<string, string>)['registration.mode'],
);
const registrationEmailError = computed(
    () => (registrationForm.errors as Record<string, string>)['registration.email'],
);

const registrationJustSaved = ref(false);
let registrationSavedTimeout: ReturnType<typeof setTimeout> | undefined;

const submitRegistration = (): void => {
    registrationForm
        .transform((data) => ({
            'registration.mode': data.registrationMode,
            'registration.email': data.registrationEmail,
            'registration.approval': data.registrationApproval,
            'registration.verify': data.registrationVerify,
            'registration.blocked_names': data.registrationBlockedNames
                .split('\n')
                .map((name) => name.trim())
                .filter((name) => name.length > 0),
        }))
        .patch('/settings/system', {
            preserveScroll: true,
            onSuccess: () => {
                registrationJustSaved.value = true;
                clearTimeout(registrationSavedTimeout);
                registrationSavedTimeout = setTimeout(() => {
                    registrationJustSaved.value = false;
                }, 3000);
            },
        });
};

const showRegistrationActions = computed(() => registrationForm.isDirty || registrationJustSaved.value);

/** The combination that leaves a forgotten password unrecoverable. */
const showRecoveryWarning = computed(
    () => registrationForm.registrationEmail === 'off' && registrationForm.registrationApproval === false,
);

onUnmounted(() => {
    clearTimeout(generalSavedTimeout);
    clearTimeout(mediaSavedTimeout);
    clearTimeout(registrationSavedTimeout);
});
</script>

<template>
    <Head :title="t('setting::setting.title')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <div class="flex flex-col gap-8">
            <AppSection
                :title="t('setting::setting.block_general')"
                :description="t('setting::setting.block_general_hint')"
            >
                <AppCard :padded="false">
                    <form @submit.prevent="submitGeneral">
                        <AppCardRow
                            :label="t('setting::setting.app_name')"
                            :description="t('setting::setting.app_name_hint')"
                        >
                            <div class="sm:w-72">
                                <TextInput
                                    id="system-app-name"
                                    v-model="generalForm.appName"
                                    :invalid="Boolean(appNameError)"
                                />
                                <p v-if="appNameError" class="mt-1 text-xs text-danger-strong">
                                    {{ appNameError }}
                                </p>
                            </div>
                        </AppCardRow>

                        <div
                            class="grid transition-all duration-150 ease-out"
                            :class="showGeneralActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                            :inert="!showGeneralActions"
                        >
                            <div class="overflow-hidden">
                                <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                    <p v-if="generalJustSaved" class="mr-auto text-xs text-muted">
                                        {{ t('user::ui.saved') }}
                                    </p>
                                    <AppButton
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="!generalForm.isDirty"
                                        @click="generalForm.reset()"
                                    >
                                        {{ t('user::ui.reset') }}
                                    </AppButton>
                                    <AppButton
                                        type="submit"
                                        size="sm"
                                        :disabled="!generalForm.isDirty"
                                        :loading="generalForm.processing"
                                    >
                                        {{ t('user::ui.save') }}
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </AppCard>
            </AppSection>

            <AppSection
                :title="t('setting::setting.block_media')"
                :description="t('setting::setting.block_media_hint')"
            >
                <AppCard :padded="false">
                    <form @submit.prevent="submitMedia">
                        <AppCardRow
                            :label="t('setting::setting.media_default_visibility')"
                            :description="t('setting::setting.media_default_visibility_hint')"
                        >
                            <div class="sm:w-72">
                                <AppSelect id="system-media-default-visibility" v-model="mediaForm.defaultVisibility">
                                    <option v-for="value in props.visibilities" :key="value" :value="value">
                                        {{ t(`media::visibility.${value}`) }}
                                    </option>
                                </AppSelect>
                            </div>
                        </AppCardRow>

                        <div
                            class="grid transition-all duration-150 ease-out"
                            :class="showMediaActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                            :inert="!showMediaActions"
                        >
                            <div class="overflow-hidden">
                                <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                    <p v-if="mediaJustSaved" class="mr-auto text-xs text-muted">
                                        {{ t('user::ui.saved') }}
                                    </p>
                                    <AppButton
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="!mediaForm.isDirty"
                                        @click="mediaForm.reset()"
                                    >
                                        {{ t('user::ui.reset') }}
                                    </AppButton>
                                    <AppButton
                                        type="submit"
                                        size="sm"
                                        :disabled="!mediaForm.isDirty"
                                        :loading="mediaForm.processing"
                                    >
                                        {{ t('user::ui.save') }}
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </AppCard>
            </AppSection>

            <AppSection
                :title="t('setting::setting.block_registration')"
                :description="t('setting::setting.block_registration_hint')"
            >
                <AppCard :padded="false">
                    <form @submit.prevent="submitRegistration">
                        <AppCardRow
                            :label="t('setting::setting.registration_mode')"
                            :description="t('setting::setting.registration_mode_hint')"
                        >
                            <div class="sm:w-72">
                                <AppSelect
                                    id="system-registration-mode"
                                    v-model="registrationForm.registrationMode"
                                    :invalid="Boolean(registrationModeError)"
                                >
                                    <option v-for="mode in props.registration_modes" :key="mode" :value="mode">
                                        {{ t(`setting::setting.registration_mode_${mode}`) }}
                                    </option>
                                </AppSelect>
                                <p v-if="registrationModeError" class="mt-1 text-xs text-danger-strong">
                                    {{ registrationModeError }}
                                </p>
                            </div>
                        </AppCardRow>

                        <AppCardRow
                            :label="t('setting::setting.registration_email')"
                            :description="t('setting::setting.registration_email_hint')"
                        >
                            <div class="sm:w-72">
                                <AppSelect
                                    id="system-registration-email"
                                    v-model="registrationForm.registrationEmail"
                                    :invalid="Boolean(registrationEmailError)"
                                >
                                    <option v-for="policy in props.email_policies" :key="policy" :value="policy">
                                        {{ t(`setting::setting.registration_email_${policy}`) }}
                                    </option>
                                </AppSelect>
                                <p v-if="registrationEmailError" class="mt-1 text-xs text-danger-strong">
                                    {{ registrationEmailError }}
                                </p>
                            </div>
                        </AppCardRow>

                        <AppCardRow
                            :label="t('setting::setting.registration_approval')"
                            :description="t('setting::setting.registration_approval_hint')"
                        >
                            <AppToggle
                                id="system-registration-approval"
                                v-model="registrationForm.registrationApproval"
                            />
                        </AppCardRow>

                        <AppCardRow
                            :label="t('setting::setting.registration_verify')"
                            :description="t('setting::setting.registration_verify_hint')"
                        >
                            <div class="sm:w-72">
                                <AppSelect
                                    id="system-registration-verify"
                                    v-model="registrationForm.registrationVerify"
                                >
                                    <option v-for="mode in props.verification_modes" :key="mode" :value="mode">
                                        {{ t(`setting::setting.registration_verify_${mode}`) }}
                                    </option>
                                </AppSelect>
                            </div>
                        </AppCardRow>

                        <AppCardRow
                            :label="t('setting::setting.registration_blocked_names')"
                            :description="t('setting::setting.registration_blocked_names_hint')"
                        >
                            <div class="sm:w-72">
                                <AppTextarea
                                    id="system-registration-blocked-names"
                                    v-model="registrationForm.registrationBlockedNames"
                                    :rows="4"
                                />
                            </div>
                        </AppCardRow>

                        <div
                            class="grid transition-all duration-150 ease-out"
                            :class="
                                showRegistrationActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
                            "
                            :inert="!showRegistrationActions"
                        >
                            <div class="overflow-hidden">
                                <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                    <p v-if="registrationJustSaved" class="mr-auto text-xs text-muted">
                                        {{ t('user::ui.saved') }}
                                    </p>
                                    <AppButton
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="!registrationForm.isDirty"
                                        @click="registrationForm.reset()"
                                    >
                                        {{ t('user::ui.reset') }}
                                    </AppButton>
                                    <AppButton
                                        type="submit"
                                        size="sm"
                                        :disabled="!registrationForm.isDirty"
                                        :loading="registrationForm.processing"
                                    >
                                        {{ t('user::ui.save') }}
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </AppCard>

                <Alert v-if="showRecoveryWarning" variant="warning" class="mt-3">
                    {{ t('setting::setting.no_recovery_warning') }}
                </Alert>
            </AppSection>
        </div>
    </SettingsLayout>
</template>
