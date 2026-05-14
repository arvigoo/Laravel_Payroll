<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    attendances: Array,
    summary: Array,
    currentPeriod: String,
    setting: Object,
});

const selectedPeriod = ref(props.currentPeriod);
const selectedPhoto  = ref(null);
const filterName     = ref('');

watch(selectedPeriod, (val) => {
    router.get('/attendance', { period: val }, { preserveState: true });
});

const filteredAttendances = computed(() => {
    if (!filterName.value) return props.attendances;
    return props.attendances.filter(a =>
        a.employee?.name?.toLowerCase().includes(filterName.value.toLowerCase())
    );
});

const destroyAttendance = (id) => {
    if (!confirm('Hapus data absensi ini?')) return;
    router.delete(`/attendance/${id}`, { preserveScroll: true });
};

const statusColor = (s) => ({
    present: 'bg-emerald-100 text-emerald-700',
    late:    'bg-amber-100 text-amber-700',
    absent:  'bg-red-100 text-red-700',
    holiday: 'bg-blue-100 text-blue-700',
}[s] || 'bg-slate-100');

const statusLabel = (s) => ({ present: 'Hadir', late: 'Terlambat', absent: 'Absen', holiday: 'Libur' }[s] || s);
const formatTime = (t) => t ? t.substring(0, 5) : '--:--';

const totalHadir     = computed(() => props.attendances.filter(a => a.status === 'present').length);
const totalTerlambat = computed(() => props.attendances.filter(a => a.status === 'late').length);
const totalOt        = computed(() => props.attendances.reduce((s, a) => s + (parseFloat(a.ot_hours) || 0), 0).toFixed(1));
</script>

<template>
    <AppLayout title="Rekap Absensi">
        <template #header>
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase italic">Rekap Absensi</h2>
                    <p class="text-sm text-slate-400">Periode: {{ selectedPeriod }}</p>
                </div>
                <div class="flex gap-3">
                    <input type="month" v-model="selectedPeriod" class="border-none bg-white/50 backdrop-blur rounded-xl shadow-sm focus:ring-indigo-500 font-bold text-slate-700 px-4 py-3" />
                    <input type="text" v-model="filterName" placeholder="Cari karyawan..." class="border-none bg-white/50 backdrop-blur rounded-xl shadow-sm focus:ring-indigo-500 px-4 py-3 w-48" />
                </div>
            </div>
        </template>

        <div class="py-10 bg-[#f8fafc] min-h-screen">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Summary Cards -->
                <div class="grid grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2.5rem] p-7 text-white shadow-xl">
                        <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Total Hadir</div>
                        <div class="text-4xl font-black">{{ totalHadir }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-[2.5rem] p-7 text-white shadow-xl">
                        <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Terlambat</div>
                        <div class="text-4xl font-black">{{ totalTerlambat }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-700 to-slate-900 rounded-[2.5rem] p-7 text-white shadow-xl">
                        <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Total OT (Jam)</div>
                        <div class="text-4xl font-black">{{ totalOt }}</div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white/70 backdrop-blur-2xl border border-white shadow-2xl rounded-[3rem] overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800/5 text-[10px] uppercase tracking-widest font-black text-slate-400 border-b border-slate-100">
                                <th class="p-6 border-r border-slate-50">Tanggal</th>
                                <th class="p-6 border-r border-slate-50">Karyawan</th>
                                <th class="p-6 border-r border-slate-50 text-center">Masuk</th>
                                <th class="p-6 border-r border-slate-50 text-center">Keluar</th>
                                <th class="p-6 border-r border-slate-50 text-center">Status</th>
                                <th class="p-6 border-r border-slate-50 text-center">OT</th>
                                <th class="p-6 border-r border-slate-50 text-center">Foto</th>
                                <th class="p-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="att in filteredAttendances" :key="att.id" class="border-b border-slate-50 hover:bg-slate-50 transition-all text-sm">
                                <td class="p-5 border-r border-slate-50 font-black text-slate-700 whitespace-nowrap">
                                    {{ new Date(att.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>
                                <td class="p-5 border-r border-slate-50">
                                    <div class="font-black text-slate-800 uppercase">{{ att.employee?.name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ att.employee?.position }}</div>
                                </td>
                                <td class="p-5 border-r border-slate-50 text-center font-mono font-bold text-slate-600">{{ formatTime(att.check_in) }}</td>
                                <td class="p-5 border-r border-slate-50 text-center font-mono font-bold text-slate-600">{{ formatTime(att.check_out) }}</td>
                                <td class="p-5 border-r border-slate-50 text-center">
                                    <span :class="statusColor(att.status)" class="text-xs font-black px-3 py-1 rounded-full">{{ statusLabel(att.status) }}</span>
                                </td>
                                <td class="p-5 border-r border-slate-50 text-center font-bold" :class="att.ot_hours > 0 ? 'text-amber-600' : 'text-slate-300'">
                                    {{ att.ot_hours > 0 ? att.ot_hours + 'h' : '-' }}
                                </td>
                                <td class="p-5 border-r border-slate-50 text-center">
                                    <div class="flex justify-center gap-2">
                                        <img v-if="att.check_in_photo_url" :src="att.check_in_photo_url" @click="selectedPhoto = att.check_in_photo_url" class="w-10 h-10 rounded-xl object-cover cursor-pointer hover:scale-110 transition-all ring-2 ring-indigo-200 ring-offset-1" title="Foto Masuk" />
                                        <img v-if="att.check_out_photo_url" :src="att.check_out_photo_url" @click="selectedPhoto = att.check_out_photo_url" class="w-10 h-10 rounded-xl object-cover cursor-pointer hover:scale-110 transition-all ring-2 ring-slate-200 ring-offset-1" title="Foto Keluar" />
                                        <span v-if="!att.check_in_photo_url && !att.check_out_photo_url" class="text-slate-300 text-xs">-</span>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <button @click="destroyAttendance(att.id)" class="p-2 bg-red-50 text-red-400 rounded-xl hover:bg-red-600 hover:text-white transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredAttendances.length === 0">
                                <td colspan="8" class="p-20 text-center text-slate-200 font-black uppercase tracking-[0.5em] text-xl">No Records</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Photo Lightbox -->
        <div v-if="selectedPhoto" @click="selectedPhoto = null" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 cursor-pointer">
            <img :src="selectedPhoto" class="max-w-full max-h-[90vh] rounded-3xl shadow-2xl object-contain" />
            <button class="absolute top-6 right-6 text-white bg-white/10 hover:bg-white/20 rounded-full w-10 h-10 font-black text-xl flex items-center justify-center">✕</button>
        </div>
    </AppLayout>
</template>
