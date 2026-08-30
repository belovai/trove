import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/composables/useConfirm';
import { useTranslations } from '@/composables/useTranslations';

/**
 * The "Show unsafe content" action on a covered item's overlay. Shared
 * between the list thumbnail and the detail page so the confirm copy and the
 * account update live in one place.
 */
export function useUnsafeContentReveal() {
    const { confirm } = useConfirm();
    const { t } = useTranslations();

    const requestShowUnsafeContent = async (): Promise<void> => {
        const accepted = await confirm({
            title: t('media::media.show_unsafe_confirm_title'),
            message: t('media::media.show_unsafe_confirm_message'),
            confirmLabel: t('media::media.show_unsafe'),
        });

        if (accepted) {
            router.patch(
                '/account',
                { show_unsafe_content: true },
                { preserveScroll: true, preserveState: true },
            );
        }
    };

    return { requestShowUnsafeContent };
}
