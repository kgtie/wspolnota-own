import './bootstrap';

import Alpine from 'alpinejs';

const themeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const getStoredThemePreference = () => localStorage.getItem('wspolnota_theme') || 'auto';

const resolveTheme = (preference) => {
    if (preference === 'auto') {
        return themeMediaQuery.matches ? 'dark' : 'light';
    }

    return preference === 'dark' ? 'dark' : 'light';
};

const applyTheme = (preference) => {
    const resolved = resolveTheme(preference);
    const root = document.documentElement;

    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.themePreference = preference;
    root.style.colorScheme = resolved;

    return resolved;
};

window.wspolnotaTheme = {
    apply: applyTheme,
    getStoredThemePreference,
    resolveTheme,
};

applyTheme(getStoredThemePreference());

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        preference: getStoredThemePreference(),
        resolved: applyTheme(getStoredThemePreference()),
        init() {
            this.sync();
        },
        set(preference) {
            this.preference = preference;
            localStorage.setItem('wspolnota_theme', preference);
            this.sync();
        },
        sync() {
            this.resolved = applyTheme(this.preference);
        },
    });
});

themeMediaQuery.addEventListener('change', () => {
    if (!window.Alpine?.store) {
        applyTheme(getStoredThemePreference());
        return;
    }

    const themeStore = Alpine.store('theme');

    if (!themeStore) {
        applyTheme(getStoredThemePreference());
        return;
    }

    if (themeStore.preference === 'auto') {
        themeStore.sync();
    }
});

Alpine.start();
