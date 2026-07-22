import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

const semanticColor = (variable) => ({ opacityValue }) => {
    if (opacityValue === undefined) {
        return `var(${variable})`;
    }

    const numericOpacity = Number(opacityValue);
    const percentage = Number.isFinite(numericOpacity)
        ? `${numericOpacity * 100}%`
        : `calc(${opacityValue} * 100%)`;

    return `color-mix(in srgb, var(${variable}) ${percentage}, transparent)`;
};

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
                background: semanticColor('--color-background'),
                surface: semanticColor('--color-surface'),
                'surface-container-lowest': semanticColor('--color-surface-container-lowest'),
                'surface-container-low': semanticColor('--color-surface-container-low'),
                'surface-container': semanticColor('--color-surface-container'),
                'surface-container-high': semanticColor('--color-surface-container-high'),
                'surface-container-highest': semanticColor('--color-surface-container-highest'),
                'inverse-surface': semanticColor('--color-inverse-surface'),
                'on-surface': semanticColor('--color-on-surface'),
                'on-surface-variant': semanticColor('--color-on-surface-variant'),
                primary: semanticColor('--color-primary'),
                'primary-dim': semanticColor('--color-primary-dim'),
                'on-primary': semanticColor('--color-on-primary'),
                'primary-container': semanticColor('--color-primary-container'),
                'on-primary-container': semanticColor('--color-on-primary-container'),
                secondary: semanticColor('--color-secondary'),
                'secondary-container': semanticColor('--color-secondary-container'),
                'on-secondary-container': semanticColor('--color-on-secondary-container'),
                tertiary: semanticColor('--color-tertiary'),
                'tertiary-container': semanticColor('--color-tertiary-container'),
                'tertiary-fixed-dim': semanticColor('--color-tertiary-fixed-dim'),
                'on-tertiary-container': semanticColor('--color-on-tertiary-container'),
                error: semanticColor('--color-error'),
                'on-error': semanticColor('--color-on-error'),
                'error-container': semanticColor('--color-error-container'),
                'on-error-container': semanticColor('--color-on-error-container'),
                outline: semanticColor('--color-outline'),
                'outline-variant': semanticColor('--color-outline-variant'),
            },
            borderRadius: {
                DEFAULT: '0.625rem',
                md: '0.75rem',
                lg: '0.875rem',
                xl: '1rem',
                '2xl': '1.25rem',
                '3xl': '1.5rem',
                full: '9999px',
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
