<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/solid';
import { useTranslations } from '@/composables/useTranslations';
import type { VoteBlockedReason, VoteValue } from '@/types/inertia';

const props = defineProps<{
    hashId: string;
    score: number;
    viewerVote: VoteValue | null;
    canVote: boolean;
    blockedReason: VoteBlockedReason | null;
}>();

const { t } = useTranslations();

/**
 * Local mirrors of the two server values. A click moves them immediately and
 * the request only confirms; the watch below re-seeds them from the fresh
 * props the redirect brings back, so the server always has the last word.
 */
const score = ref(props.score);
const vote = ref<VoteValue | null>(props.viewerVote);
const inFlight = ref(false);

watch(
    () => [props.score, props.viewerVote] as const,
    ([nextScore, nextVote]) => {
        score.value = nextScore;
        vote.value = nextVote;
    },
);

const label = computed(() => (score.value > 0 ? `+${score.value}` : String(score.value)));

const tone = computed(() => {
    if (score.value > 0) return 'text-success';
    if (score.value < 0) return 'text-danger';

    return 'text-muted';
});

const reason = computed(() =>
    props.blockedReason === null ? undefined : t(`media::media.vote_blocked_${props.blockedReason}`),
);

const cast = (direction: VoteValue): void => {
    if (!props.canVote || inFlight.value) {
        return;
    }

    const previousScore = score.value;
    const previousVote = vote.value;

    // Clicking the arrow you already hold withdraws the vote.
    const next = vote.value === direction ? 0 : direction;

    vote.value = next === 0 ? null : direction;
    score.value = previousScore - (previousVote ?? 0) + next;
    inFlight.value = true;

    router.post(
        `/m/${props.hashId}/vote`,
        { value: next },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                score.value = previousScore;
                vote.value = previousVote;
            },
            onFinish: () => {
                inFlight.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="flex items-center gap-1" :title="reason">
        <button
            type="button"
            :disabled="!props.canVote || inFlight"
            :aria-label="t('media::media.vote_up')"
            :aria-pressed="vote === 1"
            class="cursor-pointer rounded-md p-1 disabled:cursor-not-allowed disabled:opacity-40"
            :class="vote === 1 ? 'text-success' : 'text-muted hover:text-text'"
            @click="cast(1)"
        >
            <ChevronUpIcon class="h-5 w-5" aria-hidden="true" />
        </button>

        <span
            class="min-w-8 text-center text-sm font-medium tabular-nums"
            :class="tone"
            :aria-label="t('media::media.vote_score')"
        >
            {{ label }}
        </span>

        <button
            type="button"
            :disabled="!props.canVote || inFlight"
            :aria-label="t('media::media.vote_down')"
            :aria-pressed="vote === -1"
            class="cursor-pointer rounded-md p-1 disabled:cursor-not-allowed disabled:opacity-40"
            :class="vote === -1 ? 'text-danger' : 'text-muted hover:text-text'"
            @click="cast(-1)"
        >
            <ChevronDownIcon class="h-5 w-5" aria-hidden="true" />
        </button>
    </div>
</template>
