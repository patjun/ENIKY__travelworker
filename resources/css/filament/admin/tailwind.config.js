import preset from '../../../../vendor/filament/filament/tailwind.config.preset';
import frontendConfig from '../../../../tailwind.config.js';

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: frontendConfig.theme.extend.colors.primary,
                accent: frontendConfig.theme.extend.colors.accent,
            },
            fontFamily: frontendConfig.theme.extend.fontFamily,
        },
    },
};
