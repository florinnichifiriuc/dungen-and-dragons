/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{ts,tsx,js,jsx}',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#fff5e6',
                    100: '#ffe6bf',
                    200: '#ffcc80',
                    300: '#ffb347',
                    400: '#ff8c1a',
                    500: '#f97316',
                    600: '#dd5b0b',
                    700: '#b3420a',
                    800: '#7a2d0a',
                    900: '#411605',
                },
            },
            fontFamily: {
                sans: ['"Red Hat Display"', 'Inter', 'ui-sans-serif', 'system-ui'],
                display: ['"Cinzel Decorative"', 'serif'],
            },
            boxShadow: {
                ambient: '0 40px 80px rgba(15, 23, 42, 0.45)',
            },
        },
    },
    plugins: [],
};
