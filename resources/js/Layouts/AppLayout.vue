<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({ title: String });

const page       = usePage();
const user       = computed(() => page.props.auth.user);
const userRoles  = computed(() => user.value?.roles ?? []);
const isAdmin    = computed(() => userRoles.value.includes('admin') || user.value?.id === 2);
const isEmployee = computed(() => !isAdmin.value);

const isExpanded = ref(true);
const toggleSidebar = () => { isExpanded.value = !isExpanded.value; };
const logout = () => { router.post(route('logout')); };

// ─── Menu Definitions ────────────────────────────────────────────────────────
const adminMenus = [
    {
        label: 'Dashboard',
        route: 'dashboard',
        active: () => route().current('dashboard'),
        icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    },
    {
        label: 'Karyawan',
        route: 'employees.index',
        active: () => route().current('employees.*'),
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        label: 'Payroll Engine',
        route: 'payroll.index',
        active: () => route().current('payroll.*'),
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        label: 'Rekap Absensi',
        route: 'attendance.index',
        active: () => route().current('attendance.index'),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    },
    {
        label: 'Pengaturan',
        route: 'settings.work',
        active: () => route().current('settings.*'),
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

const employeeMenus = [
    {
        label: 'Beranda',
        route: 'dashboard',
        active: () => route().current('dashboard'),
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        label: 'Absensi Saya',
        route: 'attendance.my',
        active: () => route().current('attendance.my'),
        icon: 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

const menus = computed(() => isAdmin.value ? adminMenus : employeeMenus);

// Role badge colors per category
const roleBadge = computed(() => {
    if (isAdmin.value) return { label: 'Admin', cls: 'bg-indigo-600 text-white' };
    return { label: 'Karyawan', cls: 'bg-emerald-500 text-white' };
});
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc] flex overflow-hidden">
        <Head :title="title" />

        <!-- ─── Sidebar ────────────────────────────────────────────────── -->
        <aside
            :class="isExpanded ? 'w-72' : 'w-20'"
            class="min-h-screen flex flex-col transition-all duration-300 ease-in-out z-50 relative"
            :style="isAdmin
                ? 'background: rgba(255,255,255,0.6); backdrop-filter: blur(24px); border-right: 1px solid rgba(200,200,230,0.3);'
                : 'background: linear-gradient(180deg, #1e1b4b 0%, #312e81 60%, #3730a3 100%);'"
        >
            <!-- Toggle button -->
            <button
                @click="toggleSidebar"
                class="absolute -right-3 top-10 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center hover:bg-slate-50 transition-all shadow-sm z-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" :class="!isExpanded ? 'rotate-180' : ''" class="h-3 w-3 text-slate-500 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Logo -->
            <div class="h-20 flex items-center px-6 overflow-hidden">
                <div class="flex items-center gap-3 min-w-max">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg"
                        :class="isAdmin ? 'bg-indigo-600 shadow-indigo-200' : 'bg-white/20'"
                    >
                        <span :class="isAdmin ? 'text-white' : 'text-white'" class="font-bold">I</span>
                    </div>
                    <span v-show="isExpanded" class="text-xl font-black tracking-tighter transition-opacity duration-300"
                        :class="isAdmin ? 'text-slate-800' : 'text-white'"
                    >
                        INDOBOX<span :class="isAdmin ? 'text-indigo-600' : 'text-indigo-300'">ERP</span>
                    </span>
                </div>
            </div>

            <!-- Role Badge -->
            <div v-show="isExpanded" class="px-6 pb-4">
                <span :class="roleBadge.cls" class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full">
                    {{ roleBadge.label }}
                </span>
            </div>

            <!-- Separator -->
            <div class="mx-4 mb-3 h-px" :class="isAdmin ? 'bg-slate-100' : 'bg-white/10'"></div>

            <!-- Nav -->
            <nav class="flex-1 px-3 space-y-1">
                <Link
                    v-for="menu in menus"
                    :key="menu.label"
                    :href="route(menu.route)"
                    :class="[
                        menu.active()
                            ? (isAdmin ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'bg-white/20 text-white')
                            : (isAdmin ? 'text-slate-500 hover:bg-white/80 hover:text-slate-900' : 'text-white/60 hover:bg-white/10 hover:text-white'),
                        'flex items-center gap-4 px-3 py-3 rounded-xl transition-all duration-200 group relative'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="menu.icon" />
                    </svg>
                    <span v-show="isExpanded" class="font-semibold whitespace-nowrap overflow-hidden transition-all duration-300">
                        {{ menu.label }}
                    </span>
                    <!-- Tooltip when collapsed -->
                    <span
                        v-show="!isExpanded"
                        class="absolute left-16 text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap z-50"
                        :class="isAdmin ? 'bg-slate-800 text-white' : 'bg-indigo-900 text-white'"
                    >
                        {{ menu.label }}
                    </span>
                </Link>
            </nav>

            <!-- User footer -->
            <div class="mt-auto p-4" :class="isAdmin ? 'border-t border-slate-200/50' : 'border-t border-white/10'">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold uppercase text-sm"
                        :class="isAdmin ? 'bg-slate-100 text-slate-500' : 'bg-white/20 text-white'"
                    >
                        {{ user?.name?.charAt(0) ?? '?' }}
                    </div>
                    <div v-show="isExpanded" class="min-w-0 transition-all duration-300">
                        <p class="text-xs font-bold truncate" :class="isAdmin ? 'text-slate-800' : 'text-white'">
                            {{ user?.name }}
                        </p>
                        <button @click="logout" class="text-[10px] font-bold hover:underline" :class="isAdmin ? 'text-red-500' : 'text-white/50 hover:text-white'">
                            Sign Out
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ─── Main Content ───────────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0">
            <header v-if="$slots.header" class="h-20 flex items-center px-8 border-b border-slate-200/50 bg-white/30 backdrop-blur-sm sticky top-0 z-40">
                <div class="w-full">
                    <slot name="header" />
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <slot />
            </main>
        </div>
    </div>
</template>