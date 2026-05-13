<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Ambil data dari Controller
defineProps({ employees: Array });

// State untuk UI
const confirmingEmployeeAddition = ref(false);
const employeeBeingDeleted = ref(null);

// Inisialisasi Form Inertia (Best Practice)
const form = useForm({
    nik: '',
    name: '',
    position: '',
    payroll_category: 'Staff',
    tax_status: 'TK',
    base_salary: '',
});

// Logic: Buka Modal Tambah
const startAddingEmployee = () => {
    confirmingEmployeeAddition.value = true;
};

// Logic: Simpan Data
const saveEmployee = () => {
    form.post(route('employees.store'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingEmployeeAddition.value = false;
            form.reset();
        },
    });
};

// Logic: Hapus Data
const confirmEmployeeDeletion = (id) => {
    employeeBeingDeleted.value = id;
};

const deleteEmployee = () => {
    form.delete(route('employees.destroy', employeeBeingDeleted.value), {
        preserveScroll: true,
        onSuccess: () => employeeBeingDeleted.value = null,
    });
};
</script>

<template>
    <AppLayout title="Management Karyawan">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Data Karyawan</h2>
        </template>

        <div class="py-12 bg-gradient-to-br from-indigo-50 via-white to-blue-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6 flex justify-between items-center bg-white/30 backdrop-blur-md p-6 rounded-3xl border border-white/20 shadow-sm">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Daftar Karyawan</h3>
                        <p class="text-sm text-slate-500">Total: {{ employees.length }} Personel</p>
                    </div>
                    <PrimaryButton @click="startAddingEmployee" class="rounded-2xl px-6 py-3 bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 border-none">
                        + Tambah Karyawan
                    </PrimaryButton>
                </div>

                <div class="bg-white/40 backdrop-blur-xl border border-white/30 shadow-2xl rounded-[2.5rem] overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/50 text-slate-500 text-xs uppercase tracking-widest font-bold">
                                <th class="p-6">NIK</th>
                                <th class="p-6">Nama</th>
                                <th class="p-6">Jabatan</th>
                                <th class="p-6">Gaji Pokok</th>
                                <th class="p-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/20">
                            <tr v-for="emp in employees" :key="emp.id" class="hover:bg-white/40 transition-all group">
                                <td class="p-6 text-sm font-mono text-slate-500">{{ emp.nik }}</td>
                                <td class="p-6 font-semibold text-slate-800">{{ emp.name }}</td>
                                <td class="p-6 text-slate-600">{{ emp.position }}</td>
                                <td class="p-6 font-medium text-slate-900">
                                    Rp {{ Number(emp.base_salary).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-6 text-center">
                                    <button @click="confirmEmployeeDeletion(emp.id)" class="text-red-400 hover:text-red-600 transition p-2 hover:bg-red-50 rounded-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="employees.length === 0">
                                <td colspan="5" class="p-20 text-center text-slate-400 italic">Data kosong. Klik "+ Tambah Karyawan" untuk memulai.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <DialogModal :show="confirmingEmployeeAddition" @close="confirmingEmployeeAddition = false">
            <template #title> Registrasi Karyawan Baru </template>
            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="nik" value="NIK" />
                        <TextInput id="nik" v-model="form.nik" type="text" class="mt-1 block w-full bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: 3201..." />
                        <InputError :message="form.errors.nik" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full bg-slate-50 border-none rounded-xl" placeholder="Nama Sesuai KTP" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="position" value="Jabatan" />
                        <TextInput id="position" v-model="form.position" type="text" class="mt-1 block w-full bg-slate-50 border-none rounded-xl" placeholder="Contoh: Marketing" />
                        <InputError :message="form.errors.position" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="payroll_category" value="Kategori Payroll" />
                        <select v-model="form.payroll_category" class="mt-1 block w-full border-none bg-slate-50 rounded-xl focus:ring-2 focus:ring-indigo-500 h-[42px] text-sm">
                            <option value="Staff">Staff & Operasional</option>
                            <option value="Direksi">Direksi</option>
                        </select>
                        <InputError :message="form.errors.payroll_category" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="base_salary" value="Gaji Pokok" />
                        <TextInput id="base_salary" v-model="form.base_salary" type="number" class="mt-1 block w-full bg-slate-50 border-none rounded-xl" placeholder="Masukkan Nominal" />
                        <InputError :message="form.errors.base_salary" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="tax_status" value="Status Pajak (PTKP)" />
                        <select v-model="form.tax_status" class="mt-1 block w-full border-none bg-slate-50 rounded-xl focus:ring-2 focus:ring-indigo-500">
                            <option value="TK">TK (Tidak Kawin)</option>
                            <option value="K1">K1 (Kawin Anak 1)</option>
                            <option value="K2">K2 (Kawin Anak 2)</option>
                            <option value="K3">K3 (Kawin Anak 3)</option>
                        </select>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingEmployeeAddition = false" class="rounded-xl"> Batal </SecondaryButton>
                <PrimaryButton class="ms-3 rounded-xl px-8" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveEmployee"> Simpan </PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="employeeBeingDeleted !== null" @close="employeeBeingDeleted = null">
            <template #title> Hapus Data Karyawan </template>
            <template #content> Apakah Anda yakin ingin menghapus data ini? Semua data payroll yang terkait juga akan hilang. </template>
            <template #footer>
                <SecondaryButton @click="employeeBeingDeleted = null"> Batal </SecondaryButton>
                <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteEmployee"> Hapus Sekarang </DangerButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>