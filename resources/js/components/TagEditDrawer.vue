<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';
import FormField from '@/components/FormField.vue';
import TextInput from '@/components/TextInput.vue';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    tag: {
        name: string;
        description: string | null;
        category: string | null;
        aliases: string[];
        descendants: string[];
    };
    categories: { id: number; name: string }[];
}>();

const emit = defineEmits<{ close: [] }>();

const { t } = useTranslations();

const path = (suffix = ''): string => `/tags/${encodeURIComponent(props.tag.name)}${suffix}`;

const details = useForm({
    name: props.tag.name,
    category_id: props.categories.find((category) => category.name === props.tag.category)?.id ?? null,
    description: props.tag.description ?? '',
});

const alias = useForm({ alias: '' });
const implication = useForm({ implies: '' });
const merge = useForm({ into: '' });

const confirmingDelete = ref(false);

const submitDetails = (): void => details.patch(path());
const addAlias = (): void => alias.post(path('/aliases'), { onSuccess: () => alias.reset() });
const removeAlias = (name: string): void => router.delete(path(`/aliases/${encodeURIComponent(name)}`));
const addImplication = (): void => implication.post(path('/implications'), { onSuccess: () => implication.reset() });
const removeImplication = (name: string): void =>
    router.delete(path(`/implications/${encodeURIComponent(name)}`));
const submitMerge = (): void => merge.post(path('/merge'));
const destroy = (): void => router.delete(path());
</script>

<template>
    <!--
        A panel over the live page rather than a separate screen: one URL to
        share, and the destructive operations sit in their own layer where a
        stray click cannot reach them.
    -->
    <aside
        class="fixed inset-y-0 right-0 z-20 flex w-full max-w-md flex-col gap-6 overflow-y-auto border-l border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
    >
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium">{{ props.tag.name }}</h2>
            <button type="button" class="text-sm text-gray-500" @click="emit('close')">
                {{ t('tag::tag.close') }}
            </button>
        </div>

        <form class="flex flex-col gap-3" @submit.prevent="submitDetails">
            <FormField id="tag-name" :label="t('tag::tag.name')" :error="details.errors.name">
                <TextInput id="tag-name" v-model="details.name" />
            </FormField>

            <FormField id="tag-category" :label="t('tag::tag.category')" :error="details.errors.category_id">
                <select
                    id="tag-category"
                    v-model="details.category_id"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <option :value="null">{{ t('tag::tag.uncategorized') }}</option>
                    <option v-for="category in props.categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </FormField>

            <FormField id="tag-description" :label="t('tag::tag.description')" :error="details.errors.description">
                <textarea
                    id="tag-description"
                    v-model="details.description"
                    rows="4"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                ></textarea>
            </FormField>

            <Button type="submit" :disabled="details.processing">{{ t('tag::tag.save') }}</Button>
        </form>

        <section class="flex flex-col gap-2">
            <h3 class="text-sm font-medium">{{ t('tag::tag.aliases') }}</h3>

            <div class="flex flex-wrap gap-1">
                <span
                    v-for="name in props.tag.aliases"
                    :key="name"
                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-sm dark:bg-gray-800"
                >
                    {{ name }}
                    <button type="button" class="text-gray-400 hover:text-red-600" @click="removeAlias(name)">
                        &times;
                    </button>
                </span>
            </div>

            <form class="flex gap-2" @submit.prevent="addAlias">
                <TextInput id="tag-alias" v-model="alias.alias" />
                <Button type="submit" variant="secondary">{{ t('tag::tag.add_alias') }}</Button>
            </form>
            <p v-if="alias.errors.alias" class="text-xs text-red-600">{{ alias.errors.alias }}</p>
        </section>

        <section class="flex flex-col gap-2">
            <h3 class="text-sm font-medium">{{ t('tag::tag.implies') }}</h3>

            <div class="flex flex-wrap gap-1">
                <span
                    v-for="name in props.tag.descendants"
                    :key="name"
                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-sm dark:bg-gray-800"
                >
                    {{ name }}
                    <button type="button" class="text-gray-400 hover:text-red-600" @click="removeImplication(name)">
                        &times;
                    </button>
                </span>
            </div>

            <form class="flex gap-2" @submit.prevent="addImplication">
                <TextInput id="tag-implies" v-model="implication.implies" />
                <Button type="submit" variant="secondary">{{ t('tag::tag.add_implication') }}</Button>
            </form>
            <p v-if="implication.errors.implies" class="text-xs text-red-600">{{ implication.errors.implies }}</p>
        </section>

        <section class="flex flex-col gap-2 border-t border-gray-200 pt-4 dark:border-gray-800">
            <h3 class="text-sm font-medium">{{ t('tag::tag.merge_into') }}</h3>

            <form class="flex gap-2" @submit.prevent="submitMerge">
                <TextInput id="tag-merge" v-model="merge.into" />
                <Button type="submit" variant="warning">{{ t('tag::tag.merge') }}</Button>
            </form>
            <p v-if="merge.errors.into" class="text-xs text-red-600">{{ merge.errors.into }}</p>

            <Button v-if="!confirmingDelete" type="button" variant="secondary" @click="confirmingDelete = true">
                {{ t('tag::tag.delete') }}
            </Button>

            <div v-else class="flex flex-col gap-2">
                <p class="text-sm text-red-600">{{ t('tag::tag.delete_confirm') }}</p>
                <div class="flex gap-2">
                    <Button type="button" variant="warning" @click="destroy">{{ t('tag::tag.delete') }}</Button>
                    <Button type="button" variant="secondary" @click="confirmingDelete = false">
                        {{ t('tag::tag.cancel') }}
                    </Button>
                </div>
            </div>
        </section>
    </aside>
</template>
