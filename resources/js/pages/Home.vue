<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: AppLayout });

const { t } = useTranslations();

const tags = ref('');

// The grid ignores `tags` for now. Searching already navigates there, so
// wiring the query up later is a controller change, not a redesign.
const search = (): void => {
    const query = tags.value.trim();

    router.visit(query === '' ? '/posts' : `/posts?tags=${encodeURIComponent(query)}`);
};
</script>

<template>
    <Head title="Trove" />

    <div class="flex flex-col items-center gap-6 py-16">
        <h1 class="text-6xl font-semibold tracking-tight">Trove</h1>

        <form class="flex w-full max-w-xl flex-wrap items-center justify-center gap-2" @submit.prevent="search">
            <TextInput
                id="tags"
                v-model="tags"
                class="min-w-0 flex-1"
                :placeholder="t('media::media.search_placeholder')"
                :aria-label="t('media::media.search')"
            />

            <Button type="submit">{{ t('media::media.search') }}</Button>
        </form>

        <Link href="/posts" class="text-sm underline">{{ t('media::media.browse_all') }}</Link>
    </div>
</template>
