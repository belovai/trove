<script setup lang="ts"></script>

<template>
    <div
        class="flex flex-col-reverse gap-2 border-t border-divider bg-surface px-5 py-3 sm:flex-row sm:justify-end"
        :style="{ paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))' }"
    >
        <slot />
    </div>
</template>
