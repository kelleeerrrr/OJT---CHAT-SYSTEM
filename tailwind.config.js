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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: '#eb1c24',
                secondary: '#373737',
                accent: '#F7A01A',
                'breadcrumb-active': '#0072BB',
                info: '#0072BB',
            },
            spacing: {
                'sidebar': '260px',
                'header': '56px',
            },
        },
    },

    plugins: [forms],
};
