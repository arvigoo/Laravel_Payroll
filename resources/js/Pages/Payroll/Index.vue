<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({ 
    payrolls: Array, 
    currentPeriod: String 
});

const selectedPeriod = ref(props.currentPeriod);
const activeSheet = ref('Staff'); 

watch(selectedPeriod, (newPeriod) => {
    router.get(route('payroll.index'), { period: newPeriod }, { preserveState: true });
});

const filteredPayrolls = computed(() => {
    return props.payrolls.filter(p => p.employee.payroll_category === activeSheet.value);
});

const form = useForm({
    period: selectedPeriod,
});

const generatePayroll = () => {
    if (confirm(`Generate payroll ${activeSheet.value} untuk periode ${selectedPeriod.value}?`)) {
        form.post(route('payroll.generate'), {
            preserveScroll: true,
        });
    }
};

// --- LOGIC UPDATE MANUAL (KHUSUS DIREKSI) ---
const updateManualGaji = (pay) => {
    if (!pay.id) return;

    // Kirim semua variabel yang bisa diinput manual
    router.patch(route('payroll.update', { payroll: pay.id }), {
        salary_pokok: pay.salary_pokok,
        allowances: pay.allowances,
        overtime: pay.overtime || 0,
        pph21: pay.pph21,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => console.log("✅ Data Direksi Disinkronkan"),
    });
};

const formatRp = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <AppLayout title="Payroll Management">
        <template #header>
            <div class="flex justify-between items-center px-4">
                <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase italic">Payroll Engine <span class="text-indigo-600">v2.0</span></h2>
                <div class="flex items-center gap-4">
                    <input 
                        type="month" 
                        v-model="selectedPeriod" 
                        class="border-none bg-white/50 backdrop-blur-md rounded-xl shadow-sm focus:ring-indigo-500 font-bold text-slate-700"
                    >
                    <PrimaryButton @click="generatePayroll" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-100 px-6">
                        {{ form.processing ? 'Processing...' : `Generate Gaji ${activeSheet}` }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#f8fafc] min-h-screen">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                
                <div class="flex p-1 bg-slate-200/60 backdrop-blur-md rounded-2xl w-fit mb-8 border border-white shadow-inner">
                    <button @click="activeSheet = 'Staff'" :class="activeSheet === 'Staff' ? 'bg-white text-indigo-600 shadow-md' : 'text-slate-500 hover:text-slate-700'" class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300">Staff & Operasional</button>
                    <button @click="activeSheet = 'Direksi'" :class="activeSheet === 'Direksi' ? 'bg-white text-indigo-600 shadow-md' : 'text-slate-500 hover:text-slate-700'" class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300">Direksi & Eksekutif</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                    <div :class="activeSheet === 'Direksi' ? 'from-slate-800 to-slate-900' : 'from-indigo-600 to-purple-700'" class="bg-gradient-to-br p-8 rounded-[2.5rem] shadow-2xl text-white">
                        <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1 text-white">Total Net Payroll ({{ activeSheet }})</div>
                        <div class="text-3xl font-black italic">{{ formatRp(filteredPayrolls.reduce((acc, curr) => acc + parseFloat(curr.net_salary), 0)) }}</div>
                    </div>
                </div>  

                <div class="bg-white/70 backdrop-blur-2xl border border-white shadow-2xl rounded-[3rem] overflow-x-auto ring-1 ring-slate-200/50">
                    <table class="w-full text-left border-collapse min-w-[1800px]">
                        <thead>
                            <tr class="bg-slate-800/5 text-[10px] uppercase tracking-tighter font-black text-slate-500 border-b border-slate-100">
                                <th rowspan="2" class="p-6 border-r border-slate-100 text-center w-16 text-slate-300">No</th>
                                <th rowspan="2" class="p-6 border-r border-slate-100 min-w-[250px]">Data Karyawan</th>
                                <th rowspan="2" class="p-6 border-r border-slate-100 text-center">STS</th>
                                <th colspan="4" class="p-3 border-r border-slate-100 text-center bg-blue-50/50 text-blue-600">Earnings (Pendapatan)</th>
                                
                                <th v-if="activeSheet !== 'Direksi'" colspan="5" class="p-3 border-r border-slate-100 text-center bg-orange-50/50 text-orange-600">BPJS Perusahaan (Cost)</th>
                                
                                <th rowspan="2" class="p-6 border-r border-slate-100 text-right bg-slate-900 text-white shadow-xl">Gaji Bruto</th>
                                
                                <th v-if="activeSheet !== 'Direksi'" colspan="4" class="p-3 border-r border-slate-100 text-center bg-red-50/50 text-red-600">Potongan Karyawan</th>
                                
                                <th rowspan="2" class="p-6 border-r border-slate-100 text-right text-red-600 font-black">PPh 21</th>
                                <th rowspan="2" class="p-6 text-right bg-emerald-500 text-white font-black shadow-2xl">Total Gaji (THP)</th>
                            </tr>
                            <tr class="bg-slate-50/50 text-[9px] uppercase font-black text-slate-400 border-b border-slate-100 text-right">
                                <th class="p-3 border-r border-slate-100">Gaji Pokok</th>
                                <th class="p-3 border-r border-slate-100">Tunjangan</th>
                                <th class="p-3 border-r border-slate-100">Lembur</th>
                                <th class="p-3 border-r border-slate-100 font-bold text-slate-600 uppercase">Total</th>
                                
                                <template v-if="activeSheet !== 'Direksi'">
                                    <th class="p-3 border-r border-slate-100">JKK JKM</th>
                                    <th class="p-3 border-r border-slate-100">JHT</th>
                                    <th class="p-3 border-r border-slate-100">JP</th>
                                    <th class="p-3 border-r border-slate-100">Kes</th>
                                    <th class="p-3 border-r border-slate-100 italic font-black text-orange-700">Total BPJS P</th>
                                    <th class="p-3 border-r border-slate-100">JHT</th>
                                    <th class="p-3 border-r border-slate-100">JP</th>
                                    <th class="p-3 border-r border-slate-100">Kes</th>
                                    <th class="p-3 border-r border-slate-100 font-black text-red-700 uppercase tracking-tighter">Total Pot B</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="text-[11px]">
                            <tr v-for="(pay, index) in filteredPayrolls" :key="pay.id" class="hover:bg-slate-50/80 transition-all border-b border-slate-100 group">
                                <td class="p-4 border-r border-slate-50 text-center text-slate-300 font-black">{{ index + 1 }}</td>
                                <td class="p-4 border-r border-slate-50">
                                    <div class="font-black text-slate-800 uppercase leading-none mb-1">{{ pay.employee.name }}</div>
                                    <div class="text-[9px] text-slate-400 font-bold italic tracking-wide uppercase">{{ pay.employee.position }}</div>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-center font-black text-indigo-500">{{ pay.employee.tax_status }}</td>
                                
                                <td class="p-4 border-r border-slate-50 text-right font-bold">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input type="number" v-model="pay.salary_pokok" @change="updateManualGaji(pay)" class="input-modern">
                                    </template>
                                    <template v-else>{{ formatRp(pay.salary_pokok) }}</template>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-right">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input type="number" v-model="pay.allowances" @change="updateManualGaji(pay)" class="input-modern">
                                    </template>
                                    <template v-else>{{ formatRp(pay.allowances) }}</template>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-right">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input type="number" v-model="pay.overtime" @change="updateManualGaji(pay)" class="input-modern">
                                    </template>
                                    <template v-else>{{ formatRp(pay.overtime || 0) }}</template>
                                </td>
                                <td class="p-4 border-r border-slate-100 text-right font-black bg-blue-50/30 text-blue-800">
                                    {{ formatRp(parseFloat(pay.salary_pokok) + parseFloat(pay.allowances) + (parseFloat(pay.overtime) || 0)) }}
                                </td>

                                <template v-if="activeSheet !== 'Direksi'">
                                    <td class="p-4 border-r border-slate-50 text-right opacity-40">{{ formatRp(pay.salary_pokok * 0.0119) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right opacity-40">{{ formatRp(pay.salary_pokok * 0.037) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right opacity-40">{{ formatRp(pay.salary_pokok * 0.02) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right opacity-40">{{ formatRp(pay.salary_pokok * 0.04) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right italic font-black bg-orange-50/30 text-orange-800">{{ formatRp(pay.salary_pokok * 0.1089) }}</td>
                                </template>

                                <td class="p-4 border-r border-slate-100 text-right font-black bg-slate-800 text-white shadow-lg">
                                    {{ formatRp(parseFloat(pay.salary_pokok) + parseFloat(pay.allowances) + (parseFloat(pay.overtime) || 0) + (activeSheet === 'Direksi' ? 0 : (pay.salary_pokok * 0.1089))) }}
                                </td>

                                <template v-if="activeSheet !== 'Direksi'">
                                    <td class="p-4 border-r border-slate-50 text-right text-red-300 italic">{{ formatRp(pay.salary_pokok * 0.02) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right text-red-300 italic">{{ formatRp(pay.salary_pokok * 0.01) }}</td>
                                    <td class="p-4 border-r border-slate-50 text-right text-red-300 italic">{{ formatRp(pay.salary_pokok * 0.01) }}</td>
                                    <td class="p-4 border-r border-slate-100 text-right font-black text-red-700 bg-red-50/30">-{{ formatRp(pay.bpjs_employee) }}</td>
                                </template>

                                <td class="p-4 border-r border-slate-100 text-right text-red-600 font-black">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input type="number" v-model="pay.pph21" @change="updateManualGaji(pay)" class="input-modern bg-red-50 text-red-600 focus:ring-red-500">
                                    </template>
                                    <template v-else>-{{ formatRp(pay.pph21) }}</template>
                                </td>

                                <td class="p-4 text-right font-black bg-emerald-500 text-white shadow-inner text-xs">
                                    {{ formatRp(parseFloat(pay.salary_pokok) + parseFloat(pay.allowances) + (parseFloat(pay.overtime) || 0) - parseFloat(pay.pph21) - (activeSheet === 'Direksi' ? 0 : parseFloat(pay.bpjs_employee))) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input-modern {
    @apply w-full bg-slate-100/50 border-none rounded-lg text-right text-[11px] font-black focus:ring-2 focus:ring-indigo-500 h-8;
}

input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    -moz-appearance: textfield;
}
</style>