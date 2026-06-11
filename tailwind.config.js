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
                    DEFAULT: '#1cb0f6',
                    dark: '#1899d6',
                    light: '#ddf4ff',
                },
            },
            fontFamily: {
                sans: ['Nunito', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
