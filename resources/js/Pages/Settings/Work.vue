<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ setting: Object });

const flash = ref(null);

const form = useForm({
    check_in_start:  props.setting.check_in_start?.substring(0, 5) ?? '07:00',
    check_in_end:    props.setting.check_in_end?.substring(0, 5) ?? '09:00',
    check_out_start: props.setting.check_out_start?.substring(0, 5) ?? '16:00',
    work_end:        props.setting.work_end?.substring(0, 5) ?? '17:00',
    ot_multiplier:   props.setting.ot_multiplier ?? 2,
});

const save = () => {
    form.put('/settings/work', {
        preserveScroll: true,
        onSuccess: () => { flash.value = 'Pengaturan jam kerja berhasil disimpan ✓'; },
    });
};
</script>

<template>
    <AppLayout title="Pengaturan Jam Kerja">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase italic">Pengaturan Jam Kerja</h2>
        </template>

        <div class="py-10 bg-[#f8fafc] min-h-screen">
            <div class="max-w-2xl mx-auto px-4 space-y-6">

                <div v-if="flash" class="bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold rounded-2xl px-6 py-4">
                    {{ flash }}
                </div>

                <div class="bg-white/80 backdrop-blur-xl border border-white shadow-2xl rounded-[3rem] p-10">
                    <div class="space-y-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] uppercase font-black tracking-widest text-slate-400 mb-2">Jam Masuk Paling Awal</label>
                                <input type="time" v-model="form.check_in_start" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-4 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-black tracking-widest text-slate-400 mb-2">Batas Tidak Terlambat</label>
                                <input type="time" v-model="form.check_in_end" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-4 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-black tracking-widest text-slate-400 mb-2">Jam Pulang Paling Awal</label>
                                <input type="time" v-model="form.check_out_start" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-4 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-black tracking-widest text-slate-400 mb-2">Jam Kerja Berakhir (mulai OT)</label>
                                <input type="time" v-model="form.work_end" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-4 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase font-black tracking-widest text-slate-400 mb-2">Kelipatan Upah Lembur</label>
                            <select v-model="form.ot_multiplier" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-4 font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                <option :value="1">1x (Sama dengan gaji normal)</option>
                                <option :value="2">2x (Dua kali lipat)</option>
                                <option :value="3">3x (Tiga kali lipat)</option>
                            </select>
                            <p class="text-xs text-slate-400 mt-2">Formula OT: (Gaji Pokok / 173) × kelipatan × jam lembur</p>
                        </div>

                        <div class="bg-indigo-50 rounded-2xl p-5 text-xs text-indigo-700 space-y-1">
                            <div class="font-black uppercase tracking-wider text-indigo-400 mb-3">Preview Jam Kerja</div>
                            <div>🟢 Masuk: <strong>{{ form.check_in_start }}</strong> – <strong>{{ form.check_in_end }}</strong></div>
                            <div>🔴 Terlambat jika masuk setelah: <strong>{{ form.check_in_end }}</strong></div>
                            <div>🏠 Pulang normal: <strong>{{ form.work_end }}</strong></div>
                            <div>⏱ Lembur dihitung setelah: <strong>{{ form.work_end }}</strong> ({{ form.ot_multiplier }}x)</div>
                        </div>

                        <button @click="save" :disabled="form.processing" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest rounded-3xl py-5 shadow-xl shadow-indigo-100 transition-all hover:scale-[1.01] disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
