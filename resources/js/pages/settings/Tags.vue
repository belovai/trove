<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
import AppSection from '@/components/ui/AppSection.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppTable from '@/components/ui/AppTable.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppLinkButton from '@/components/ui/AppLinkButton.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import AppToggle from '@/components/ui/AppToggle.vue';
import Alert from '@/components/ui/Alert.vue';
import TagCategoryModal from '@/components/settings/TagCategoryModal.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useTranslations } from '@/composables/useTranslations';
import type { SettingsSection } from '@/types/inertia';

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
    sections: SettingsSection[];
    current: string;
    categories: Category[];
    health: {
        unused: { name: string }[];
        uncategorized: { name: string }[];
        duplicates: { left: string; right: string; distance: number }[];
        implications: { from: string; to: string; confidence: number }[];
    };
}>();

const { t } = useTranslations();
const { confirm } = useConfirm();
const page = usePage();

const isModalOpen = ref(false);
const editing = ref<Category | null>(null);

const edit = (category: Category): void => {
    editing.value = category;
    isModalOpen.value = true;
};

const add = (): void => {
    editing.value = null;
    isModalOpen.value = true;
};

const remove = async (category: Category): Promise<void> => {
    const accepted = await confirm({
        message: t('tag::tag.delete_category_confirm', { name: category.name }),
        confirmLabel: t('user::ui.delete'),
        variant: 'danger',
    });

    if (accepted) {
        router.delete(`/admin/tags/categories/${category.id}`, { preserveScroll: true });
    }
};

const importing = useForm<{ file: File | null; replace: boolean }>({ file: null, replace: false });

const submitImport = (): void => importing.post('/admin/tags/taxonomy');
</script>

<template>
    <Head :title="t('tag::tag.section_tags')" />

    <SettingsLayout :sections="props.sections" :current="props.current">
        <AppSection :title="t('tag::tag.categories')">
            <template #actions>
                <AppButton size="sm" @click="add">{{ t('tag::tag.add_category') }}</AppButton>
            </template>

            <AppCard :padded="false">
                <AppTable>
                    <template #head>
                        <tr>
                            <th>{{ t('tag::tag.name') }}</th>
                            <th class="hidden sm:table-cell">{{ t('tag::tag.color') }}</th>
                            <th class="hidden sm:table-cell">{{ t('tag::tag.sort_order') }}</th>
                            <th class="hidden sm:table-cell">{{ t('tag::tag.tags_in_category') }}</th>
                            <th><span class="sr-only">{{ t('user::ui.edit') }}</span></th>
                        </tr>
                    </template>

                    <tr v-for="category in props.categories" :key="category.id">
                        <td>
                            <span class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-xl" :style="{ backgroundColor: category.color }" />
                                <span class="text-text">{{ category.name }}</span>
                            </span>
                        </td>
                        <td class="hidden font-mono text-xs text-muted sm:table-cell">{{ category.color }}</td>
                        <td class="hidden text-muted sm:table-cell">{{ category.sort_order }}</td>
                        <td class="hidden text-muted sm:table-cell">{{ category.tags_count }}</td>
                        <td>
                            <span class="flex justify-end gap-1">
                                <AppButton variant="ghost" size="icon" :aria-label="t('user::ui.edit')" @click="edit(category)">
                                    <PencilSquareIcon class="h-5 w-5" aria-hidden="true" />
                                </AppButton>
                                <!-- The default category has nowhere to reassign its tags to. -->
                                <AppButton
                                    v-if="!category.is_default"
                                    variant="ghost"
                                    size="icon"
                                    class="text-danger"
                                    :aria-label="t('user::ui.delete')"
                                    @click="remove(category)"
                                >
                                    <TrashIcon class="h-5 w-5" aria-hidden="true" />
                                </AppButton>
                            </span>
                        </td>
                    </tr>
                </AppTable>
            </AppCard>
        </AppSection>

        <AppSection :title="t('tag::tag.health')">
            <div class="grid gap-4 md:grid-cols-2">
                <AppCard :title="t('tag::tag.health_unused')">
                    <AppEmptyState v-if="props.health.unused.length === 0" :title="t('tag::tag.nothing_here')" />
                    <div v-else class="flex flex-wrap gap-x-2 gap-y-1">
                        <Link
                            v-for="tag in props.health.unused"
                            :key="tag.name"
                            :href="`/tags/${encodeURIComponent(tag.name)}`"
                            class="text-sm text-accent hover:text-accent-hover"
                        >
                            {{ tag.name }}
                        </Link>
                    </div>
                </AppCard>

                <AppCard :title="t('tag::tag.health_uncategorized')">
                    <AppEmptyState v-if="props.health.uncategorized.length === 0" :title="t('tag::tag.nothing_here')" />
                    <div v-else class="flex flex-wrap gap-x-2 gap-y-1">
                        <Link
                            v-for="tag in props.health.uncategorized"
                            :key="tag.name"
                            :href="`/tags/${encodeURIComponent(tag.name)}`"
                            class="text-sm text-accent hover:text-accent-hover"
                        >
                            {{ tag.name }}
                        </Link>
                    </div>
                </AppCard>

                <AppCard :title="t('tag::tag.health_duplicates')">
                    <AppEmptyState v-if="props.health.duplicates.length === 0" :title="t('tag::tag.nothing_here')" />
                    <!-- A hint for a human, never an automatic merge. -->
                    <p
                        v-for="pair in props.health.duplicates"
                        :key="`${pair.left}-${pair.right}`"
                        class="text-sm text-text"
                    >
                        <Link :href="`/tags/${encodeURIComponent(pair.left)}`" class="text-accent hover:text-accent-hover">
                            {{ pair.left }}
                        </Link>
                        /
                        <Link :href="`/tags/${encodeURIComponent(pair.right)}`" class="text-accent hover:text-accent-hover">
                            {{ pair.right }}
                        </Link>
                    </p>
                </AppCard>

                <AppCard :title="t('tag::tag.health_implications')">
                    <AppEmptyState v-if="props.health.implications.length === 0" :title="t('tag::tag.nothing_here')" />
                    <p
                        v-for="candidate in props.health.implications"
                        :key="`${candidate.from}-${candidate.to}`"
                        class="text-sm text-text"
                    >
                        <Link :href="`/tags/${encodeURIComponent(candidate.from)}`" class="text-accent hover:text-accent-hover">
                            {{ candidate.from }}
                        </Link>
                        &rarr; {{ candidate.to }}
                        <span class="text-xs text-muted">{{ candidate.confidence }}</span>
                    </p>
                </AppCard>
            </div>
        </AppSection>

        <AppSection :title="t('tag::tag.taxonomy')">
            <AppCard>
                <div class="flex flex-col gap-4">
                    <AppLinkButton href="/admin/tags/taxonomy" variant="secondary" size="sm">
                        {{ t('tag::tag.export') }}
                    </AppLinkButton>

                    <form class="flex flex-col gap-3" @submit.prevent="submitImport">
                        <input
                            type="file"
                            accept="application/json"
                            class="text-sm text-text"
                            @change="importing.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        />

                        <label class="flex items-center gap-2 text-sm text-text">
                            <AppToggle id="taxonomy-replace" v-model="importing.replace" />
                            {{ t('tag::tag.replace_on_import') }}
                        </label>

                        <AppButton type="submit" size="sm" class="self-start" :disabled="importing.file === null">
                            {{ t('tag::tag.import') }}
                        </AppButton>

                        <p v-if="importing.errors.file" class="text-xs text-danger-strong">{{ importing.errors.file }}</p>
                    </form>

                    <div v-if="(page.props.taxonomy_conflicts as string[] | undefined)?.length" class="flex flex-col gap-1">
                        <h3 class="text-xs text-muted">{{ t('tag::tag.conflicts') }}</h3>
                        <Alert variant="warning">
                            <p v-for="conflict in page.props.taxonomy_conflicts as string[]" :key="conflict">
                                {{ conflict }}
                            </p>
                        </Alert>
                    </div>
                </div>
            </AppCard>
        </AppSection>

        <TagCategoryModal v-if="isModalOpen" :category="editing" @close="isModalOpen = false" />
    </SettingsLayout>
</template>
