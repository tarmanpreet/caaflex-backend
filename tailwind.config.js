import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                background: 'var(--color-background)',
                surface: 'var(--color-surface)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-high': 'var(--color-surface-container-high)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                'inverse-surface': 'var(--color-inverse-surface)',
                'on-surface': 'var(--color-on-surface)',
                'on-surface-variant': 'var(--color-on-surface-variant)',
                primary: 'var(--color-primary)',
                'primary-dim': 'var(--color-primary-dim)',
                'on-primary': 'var(--color-on-primary)',
                'primary-container': 'var(--color-primary-container)',
                'on-primary-container': 'var(--color-on-primary-container)',
                secondary: 'var(--color-secondary)',
                'secondary-container': 'var(--color-secondary-container)',
                'on-secondary-container': 'var(--color-on-secondary-container)',
                tertiary: 'var(--color-tertiary)',
                'tertiary-container': 'var(--color-tertiary-container)',
                'tertiary-fixed-dim': 'var(--color-tertiary-fixed-dim)',
                'on-tertiary-container': 'var(--color-on-tertiary-container)',
                error: 'var(--color-error)',
                'on-error': 'var(--color-on-error)',
                'error-container': 'var(--color-error-container)',
                'on-error-container': 'var(--color-on-error-container)',
                outline: 'var(--color-outline)',
                'outline-variant': 'var(--color-outline-variant)',
            },
            borderRadius: {
                "DEFAULT": "0.125rem",
                "lg": "0.25rem",
                "xl": "0.5rem",
                "full": "0.75rem",
                "md": "0.375rem"
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                headline: ["Manrope", ...defaultTheme.fontFamily.sans],
                body: ["Inter", ...defaultTheme.fontFamily.sans],
                label: ["Inter", ...defaultTheme.fontFamily.sans]
            },
        },
    },

    plugins: [forms, typography],
};
