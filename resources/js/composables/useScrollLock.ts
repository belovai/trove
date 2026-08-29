/**
 * Stops the page behind an overlay from scrolling. Nested overlays are
 * counted, so closing the inner one does not unlock the outer one.
 */
let depth = 0;
let previousOverflow = '';

export function useScrollLock() {
    const lock = (): void => {
        if (depth === 0) {
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        }

        depth += 1;
    };

    const unlock = (): void => {
        depth = Math.max(0, depth - 1);

        if (depth === 0) {
            document.body.style.overflow = previousOverflow;
        }
    };

    return { lock, unlock };
}
