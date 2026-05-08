import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'tailoy': {
                    'yellow': '#FFDD00',
                    'blue': '#002E6E',
                    'blue-light': '#00439E',
                    'blue-dark': '#001A40',
                    'red': '#E3001B',
                }
            }
        },
    },

    plugins: [forms],
};
