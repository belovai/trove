<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useTranslations } from '@/composables/useTranslations';
import type { MediaFilters, SafetyRating } from '@/types/inertia';

const props = withDefaults(
    defineProps<{
        filters: MediaFilters;
        /** Where a filter change navigates. Every listing page is its own URL. */
        url: string;
        /** Off where every listed item is tagged by definition, as on a tag page. */
        showUntagged?: boolean;
    }>(),
    { showUntagged: true },
);

const page = usePage();
const { t } = useTranslations();
const { user } = useAuth();

/**
 * Each rating is an independent toggle, not a threshold — "unsafe only" is a
 * valid selection, and so is nothing at all.
 */
const ratings = computed<SafetyRating[]>(() => page.props.safety_ratings);

const swatch: Record<SafetyRating, { on: string; off: string }> = {
    safe: {
        on: 'border-success bg-success-soft text-success',
        off: 'border-success/40 text-success',
    },
    sketchy: {
        on: 'border-warning bg-warning-soft text-warning',
        off: 'border-warning/40 text-warning',
    },
    unsafe: {
        on: 'border-danger bg-danger-soft text-danger-strong',
        off: 'border-danger/40 text-danger-strong',
    },
};

/** The stored threshold expanded to a set — what the page shows with no params. */
const defaultSafety = computed<SafetyRating[]>(() => {
    const threshold = user.value?.default_safety_filter ?? 'safe';
    const index = ratings.value.indexOf(threshold);

    return ratings.value.slice(0, index === -1 ? 1 : index + 1);
});

const isDefault = computed(
    () =>
        props.filters.untagged === false &&
        props.filters.unlisted === false &&
        props.filters.safety.length === defaultSafety.value.length &&
        defaultSafety.value.every((rating) => props.filters.safety.includes(rating)),
);

/**
 * `params` empty means "no parameters at all", which is how the viewer gets
 * back to their stored default. Anything else travels in full — an empty
 * safety selection has to reach the server, or it falls back to that default
 * instead of listing nothing.
 */
const visit = (params: Record<string, string> = {}): void => {
    router.get(props.url, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['media', 'filters'],
    });
};

const apply = (safety: SafetyRating[], untagged: boolean, unlisted: boolean): void => {
    visit({ safety: safety.join(','), untagged: untagged ? '1' : '0', unlisted: unlisted ? '1' : '0' });
};

const toggleRating = (rating: SafetyRating): void => {
    const next = props.filters.safety.includes(rating)
        ? props.filters.safety.filter((value) => value !== rating)
        : ratings.value.filter((value) => value === rating || props.filters.safety.includes(value));

    apply(next, props.filters.untagged, props.filters.unlisted);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <span id="media-filter-safety" class="text-sm text-muted">
            {{ t('media::media.filter_safety') }}
        </span>

        <div class="flex flex-wrap items-center gap-2" role="group" aria-labelledby="media-filter-safety">
            <button
                v-for="rating in ratings"
                :key="rating"
                type="button"
                :aria-pressed="props.filters.safety.includes(rating)"
                class="rounded-md border px-2 py-1 text-xs font-medium"
                :class="props.filters.safety.includes(rating) ? swatch[rating].on : swatch[rating].off"
                @click="toggleRating(rating)"
            >
                {{ t(`media::safety.${rating}`) }}
            </button>
        </div>

        <button
            v-if="props.showUntagged"
            type="button"
            :aria-pressed="props.filters.untagged"
            class="rounded-md border px-2 py-1 text-xs font-medium"
            :class="
                props.filters.untagged
                    ? 'border-transparent bg-primary text-primary-fg'
                    : 'border-divider text-muted'
            "
            @click="apply(props.filters.safety, !props.filters.untagged, props.filters.unlisted)"
        >
            {{ t('media::media.filter_untagged') }}
        </button>

        <button
            v-if="user"
            type="button"
            :aria-pressed="props.filters.unlisted"
            class="rounded-md border px-2 py-1 text-xs font-medium"
            :class="
                props.filters.unlisted
                    ? 'border-transparent bg-primary text-primary-fg'
                    : 'border-divider text-muted'
            "
            @click="apply(props.filters.safety, props.filters.untagged, !props.filters.unlisted)"
        >
            {{ t('media::media.filter_unlisted') }}
        </button>

        <button v-if="!isDefault" type="button" class="text-xs text-accent hover:text-accent-hover" @click="visit()">
            {{ t('media::media.filter_reset') }}
        </button>
    </div>
</template>
