<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    payrolls: Array,
    currentPeriod: String,
    category: String,
});

const localPayrolls = ref([]);
const searchTerm = ref('');
const processing = ref(false);

watch(
    () => props.payrolls,
    (payrolls) => {
        localPayrolls.value = payrolls.map((pay) => ({
            ...pay,
            employee: { ...pay.employee },
        }));
    },
    { immediate: true, deep: true }
);

const filteredPayrolls = computed(() => {
    const term = searchTerm.value.toLowerCase().trim();
    if (!term) {
        return localPayrolls.value;
    }

    return localPayrolls.value.filter((pay) => {
        return [
            pay.employee.name,
            pay.employee.nik,
            pay.employee.position,
        ].some((value) => String(value || '').toLowerCase().includes(term));
    });
});

const formatRp = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const totalNet = computed(() => {
    return filteredPayrolls.value.reduce((sum, item) => sum + parseFloat(item.net_salary || 0), 0);
});

const totalBruto = computed(() => {
    return filteredPayrolls.value.reduce((sum, item) => sum + parseFloat(item.gaji_bruto || 0), 0);
});

const totalPph = computed(() => {
    return filteredPayrolls.value.reduce((sum, item) => sum + parseFloat(item.pph21 || 0), 0);
});

const goBack = () => {
    router.get(route('payroll.index'), { period: props.currentPeriod });
};

const updatePayroll = async (pay, updates) => {
    const url = route('payroll.detail.update', { payroll: pay.id });

    try {
        const response = await axios.patch(url, updates);
        const updated = response.data.payroll;

        const index = localPayrolls.value.findIndex((item) => item.id === pay.id);
        if (index !== -1) {
            localPayrolls.value[index] = {
                ...localPayrolls.value[index],
                ...updated,
                employee: localPayrolls.value[index].employee,
            };
        }
    } catch (error) {
        console.error('Update payroll failed:', error);
    }
};

const recalculatePayroll = async (pay) => {
    if (processing.value) {
        return;
    }

    processing.value = true;
    await updatePayroll(pay, {});
    processing.value = false;
};

const recalculateAll = async () => {
    if (processing.value) {
        return;
    }

    processing.value = true;
    for (const pay of localPayrolls.value) {
        await updatePayroll(pay, {});
    }
    processing.value = false;
};
</script>

<template>
    <AppLayout title="Detail Payroll">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start gap-4 px-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 uppercase tracking-tighter">Payroll Detail</h2>
                    <p class="text-sm text-slate-500">Kategori: {{ props.category }} • Periode: {{ props.currentPeriod }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <PrimaryButton @click="goBack" class="bg-slate-800 hover:bg-slate-900 rounded-xl px-6 py-3">Kembali</PrimaryButton>
                    <PrimaryButton @click="recalculateAll" :disabled="processing" class="bg-emerald-600 hover:bg-emerald-700 rounded-xl px-6 py-3">
                        {{ processing ? 'Processing...' : 'Recalculate All' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#f8fafc] min-h-screen">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                    <div class="w-full sm:w-1/2">
                        <input
                            type="search"
                            v-model="searchTerm"
                            placeholder="Cari nama, NIK, atau jabatan..."
                            class="w-full rounded-3xl border border-slate-300 bg-white px-5 py-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        />
                    </div>
                    <div class="text-sm text-slate-500">
                        Menampilkan {{ filteredPayrolls.length }} dari {{ localPayrolls.length }} entri
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-slate-200">
                        <div class="text-slate-500 uppercase tracking-[0.3em] text-[10px] font-black mb-3">Total Net</div>
                        <div class="text-3xl font-black">{{ formatRp(totalNet) }}</div>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-slate-200">
                        <div class="text-slate-500 uppercase tracking-[0.3em] text-[10px] font-black mb-3">Total Bruto</div>
                        <div class="text-3xl font-black">{{ formatRp(totalBruto) }}</div>
                    </div>
                    <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-slate-200">
                        <div class="text-slate-500 uppercase tracking-[0.3em] text-[10px] font-black mb-3">Total PPh21</div>
                        <div class="text-3xl font-black">{{ formatRp(totalPph) }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-[3rem] border border-slate-200 shadow-2xl overflow-x-auto ring-1 ring-slate-200/50">
                    <table class="w-full min-w-[1500px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/5 text-[10px] uppercase tracking-tighter font-black text-slate-500 border-b border-slate-100">
                                <th class="p-6 border-r border-slate-100 text-center w-16">No</th>
                                <th class="p-6 border-r border-slate-100 min-w-[250px]">Nama</th>
                                <th class="p-6 border-r border-slate-100">Jabatan</th>
                                <th class="p-6 border-r border-slate-100 text-right">Gaji Pokok</th>
                                <th class="p-6 border-r border-slate-100 text-right">Tunjangan</th>
                                <th class="p-6 border-r border-slate-100 text-right">Lembur</th>
                                <th class="p-6 border-r border-slate-100 text-right">BPJS Co.</th>
                                <th class="p-6 border-r border-slate-100 text-right">BPJS Emp.</th>
                                <th class="p-6 border-r border-slate-100 text-right">PPh 21</th>
                                <th class="p-6 border-r border-slate-100 text-right">THP</th>
                                <th class="p-6 text-center">Slip</th>
                            </tr>
                        </thead>
                        <tbody class="text-[11px]">
                            <template v-if="filteredPayrolls.length">
                                <tr v-for="(pay, index) in filteredPayrolls" :key="pay.id" class="border-b border-slate-100 hover:bg-slate-50 transition-all">
                                    <td class="p-4 text-center text-slate-400 font-black">{{ index + 1 }}</td>
                                    <td class="p-4 border-r border-slate-50">
                                        <div class="font-black text-slate-800 uppercase">{{ pay.employee.name }}</div>
                                        <div class="text-[9px] text-slate-400 uppercase tracking-wide">{{ pay.employee.nik }}</div>
                                    </td>
                                    <td class="p-4 border-r border-slate-50">{{ pay.employee.position }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right">
                                        <template v-if="props.category === 'Direksi'">
                                            <input
                                                type="number"
                                                v-model.number="pay.salary_pokok"
                                                @change="updatePayroll(pay, { salary_pokok: pay.salary_pokok })"
                                                class="w-full bg-slate-100/80 border-none rounded-lg text-right text-[11px] font-black focus:ring-2 focus:ring-indigo-500 h-9"
                                            />
                                        </template>
                                        <template v-else>{{ formatRp(pay.salary_pokok) }}</template>
                                    </td>
                                    <td class="p-4 border-r border-slate-50 text-right">{{ formatRp(pay.allowances) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right">{{ formatRp(pay.overtime) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right">{{ formatRp(pay.total_bpjs_company) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right">{{ formatRp(pay.total_bpjs_employee) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right">
                                        <template v-if="props.category === 'Direksi'">
                                            <input
                                                type="number"
                                                v-model.number="pay.pph21"
                                                @change="updatePayroll(pay, { pph21: pay.pph21 })"
                                                class="w-full bg-red-50 border-none rounded-lg text-right text-[11px] font-black text-red-600 focus:ring-2 focus:ring-red-500 h-9"
                                            />
                                        </template>
                                        <template v-else>{{ formatRp(pay.pph21) }}</template>
                                    </td>
                                    <td class="p-4 border-r border-slate-50 text-right font-black text-emerald-700">{{ formatRp(pay.net_salary) }}</td>
                                    <td class="p-4 text-center space-y-2">
                                        <button
                                            @click.stop="router.get(route('payroll.slip', { payroll: pay.id }))"
                                            class="text-xs font-black uppercase text-indigo-600 hover:text-indigo-900"
                                        >Slip</button>
                                        <button
                                            type="button"
                                            @click.stop="recalculatePayroll(pay)"
                                            :disabled="processing"
                                            class="text-xs font-black uppercase text-slate-500 hover:text-slate-900 disabled:text-slate-300 disabled:cursor-not-allowed"
                                        >Recalculate</button>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="11" class="p-24 text-center text-slate-400 font-black uppercase tracking-[0.8em] text-sm">
                                    Tidak ada data yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
