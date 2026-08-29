<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import AppSlideOver from '@/components/ui/AppSlideOver.vue';
import ModalFooter from '@/components/ui/ModalFooter.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import FormField from '@/components/ui/FormField.vue';
import TextInput from '@/components/ui/TextInput.vue';
import { useConfirm } from '@/composables/useConfirm';
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
const { confirm } = useConfirm();

const path = (suffix = ''): string => `/tags/${encodeURIComponent(props.tag.name)}${suffix}`;

const details = useForm({
    name: props.tag.name,
    category_id: props.categories.find((category) => category.name === props.tag.category)?.id ?? null,
    description: props.tag.description ?? '',
});

const alias = useForm({ alias: '' });
const implication = useForm({ implies: '' });
const merge = useForm({ into: '' });

const submitDetails = (): void => details.patch(path());
const addAlias = (): void => alias.post(path('/aliases'), { onSuccess: () => alias.reset() });
const removeAlias = (name: string): void => router.delete(path(`/aliases/${encodeURIComponent(name)}`));
const addImplication = (): void => implication.post(path('/implications'), { onSuccess: () => implication.reset() });
const removeImplication = (name: string): void =>
    router.delete(path(`/implications/${encodeURIComponent(name)}`));

const submitMerge = async (): Promise<void> => {
    const accepted = await confirm({
        message: t('tag::tag.merge_confirm', { from: props.tag.name, to: merge.into }),
        confirmLabel: t('tag::tag.merge'),
        variant: 'danger',
    });

    if (accepted) {
        merge.post(path('/merge'));
    }
};

const destroy = async (): Promise<void> => {
    const accepted = await confirm({
        message: t('tag::tag.delete_confirm'),
        confirmLabel: t('tag::tag.delete'),
        variant: 'danger',
    });

    if (accepted) {
        router.delete(path());
    }
};
</script>

<template>
    <!--
        A panel over the live page rather than a separate screen: one URL to
        share, and the destructive operations sit in their own layer where a
        stray click cannot reach them.
    -->
    <AppSlideOver :title="props.tag.name" @close="emit('close')">
        <div class="flex flex-col gap-6">
            <form id="tag-details-form" class="flex flex-col gap-3" @submit.prevent="submitDetails">
                <FormField id="tag-name" :label="t('tag::tag.name')" :error="details.errors.name">
                    <TextInput id="tag-name" v-model="details.name" />
                </FormField>

                <FormField id="tag-category" :label="t('tag::tag.category')" :error="details.errors.category_id">
                    <AppSelect id="tag-category" v-model="details.category_id">
                        <option :value="null">{{ t('tag::tag.uncategorized') }}</option>
                        <option v-for="category in props.categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </AppSelect>
                </FormField>

                <FormField id="tag-description" :label="t('tag::tag.description')" :error="details.errors.description">
                    <AppTextarea id="tag-description" v-model="details.description" :rows="4" />
                </FormField>

                <AppButton type="submit" size="sm" class="self-start" :loading="details.processing">
                    {{ t('tag::tag.save') }}
                </AppButton>
            </form>

            <section class="flex flex-col gap-2">
                <h3 class="text-sm font-medium text-text">{{ t('tag::tag.aliases') }}</h3>

                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="name in props.tag.aliases"
                        :key="name"
                        class="inline-flex items-center gap-1 rounded-sm bg-surface px-2 py-0.5 text-sm text-text"
                    >
                        {{ name }}
                        <button type="button" class="text-muted hover:text-danger" @click="removeAlias(name)">
                            &times;
                        </button>
                    </span>
                </div>

                <form class="flex gap-2" @submit.prevent="addAlias">
                    <TextInput id="tag-alias" v-model="alias.alias" />
                    <AppButton type="submit" variant="secondary" size="sm">{{ t('tag::tag.add_alias') }}</AppButton>
                </form>
                <p v-if="alias.errors.alias" class="text-xs text-danger-strong">{{ alias.errors.alias }}</p>
            </section>

            <section class="flex flex-col gap-2">
                <h3 class="text-sm font-medium text-text">{{ t('tag::tag.implies') }}</h3>

                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="name in props.tag.descendants"
                        :key="name"
                        class="inline-flex items-center gap-1 rounded-sm bg-surface px-2 py-0.5 text-sm text-text"
                    >
                        {{ name }}
                        <button type="button" class="text-muted hover:text-danger" @click="removeImplication(name)">
                            &times;
                        </button>
                    </span>
                </div>

                <form class="flex gap-2" @submit.prevent="addImplication">
                    <TextInput id="tag-implies" v-model="implication.implies" />
                    <AppButton type="submit" variant="secondary" size="sm">
                        {{ t('tag::tag.add_implication') }}
                    </AppButton>
                </form>
                <p v-if="implication.errors.implies" class="text-xs text-danger-strong">
                    {{ implication.errors.implies }}
                </p>
            </section>

            <section class="flex flex-col gap-2 border-t border-divider pt-4">
                <h3 class="text-sm font-medium text-text">{{ t('tag::tag.merge_into') }}</h3>

                <form class="flex gap-2" @submit.prevent="submitMerge">
                    <TextInput id="tag-merge" v-model="merge.into" />
                    <AppButton type="submit" variant="danger" size="sm">{{ t('tag::tag.merge') }}</AppButton>
                </form>
                <p v-if="merge.errors.into" class="text-xs text-danger-strong">{{ merge.errors.into }}</p>

                <AppButton type="button" variant="danger" size="sm" class="self-start" @click="destroy">
                    {{ t('tag::tag.delete') }}
                </AppButton>
            </section>
        </div>

        <template #footer>
            <ModalFooter>
                <AppButton variant="secondary" @click="emit('close')">{{ t('tag::tag.close') }}</AppButton>
            </ModalFooter>
        </template>
    </AppSlideOver>
</template>
