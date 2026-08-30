<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch, type ComponentPublicInstance } from 'vue';
import type { MediaCardData } from '@/types/inertia';

const props = defineProps<{
    media: MediaCardData;
    size: 'thumb' | 'preview';
    /** Whether to blur the image — the cover overlay itself (icon, buttons)
     * is drawn by the parent, as a sibling of the link, not in here. */
    covered: boolean;
}>();

/*
    Thumbnails are written by a queued job, so an item can reach the grid
    before its thumbnail exists — an upload that redirects straight to the
    list is the normal case. The thumbnail route 404s until the job has run,
    so poll it on a backing-off schedule and swap the image in as soon as it
    answers, instead of leaving a bare placeholder until the next page load.
*/
const POLL_DELAYS_MS = [800, 1200, 2000, 2000, 3000, 5000, 5000, 8000, 8000, 10_000];

const ready = ref(props.media.has_thumbnail);
const loaded = ref(false);
const attempt = ref(0);
const version = ref(0);

let timer: ReturnType<typeof setTimeout> | undefined;
let probe: HTMLImageElement | undefined;

const src = computed(() => {
    const base = `/m/${props.media.hash_id}/thumbnail/${props.size}`;

    return version.value === 0 ? base : `${base}?v=${version.value}`;
});

// The shimmer runs only while a thumbnail is still expected. Once the polls
// are exhausted the tile settles back to the plain dominant-color placeholder.
const polling = computed(() => !ready.value && attempt.value < POLL_DELAYS_MS.length);

function stop(): void {
    clearTimeout(timer);
    timer = undefined;

    if (probe !== undefined) {
        probe.onload = null;
        probe.onerror = null;
        probe = undefined;
    }
}

function schedule(): void {
    const delay = POLL_DELAYS_MS[attempt.value];

    if (delay === undefined) {
        return;
    }

    timer = setTimeout(() => {
        attempt.value += 1;
        // A fresh query string keeps the browser from replaying its own cached
        // 404; the <img> below then reuses this exact URL out of the cache.
        version.value = Date.now();

        const image = new Image();
        probe = image;
        image.onload = () => {
            ready.value = true;
        };
        image.onerror = () => schedule();
        image.src = src.value;
    }, delay);
}

watch(
    () => [props.media.hash_id, props.media.has_thumbnail] as const,
    ([, hasThumbnail]) => {
        stop();
        ready.value = hasThumbnail;
        loaded.value = false;
        attempt.value = 0;
        version.value = 0;

        if (!ready.value) {
            schedule();
        }
    },
    { immediate: true },
);

onBeforeUnmount(stop);

/*
    A cached image can finish loading before Vue attaches the `@load`
    listener (`src` is patched first), so `loaded` never flips and the tile
    stays at opacity-0 forever. Checking `complete` right after mount covers
    that race without depending on event ordering.
*/
function checkAlreadyLoaded(el: Element | ComponentPublicInstance | null): void {
    if (el instanceof HTMLImageElement && el.complete) {
        loaded.value = true;
    }
}
</script>

<template>
    <!-- The dominant color stands in until the image arrives, and stays if the
         thumbnail job has not run or failed. -->
    <div
        class="media-thumbnail relative aspect-square w-full overflow-hidden rounded-md bg-surface"
        :class="{ 'media-shimmer': polling }"
        :style="{ backgroundColor: props.media.dominant_color ?? undefined }"
    >
        <img
            v-if="ready"
            :ref="checkAlreadyLoaded"
            :src="src"
            :alt="props.media.title ?? ''"
            loading="lazy"
            class="h-full w-full object-cover opacity-0 transition-opacity duration-200"
            :class="{ 'opacity-100': loaded, 'blur-xl scale-110': props.covered }"
            @load="loaded = true"
        />
    </div>
</template>
