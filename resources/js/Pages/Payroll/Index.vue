<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch, computed } from 'vue';

const props = defineProps({ payrolls: Array, currentPeriod: String });

const categories = ['Direksi', 'Staff', 'General', 'Produksi 1', 'Produksi 2'];
const selectedPeriod = ref(props.currentPeriod);
const activeSheet = ref('Staff');
const selectedPayId = ref(null);
const localPayrolls = ref([]);

const form = useForm({
    period: selectedPeriod.value,
    category: activeSheet.value,
});

watch(selectedPeriod, (newPeriod) => {
    form.period = newPeriod;
    router.get(route('payroll.index'), { period: newPeriod }, { preserveState: true });
});

watch(activeSheet, (newCategory) => {
    form.category = newCategory;
    selectedPayId.value = null;
});

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
    return localPayrolls.value.filter((pay) => pay.employee.payroll_category === activeSheet.value);
});

const selectedPay = computed(() => {
    return localPayrolls.value.find((pay) => pay.id === selectedPayId.value) ?? null;
});

const totalNet = computed(() => {
    return filteredPayrolls.value.reduce((sum, pay) => sum + parseFloat(pay.net_salary || 0), 0);
});

const totalBruto = computed(() => {
    return filteredPayrolls.value.reduce((sum, pay) => sum + parseFloat(pay.gaji_bruto || 0), 0);
});

const totalPph = computed(() => {
    return filteredPayrolls.value.reduce((sum, pay) => sum + parseFloat(pay.pph21 || 0), 0);
});

const formatRp = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const generatePayroll = () => {
    if (!confirm(`Generate payroll ${activeSheet.value} untuk periode ${selectedPeriod.value}?`)) {
        return;
    }

    form.post(route('payroll.generate'), {
        preserveScroll: true,
        onError: (errors) => console.error('Generate error:', errors),
    });
};

const selectForAudit = (pay) => {
    selectedPayId.value = selectedPayId.value === pay.id ? null : pay.id;
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

        if (selectedPayId.value === pay.id) {
            selectedPayId.value = pay.id;
        }
    } catch (error) {
        console.error('Payroll update error:', error);
    }
};

const viewSlip = (pay) => {
    router.get(route('payroll.slip', { payroll: pay.id }));
};

const navigateDetail = () => {
    router.get(route('payroll.show', {
        period: selectedPeriod.value,
        category: activeSheet.value,
    }));
};

const recalculatePayroll = (pay) => {
    router.post(route('payroll.recalculate', { payroll: pay.id }), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AppLayout title="Payroll Engine">
        <template #header>
            <div class="flex flex-wrap justify-between items-center gap-4 px-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase italic">Payroll Engine</h2>
                    <p class="text-sm text-slate-500">Periode: {{ selectedPeriod }}</p>
                </div>
                <div class="flex flex-wrap gap-3 items-center">
                    <input
                        type="month"
                        v-model="selectedPeriod"
                        class="border-none bg-white/50 backdrop-blur-md rounded-xl shadow-sm focus:ring-indigo-500 font-bold text-slate-700 px-4 py-3"
                    />
                    <PrimaryButton @click="generatePayroll" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg px-6 py-3">
                        {{ form.processing ? 'Processing...' : `Generate Gaji` }}
                    </PrimaryButton>
                    <PrimaryButton @click="navigateDetail" class="bg-slate-800 hover:bg-slate-900 rounded-xl shadow-lg px-6 py-3">
                        Lihat Detail {{ activeSheet }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[#f8fafc] min-h-screen">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-wrap p-1 bg-slate-200/60 backdrop-blur-md rounded-2xl w-fit mb-8 border border-white shadow-inner">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        @click="activeSheet = cat"
                        :class="activeSheet === cat ? 'bg-white text-indigo-600 shadow-md' : 'text-slate-500 hover:text-slate-700'"
                        class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300"
                    >
                        {{ cat }}
                    </button>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_0.85fr] gap-6 mb-10">
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 shadow-2xl rounded-[3rem] p-8 text-white">
                            <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Total Net Payroll</div>
                            <div class="text-3xl font-black italic">{{ formatRp(totalNet) }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-slate-800 to-slate-900 shadow-2xl rounded-[3rem] p-8 text-white">
                            <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Total Bruto</div>
                            <div class="text-3xl font-black italic">{{ formatRp(totalBruto) }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-600 to-lime-600 shadow-2xl rounded-[3rem] p-8 text-white">
                            <div class="text-[10px] opacity-60 uppercase tracking-[0.2em] font-black mb-1">Total PPh 21</div>
                            <div class="text-3xl font-black italic">{{ formatRp(totalPph) }}</div>
                        </div>
                    </div>

                    <div class="bg-white/80 backdrop-blur-2xl border border-white shadow-2xl rounded-[3rem] p-8">
                        <div class="text-slate-500 uppercase text-[10px] tracking-[0.3em] font-black mb-4">Sheet Audit</div>
                        <div class="grid gap-4 text-sm text-slate-700">
                            <div class="flex justify-between border-b border-slate-200 pb-3">
                                <span class="font-black uppercase tracking-[0.18em]">Kategori</span>
                                <span>{{ activeSheet }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-3">
                                <span class="font-black uppercase tracking-[0.18em]">Jumlah Baris</span>
                                <span>{{ filteredPayrolls.length }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-3">
                                <span class="font-black uppercase tracking-[0.18em]">Periode</span>
                                <span>{{ selectedPeriod }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 backdrop-blur-2xl border border-white shadow-2xl rounded-[3rem] overflow-x-auto ring-1 ring-slate-200/50">
                    <table class="w-full min-w-[1500px] text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800/5 text-[10px] uppercase tracking-tighter font-black text-slate-500 border-b border-slate-100">
                                <th class="p-6 border-r border-slate-100 text-center w-16">No</th>
                                <th class="p-6 border-r border-slate-100 min-w-[260px]">Karyawan</th>
                                <th class="p-6 border-r border-slate-100 text-center">Gaji Pokok</th>
                                <th class="p-6 border-r border-slate-100 text-right">Tunjangan</th>
                                <th class="p-6 border-r border-slate-100 text-right">Lembur</th>
                                <th class="p-6 border-r border-slate-100 text-right">BPJS Co.</th>
                                <th class="p-6 border-r border-slate-100 text-right">BPJS Karyawan</th>
                                <th class="p-6 border-r border-slate-100 text-right">PPh 21</th>
                                <th class="p-6 border-r border-slate-100 text-right">THP</th>
                                <th class="p-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-[11px]">
                            <tr
                                v-for="(pay, index) in filteredPayrolls"
                                :key="pay.id"
                                @click="selectForAudit(pay)"
                                :class="[
                                    'border-b border-slate-100 transition-all',
                                    selectedPayId === pay.id ? 'bg-indigo-50' : 'hover:bg-slate-50',
                                ]"
                                class="cursor-pointer"
                            >
                                <td class="p-4 border-r border-slate-50 text-center text-slate-300 font-black">{{ index + 1 }}</td>
                                <td class="p-4 border-r border-slate-50">
                                    <div class="font-black text-slate-800 uppercase leading-none mb-1">{{ pay.employee.name }}</div>
                                    <div class="text-[9px] text-slate-400 font-bold italic tracking-wide uppercase">{{ pay.employee.position }}</div>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-right font-bold">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input
                                            type="number"
                                            v-model.number="pay.salary_pokok"
                                            @change="updatePayroll(pay, { salary_pokok: pay.salary_pokok })"
                                            class="w-full bg-slate-100/80 border-none rounded-lg text-right text-[11px] font-black focus:ring-2 focus:ring-indigo-500 h-9"
                                        />
                                    </template>
                                    <template v-else>{{ formatRp(pay.salary_pokok) }}</template>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-right text-slate-500">{{ formatRp(pay.allowances) }}</td>
                                <td class="p-4 border-r border-slate-50 text-right text-slate-500">{{ formatRp(pay.overtime) }}</td>
                                <td class="p-4 border-r border-slate-50 text-right font-black text-indigo-700">{{ formatRp(pay.total_bpjs_company) }}</td>
                                <td class="p-4 border-r border-slate-50 text-right font-black text-orange-700">{{ formatRp(pay.total_bpjs_employee) }}</td>
                                <td class="p-4 border-r border-slate-50 text-right font-black text-red-600">
                                    <template v-if="activeSheet === 'Direksi'">
                                        <input
                                            type="number"
                                            v-model.number="pay.pph21"
                                            @change="updatePayroll(pay, { pph21: pay.pph21 })"
                                            class="w-full bg-red-50 border-none rounded-lg text-right text-[11px] font-black text-red-600 focus:ring-2 focus:ring-red-500 h-9"
                                        />
                                    </template>
                                    <template v-else>{{ formatRp(pay.pph21) }}</template>
                                </td>
                                <td class="p-4 border-r border-slate-50 text-right font-black bg-emerald-500 text-white rounded-2xl">{{ formatRp(pay.net_salary) }}</td>
                                <td class="p-4 text-center">
                                    <div class="flex flex-col gap-2 items-center">
                                        <button
                                            @click.stop="viewSlip(pay)"
                                            class="text-xs font-black uppercase text-indigo-600 hover:text-indigo-900"
                                        >Slip</button>
                                        <button
                                            @click.stop="selectForAudit(pay)"
                                            class="text-xs font-black uppercase text-slate-500 hover:text-slate-900"
                                        >Audit</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredPayrolls.length === 0">
                                <td colspan="10" class="p-24 text-center text-slate-300 font-black uppercase tracking-[0.8em] text-xl">No records found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="selectedPay" class="py-4 bg-[#f8fafc]">
            <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-700 overflow-hidden">
                    <div class="p-8 bg-slate-800 border-b border-slate-700">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-white font-black uppercase tracking-tighter text-xl">Audit Payroll</h3>
                                <p class="text-slate-400 text-sm mt-2">{{ selectedPay.employee.position }} / {{ selectedPay.employee.payroll_category }}</p>
                            </div>
                            <button @click="selectedPayId.value = null" class="text-slate-500 hover:text-white">✕</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-slate-300 text-[11px]">
                            <div class="bg-slate-800/60 rounded-3xl p-4">
                                <div class="text-[9px] uppercase text-slate-500">Net Salary</div>
                                <div class="text-2xl font-black text-emerald-300">{{ formatRp(selectedPay.net_salary) }}</div>
                            </div>
                            <div class="bg-slate-800/60 rounded-3xl p-4">
                                <div class="text-[9px] uppercase text-slate-500">PPh 21</div>
                                <div class="text-2xl font-black text-red-300">{{ formatRp(selectedPay.pph21) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-8 space-y-4">
                        <div class="flex justify-between text-xs uppercase text-slate-400 tracking-[0.2em]">
                            <span>Gaji Bruto</span>
                            <span class="font-black text-white">{{ formatRp(selectedPay.gaji_bruto) }}</span>
                        </div>
                        <div class="flex justify-between text-xs uppercase text-slate-400 tracking-[0.2em]">
                            <span>Total BPJS Perusahaan</span>
                            <span class="font-black text-white">{{ formatRp(selectedPay.total_bpjs_company) }}</span>
                        </div>
                        <div class="flex justify-between text-xs uppercase text-slate-400 tracking-[0.2em]">
                            <span>Total BPJS Karyawan</span>
                            <span class="font-black text-white">{{ formatRp(selectedPay.total_bpjs_employee) }}</span>
                        </div>
                        <div class="bg-slate-950/80 border border-slate-800 rounded-3xl p-4">
                            <div class="text-[10px] uppercase text-slate-500 mb-3">Detail PPh 21</div>
                            <div class="grid gap-2 text-slate-300 text-[11px]">
                                <div class="flex justify-between"><span>Biaya Jabatan</span><span>{{ formatRp(selectedPay.biaya_jabatan) }}</span></div>
                                <div class="flex justify-between"><span>Gaji Netto Pajak</span><span>{{ formatRp(selectedPay.gaji_netto_pajak) }}</span></div>
                                <div class="flex justify-between"><span>Gaji Setahun</span><span>{{ formatRp(selectedPay.gaji_setahun) }}</span></div>
                                <div class="flex justify-between"><span>PTKP</span><span>{{ formatRp(selectedPay.ptkp) }}</span></div>
                                <div class="flex justify-between"><span>PKP</span><span>{{ formatRp(selectedPay.pkp) }}</span></div>
                            </div>
                        </div>
                        <button
                            @click="recalculatePayroll(selectedPay)"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase rounded-3xl py-3"
                        >Recalculate</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>