<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppSelect from '@/components/ui/AppSelect.vue';
import type { SettingsSection } from '@/types/inertia';

const props = defineProps<{ sections: SettingsSection[]; current: string }>();

const navigate = (key: string | number | null): void => {
    const section = props.sections.find((item) => item.key === key);

    if (section) {
        router.visit(section.href);
    }
};
</script>

<template>
    <div class="flex flex-col gap-6 md:flex-row md:gap-8">
        <nav class="hidden w-48 shrink-0 flex-col gap-0.5 md:flex">
            <Link
                v-for="section in props.sections"
                :key="section.key"
                :href="section.href"
                class="rounded-md px-3 py-2 text-sm transition-colors"
                :class="
                    section.key === props.current
                        ? 'bg-surface font-semibold text-text'
                        : 'text-muted hover:bg-surface hover:text-text'
                "
            >
                {{ section.label }}
            </Link>
        </nav>

        <AppSelect
            id="settings-section"
            class="md:hidden"
            :model-value="props.current"
            @update:model-value="navigate"
        >
            <option v-for="section in props.sections" :key="section.key" :value="section.key">
                {{ section.label }}
            </option>
        </AppSelect>

        <div class="flex min-w-0 flex-1 flex-col gap-8">
            <slot />
        </div>
    </div>
</template>
