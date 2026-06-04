<script setup>
/*
|--------------------------------------------------------------------------
| IMPORT
|--------------------------------------------------------------------------
*/
import { computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

/*
|--------------------------------------------------------------------------
| AMBIL DATA DARI INERTIA
|--------------------------------------------------------------------------
*/
const rankingsData = computed(() => {
    const props = usePage().props
    
    // Skenario 1: Ambil langsung dari props root Inertia
    if (props.rankings) {
        return props.rankings
    }
    // Skenario 2: Atribut cadangan jika di-share via middleware attrs
    if (props.attrs?.rankings) {
        return props.attrs.rankings
    }
    return []
})

onMounted(() => {
    // Intip log ini di F12 untuk memastikan data dari database masuk dengan selamat
    console.log("=== DEBUGGING DATA SUPABASE ===");
    console.log("Raw Props dari Backend:", usePage().props);
    console.log("Data Terbaca di Tabel:", rankingsData.value);
})

// Menentukan link navigasi yang sedang aktif
const isActive = (page) => {
    const currentUrl = usePage().url
    if (page === 'dashboard') return currentUrl === '/dashboard'
    if (page === 'process') return currentUrl === '/process'
    if (page === 'result') return currentUrl === '/result'
    return false
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS (SINKRONISASI SKOR DESIMAL DB)
|--------------------------------------------------------------------------
*/
const getScoreClass = (score) => {
    // Pengecekan warna baris dan badge berdasarkan nilai desimal 0 s.d 1 dari DB
    if (score >= 0.75) return 'score-high'
    if (score >= 0.6) return 'score-medium'
    if (score >= 0.4) return 'score-low'
    return 'score-very-low'
}

const formatScore = (score) => {
    // Mengonversi nilai desimal database (misal 0.845) menjadi persen (84.50%)
    return (parseFloat(score) * 100).toFixed(2) + '%'
}
</script>

<template>
    <div class="process-container">
        <!-- HEADER / NAVIGATION BAR -->
        <div class="process-header">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="currentColor"/>
                        <circle cx="12" cy="9" r="3" fill="white"/>
                    </svg>
                </div>
                <h1>SIG Bengkel Medan Baru</h1>
            </div>

            <div class="nav-menu">
                <Link href="/dashboard" :class="{ active: isActive('dashboard') }" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H4a2 2 0 0 1-2-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </Link>

                <Link href="/process" :class="{ active: isActive('process') }" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <span>Proses SAW</span>
                </Link>

                <Link href="/result" :class="{ active: isActive('result') }" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 6l-9.5 9.5-5-5L1 18"/>
                        <path d="M17 6h6v6"/>
                    </svg>
                    <span>Hasil Ranking</span>
                </Link>
            </div>

            <div class="user-info">
                <div class="avatar">
                    <span>👤</span>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="process-main">
            <div class="content-wrapper">
                
                <!-- SUMMARY PANEL -->
                <div class="info-panel">
                    <div class="info-card">
                        <div class="info-icon">📊</div>
                        <div class="info-content">
                            <h4>Metode Analisis</h4>
                            <p>SAW (Direct DB Engine)</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-icon">🏪</div>
                        <div class="info-content">
                            <h4>Total Rekomendasi Lokasi</h4>
                            <p>{{ rankingsData.length }} Alternatif</p>
                        </div>
                    </div>
                </div>

                <!-- RANKING TABLE CARD -->
                <div class="data-card ranking-card">
                    <div class="card-header">
                        <div class="header-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </div>
                        <h2>Rekomendasi Hasil Akhir Lokasi Strategis</h2>
                        <span class="badge ranking">Urutan Terbaik</span>
                    </div>

                    <div class="table-wrapper">
                        <table class="modern-table ranking-table">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Rank</th>
                                    <th>Nama Tempat / Plan</th>
                                    <th>Alamat Lokasi</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 140px;">Jam Operasional</th>
                                    <th style="width: 160px;">Skor Kelayakan</th>
                                    <th style="width: 200px;">Keterangan Kelayakan</th>
                                    <th style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="(item, index) in rankingsData" 
                                    :key="index"
                                    :class="['ranking-row', getScoreClass(item.score)]"
                                >
                                    <!-- BADGE PERINGKAT (1, 2, 3 mendapat warna spesial) -->
                                    <td class="text-center">
                                        <div class="rank-badge" :class="{
                                            'rank-1': index === 0,
                                            'rank-2': index === 1,
                                            'rank-3': index === 2
                                        }">
                                            {{ index + 1 }}
                                        </div>
                                    </td>

                                    <!-- NAMA BENGKEL -->
                                    <td class="font-medium" style="color: #1e293b;">
                                        {{ item.nama_bengkel }}
                                    </td>

                                    <!-- ALAMAT JALAN -->
                                    <td style="max-width: 320px; font-size: 13px; color: #475569; line-height: 1.4;">
                                        {{ item.jalan }}
                                    </td>

                                    <!-- STATUS (RESMI / UMUM) -->
                                    <td class="text-center">
                                        <span :class="['tipe-badge', item.is_resmi ? 'benefit' : 'neutral-badge']">
                                            {{ item.is_resmi ? 'Resmi' : 'Umum' }}
                                        </span>
                                    </td>

                                    <!-- JAM OPERASIONAL -->
                                    <td class="text-center" style="font-size: 13px; font-weight: 500;">
                                        <span v-if="item.waktu_buka && item.waktu_tutup">
                                            {{ item.waktu_buka.substring(0,5) }} - {{ item.waktu_tutup.substring(0,5) }}
                                        </span>
                                        <span v-else style="color: #94a3b8; font-style: italic;">Kondisional</span>
                                    </td>

                                    <!-- PROGRESS BAR SKOR -->
                                    <td class="text-center">
                                        <div class="score-container">
                                            <span class="score-value">{{ formatScore(item.score) }}</span>
                                            <div class="score-bar">
                                                <div class="score-fill" :style="{ width: (item.score * 100) + '%' }"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- STATUS LABELLING -->
                                    <td>
                                        <span :class="['status-badge', getScoreClass(item.score)]">
                                            {{ item.score >= 0.75 ? 'Sangat Direkomendasikan' : 
                                               item.score >= 0.6 ? 'Direkomendasikan' : 
                                               item.score >= 0.4 ? 'Cukup Layak' : 'Kurang Direkomendasikan' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <button class="btn-detail">Detail</button>
                                    </td>
                                </tr>

                                <!-- DATA KOSONG FALLBACK -->
                                <tr v-if="rankingsData.length === 0">
                                    <td colspan="8" class="text-center" style="padding: 40px; color: #64748b; font-weight: 500;">
                                        Tidak ada data rekomendasi bengkel yang ditemukan dari database.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.process-container {
    width: 100%;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.process-header {
    height: 70px;
    background: rgba(255, 255, 255, 0.98);
    color: #1e293b;
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.process-header h1 {
    font-size: 20px;
    font-weight: 600;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-menu {
    display: flex;
    gap: 8px;
    background: #f8fafc;
    padding: 4px;
    border-radius: 12px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    text-decoration: none;
    color: #475569;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-link:hover, .nav-link.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.user-info {
    display: flex;
    align-items: center;
}

.avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.avatar:hover { transform: scale(1.05); }
.process-main { padding: 30px; min-height: calc(100vh - 70px); }
.content-wrapper { max-width: 1400px; margin: 0 auto; }

.info-panel {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    color: #1e293b;
    padding: 20px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.info-content h4 { color: #1e293b; margin-bottom: 5px; font-size: 14px; }
.info-content p { color: #667eea; font-size: 20px; font-weight: 700; }

.data-card {
    background: white;
    color: #1e293b;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f5f9;
}

.header-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.card-header h2 { font-size: 18px; color: #1e293b; margin: 0; flex: 1; }

.badge {
    padding: 4px 12px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge.ranking { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
.table-wrapper { overflow-x: auto; }
.modern-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.modern-table thead tr { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); }
.modern-table th { padding: 12px; color: white; font-weight: 600; text-align: center; }
.modern-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; vertical-align: middle; }

.text-center { text-align: center; }
.font-medium { font-weight: 500; }

.ranking-row.score-high { background-color: #f0fdf4; }
.ranking-row.score-medium { background-color: #eff6ff; }
.ranking-row.score-low { background-color: #fefce8; }
.ranking-row.score-very-low { background-color: #fef2f2; }

.rank-badge {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 700;
    background: #cbd5e1;
    color: #475569;
}

.rank-badge.rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
.rank-badge.rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); color: white; }
.rank-badge.rank-3 { background: linear-gradient(135deg, #fb923c, #f97316); color: white; }

.score-container { min-width: 120px; }
.score-value { font-weight: 700; color: #1e293b; display: block; margin-bottom: 5px; }
.score-bar { width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
.score-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    transition: width 0.5s ease;
}

.tipe-badge { display: inline-block; padding: 4px 12px; border-radius: 8px; font-weight: 600; font-size: 12px; }
.tipe-badge.benefit { background: #dcfce7; color: #166534; }
.neutral-badge { background: #f1f5f9; color: #475569; }

.status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.status-badge.score-high { background: #dcfce7; color: #166534; }
.status-badge.score-medium { background: #dbeafe; color: #1e40af; }
.status-badge.score-low { background: #fef3c7; color: #92400e; }
.status-badge.score-very-low { background: #fee2e2; color: #991b1b; }

.btn-detail {
    padding: 6px 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 768px) {
    .nav-link span { display: none; }
    .process-main { padding: 20px; }
    .info-panel { grid-template-columns: 1fr; }
}
</style>