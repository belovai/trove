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

const senderKeys = ['mail.from_address', 'mail.from_name', 'mail.reply_to', 'mail.admin_address'];

/**
 * Setting keys are dotted ("mail.smtp.host"). Inertia's `isDirty` unflattens
 * a form's data via its dotted keys but compares it against flat defaults, so
 * using the dotted key as the field name directly makes the form permanently
 * "dirty". Form fields use this underscored form instead; `unsafeKey` maps
 * back to the real setting key only when building the submit payload.
 */
const safeKey = (key: string): string => key.replaceAll('.', '__');
const unsafeKey = (key: string): string => key.replaceAll('__', '.');

const pick = (keys: string[]): Record<string, string | number | boolean> =>
    Object.fromEntries(keys.map((key) => [safeKey(key), props.settings[key]]));

const toPayload = (data: Record<string, string | number | boolean>): Record<string, string | number | boolean> =>
    Object.fromEntries(Object.entries(data).map(([key, value]) => [unsafeKey(key), value]));

/**
 * The transport select and its own credential fields live in one save: the
 * fields shown depend on the chosen transport, so submitting them apart from
 * it would let a user save a transport whose credentials are still unsaved.
 * The sender identity is unrelated to delivery, so it saves independently.
 */
const deliveryForm = useForm<Record<string, string | number | boolean>>(
    pick(Object.keys(props.settings).filter((key) => !senderKeys.includes(key))),
);
const senderForm = useForm<Record<string, string | number | boolean>>(pick(senderKeys));

const deliveryErrorFor = (key: string): string | undefined =>
    (deliveryForm.errors as Record<string, string>)[key];
const senderErrorFor = (key: string): string | undefined =>
    (senderForm.errors as Record<string, string>)[key];

const activeFields = computed<TransportField[]>(
    () => props.fields[String(deliveryForm[safeKey('mail.transport')])] ?? [],
);

function useSavedFlash() {
    const justSaved = ref(false);
    let timeout: ReturnType<typeof setTimeout> | undefined;

    const flash = (): void => {
        justSaved.value = true;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            justSaved.value = false;
        }, 3000);
    };

    onUnmounted(() => clearTimeout(timeout));

    return { justSaved, flash };
}

const { justSaved: deliveryJustSaved, flash: flashDeliverySaved } = useSavedFlash();
const { justSaved: senderJustSaved, flash: flashSenderSaved } = useSavedFlash();

const showDeliveryActions = computed(() => deliveryForm.isDirty || deliveryJustSaved.value);
const showSenderActions = computed(() => senderForm.isDirty || senderJustSaved.value);

const submitDelivery = (): void => {
    deliveryForm.transform(toPayload).patch('/settings/mail', { preserveScroll: true, onSuccess: flashDeliverySaved });
};

const submitSender = (): void => {
    senderForm.transform(toPayload).patch('/settings/mail', { preserveScroll: true, onSuccess: flashSenderSaved });
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

            <AppSection
                :title="t('mail::mail.block_delivery')"
                :description="t('mail::mail.block_delivery_hint')"
            >
                <AppCard :padded="false">
                    <form @submit.prevent="submitDelivery">
                        <AppCardRow :label="t('mail::mail.enabled')" :description="t('mail::mail.enabled_hint')">
                            <AppToggle
                                id="mail-enabled"
                                v-model="deliveryForm[safeKey('mail.enabled')] as boolean"
                            />
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.transport')">
                            <div class="sm:w-72">
                                <AppSelect
                                    id="mail-transport"
                                    v-model="deliveryForm[safeKey('mail.transport')] as string"
                                >
                                    <option v-for="key in props.transports" :key="key" :value="key">
                                        {{ t(props.transport_labels[key]) }}
                                    </option>
                                </AppSelect>
                                <p v-if="deliveryErrorFor('mail.transport')" class="mt-1 text-xs text-danger-strong">
                                    {{ deliveryErrorFor('mail.transport') }}
                                </p>
                            </div>
                        </AppCardRow>

                        <div v-if="activeFields.length > 0" class="border-t border-divider bg-surface px-5 py-2">
                            <h4 class="text-xs font-semibold text-muted">{{ t('mail::mail.block_smtp') }}</h4>
                        </div>

                        <AppCardRow v-for="field in activeFields" :key="field.key" :label="t(field.label)">
                            <div class="sm:w-72">
                                <AppSelect
                                    v-if="field.type === 'select'"
                                    :id="field.key"
                                    v-model="deliveryForm[safeKey(field.key)] as string"
                                >
                                    <option v-for="option in field.options ?? []" :key="option" :value="option">
                                        {{ t(`mail::mail.smtp_encryption_${option}`) }}
                                    </option>
                                </AppSelect>
                                <TextInput
                                    v-else
                                    :id="field.key"
                                    v-model="deliveryForm[safeKey(field.key)] as string"
                                    :type="field.type === 'password' ? 'password' : 'text'"
                                    :invalid="Boolean(deliveryErrorFor(field.key))"
                                />
                                <p v-if="props.secrets[field.key]" class="mt-1 text-xs text-muted">
                                    {{ t('mail::mail.smtp_password_set') }}
                                </p>
                                <p v-if="deliveryErrorFor(field.key)" class="mt-1 text-xs text-danger-strong">
                                    {{ deliveryErrorFor(field.key) }}
                                </p>
                            </div>
                        </AppCardRow>

                        <div
                            class="grid transition-all duration-150 ease-out"
                            :class="showDeliveryActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                            :inert="!showDeliveryActions"
                        >
                            <div class="overflow-hidden">
                                <div
                                    class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3"
                                >
                                    <p v-if="deliveryJustSaved" class="mr-auto text-xs text-muted">
                                        {{ t('user::ui.saved') }}
                                    </p>
                                    <AppButton
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="!deliveryForm.isDirty"
                                        @click="deliveryForm.reset()"
                                    >
                                        {{ t('user::ui.reset') }}
                                    </AppButton>
                                    <AppButton
                                        type="submit"
                                        size="sm"
                                        :disabled="!deliveryForm.isDirty"
                                        :loading="deliveryForm.processing"
                                    >
                                        {{ t('mail::mail.save') }}
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </AppCard>
            </AppSection>

            <AppSection
                :title="t('mail::mail.block_sender')"
                :description="t('mail::mail.block_sender_hint')"
            >
                <AppCard :padded="false">
                    <form @submit.prevent="submitSender">
                        <AppCardRow
                            :label="t('mail::mail.from_address')"
                            :description="t('mail::mail.from_address_hint')"
                        >
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-from-address"
                                    v-model="senderForm[safeKey('mail.from_address')] as string"
                                    type="email"
                                    :invalid="Boolean(senderErrorFor('mail.from_address'))"
                                />
                                <p v-if="senderErrorFor('mail.from_address')" class="mt-1 text-xs text-danger-strong">
                                    {{ senderErrorFor('mail.from_address') }}
                                </p>
                            </div>
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.from_name')" :description="t('mail::mail.from_name_hint')">
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-from-name"
                                    v-model="senderForm[safeKey('mail.from_name')] as string"
                                />
                            </div>
                        </AppCardRow>

                        <AppCardRow :label="t('mail::mail.reply_to')">
                            <div class="sm:w-72">
                                <TextInput
                                    id="mail-reply-to"
                                    v-model="senderForm[safeKey('mail.reply_to')] as string"
                                    type="email"
                                    :invalid="Boolean(senderErrorFor('mail.reply_to'))"
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
                                    v-model="senderForm[safeKey('mail.admin_address')] as string"
                                    type="email"
                                    :invalid="Boolean(senderErrorFor('mail.admin_address'))"
                                />
                            </div>
                        </AppCardRow>

                        <div
                            class="grid transition-all duration-150 ease-out"
                            :class="showSenderActions ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                            :inert="!showSenderActions"
                        >
                            <div class="overflow-hidden">
                                <div
                                    class="flex items-center justify-end gap-2 border-t border-divider bg-surface px-5 py-3"
                                >
                                    <p v-if="senderJustSaved" class="mr-auto text-xs text-muted">
                                        {{ t('user::ui.saved') }}
                                    </p>
                                    <AppButton
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        :disabled="!senderForm.isDirty"
                                        @click="senderForm.reset()"
                                    >
                                        {{ t('user::ui.reset') }}
                                    </AppButton>
                                    <AppButton
                                        type="submit"
                                        size="sm"
                                        :disabled="!senderForm.isDirty"
                                        :loading="senderForm.processing"
                                    >
                                        {{ t('mail::mail.save') }}
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </AppCard>
            </AppSection>

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
