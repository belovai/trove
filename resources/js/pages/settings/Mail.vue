<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppCardRow from '@/components/ui/AppCardRow.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppToggle from '@/components/ui/AppToggle.vue';
import Alert from '@/components/ui/Alert.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { SettingsSection } from '@/types/inertia';

defineOptions({ layout: AppLayout });

interface TransportField {
    key: string;
    type: 'text' | 'number' | 'password' | 'select';
    label: string;
    options?: string[];
}

const props = defineProps<{
    sections: SettingsSection[];
    current: string;
    settings: Record<string, string | number | boolean>;
    secrets: Record<string, boolean>;
    transports: string[];
    transport_labels: Record<string, string>;
    fields: Record<string, TransportField[]>;
    deliverable: boolean;
}>();

const { t } = useTranslations();

const errorFor = (key: string): string | undefined =>
    (form.errors as Record<string, string>)[key];

/**
 * One form for the whole page: the transport select decides which fields are
 * shown, so splitting delivery from the SMTP block would let a user save a
 * transport whose credentials are still on screen unsaved.
 */
const form = useForm<Record<string, string | number | boolean>>({
    ...props.settings,
});

const activeFields = computed<TransportField[]>(
    () => props.fields[String(form['mail.transport'])] ?? [],
);

const submit = (): void => {
    form.patch('/settings/mail', { preserveScroll: true });
};

const testEmail = ref('');
const testForm = useForm({ email: '' });

const sendTest = (): void => {
    testForm.email = testEmail.value;
    testForm.post('/settings/mail/test', { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('mail::mail.title')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <div class="flex flex-col gap-8">
            <Alert v-if="!props.deliverable" variant="warning">
                {{ t('mail::mail.not_deliverable') }}
            </Alert>

            <form class="flex flex-col gap-8" @submit.prevent="submit">
                <AppSection
                    :title="t('mail::mail.block_delivery')"
                    :description="t('mail::mail.block_delivery_hint')"
                >
                    <AppCard :padded="false">
                        <AppCardRow :label="t('mail::mail.enabled')" :description="t('mail::mail.enabled_hint')">
                            <AppToggle id="mail-enabled" v-model="form['mail.enabled'] as boolean" />
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.transport')">
                            <div class="sm:w-72">
                                <AppSelect id="mail-transport" v-model="form['mail.transport'] as string">
                                    <option v-for="key in props.transports" :key="key" :value="key">
                                        {{ t(props.transport_labels[key]) }}
                                    </option>
                                </AppSelect>
                                <p v-if="errorFor('mail.transport')" class="mt-1 text-xs text-danger-strong">
                                    {{ errorFor('mail.transport') }}
                                </p>
                            </div>
                        </AppCardRow>
                    </AppCard>
                </AppSection>

                <AppSection
                    v-if="activeFields.length > 0"
                    :title="t('mail::mail.block_smtp')"
                    :description="undefined"
                >
                    <AppCard :padded="false">
                        <AppCardRow v-for="field in activeFields" :key="field.key" :label="t(field.label)">
                            <div class="sm:w-72">
                                <AppSelect
                                    v-if="field.type === 'select'"
                                    :id="field.key"
                                    v-model="form[field.key] as string"
                                >
                                    <option v-for="option in field.options ?? []" :key="option" :value="option">
                                        {{ t(`mail::mail.smtp_encryption_${option}`) }}
                                    </option>
                                </AppSelect>
                                <TextInput
                                    v-else
                                    :id="field.key"
                                    v-model="form[field.key] as string"
                                    :type="field.type === 'password' ? 'password' : 'text'"
                                    :invalid="Boolean(errorFor(field.key))"
                                />
                                <p v-if="props.secrets[field.key]" class="mt-1 text-xs text-muted">
                                    {{ t('mail::mail.smtp_password_set') }}
                                </p>
                                <p v-if="errorFor(field.key)" class="mt-1 text-xs text-danger-strong">
                                    {{ errorFor(field.key) }}
                                </p>
                            </div>
                        </AppCardRow>
                    </AppCard>
                </AppSection>

                <AppSection
                    :title="t('mail::mail.block_sender')"
                    :description="t('mail::mail.block_sender_hint')"
                >
                    <AppCard :padded="false">
                        <AppCardRow
                            :label="t('mail::mail.from_address')"
                            :description="t('mail::mail.from_address_hint')"
                        >
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-from-address"
                                    v-model="form['mail.from_address'] as string"
                                    type="email"
                                    :invalid="Boolean(errorFor('mail.from_address'))"
                                />
                                <p v-if="errorFor('mail.from_address')" class="mt-1 text-xs text-danger-strong">
                                    {{ errorFor('mail.from_address') }}
                                </p>
                            </div>
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.from_name')" :description="t('mail::mail.from_name_hint')">
                            <div class="sm:w-72">
                                <TextInput id="mail-from-name" v-model="form['mail.from_name'] as string" />
                            </div>
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.reply_to')">
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-reply-to"
                                    v-model="form['mail.reply_to'] as string"
                                    type="email"
                                    :invalid="Boolean(errorFor('mail.reply_to'))"
                                />
                            </div>
                        </AppCardRow>

                        <AppCardRow
                            :label="t('mail::mail.admin_address')"
                            :description="t('mail::mail.admin_address_hint')"
                        >
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-admin-address"
                                    v-model="form['mail.admin_address'] as string"
                                    type="email"
                                    :invalid="Boolean(errorFor('mail.admin_address'))"
                                />
                            </div>
                        </AppCardRow>
                    </AppCard>

                    <div class="mt-4 flex justify-end">
                        <AppButton type="submit" :loading="form.processing">{{ t('mail::mail.save') }}</AppButton>
                    </div>
                </AppSection>
            </form>

            <AppSection :title="t('mail::mail.block_test')" :description="t('mail::mail.block_test_hint')">
                <AppCard>
                    <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="sendTest">
                        <div class="sm:w-72">
                            <TextInput
                                id="mail-test-email"
                                v-model="testEmail"
                                type="email"
                                :placeholder="t('mail::mail.test_email')"
                                :invalid="Boolean(testForm.errors.email)"
                            />
                            <p v-if="testForm.errors.email" class="mt-1 text-xs text-danger-strong">
                                {{ testForm.errors.email }}
                            </p>
                        </div>
                        <AppButton type="submit" variant="secondary" :loading="testForm.processing">
                            {{ t('mail::mail.test_send') }}
                        </AppButton>
                    </form>
                </AppCard>
            </AppSection>
        </div>
    </SettingsLayout>
</template>
