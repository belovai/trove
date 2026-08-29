<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline';
import BaseOverlay from './BaseOverlay.vue';
import AppButton from './AppButton.vue';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{ title: string; description?: string }>();

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();

const titleId = `modal-title-${Math.random().toString(36).slice(2)}`;
</script>

<template>
    <BaseOverlay :title-id="titleId" variant="center" @close="emit('close')">
        <header class="flex items-start gap-4 border-b border-divider px-5 py-4">
            <div class="min-w-0 flex-1">
                <h2 :id="titleId" class="text-base font-semibold text-text">{{ title }}</h2>
                <p v-if="description" class="mt-0.5 text-xs text-muted">{{ description }}</p>
            </div>
            <AppButton variant="ghost" size="icon" :aria-label="t('user::ui.close')" @click="emit('close')">
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </AppButton>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            <slot />
        </div>

        <slot name="footer" />
    </BaseOverlay>
</template>
