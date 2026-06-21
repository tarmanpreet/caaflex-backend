import { ref, onMounted } from 'vue';

export function useDarkMode() {
    const isDark = ref(false);

    const initializeDarkMode = () => {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark') {
            isDark.value = true;
        } else if (theme === 'light') {
            isDark.value = false;
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            isDark.value = true;
        } else {
            isDark.value = false;
        }
        
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    const toggleDark = () => {
        isDark.value = !isDark.value;
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    onMounted(() => {
        initializeDarkMode();
    });

    return {
        isDark,
        toggleDark,
    };
}
