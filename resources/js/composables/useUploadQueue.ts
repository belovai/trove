import { ref } from 'vue';
import type { DuplicateMatch, SafetyRating, Visibility } from '@/types/inertia';

export type UploadStatus = 'pending' | 'uploading' | 'done' | 'duplicate' | 'error';

export interface QueueItem {
    id: number;
    file: File;
    preview: string;
    visibility: Visibility;
    safety_rating: SafetyRating;
    is_anonymous: boolean;
    tags: string[];
    status: UploadStatus;
    progress: number;
    error: string | null;
    duplicate: DuplicateMatch | null;
}

interface QueueOptions {
    allowedMimes: string[];
    maxFilesize: number; // KB
    messages: {
        type: string;
        size: string;
        failed: string;
    };
}

// Two at a time: enough to keep a slow connection busy without turning a
// hundred-file drop into a hundred parallel requests.
const CONCURRENCY = 2;

/**
 * Reads Laravel's CSRF cookie. The upload posts raw XHR rather than going
 * through Inertia, so nothing sets the header for us.
 */
const csrfToken = (): string | null => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
};

export const useUploadQueue = (options: QueueOptions) => {
    const items = ref<QueueItem[]>([]);
    const uploading = ref(false);

    let nextId = 0;

    const add = (files: File[]): void => {
        for (const file of files) {
            const item: QueueItem = {
                id: nextId++,
                file,
                preview: URL.createObjectURL(file),
                visibility: 'public',
                safety_rating: 'safe',
                is_anonymous: false,
                tags: [],
                status: 'pending',
                progress: 0,
                error: null,
                duplicate: null,
            };

            // A courtesy, never a substitute: the server checks the file's
            // actual content, which the browser's `type` does not.
            if (!options.allowedMimes.includes(file.type)) {
                item.status = 'error';
                item.error = options.messages.type;
            } else if (file.size / 1024 > options.maxFilesize) {
                item.status = 'error';
                item.error = options.messages.size;
            }

            items.value.push(item);
        }
    };

    const remove = (id: number): void => {
        const index = items.value.findIndex((item) => item.id === id);

        if (index === -1) {
            return;
        }

        URL.revokeObjectURL(items.value[index].preview);
        items.value.splice(index, 1);
    };

    const send = (item: QueueItem, confirmDuplicate: boolean): Promise<void> =>
        new Promise((resolve) => {
            const body = new FormData();

            body.append('file', item.file);
            body.append('visibility', item.visibility);
            body.append('safety_rating', item.safety_rating);
            body.append('is_anonymous', item.is_anonymous ? '1' : '0');

            for (const tag of item.tags) {
                body.append('tags[]', tag);
            }

            if (confirmDuplicate) {
                body.append('confirm_duplicate', '1');
            }

            const request = new XMLHttpRequest();

            request.open('POST', '/upload');
            request.setRequestHeader('Accept', 'application/json');
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            const token = csrfToken();

            if (token !== null) {
                request.setRequestHeader('X-XSRF-TOKEN', token);
            }

            request.upload.onprogress = (event: ProgressEvent): void => {
                if (event.lengthComputable) {
                    item.progress = event.loaded / event.total;
                }
            };

            request.onload = (): void => {
                const payload = ((): Record<string, any> => {
                    try {
                        return JSON.parse(request.responseText);
                    } catch {
                        return {};
                    }
                })();

                if (request.status === 201) {
                    item.status = 'done';
                    item.progress = 1;
                } else if (request.status === 409) {
                    item.status = 'duplicate';
                    item.duplicate = payload.duplicate ?? null;
                    item.error = payload.message ?? null;
                } else {
                    item.status = 'error';
                    item.error =
                        Object.values(payload.errors ?? {})
                            .flat()
                            .at(0) ??
                        payload.message ??
                        options.messages.failed;
                }

                resolve();
            };

            request.onerror = (): void => {
                item.status = 'error';
                item.error = options.messages.failed;
                resolve();
            };

            item.status = 'uploading';
            item.progress = 0;
            item.error = null;
            item.duplicate = null;

            request.send(body);
        });

    /**
     * Uploads everything still waiting, `CONCURRENCY` files at a time. Each
     * file settles on its own card; one failure never stops the queue.
     */
    const uploadAll = async (): Promise<void> => {
        if (uploading.value) {
            return;
        }

        uploading.value = true;

        const queue = items.value.filter((item) => item.status === 'pending');
        const workers = Array.from({ length: Math.min(CONCURRENCY, queue.length) }, async () => {
            for (let item = queue.shift(); item !== undefined; item = queue.shift()) {
                await send(item, false);
            }
        });

        await Promise.all(workers);

        uploading.value = false;
    };

    /** Re-sends a single file past the duplicate warning. */
    const confirmDuplicate = async (id: number): Promise<void> => {
        const item = items.value.find((entry) => entry.id === id);

        if (item !== undefined) {
            await send(item, true);
        }
    };

    return { items, uploading, add, remove, uploadAll, confirmDuplicate };
};
