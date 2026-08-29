<script setup lang="ts"></script>

<template>
    <!-- The table scrolls inside its own container: the page body must never
         scroll horizontally, however many columns a record has. -->
    <div class="overflow-x-auto">
        <table class="w-full min-w-max text-left text-sm">
            <thead class="bg-surface text-xs text-muted">
                <slot name="head" />
            </thead>
            <tbody class="divide-y divide-divider">
                <slot />
            </tbody>
        </table>
    </div>
</template>

<style scoped>
:deep(th) {
    padding: 0.5rem 1.25rem;
    font-weight: 500;
}

:deep(td) {
    padding: 0.625rem 1.25rem;
}
</style>
