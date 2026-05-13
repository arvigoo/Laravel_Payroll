<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
});

// State untuk Expand/Collapse Sidebar
const isExpanded = ref(true);

const toggleSidebar = () => {
    isExpanded.value = !isExpanded.value;
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc] flex overflow-hidden">
        <Head :title="title" />

        <aside 
            :class="isExpanded ? 'w-72' : 'w-20'" 
            class="min-h-screen bg-white/60 backdrop-blur-2xl border-r border-slate-200/50 flex flex-col transition-all duration-300 ease-in-out z-50 relative"
        >
            <button 
                @click="toggleSidebar"
                class="absolute -right-3 top-10 w-6 h-6 bg-white border border-slate-200 rounded-full flex items-center justify-center hover:bg-slate-50 transition-all shadow-sm z-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" :class="!isExpanded ? 'rotate-180' : ''" class="h-3 w-3 text-slate-500 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="h-20 flex items-center px-6 overflow-hidden">
                <div class="flex items-center gap-3 min-w-max">
                    <div class="w-9 h-9 bg-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-200">
                        <span class="text-white font-bold">I</span>
                    </div>
                    <span v-show="isExpanded" class="text-xl font-black text-slate-800 tracking-tighter transition-opacity duration-300">
                        INDOBOX<span class="text-indigo-600">ERP</span>
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-3 space-y-1 mt-4">
                <Link :href="route('dashboard')" 
                    :class="route().current('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-slate-500 hover:bg-white/80 hover:text-slate-900'"
                    class="flex items-center gap-4 px-3 py-3 rounded-xl transition-all duration-200 group relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span v-show="isExpanded" class="font-semibold whitespace-nowrap overflow-hidden transition-all duration-300">Dashboard</span>
                    <span v-show="!isExpanded" class="absolute left-16 bg-slate-800 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity">Dashboard</span>
                </Link>

                <Link :href="route('employees.index')" 
                    :class="route().current('employees.index') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:bg-white/80'"
                    class="flex items-center gap-4 px-3 py-3 rounded-xl transition-all group relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span v-show="isExpanded" class="font-semibold whitespace-nowrap transition-all">Karyawan</span>
                    <span v-show="!isExpanded" class="absolute left-16 bg-slate-800 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 pointer-events-none">Karyawan</span>
                </Link>

                <Link :href="route('payroll.index')" 
                    :class="route().current('payroll.index') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:bg-white/80'"
                    class="flex items-center gap-4 px-3 py-3 rounded-xl transition-all group relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span v-show="isExpanded" class="font-semibold whitespace-nowrap transition-all">Payroll Engine</span>
                    <span v-show="!isExpanded" class="absolute left-16 bg-slate-800 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 pointer-events-none">Payroll</span>
                </Link>
            </nav>

            <div class="mt-auto p-4 border-t border-slate-200/50">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0 font-bold text-slate-500 uppercase">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div v-show="isExpanded" class="min-w-0 transition-all duration-300">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ $page.props.auth.user.name }}</p>
                        <button @click="logout" class="text-[10px] text-red-500 font-bold hover:underline">Sign Out</button>
                    </div>
                </div>
            </div>
        </aside>
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