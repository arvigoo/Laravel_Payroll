<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
</script>
<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ringkasan Payroll
            </h2>
        </template>

        <div class="py-12 bg-gradient-to-br from-indigo-100 via-white to-purple-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white/40 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-xl">
                        <div class="text-sm font-medium text-indigo-600 mb-1">Total Karyawan</div>
                        <div class="text-3xl font-bold text-gray-900">0</div>
                        <div class="mt-4 text-xs text-gray-500 italic">*Data belum di-import</div>
                    </div>

                    <div class="bg-white/40 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-xl">
                        <div class="text-sm font-medium text-purple-600 mb-1">Pengeluaran Gaji (Bulan Ini)</div>
                        <div class="text-3xl font-bold text-gray-900">Rp 0</div>
                        <div class="mt-4 flex items-center text-xs text-green-600">
                             <span>Periode: November 2025</span>
                        </div>
                    </div>

                    <div class="bg-white/40 backdrop-blur-xl border border-white/20 p-6 rounded-3xl shadow-xl">
                        <div class="text-sm font-medium text-pink-600 mb-1">Status PPh 21</div>
                        <div class="text-3xl font-bold text-gray-900">Ready</div>
                        <div class="mt-4 text-xs text-gray-500 italic">Sesuai regulasi terbaru</div>
                    </div>

                </div>

            </div>
        </div>
    </AppLayout>
</template>