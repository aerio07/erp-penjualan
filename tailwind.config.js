import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                "primary": "#03193c",
                "primary-container": "#1b2e52",
                "on-primary": "#ffffff",
                "on-primary-container": "#8496c0",
                "primary-fixed": "#d8e2ff",
                "primary-fixed-dim": "#b4c6f3",
                "on-primary-fixed-variant": "#34466c",
                "secondary": "#455e90",
                "secondary-container": "#aec6ff",
                "on-secondary-container": "#395283",
                "slate-bg": "#F5F6F8",
                "paper": "#FFFFFF",
                "surface": "#faf9ff",
                "surface-variant": "#d9e2ff",
                "surface-dim": "#cddafc",
                "surface-container": "#e9edff",
                "surface-container-low": "#f1f3ff",
                "on-surface": "#0e1b35",
                "on-surface-variant": "#44474e",
                "border-light": "#E2E8F0",
                "border-medium": "#CBD5E1",
                "outline": "#75777f",
                "outline-variant": "#c5c6cf",
                "status-active-bg": "#DBE7FB", "status-active-text": "#1D4ED8",
                "status-success-bg": "#DCFCE3", "status-success-text": "#166534",
                "status-pending-bg": "#FBEBD2", "status-pending-text": "#92640B",
                "status-danger-bg": "#FDE2E1", "status-danger-text": "#B91C1C",
                "status-neutral-bg": "#F3F4F6", "status-neutral-text": "#6B7280",
                "tertiary-container": "#432900", "on-tertiary": "#ffffff",
                "error": "#ba1a1a"
            },
            borderRadius: { 
                "DEFAULT": "0.25rem", 
                "lg": "0.5rem", 
                "xl": "0.75rem", 
                "full": "9999px" 
            },
            spacing: {
                "unit-xs": "4px", "unit-sm": "8px", "unit-md": "16px", "unit-lg": "24px", "unit-xl": "32px",
                "gutter": "16px", "page-margin": "24px", "sidebar-width": "240px", "header-height": "56px"
            },
            fontFamily: {
                sans: ['Inter', 'Public Sans', ...defaultTheme.fontFamily.sans],
                "headline-lg": ["Public Sans", "sans-serif"],
                "headline-md": ["Public Sans", "sans-serif"],
                "title-sm": ["Public Sans", "sans-serif"],
                "label-xs": ["Public Sans", "sans-serif"],
                "body-base": ["Inter", "sans-serif"],
                "body-medium": ["Inter", "sans-serif"],
                "body-sm": ["Inter", "sans-serif"],
                "table-data": ["Inter", "sans-serif"],
                "stat-number": ["Inter", "sans-serif"]
            },
        },
    },

    plugins: [forms],
};
