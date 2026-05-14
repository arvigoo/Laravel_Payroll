<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    employee: Object,
    todayRecord: Object,
    setting: Object,
    today: String,
    history: Array,
});

// Camera state
const videoRef    = ref(null);
const canvasRef   = ref(null);
const cameraOn    = ref(false);
const capturedImg = ref(null);
const mode        = ref(''); // 'checkin' | 'checkout'
const flashMsg    = ref(null);

const form = useForm({ photo: '' });

const alreadyCheckedIn  = computed(() => !!props.todayRecord?.check_in);
const alreadyCheckedOut = computed(() => !!props.todayRecord?.check_out);

const now = ref(new Date());
setInterval(() => now.value = new Date(), 1000);

const timeStr = computed(() =>
    now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
);
const dateStr = computed(() =>
    now.value.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

const openCamera = async (m) => {
    mode.value       = m;
    capturedImg.value = null;
    cameraOn.value   = true;

    await new Promise(r => setTimeout(r, 100));
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        videoRef.value.srcObject = stream;
    } catch (e) {
        flashMsg.value = { type: 'error', text: 'Tidak bisa mengakses kamera: ' + e.message };
        cameraOn.value = false;
    }
};

const capture = () => {
    const canvas = canvasRef.value;
    const video  = videoRef.value;

    if (!video.videoWidth || !video.videoHeight) {
        alert('Kamera belum siap sepenuhnya, tunggu sebentar lalu coba lagi.');
        return;
    }

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    capturedImg.value = canvas.toDataURL('image/jpeg', 0.85);

    // Stop stream
    video.srcObject?.getTracks().forEach(t => t.stop());
};

const retake = async () => {
    capturedImg.value = null;
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    videoRef.value.srcObject = stream;
};

const submit = () => {
    if (!capturedImg.value) return;
    form.photo = capturedImg.value;

    const url = mode.value === 'checkin' ? '/absen/check-in' : '/absen/check-out';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            cameraOn.value    = false;
            capturedImg.value = null;
            router.reload({ only: ['todayRecord', 'history'] });
        },
        onError: (err) => {
            flashMsg.value = { type: 'error', text: Object.values(err).join(', ') };
        },
    });
};

const closeCamera = () => {
    videoRef.value?.srcObject?.getTracks().forEach(t => t.stop());
    cameraOn.value    = false;
    capturedImg.value = null;
};

const formatTime = (t) => t ? t.substring(0, 5) : '--:--';

const statusColor = (s) => ({
    present: 'bg-emerald-100 text-emerald-700',
    late:    'bg-amber-100 text-amber-700',
    absent:  'bg-red-100 text-red-700',
    holiday: 'bg-blue-100 text-blue-700',
}[s] || 'bg-slate-100 text-slate-500');

const statusLabel = (s) => ({ present: 'Hadir', late: 'Terlambat', absent: 'Absen', holiday: 'Libur' }[s] || s);
</script>

<template>
    <AppLayout title="Absensi">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase italic">Portal Absensi</h2>
                    <p class="text-sm text-slate-400 mt-1">{{ employee?.name ?? 'Karyawan' }}</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black text-slate-800 tabular-nums">{{ timeStr }}</div>
                    <div class="text-xs text-slate-400 capitalize">{{ dateStr }}</div>
                </div>
            </div>
        </template>

        <div class="py-10 bg-[#f8fafc] min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- No Employee Warning -->
                <div v-if="!employee" class="bg-amber-50 border border-amber-200 rounded-3xl p-8 text-center">
                    <div class="text-5xl mb-4">⚠️</div>
                    <div class="font-black text-amber-800 text-xl">Akun Anda belum terhubung ke data karyawan.</div>
                    <p class="text-amber-600 mt-2">Hubungi Admin untuk menghubungkan akun Anda.</p>
                </div>

                <!-- Flash Message -->
                <div v-if="flashMsg" :class="flashMsg.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700'" class="border rounded-2xl px-6 py-4 font-bold flex items-center gap-3">
                    <span class="text-xl">{{ flashMsg.type === 'error' ? '✕' : '✓' }}</span>
                    {{ flashMsg.text }}
                </div>

                <!-- Today Status Card -->
                <div v-if="employee" class="bg-white/80 backdrop-blur-xl border border-white shadow-2xl rounded-[3rem] p-8">
                    <div class="text-[10px] uppercase font-black tracking-[0.3em] text-slate-400 mb-6">Status Absensi Hari Ini</div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <!-- Check In -->
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white">
                            <div class="text-[10px] opacity-60 uppercase tracking-widest font-black mb-2">Jam Masuk</div>
                            <div class="text-4xl font-black mb-3">
                                {{ todayRecord?.check_in ? formatTime(todayRecord.check_in) : '--:--' }}
                            </div>
                            <div v-if="todayRecord?.status" :class="statusColor(todayRecord.status)" class="inline-block text-xs font-black px-3 py-1 rounded-full">
                                {{ statusLabel(todayRecord.status) }}
                            </div>
                            <!-- Foto Check-in -->
                            <div v-if="todayRecord?.check_in_photo_url" class="mt-4">
                                <img :src="todayRecord.check_in_photo_url" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/30" />
                            </div>
                        </div>

                        <!-- Check Out -->
                        <div class="bg-gradient-to-br from-slate-700 to-slate-900 rounded-3xl p-6 text-white">
                            <div class="text-[10px] opacity-60 uppercase tracking-widest font-black mb-2">Jam Keluar</div>
                            <div class="text-4xl font-black mb-3">
                                {{ todayRecord?.check_out ? formatTime(todayRecord.check_out) : '--:--' }}
                            </div>
                            <div v-if="todayRecord?.ot_hours > 0" class="inline-block text-xs font-black px-3 py-1 rounded-full bg-amber-400/20 text-amber-300">
                                Lembur {{ todayRecord.ot_hours }}h
                            </div>
                            <!-- Foto Check-out -->
                            <div v-if="todayRecord?.check_out_photo_url" class="mt-4">
                                <img :src="todayRecord.check_out_photo_url" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/30" />
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button
                            @click="openCamera('checkin')"
                            :disabled="alreadyCheckedIn"
                            class="flex-1 py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all duration-300 flex items-center justify-center gap-3"
                            :class="alreadyCheckedIn
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                : 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-xl shadow-indigo-200 hover:scale-[1.02]'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            {{ alreadyCheckedIn ? '✓ Sudah Check-In' : 'Check In' }}
                        </button>
                        <button
                            @click="openCamera('checkout')"
                            :disabled="!alreadyCheckedIn || alreadyCheckedOut"
                            class="flex-1 py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all duration-300 flex items-center justify-center gap-3"
                            :class="(!alreadyCheckedIn || alreadyCheckedOut)
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                : 'bg-gradient-to-r from-slate-700 to-slate-900 text-white shadow-xl shadow-slate-300 hover:scale-[1.02]'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            {{ alreadyCheckedOut ? '✓ Sudah Check-Out' : 'Check Out' }}
                        </button>
                    </div>

                    <!-- Work Hours Info -->
                    <div class="mt-6 grid grid-cols-2 gap-4 text-xs text-slate-500 bg-slate-50 rounded-2xl p-4">
                        <div>
                            <span class="font-black text-slate-400 uppercase tracking-wider">Masuk Standar</span>
                            <div class="font-bold text-slate-700 mt-1">{{ setting?.check_in_start?.substring(0,5) }} – {{ setting?.check_in_end?.substring(0,5) }}</div>
                        </div>
                        <div>
                            <span class="font-black text-slate-400 uppercase tracking-wider">Jam Kerja Berakhir</span>
                            <div class="font-bold text-slate-700 mt-1">{{ setting?.work_end?.substring(0,5) }} (OT setelah ini)</div>
                        </div>
                    </div>
                </div>

                <!-- History -->
                <div v-if="employee && history.length > 0" class="bg-white/80 backdrop-blur-xl border border-white shadow-xl rounded-[3rem] p-8">
                    <div class="text-[10px] uppercase font-black tracking-[0.3em] text-slate-400 mb-6">Riwayat 7 Hari Terakhir</div>
                    <div class="space-y-3">
                        <div v-for="rec in history" :key="rec.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                            <div>
                                <div class="font-black text-slate-700 text-sm">{{ new Date(rec.date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) }}</div>
                                <div class="text-xs text-slate-400 mt-1">{{ formatTime(rec.check_in) }} → {{ formatTime(rec.check_out) }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="rec.ot_hours > 0" class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">OT {{ rec.ot_hours }}h</span>
                                <span :class="statusColor(rec.status)" class="text-xs font-black px-3 py-1 rounded-full">{{ statusLabel(rec.status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Camera Modal -->
        <div v-if="cameraOn" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
                    <div>
                        <div class="font-black text-white uppercase tracking-widest text-sm">
                            {{ mode === 'checkin' ? '📷 Foto Check-In' : '📷 Foto Check-Out' }}
                        </div>
                        <div class="text-slate-400 text-xs mt-1">Arahkan wajah ke kamera lalu klik Ambil Foto</div>
                    </div>
                    <button @click="closeCamera" class="text-slate-500 hover:text-white text-xl font-black">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Video / Preview -->
                    <div class="rounded-3xl overflow-hidden bg-slate-900 aspect-video relative">
                        <video v-show="!capturedImg" ref="videoRef" autoplay playsinline class="w-full h-full object-cover" />
                        <img v-if="capturedImg" :src="capturedImg" class="w-full h-full object-cover" />
                        <canvas ref="canvasRef" class="hidden" />
                        <!-- Guide overlay -->
                        <div v-if="!capturedImg" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-52 h-52 border-4 border-white/40 rounded-full"></div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3">
                        <button v-if="!capturedImg" @click="capture" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl py-4 uppercase tracking-widest text-sm transition-all hover:scale-[1.02] shadow-lg shadow-indigo-200">
                            📸 Ambil Foto
                        </button>
                        <template v-else>
                            <button @click="retake" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black rounded-2xl py-4 uppercase tracking-widest text-sm">
                                Ulangi
                            </button>
                            <button @click="submit" :disabled="form.processing" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-black rounded-2xl py-4 uppercase tracking-widest text-sm transition-all shadow-lg shadow-emerald-200 disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : (mode === 'checkin' ? '✓ Absen Masuk' : '✓ Absen Keluar') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
