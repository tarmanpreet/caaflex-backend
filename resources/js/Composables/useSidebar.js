import { ref, onMounted } from 'vue';

export function useSidebar() {
    const isCollapsed = ref(false);
    const isMobileOpen = ref(false);

    const initializeSidebar = () => {
        const collapsed = localStorage.getItem('sidebar-collapsed');
        isCollapsed.value = collapsed === '1';
    };

    const toggleSidebar = () => {
        isCollapsed.value = !isCollapsed.value;
        localStorage.setItem('sidebar-collapsed', isCollapsed.value ? '1' : '0');
    };

    const openMobile = () => {
        isMobileOpen.value = true;
    };

    const closeMobile = () => {
        isMobileOpen.value = false;
    };

    onMounted(() => {
        initializeSidebar();
    });

    return {
        isCollapsed,
        toggleSidebar,
        isMobileOpen,
        openMobile,
        closeMobile,
    };
}
