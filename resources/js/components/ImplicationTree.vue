<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    name: string;
    ancestors: string[];
    descendants: string[];
}>();

const { t } = useTranslations();
</script>

<template>
    <!--
        Parents above, this tag in the middle, children below. Position carries
        the direction, so nobody has to remember which way "implies" points.
    -->
    <div v-if="props.ancestors.length > 0 || props.descendants.length > 0" class="flex flex-col gap-1 text-sm">
        <div v-if="props.ancestors.length > 0" class="flex flex-wrap items-baseline gap-2">
            <span class="text-xs text-gray-500">{{ t('tag::tag.implied_by') }}</span>
            <Link
                v-for="ancestor in props.ancestors"
                :key="ancestor"
                :href="`/tags/${encodeURIComponent(ancestor)}`"
                class="underline"
            >
                {{ ancestor }}
            </Link>
        </div>

        <div class="pl-2 font-medium">{{ props.name }}</div>

        <div v-if="props.descendants.length > 0" class="flex flex-wrap items-baseline gap-2 pl-4">
            <span class="text-xs text-gray-500">{{ t('tag::tag.implies') }}</span>
            <Link
                v-for="descendant in props.descendants"
                :key="descendant"
                :href="`/tags/${encodeURIComponent(descendant)}`"
                class="underline"
            >
                {{ descendant }}
            </Link>
        </div>
    </div>
</template>
