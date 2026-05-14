<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ 
    employees: Object 
});

// --- UI STATE ---
const confirmingEmployeeAddition = ref(false);
const employeeBeingDeleted = ref(null);
const isEditing = ref(false);
const editingId = ref(null);
const managingAccount = ref(false);
const accountEmp = ref(null);

// --- FORM DATA ---
const form = useForm({
    nik: '',
    name: '',
    position: '',
    payroll_category: 'Staff',
    tax_status: 'TK',
    base_salary: '',
    daily_rate: '',
    masa_kerja: '',
});

const accountForm = useForm({
    password: '',
});

// --- GROUPING LOGIC ---
const categoryOrder = ['Direksi', 'General', 'Staff', 'Produksi 1', 'Produksi 2'];

const groupedEmployees = computed(() => {
    return props.employees.data.reduce((acc, emp) => {
        const cat = emp.payroll_category || 'Uncategorized';
        if (!acc[cat]) acc[cat] = [];
        acc[cat].push(emp);
        return acc;
    }, {});
});

// --- ACTIONS ---
const startAddingEmployee = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    confirmingEmployeeAddition.value = true;
};

const editEmployee = (emp) => {
    isEditing.value = true;
    editingId.value = emp.id;
    
    form.nik = emp.nik;
    form.name = emp.name;
    form.position = emp.position;
    form.payroll_category = emp.payroll_category;
    form.tax_status = emp.tax_status;
    form.base_salary = emp.base_salary;
    form.daily_rate = emp.daily_rate || '';
    form.masa_kerja = emp.masa_kerja || '';
    
    confirmingEmployeeAddition.value = true;
};

const saveEmployee = () => {
    if (isEditing.value) {
        // Mode Update
        form.put(`/employees/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                confirmingEmployeeAddition.value = false;
                form.reset();
            },
        });
    } else {
        // Mode Simpan Baru
        form.post(route('employees.store'), {
            preserveScroll: true,
            onSuccess: () => {
                confirmingEmployeeAddition.value = false;
                form.reset();
            },
        });
    }
};

const confirmEmployeeDeletion = (id) => {
    employeeBeingDeleted.value = id;
};

const deleteEmployee = () => {
    router.delete(route('employees.destroy', employeeBeingDeleted.value), {
        preserveScroll: true,
        onSuccess: () => employeeBeingDeleted.value = null,
    });
};

const openAccountModal = (emp) => {
    accountEmp.value = emp;
    accountForm.password = '';
    managingAccount.value = true;
};

const saveAccount = () => {
    accountForm.put(`/employees/${accountEmp.value.id}/account`, {
        preserveScroll: true,
        onSuccess: () => {
            managingAccount.value = false;
            accountForm.reset();
        },
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
    <AppLayout title="Personnel Database">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase">Master Data Karyawan</h2>
                <PrimaryButton @click="startAddingEmployee" class="bg-indigo-600 hover:bg-indigo-700 rounded-2xl px-6 py-3 shadow-xl shadow-indigo-100 border-none transition-all hover:scale-105">
                    <span class="mr-2 text-xl">+</span> Registrasi Personel
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white/70 backdrop-blur-2xl border border-white shadow-2xl rounded-[3rem] overflow-hidden ring-1 ring-slate-200/50">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800 text-white text-[10px] uppercase tracking-[0.2em] font-black">
                                <th class="p-6 text-center w-20">No</th>
                                <th class="p-6">Identitas / NIK</th>
                                <th class="p-6">Posisi & Jabatan</th>
                                <th class="p-6 text-right">Gaji Pokok (Base)</th>
                                <th class="p-6 text-center">Aksi</th>
                            </tr>
                        </thead>

                        <template v-for="cat in categoryOrder" :key="cat">
                            <tbody v-if="groupedEmployees[cat]" class="border-t-8 border-slate-100">
                                <tr class="bg-slate-50/50">
                                    <td colspan="5" class="p-5 px-10">
                                        <div class="flex items-center gap-3">
                                            <div :class="{
                                                'bg-slate-900': cat === 'Direksi',
                                                'bg-indigo-500': cat === 'Staff',
                                                'bg-emerald-500': cat === 'General',
                                                'bg-orange-500': cat === 'Produksi 1',
                                                'bg-amber-500': cat === 'Produksi 2',
                                            }" class="w-3 h-3 rounded-full shadow-sm"></div>
                                            <span class="font-black text-slate-800 text-sm uppercase tracking-widest">Divisi {{ cat }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1 rounded-full shadow-inner ring-1 ring-slate-100">
                                                {{ groupedEmployees[cat].length }} Personel
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-for="(emp, index) in groupedEmployees[cat]" :key="emp.id" class="hover:bg-white transition-all group border-b border-slate-50">
                                    <td class="p-6 text-center text-slate-300 font-bold text-xs">{{ index + 1 }}</td>
                                    <td class="p-6">
                                        <div class="font-black text-slate-800 uppercase leading-none mb-1 group-hover:text-indigo-600 transition-colors">{{ emp.name }}</div>
                                        <div class="text-[10px] font-mono text-slate-400 tracking-widest uppercase italic">NIK: {{ emp.nik }}</div>
                                    </td>
                                    <td class="p-6">
                                        <div class="text-sm font-bold text-slate-600 tracking-tight">{{ emp.position }}</div>
                                        <div class="text-[9px] text-indigo-400 uppercase font-black tracking-tighter">Status Pajak: {{ emp.tax_status }}</div>
                                    </td>
                                    <td class="p-6 text-right font-black text-slate-900 text-sm italic">
                                        {{ formatRp(emp.base_salary) }}
                                    </td>
                                    <td class="p-6">
                                        <div class="flex justify-center gap-3">
                                            <button @click="openAccountModal(emp)" class="p-2.5 bg-emerald-50 text-emerald-500 rounded-2xl hover:bg-emerald-600 hover:text-white transition-all duration-300 shadow-sm ring-1 ring-emerald-100" title="Akun User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            </button>
                                            <button @click="editEmployee(emp)" class="p-2.5 bg-indigo-50 text-indigo-500 rounded-2xl hover:bg-indigo-600 hover:text-white transition-all duration-300 shadow-sm ring-1 ring-indigo-100" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="confirmEmployeeDeletion(emp.id)" class="p-2.5 bg-red-50 text-red-400 rounded-2xl hover:bg-red-600 hover:text-white transition-all duration-300 shadow-sm ring-1 ring-red-100" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>

                        <tbody v-if="employees.data.length === 0">
                            <tr>
                                <td colspan="5" class="p-40 text-center">
                                    <div class="text-slate-200 font-black uppercase tracking-[1em] text-3xl opacity-50 italic">Database Empty</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="employees.last_page > 1" class="mt-8 flex justify-center">
                        <div class="flex items-center gap-2">
                            <button
                                v-if="employees.prev_page_url"
                                @click="router.get(employees.prev_page_url)"
                                class="px-4 py-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600 font-bold"
                            >
                                Previous
                            </button>

                            <span
                                v-for="link in employees.links"
                                :key="link.label"
                                @click="link.url ? router.get(link.url) : null"
                                :class="[
                                    'px-4 py-2 rounded-xl font-bold cursor-pointer',
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : link.url
                                            ? 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-600'
                                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                ]"
                            >
                                <span v-html="link.label"></span>
                            </span>

                            <button
                                v-if="employees.next_page_url"
                                @click="router.get(employees.next_page_url)"
                                class="px-4 py-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600 font-bold"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal :show="confirmingEmployeeAddition" @close="confirmingEmployeeAddition = false">
            <template #title> 
                <div class="text-2xl font-black text-slate-800 uppercase tracking-tighter">
                    {{ isEditing ? 'Update Personnel Profile' : 'New Personnel Registration' }} 
                </div>
            </template>
            
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div class="col-span-1">
                        <InputLabel for="nik" value="NIK (Nomor Induk Karyawan)" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="nik" v-model="form.nik" type="text" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="E.g. EMP001" />
                        <InputError :message="form.errors.nik" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="name" value="Nama Lengkap" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="Sesuai Kartu Identitas" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="position" value="Jabatan Pekerjaan" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="position" v-model="form.position" type="text" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="E.g. Senior Supervisor" />
                        <InputError :message="form.errors.position" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="payroll_category" value="Divisi Kategori Payroll" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <select v-model="form.payroll_category" class="mt-2 block w-full border-none bg-slate-50 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 text-sm font-black uppercase tracking-tight shadow-inner">
                            <option value="Staff">Staff & Operasional</option>
                            <option value="Direksi">Direksi (Executive)</option>
                            <option value="General">General (Umum)</option>
                            <option value="Produksi 1">Produksi 1 (Line A)</option>
                            <option value="Produksi 2">Produksi 2 (Line B)</option>
                        </select>
                        <InputError :message="form.errors.payroll_category" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="base_salary" value="Gaji Pokok (Nominal)" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="base_salary" v-model="form.base_salary" type="number" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="Rp 0" />
                        <InputError :message="form.errors.base_salary" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="tax_status" value="Status PTKP (Pajak)" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <select v-model="form.tax_status" class="mt-2 block w-full border-none bg-slate-50 rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 text-sm font-black shadow-inner">
                            <option value="TK">TK (Tidak Kawin)</option>
                            <option value="K">K (Kawin, 0 Anak)</option>
                            <option value="K1">K1 (Kawin, 1 Anak)</option>
                            <option value="K2">K2 (Kawin, 2 Anak)</option>
                            <option value="K3">K3 (Kawin, 3 Anak)</option>
                        </select>
                        <InputError :message="form.errors.tax_status" class="mt-2" />
                    </div>
                    <!-- Daily Rate (untuk Produksi) -->
                    <div v-if="form.payroll_category === 'Produksi 1' || form.payroll_category === 'Produksi 2'" class="col-span-1">
                        <InputLabel for="daily_rate" value="Gaji Harian (Rate)" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="daily_rate" v-model="form.daily_rate" type="number" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="Rp 0" />
                        <div class="text-[9px] text-slate-400 mt-1">Gaji = Daily Rate × Hari Kerja</div>
                        <InputError :message="form.errors.daily_rate" class="mt-2" />
                    </div>
                    <div class="col-span-1">
                        <InputLabel for="masa_kerja" value="Masa Kerja (Bulan)" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                        <TextInput id="masa_kerja" v-model="form.masa_kerja" type="number" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="0" />
                        <InputError :message="form.errors.masa_kerja" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex gap-4 p-4">
                    <SecondaryButton @click="confirmingEmployeeAddition = false" class="rounded-2xl px-8 py-3 border-none bg-slate-100 hover:bg-slate-200"> Batal </SecondaryButton>
                    <PrimaryButton class="rounded-2xl px-12 py-3 bg-indigo-600 shadow-xl shadow-indigo-100" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveEmployee"> 
                        {{ isEditing ? 'Update Records' : 'Save Personnel' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <DialogModal :show="employeeBeingDeleted !== null" @close="employeeBeingDeleted = null">
            <template #title>
                <div class="text-2xl font-black text-red-600 uppercase tracking-tighter">Hapus Personel</div>
            </template>
            <template #content>
                <div class="text-slate-600 font-bold">Apakah anda yakin ingin menghapus data karyawan ini? Tindakan ini tidak dapat dibatalkan.</div>
            </template>
            <template #footer>
                <SecondaryButton @click="employeeBeingDeleted = null" class="rounded-2xl px-6 py-3 border-none bg-slate-100 hover:bg-slate-200">Batal</SecondaryButton>
                <DangerButton @click="deleteEmployee" class="ml-3 rounded-2xl px-6 py-3 shadow-lg shadow-red-200" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Hapus
                </DangerButton>
            </template>
        </DialogModal>

        <!-- Modal Akun User -->
        <DialogModal :show="managingAccount" @close="managingAccount = false">
            <template #title>
                <div class="text-2xl font-black text-slate-800 uppercase tracking-tighter">
                    Kelola Akun Personel
                </div>
            </template>
            <template #content>
                <div v-if="accountEmp" class="space-y-6 mt-4">
                    <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mb-1">Nama Karyawan</div>
                                <div class="font-bold text-slate-800">{{ accountEmp.name }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mb-1">Status Akun</div>
                                <div v-if="accountEmp.user" class="inline-flex items-center gap-2 text-emerald-600 font-bold text-sm bg-emerald-100 px-3 py-1 rounded-full">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Aktif
                                </div>
                                <div v-else class="inline-flex items-center gap-2 text-red-600 font-bold text-sm bg-red-100 px-3 py-1 rounded-full">
                                    <div class="w-2 h-2 rounded-full bg-red-500"></div> Belum Ada Akun
                                </div>
                            </div>
                            <div class="col-span-2 mt-2" v-if="accountEmp.user">
                                <div class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mb-1">Username / Email</div>
                                <div class="font-mono text-slate-700 bg-white px-4 py-2 rounded-xl ring-1 ring-slate-200 inline-block">{{ accountEmp.user.email }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="accountEmp.user">
                        <div class="border-t border-slate-100 pt-6">
                            <InputLabel for="new_password" value="Ubah Password" class="font-black text-[10px] uppercase text-slate-400 tracking-widest" />
                            <TextInput id="new_password" v-model="accountForm.password" type="password" class="mt-2 block w-full bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 py-4 shadow-inner" placeholder="Masukkan password baru (minimal 8 karakter)" />
                            <InputError :message="accountForm.errors.password" class="mt-2" />
                            
                            <div class="mt-4 text-xs font-bold text-amber-600 bg-amber-50 p-4 rounded-xl border border-amber-100 flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Demi keamanan, password lama yang sudah di-hash (dienkripsi) tidak dapat ditampilkan. Jika karyawan lupa password, Anda bisa langsung menggantinya di sini.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="managingAccount = false" class="rounded-2xl px-6 py-3 border-none bg-slate-100 hover:bg-slate-200">Tutup</SecondaryButton>
                <PrimaryButton v-if="accountEmp?.user" @click="saveAccount" class="ml-3 bg-emerald-500 hover:bg-emerald-600 rounded-2xl px-6 py-3 shadow-xl shadow-emerald-200 border-none" :class="{ 'opacity-25': accountForm.processing }" :disabled="accountForm.processing">
                    Simpan Password Baru
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<style>
/* Clean up number inputs */
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    -moz-appearance: textfield;
}
</style>