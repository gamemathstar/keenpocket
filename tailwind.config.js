/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './app/Http/Controllers/Web/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#059669',
                    dark: '#047857',
                    light: '#d1fae5',
                },
            },
        },
    },
    plugins: [],
};
