const THEME_KEY = 'sh3-theme';

function getStoredTheme() {
    return localStorage.getItem(THEME_KEY);
}

function applyTheme(dark) {
    document.documentElement.classList.toggle('dark', dark);
}

function initTheme() {
    const stored = getStoredTheme();
    if (stored) {
        applyTheme(stored === 'dark');
    } else {
        applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
}

initTheme();

document.addEventListener('alpine:init', () => {
    Alpine.data('themeToggle', () => ({
        dark: false,

        init() {
            this.dark = document.documentElement.classList.contains('dark');
        },

        toggle() {
            this.dark = !this.dark;
            applyTheme(this.dark);
            localStorage.setItem(THEME_KEY, this.dark ? 'dark' : 'light');
        },
    }));
});
