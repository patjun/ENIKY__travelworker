import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/views/**/*.blade.php',
        '!./resources/views/filament/**',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#5cd0dd',
                accent: '#39527d',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
