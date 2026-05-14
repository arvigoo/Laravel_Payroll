<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    payroll: Object,
});

const emp = computed(() => props.payroll.employee);
const pay = computed(() => props.payroll);

const formatRp = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);

const formatPeriod = (period) => {
    if (!period) return '';
    const [year, month] = period.split('-');
    const months = ['','JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                    'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    return `01 ${months[parseInt(month)]} S/D ${new Date(year, month, 0).getDate()} ${months[parseInt(month)]} ${year}`;
};

const handlePrint = () => window.print();
const goBack = () => router.get(route('payroll.index', { period: pay.value.period }));

// Computed components
const gajiPokok = computed(() => parseFloat(pay.value.salary_pokok || 0));
const tunjanganJabatan = computed(() => parseFloat(pay.value.uang_jabatan || 0));
const tunjanganPrestasi = computed(() => parseFloat(pay.value.tunjangan_prestasi || 0));
const tunjanganInsentif = computed(() => parseFloat(pay.value.tunjangan_insentif || 0));
const tunjanganBpjs = computed(() => parseFloat(pay.value.total_bpjs_company || 0));
const uangMakan = computed(() => parseFloat(pay.value.uang_makan || 0));
const lembur = computed(() => parseFloat(pay.value.overtime || 0));
const totalPenerimaan = computed(() =>
    gajiPokok.value + tunjanganJabatan.value + tunjanganPrestasi.value +
    tunjanganInsentif.value + tunjanganBpjs.value + uangMakan.value + lembur.value
);
const pph21 = computed(() => parseFloat(pay.value.pph21 || 0));
const bpjsKes = computed(() => parseFloat(pay.value.bpjs_kes_employee || 0));
const bpjsTk = computed(() => parseFloat(pay.value.jht_employee || 0) + parseFloat(pay.value.jp_employee || 0));
const totalPotongan = computed(() => pph21.value + bpjsKes.value + bpjsTk.value + tunjanganBpjs.value);
const totalGaji = computed(() => totalPenerimaan.value - totalPotongan.value);
const kasBon = computed(() => parseFloat(pay.value.kas_bon || 0));
const totalDiterima = computed(() => totalGaji.value - kasBon.value);
</script>

<template>
    <div class="min-h-screen bg-slate-100 font-sans" id="slip-root">

        <!-- Toolbar (tidak tercetak) -->
        <div class="no-print bg-slate-800 text-white py-4 px-8 flex items-center justify-between sticky top-0 z-50 shadow-xl">
            <button @click="goBack"
                class="flex items-center gap-2 text-slate-300 hover:text-white transition-colors font-bold text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Payroll
            </button>
            <div class="text-xs text-slate-400 font-black uppercase tracking-widest">Slip Gaji Preview</div>
            <button @click="handlePrint"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-2.5 rounded-xl shadow-lg transition-all hover:scale-105 text-sm">
                🖨️ Print Slip
            </button>
        </div>

        <!-- Slip Card -->
        <div class="max-w-3xl mx-auto my-10 px-4 print:my-0 print:max-w-none print:px-0">
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden print:shadow-none print:rounded-none" id="slip-card">

                <!-- Header -->
                <div class="bg-slate-900 text-white px-10 py-8 print:bg-slate-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">PT INDOBOX UTAMA JAYA</div>
                            <h1 class="text-3xl font-black uppercase tracking-tighter">SLIP GAJI</h1>
                            <div class="text-slate-400 text-sm mt-1 font-mono">{{ formatPeriod(pay.period) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] uppercase text-slate-500 tracking-widest font-black">Divisi</div>
                            <div class="text-lg font-black text-indigo-400 uppercase mt-1">{{ emp.payroll_category }}</div>
                        </div>
                    </div>

                    <!-- Employee Info -->
                    <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-700 pt-5">
                        <div>
                            <div class="text-[9px] uppercase text-slate-500 tracking-widest font-black">Nama Karyawan</div>
                            <div class="text-white font-black uppercase text-base mt-0.5">{{ emp.name }}</div>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase text-slate-500 tracking-widest font-black">NIK</div>
                            <div class="text-slate-300 font-mono text-sm mt-0.5">{{ emp.nik }}</div>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase text-slate-500 tracking-widest font-black">Jabatan / Bagian</div>
                            <div class="text-slate-300 font-bold text-sm mt-0.5">{{ emp.position }}</div>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase text-slate-500 tracking-widest font-black">Status Pajak</div>
                            <div class="text-slate-300 font-bold text-sm mt-0.5">{{ emp.tax_status }}</div>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-10 py-8 grid grid-cols-1 md:grid-cols-2 gap-8">

                    <!-- PENERIMAAN -->
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4 pb-2 border-b border-slate-100">
                            PENERIMAAN
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600">Gaji Pokok</span>
                                <span class="font-black text-slate-900">{{ formatRp(gajiPokok) }}</span>
                            </div>
                            <div v-if="tunjanganJabatan > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">Tunjangan Jabatan</span>
                                <span class="font-bold">{{ formatRp(tunjanganJabatan) }}</span>
                            </div>
                            <div v-if="tunjanganPrestasi > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">Tunjangan Prestasi</span>
                                <span class="font-bold">{{ formatRp(tunjanganPrestasi) }}</span>
                            </div>
                            <div v-if="tunjanganInsentif > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">Tunjangan Insentif</span>
                                <span class="font-bold">{{ formatRp(tunjanganInsentif) }}</span>
                            </div>
                            <div v-if="tunjanganBpjs > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">Tunjangan BPJS</span>
                                <span class="font-bold">{{ formatRp(tunjanganBpjs) }}</span>
                            </div>
                            <div v-if="uangMakan > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    Uang Makan
                                    <span v-if="pay.hari_makan" class="text-xs text-slate-400">({{ pay.hari_makan }} hari)</span>
                                </span>
                                <span class="font-bold">{{ formatRp(uangMakan) }}</span>
                            </div>
                            <div v-if="lembur > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    Lembur
                                    <span v-if="pay.ot_hari_biasa > 0" class="text-xs text-slate-400">({{ pay.ot_hari_biasa }} jam biasa)</span>
                                    <span v-if="pay.ot_hari_libur > 0" class="text-xs text-slate-400"> + ({{ pay.ot_hari_libur }} jam libur)</span>
                                </span>
                                <span class="font-bold">{{ formatRp(lembur) }}</span>
                            </div>
                            <!-- Total Penerimaan -->
                            <div class="flex justify-between text-sm font-black pt-3 border-t border-slate-200 mt-1">
                                <span class="text-slate-800 uppercase text-xs tracking-wider">Total Penerimaan</span>
                                <span class="text-slate-900">{{ formatRp(totalPenerimaan) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- POTONGAN -->
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4 pb-2 border-b border-slate-100">
                            POTONGAN
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600">PPh 21 <span class="text-xs text-slate-400">({{ emp.tax_status }})</span></span>
                                <span class="font-bold text-red-600">{{ formatRp(pph21) }}</span>
                            </div>
                            <div v-if="bpjsKes > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">BPJS Kes 1%</span>
                                <span class="font-bold text-red-500">{{ formatRp(bpjsKes) }}</span>
                            </div>
                            <div v-if="bpjsTk > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">BPJS TK (JHT+JP) 3%</span>
                                <span class="font-bold text-red-500">{{ formatRp(bpjsTk) }}</span>
                            </div>
                            <div v-if="tunjanganBpjs > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">BPJS Ditanggung Perusahaan</span>
                                <span class="font-bold text-slate-500">{{ formatRp(tunjanganBpjs) }}</span>
                            </div>
                            <!-- Total Potongan -->
                            <div class="flex justify-between text-sm font-black pt-3 border-t border-slate-200 mt-1">
                                <span class="text-slate-800 uppercase text-xs tracking-wider">Total Potongan</span>
                                <span class="text-red-700">{{ formatRp(totalPotongan) }}</span>
                            </div>
                        </div>

                        <!-- Detail PPh 21 -->
                        <div class="mt-6 bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <div class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">Detail Perhitungan PPh 21</div>
                            <div class="space-y-1.5 text-[11px]">
                                <div class="flex justify-between text-slate-500">
                                    <span>Gaji Bruto Pajak</span>
                                    <span>{{ formatRp(pay.gaji_bruto) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500">
                                    <span>Biaya Jabatan (5%)</span>
                                    <span>- {{ formatRp(pay.biaya_jabatan) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500">
                                    <span>Gaji Netto Pajak</span>
                                    <span>{{ formatRp(pay.gaji_netto_pajak) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500">
                                    <span>Gaji Setahun</span>
                                    <span>{{ formatRp(pay.gaji_setahun) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500">
                                    <span>PTKP ({{ emp.tax_status }})</span>
                                    <span>- {{ formatRp(pay.ptkp) }}</span>
                                </div>
                                <div class="flex justify-between font-bold text-slate-700 border-t border-slate-200 pt-1.5 mt-1">
                                    <span>PKP</span>
                                    <span>{{ formatRp(pay.pkp) }}</span>
                                </div>
                                <div class="flex justify-between font-bold text-red-600">
                                    <span>PPh 21 / Bulan</span>
                                    <span>{{ formatRp(pph21) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Gaji Diterima -->
                <div class="px-10 pb-8">
                    <div class="bg-gradient-to-r from-slate-900 to-indigo-900 rounded-3xl p-6 flex items-center justify-between">
                        <div>
                            <div class="text-[9px] uppercase font-black text-slate-400 tracking-[0.3em]">Total Gaji Diterima</div>
                            <div v-if="kasBon > 0" class="text-xs text-slate-500 mt-0.5">
                                Setelah dipotong Kas/Bon {{ formatRp(kasBon) }}
                            </div>
                        </div>
                        <div class="text-3xl font-black text-emerald-400 italic">
                            {{ formatRp(totalDiterima) }}
                        </div>
                    </div>
                </div>

                <!-- Signature -->
                <div class="px-10 pb-10 grid grid-cols-3 gap-8 text-center text-xs text-slate-500">
                    <div>
                        <div class="font-black uppercase tracking-wider mb-12">Dibuat Oleh</div>
                        <div class="border-t border-slate-300 pt-2">(____________________)</div>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-wider mb-12">Disetujui Oleh</div>
                        <div class="border-t border-slate-300 pt-2">(____________________)</div>
                    </div>
                    <div>
                        <div class="font-black uppercase tracking-wider mb-12">Diterima</div>
                        <div class="border-t border-slate-300 pt-2">{{ emp.name }}</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white; }
    #slip-card { box-shadow: none; border-radius: 0; }
    .bg-slate-900 { background-color: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-gradient-to-r { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
