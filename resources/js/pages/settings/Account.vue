<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppCardRow from '@/components/ui/AppCardRow.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppBadge from '@/components/ui/AppBadge.vue';
import AppStatTile from '@/components/ui/AppStatTile.vue';
import AppToggle from '@/components/ui/AppToggle.vue';
import DangerZone from '@/components/ui/DangerZone.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import DeleteAccountModal from '@/components/settings/DeleteAccountModal.vue';
import { useAuth } from '@/composables/useAuth';
import { useDateFormat } from '@/composables/useDateFormat';
import { useTranslations } from '@/composables/useTranslations';
import type { AccountStats, SettingsSection, TimezoneOption } from '@/types/inertia';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    sections: SettingsSection[];
    current: string;
    locales: string[];
    email: string | null;
    stats: AccountStats;
    visibilities: string[];
    timezones: TimezoneOption[];
    date_formats: string[];
    time_formats: string[];
}>();

const { t } = useTranslations();
const { user } = useAuth();
const { formatDate, preview } = useDateFormat();

const initial = computed(() => (user.value?.display_name ?? '?').charAt(0).toUpperCase());

const formatRegistered = (iso: string | null): string => formatDate(iso, t('user::account.never'));

// General block: email, language, default content filter.
const generalForm = useForm({
    email: props.email ?? '',
    locale: user.value?.locale ?? '',
    timezone: user.value?.timezone ?? '',
    date_format: user.value?.date_format ?? '',
    time_format: user.value?.time_format ?? '',
    default_safety_filter: user.value?.default_safety_filter ?? '',
    default_visibility: user.value?.default_visibility ?? '',
    show_unsafe_content: user.value?.show_unsafe_content ?? false,
});

// The timezone list is long, so it is grouped by region the way every OS
// picker does; the offset is computed server-side and shown in the label.
const timezoneGroups = computed(() => {
    const groups = new Map<string, TimezoneOption[]>();

    for (const zone of props.timezones) {
        const region = zone.value.includes('/') ? zone.value.split('/')[0] : 'UTC';
        const bucket = groups.get(region);

        if (bucket === undefined) {
            groups.set(region, [zone]);
        } else {
            bucket.push(zone);
        }
    }

    return [...groups.entries()].map(([region, zones]) => ({ region, zones }));
});

const timezoneLabel = (zone: TimezoneOption): string =>
    `${zone.value.replaceAll('_', ' ')} (${zone.offset})`;

// Previews follow the timezone select's unsaved value, so the three fields
// agree while they are being changed.
const formatPreview = (pattern: string): string =>
    preview(pattern, generalForm.timezone === '' ? undefined : generalForm.timezone);

const generalJustSaved = ref(false);
let generalSavedTimeout: ReturnType<typeof setTimeout> | undefined;

const submitGeneral = (): void => {
    generalForm.patch('/account', {
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

// Security block: password change.
const securityForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const securityJustSaved = ref(false);
let securitySavedTimeout: ReturnType<typeof setTimeout> | undefined;

const submitSecurity = (): void => {
    securityForm.patch('/account/password', {
        preserveScroll: true,
        onSuccess: () => {
            securityForm.reset();
            securityJustSaved.value = true;
            clearTimeout(securitySavedTimeout);
            securitySavedTimeout = setTimeout(() => {
                securityJustSaved.value = false;
            }, 3000);
        },
    });
};

const showSecurityActions = computed(() => securityForm.isDirty || securityJustSaved.value);

onUnmounted(() => {
    clearTimeout(generalSavedTimeout);
    clearTimeout(securitySavedTimeout);
});

const isDeleteOpen = ref(false);
</script>

<template>
    <Head :title="t('user::account.section_account')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <AppSection :title="t('user::account.block_info')">
            <AppCard :padded="false">
                <AppCardRow :label="t('user::account.username')" :description="t('user::account.username_hint')">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface text-sm font-semibold text-muted"
                        aria-hidden="true"
                    >
                        {{ initial }}
                    </span>
                </AppCardRow>
                <AppCardRow :label="t('user::account.registered')">
                    {{ formatRegistered(props.stats.registered_at) }}
                </AppCardRow>
                <AppCardRow :label="t('user::account.last_seen')">
                    {{ formatRegistered(props.stats.last_seen_at) }}
                </AppCardRow>
                <AppCardRow :label="t('user::account.rank')">
                    <AppBadge variant="accent">{{ props.stats.rank }}</AppBadge>
                </AppCardRow>

                <div class="grid grid-cols-2 gap-3 px-5 py-4 sm:grid-cols-3">
                    <AppStatTile :label="t('user::account.uploads')" :value="String(props.stats.uploads)" />
                    <AppStatTile :label="t('user::account.favorites')" :value="String(props.stats.favorites)" />
                    <AppStatTile :label="t('user::account.comments')" :value="String(props.stats.comments)" />
                    <AppStatTile :label="t('user::account.liked')" :value="String(props.stats.liked)" />
                    <AppStatTile :label="t('user::account.disliked')" :value="String(props.stats.disliked)" />
                </div>
            </AppCard>
        </AppSection>

        <AppSection :title="t('user::account.block_general')" :description="t('user::account.block_general_hint')">
            <AppCard :padded="false">
                <form @submit.prevent="submitGeneral">
                    <div class="flex flex-col gap-4 px-5 py-4">
                        <FormField
                            id="account-email"
                            :label="t('user::account.email')"
                            :hint="t('user::account.email_hint')"
                            :error="generalForm.errors.email"
                        >
                            <TextInput
                                id="account-email"
                                v-model="generalForm.email"
                                type="email"
                                autocomplete="email"
                                :invalid="Boolean(generalForm.errors.email)"
                            />
                        </FormField>

                        <FormField
                            id="account-locale"
                            :label="t('user::account.locale')"
                            :error="generalForm.errors.locale"
                        >
                            <AppSelect id="account-locale" v-model="generalForm.locale" :invalid="Boolean(generalForm.errors.locale)">
                                <option value="">{{ t('user::account.locale_default') }}</option>
                                <option v-for="item in props.locales" :key="item" :value="item">{{ item }}</option>
                            </AppSelect>
                        </FormField>

                        <FormField
                            id="account-timezone"
                            :label="t('user::account.timezone')"
                            :hint="t('user::account.timezone_hint')"
                            :error="generalForm.errors.timezone"
                        >
                            <AppSelect
                                id="account-timezone"
                                v-model="generalForm.timezone"
                                :invalid="Boolean(generalForm.errors.timezone)"
                            >
                                <option value="">{{ t('user::account.format_system') }}</option>
                                <optgroup v-for="group in timezoneGroups" :key="group.region" :label="group.region">
                                    <option v-for="zone in group.zones" :key="zone.value" :value="zone.value">
                                        {{ timezoneLabel(zone) }}
                                    </option>
                                </optgroup>
                            </AppSelect>
                        </FormField>

                        <FormField
                            id="account-date-format"
                            :label="t('user::account.date_format')"
                            :error="generalForm.errors.date_format"
                        >
                            <AppSelect
                                id="account-date-format"
                                v-model="generalForm.date_format"
                                :invalid="Boolean(generalForm.errors.date_format)"
                            >
                                <option value="">{{ t('user::account.format_system') }}</option>
                                <option v-for="pattern in props.date_formats" :key="pattern" :value="pattern">
                                    {{ formatPreview(pattern) }}
                                </option>
                            </AppSelect>
                        </FormField>

                        <FormField
                            id="account-time-format"
                            :label="t('user::account.time_format')"
                            :error="generalForm.errors.time_format"
                        >
                            <AppSelect
                                id="account-time-format"
                                v-model="generalForm.time_format"
                                :invalid="Boolean(generalForm.errors.time_format)"
                            >
                                <option value="">{{ t('user::account.format_system') }}</option>
                                <option v-for="pattern in props.time_formats" :key="pattern" :value="pattern">
                                    {{ formatPreview(pattern) }}
                                </option>
                            </AppSelect>
                        </FormField>

                        <FormField
                            id="account-safety-filter"
                            :label="t('user::account.default_safety_filter')"
                            :hint="t('user::account.default_safety_filter_hint')"
                            :error="generalForm.errors.default_safety_filter"
                        >
                            <AppSelect
                                id="account-safety-filter"
                                v-model="generalForm.default_safety_filter"
                                :invalid="Boolean(generalForm.errors.default_safety_filter)"
                            >
                                <option value="safe">safe</option>
                                <option value="sketchy">sketchy</option>
                                <option value="unsafe">unsafe</option>
                            </AppSelect>
                        </FormField>

                        <div class="flex items-center justify-between gap-6">
                            <div class="min-w-0">
                                <label for="account-show-unsafe" class="text-sm font-medium text-text">
                                    {{ t('user::account.show_unsafe_content') }}
                                </label>
                                <p class="mt-0.5 text-xs text-muted">{{ t('user::account.show_unsafe_content_hint') }}</p>
                            </div>
                            <AppToggle id="account-show-unsafe" v-model="generalForm.show_unsafe_content" />
                        </div>

                        <FormField
                            id="account-default-visibility"
                            :label="t('user::account.default_visibility')"
                            :hint="t('user::account.default_visibility_hint')"
                            :error="generalForm.errors.default_visibility"
                        >
                            <AppSelect
                                id="account-default-visibility"
                                v-model="generalForm.default_visibility"
                                :invalid="Boolean(generalForm.errors.default_visibility)"
                            >
                                <option value="">{{ t('user::account.default_visibility_system') }}</option>
                                <option v-for="value in props.visibilities" :key="value" :value="value">
                                    {{ t(`media::visibility.${value}`) }}
                                </option>
                            </AppSelect>
                        </FormField>
                    </div>

                    <div
                        class="grid transition-all duration-150 ease-out"
                        :class="showGeneralActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                        :inert="!showGeneralActions"
                    >
                        <div class="overflow-hidden">
                            <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                <p v-if="generalJustSaved" class="mr-auto text-xs text-muted">{{ t('user::ui.saved') }}</p>
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

        <AppSection :title="t('user::account.block_security')" :description="t('user::account.block_security_hint')">
            <AppCard :padded="false">
                <form @submit.prevent="submitSecurity">
                    <div class="flex flex-col gap-4 px-5 py-4">
                        <FormField
                            id="account-current-password"
                            :label="t('user::account.current_password')"
                            :error="securityForm.errors.current_password"
                        >
                            <TextInput
                                id="account-current-password"
                                v-model="securityForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                :invalid="Boolean(securityForm.errors.current_password)"
                            />
                        </FormField>

                        <FormField
                            id="account-new-password"
                            :label="t('user::account.new_password')"
                            :error="securityForm.errors.password"
                        >
                            <TextInput
                                id="account-new-password"
                                v-model="securityForm.password"
                                type="password"
                                autocomplete="new-password"
                                :invalid="Boolean(securityForm.errors.password)"
                            />
                        </FormField>

                        <FormField
                            id="account-new-password-confirmation"
                            :label="t('user::account.new_password_confirmation')"
                        >
                            <TextInput
                                id="account-new-password-confirmation"
                                v-model="securityForm.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                        </FormField>
                    </div>

                    <div
                        class="grid transition-all duration-150 ease-out"
                        :class="showSecurityActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                        :inert="!showSecurityActions"
                    >
                        <div class="overflow-hidden">
                            <div class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3">
                                <p v-if="securityJustSaved" class="mr-auto text-xs text-muted">{{ t('user::ui.saved') }}</p>
                                <AppButton
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    :disabled="!securityForm.isDirty"
                                    @click="securityForm.reset()"
                                >
                                    {{ t('user::ui.reset') }}
                                </AppButton>
                                <AppButton
                                    type="submit"
                                    size="sm"
                                    :disabled="!securityForm.isDirty"
                                    :loading="securityForm.processing"
                                >
                                    {{ t('user::account.change_password') }}
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </form>

                <AppCardRow :label="t('user::account.two_factor')" :description="t('user::account.two_factor_hint')">
                    <AppToggle id="account-two-factor" :model-value="false" disabled />
                </AppCardRow>
            </AppCard>
        </AppSection>

        <DangerZone :title="t('user::account.danger')" :description="t('user::account.danger_hint')">
            <AppCardRow :label="t('user::account.delete')">
                <AppButton variant="danger" size="sm" @click="isDeleteOpen = true">
                    {{ t('user::account.delete') }}
                </AppButton>
            </AppCardRow>
        </DangerZone>

        <DeleteAccountModal v-if="isDeleteOpen" @close="isDeleteOpen = false" />
    </SettingsLayout>
</template>
