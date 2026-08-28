<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import ColorInput from '@/components/ColorInput.vue';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ layout: AppLayout });

interface Category {
    id: number;
    name: string;
    color: string;
    sort_order: number;
    is_default: boolean;
    tags_count: number;
}

const props = defineProps<{
    categories: Category[];
    health: {
        unused: { name: string }[];
        uncategorized: { name: string }[];
        duplicates: { left: string; right: string; distance: number }[];
        implications: { from: string; to: string; confidence: number }[];
    };
}>();

const { t } = useTranslations();
const page = usePage();

const creating = useForm({ name: '', color: '#888888', sort_order: 0 });
const importing = useForm<{ file: File | null; replace: boolean }>({ file: null, replace: false });

const editing = ref<number | null>(null);

const saveCategory = (category: Category): void => {
    router.patch(`/admin/tags/categories/${category.id}`, {
        name: category.name,
        color: category.color,
        sort_order: category.sort_order,
    });

    editing.value = null;
};

const deleteCategory = (category: Category): void => {
    router.delete(`/admin/tags/categories/${category.id}`);
};

const submitImport = (): void => importing.post('/admin/tags/taxonomy');
</script>

<template>
    <Head :title="t('tag::tag.admin_title')" />

    <div class="flex flex-col gap-8">
        <section class="flex flex-col gap-3">
            <h2 class="text-lg font-medium">{{ t('tag::tag.categories') }}</h2>

            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500">
                    <tr>
                        <th class="py-1">{{ t('tag::tag.name') }}</th>
                        <th class="py-1">{{ t('tag::tag.color') }}</th>
                        <th class="py-1">{{ t('tag::tag.sort_order') }}</th>
                        <th class="py-1">{{ t('tag::tag.tags_in_category') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="category in props.categories" :key="category.id" class="border-t border-gray-200 dark:border-gray-800">
                        <td class="py-1">
                            <TextInput
                                v-if="editing === category.id"
                                :id="`category-name-${category.id}`"
                                v-model="category.name"
                            />
                            <span v-else :style="{ color: category.color }">{{ category.name }}</span>
                        </td>
                        <td class="py-1">
                            <ColorInput
                                :id="`category-color-${category.id}`"
                                v-model="category.color"
                                :disabled="editing !== category.id"
                            />
                        </td>
                        <td class="py-1">
                            <input
                                v-model.number="category.sort_order"
                                type="number"
                                :disabled="editing !== category.id"
                                class="w-16 bg-transparent"
                            />
                        </td>
                        <td class="py-1 text-gray-500">{{ category.tags_count }}</td>
                        <td class="flex gap-2 py-1">
                            <Button
                                v-if="editing === category.id"
                                type="button"
                                variant="secondary"
                                @click="saveCategory(category)"
                            >
                                {{ t('tag::tag.save') }}
                            </Button>
                            <Button v-else type="button" variant="secondary" @click="editing = category.id">
                                {{ t('tag::tag.edit') }}
                            </Button>
                            <!-- The default category has nowhere to reassign its tags to. -->
                            <Button
                                v-if="!category.is_default"
                                type="button"
                                variant="warning"
                                @click="deleteCategory(category)"
                            >
                                {{ t('tag::tag.delete') }}
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <form class="flex flex-wrap items-end gap-2" @submit.prevent="creating.post('/admin/tags/categories')">
                <TextInput id="new-category" v-model="creating.name" :placeholder="t('tag::tag.name')" />
                <ColorInput id="new-category-color" v-model="creating.color" />
                <input v-model.number="creating.sort_order" type="number" class="w-16 bg-transparent" />
                <Button type="submit">{{ t('tag::tag.add_category') }}</Button>
                <p v-if="creating.errors.name" class="w-full text-xs text-red-600">{{ creating.errors.name }}</p>
            </form>
        </section>

        <section class="flex flex-col gap-3">
            <h2 class="text-lg font-medium">{{ t('tag::tag.health') }}</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <h3 class="text-xs text-gray-500">{{ t('tag::tag.health_unused') }}</h3>
                    <p v-if="props.health.unused.length === 0" class="text-sm text-gray-500">
                        {{ t('tag::tag.nothing_here') }}
                    </p>
                    <Link
                        v-for="tag in props.health.unused"
                        :key="tag.name"
                        :href="`/tags/${encodeURIComponent(tag.name)}`"
                        class="mr-2 text-sm underline"
                    >
                        {{ tag.name }}
                    </Link>
                </div>

                <div>
                    <h3 class="text-xs text-gray-500">{{ t('tag::tag.health_uncategorized') }}</h3>
                    <p v-if="props.health.uncategorized.length === 0" class="text-sm text-gray-500">
                        {{ t('tag::tag.nothing_here') }}
                    </p>
                    <Link
                        v-for="tag in props.health.uncategorized"
                        :key="tag.name"
                        :href="`/tags/${encodeURIComponent(tag.name)}`"
                        class="mr-2 text-sm underline"
                    >
                        {{ tag.name }}
                    </Link>
                </div>

                <div>
                    <h3 class="text-xs text-gray-500">{{ t('tag::tag.health_duplicates') }}</h3>
                    <p v-if="props.health.duplicates.length === 0" class="text-sm text-gray-500">
                        {{ t('tag::tag.nothing_here') }}
                    </p>
                    <!-- A hint for a human, never an automatic merge. -->
                    <p v-for="pair in props.health.duplicates" :key="`${pair.left}-${pair.right}`" class="text-sm">
                        <Link :href="`/tags/${encodeURIComponent(pair.left)}`" class="underline">{{ pair.left }}</Link>
                        /
                        <Link :href="`/tags/${encodeURIComponent(pair.right)}`" class="underline">{{ pair.right }}</Link>
                    </p>
                </div>

                <div>
                    <h3 class="text-xs text-gray-500">{{ t('tag::tag.health_implications') }}</h3>
                    <p v-if="props.health.implications.length === 0" class="text-sm text-gray-500">
                        {{ t('tag::tag.nothing_here') }}
                    </p>
                    <p
                        v-for="candidate in props.health.implications"
                        :key="`${candidate.from}-${candidate.to}`"
                        class="text-sm"
                    >
                        <Link :href="`/tags/${encodeURIComponent(candidate.from)}`" class="underline">
                            {{ candidate.from }}
                        </Link>
                        &rarr; {{ candidate.to }}
                        <span class="text-xs text-gray-500">{{ candidate.confidence }}</span>
                    </p>
                </div>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <h2 class="text-lg font-medium">{{ t('tag::tag.taxonomy') }}</h2>

            <a href="/admin/tags/taxonomy" class="text-sm underline">{{ t('tag::tag.export') }}</a>

            <form class="flex flex-col gap-2" @submit.prevent="submitImport">
                <input
                    type="file"
                    accept="application/json"
                    class="text-sm"
                    @change="importing.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                />
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="importing.replace" type="checkbox" />
                    {{ t('tag::tag.replace_on_import') }}
                </label>
                <Button type="submit" :disabled="importing.file === null">{{ t('tag::tag.import') }}</Button>
                <p v-if="importing.errors.file" class="text-xs text-red-600">{{ importing.errors.file }}</p>
            </form>

            <div v-if="(page.props.taxonomy_conflicts as string[] | undefined)?.length" class="flex flex-col gap-1">
                <h3 class="text-xs text-gray-500">{{ t('tag::tag.conflicts') }}</h3>
                <p
                    v-for="conflict in page.props.taxonomy_conflicts as string[]"
                    :key="conflict"
                    class="text-xs text-yellow-600 dark:text-yellow-400"
                >
                    {{ conflict }}
                </p>
            </div>
        </section>
    </div>
</template>
