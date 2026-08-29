<script setup lang="ts">
import AppButton from './AppButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useTranslations } from '@/composables/useTranslations';

const { state, resolve } = useConfirm();
const { t } = useTranslations();

const titleId = 'confirm-dialog-title';
</script>

<template>
    <Teleport to="body">
        <template v-if="state">
            <div class="fixed inset-0 z-60 bg-black/45" @click="resolve(false)" />
            <div
                class="fixed inset-x-4 top-1/2 z-70 -translate-y-1/2 rounded-xl border border-divider bg-panel p-5 shadow-modal sm:inset-x-auto sm:left-1/2 sm:w-96 sm:-translate-x-1/2"
                role="alertdialog"
                aria-modal="true"
                :aria-labelledby="titleId"
            >
                <h2 :id="titleId" class="mb-2 text-base font-semibold text-text">
                    {{ state.title ?? t('user::ui.confirm') }}
                </h2>
                <p class="mb-5 text-sm text-muted">{{ state.message }}</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton variant="secondary" @click="resolve(false)">
                        {{ state.cancelLabel ?? t('user::ui.cancel') }}
                    </AppButton>
                    <AppButton :variant="state.variant ?? 'primary'" @click="resolve(true)">
                        {{ state.confirmLabel ?? t('user::ui.confirm') }}
                    </AppButton>
                </div>
            </div>
        </template>
    </Teleport>
</template>
