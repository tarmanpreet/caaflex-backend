<template>
    <div class="flex justify-between gap-4 md:col-span-1">
        <div>
            <h3 class="font-headline text-lg font-bold text-on-surface">
                <slot name="title" />
            </h3>

            <p class="mt-1 text-sm leading-6 text-on-surface-variant">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template>
