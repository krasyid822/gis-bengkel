<script setup>

/*
|--------------------------------------------------------------------------
| IMPORT
|--------------------------------------------------------------------------
*/

import { ref, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'

/*
|--------------------------------------------------------------------------
| NAVIGATION ACTIVE
|--------------------------------------------------------------------------
*/

const isActive = (page) => {

    const currentUrl = usePage().url

    if (page === 'dashboard') {
        return currentUrl === '/dashboard'
    }

    if (page === 'process') {
        return currentUrl === '/process'
    }

    if (page === 'result') {
        return currentUrl === '/result'
    }

    return false
}

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

const rankingData = ref([])
const loading = ref(false)
const error = ref(null)

/*
|--------------------------------------------------------------------------
| FETCH RANKING DATA FROM SUPABASE
|--------------------------------------------------------------------------
*/

const fetchRankingData = async () => {
    loading.value = true
    error.value = null
    
    try {
        const response = await axios.get('/api/ranking-locations')
        rankingData.value = response.data
        console.log('Ranking Data:', rankingData.value)
    } catch (err) {
        console.error('Gagal fetch ranking data:', err)
        error.value = 'Gagal memuat data ranking'
    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

const getMedalColor = (index) => {
    if (index === 0) return 'gold'
    if (index === 1) return 'silver'
    if (index === 2) return 'bronze'
    return 'default'
}

const getMedalIcon = (index) => {
    if (index === 0) return '🥇'
    if (index === 1) return '🥈'
    if (index === 2) return '🥉'
    return '📌'
}

const formatScore = (score) => {
    if (typeof score === 'number') {
        return (score * 100).toFixed(2) + '%'
    }
    return score
}

const getScoreColor = (score) => {
    const numScore = typeof score === 'number' ? score : parseFloat(score)
    if (numScore >= 0.8) return '#10b981'
    if (numScore >= 0.6) return '#3b82f6'
    if (numScore >= 0.4) return '#f59e0b'
    return '#ef4444'
}

/*
|--------------------------------------------------------------------------
| MOUNTED
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchRankingData()
})

</script>

<template>

<div class="result-container">

    <!-- MODERN HEADER -->
    <div class="result-header">
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
            <Link
                href="/dashboard"
                :class="{ active: isActive('dashboard') }"
                class="nav-link"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H4a2 2 0 0 1-2-2z"/>
                </svg>
                <span>Dashboard</span>
            </Link>

            <Link
                href="/process"
                :class="{ active: isActive('process') }"
                class="nav-link"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
                <span>Proses SAW</span>
            </Link>

            <Link
                href="/result"
                :class="{ active: isActive('result') }"
                class="nav-link"
            >
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

    <!-- MAIN CONTENT -->
    <div class="result-main">

        <!-- HEADER SECTION -->
        <div class="page-header">
            <div class="header-content">
                <div class="title-section">
                    <div class="title-icon">🏆</div>
                    <div>
                        <h1>Hasil Ranking Lokasi</h1>
                        <p>Berdasarkan perhitungan metode SAW (Simple Additive Weighting)</p>
                    </div>
                </div>
                <div class="stats-badge">
                    <span class="stat-count">{{ rankingData.length }}</span>
                    <span class="stat-label">Lokasi Direkomendasikan</span>
                </div>
            </div>
        </div>

        <!-- LOADING STATE -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-card">
                <div class="spinner"></div>
                <p>Memuat data ranking...</p>
            </div>
        </div>

        <!-- ERROR STATE -->
        <div v-else-if="error" class="error-container">
            <div class="error-card">
                <div class="error-icon">⚠️</div>
                <h3>Terjadi Kesalahan</h3>
                <p>{{ error }}</p>
                <button @click="fetchRankingData" class="retry-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6"/>
                        <path d="M1 20v-6h6"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    Coba Lagi
                </button>
            </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-else-if="rankingData.length === 0" class="empty-container">
            <div class="empty-card">
                <div class="empty-icon">📊</div>
                <h3>Belum Ada Data Ranking</h3>
                <p>Silakan lakukan proses perhitungan SAW terlebih dahulu</p>
                <Link href="/process" class="process-link">
                    Mulai Proses SAW
                </Link>
            </div>
        </div>

        <!-- RANKING GRID -->
        <div v-else class="ranking-grid">
            
            <!-- TOP 3 HIGHLIGHT -->
            <div class="ranking-highlight" v-if="rankingData.length >= 3">
                <div class="highlight-header">
                    <h3>🏆 TOP 3 RECOMMENDATION</h3>
                </div>
                <div class="podium">
                    <div class="podium-item second">
                        <div class="podium-rank">2</div>
                        <div class="podium-medal">🥈</div>
                        <div class="podium-name">{{ rankingData[1]?.nama_lokasi }}</div>
                        <div class="podium-score">{{ formatScore(rankingData[1]?.score) }}</div>
                    </div>
                    <div class="podium-item first">
                        <div class="podium-rank">1</div>
                        <div class="podium-medal">🥇</div>
                        <div class="podium-name">{{ rankingData[0]?.nama_lokasi }}</div>
                        <div class="podium-score">{{ formatScore(rankingData[0]?.score) }}</div>
                        <div class="crown">👑</div>
                    </div>
                    <div class="podium-item third">
                        <div class="podium-rank">3</div>
                        <div class="podium-medal">🥉</div>
                        <div class="podium-name">{{ rankingData[2]?.nama_lokasi }}</div>
                        <div class="podium-score">{{ formatScore(rankingData[2]?.score) }}</div>
                    </div>
                </div>
            </div>

            <!-- ALL RANKINGS -->
            <div class="rankings-list">
                <div
                    class="ranking-card"
                    v-for="(item, index) in rankingData"
                    :key="index"
                    :class="[
                        'ranking-card',
                        `rank-${getMedalColor(index)}`,
                        { 'top-three': index < 3 }
                    ]"
                >
                    <div class="card-header">
                        <div class="rank-badge" :class="`rank-${getMedalColor(index)}`">
                            <span class="rank-number">{{ index + 1 }}</span>
                            <span class="rank-medal">{{ getMedalIcon(index) }}</span>
                        </div>
                        <div class="score-wrapper">
                            <div class="score-circle">
                                <svg class="progress-ring" width="60" height="60">
                                    <circle
                                        class="progress-ring-circle-bg"
                                        stroke="#e2e8f0"
                                        stroke-width="4"
                                        fill="none"
                                        cx="30"
                                        cy="30"
                                        r="26"
                                    />
                                    <circle
                                        class="progress-ring-circle"
                                        stroke="currentColor"
                                        stroke-width="4"
                                        fill="none"
                                        cx="30"
                                        cy="30"
                                        r="26"
                                        :style="{
                                            strokeDasharray: `${2 * Math.PI * 26}`,
                                            strokeDashoffset: `${2 * Math.PI * 26 * (1 - (typeof item.score === 'number' ? item.score : parseFloat(item.score)))}`,
                                            color: getScoreColor(item.score)
                                        }"
                                    />
                                </svg>
                                <div class="score-text">
                                    {{ formatScore(item.score) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <h2 class="location-name">{{ item.nama_lokasi }}</h2>
                        
                        <div class="criteria-details">
                            <div class="criteria-item">
                                <span class="criteria-icon">👥</span>
                                <div class="criteria-info">
                                    <span class="criteria-label">Kepadatan Penduduk</span>
                                    <span class="criteria-value">{{ item.kepadatan_penduduk }}</span>
                                </div>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-icon">💰</span>
                                <div class="criteria-info">
                                    <span class="criteria-label">Harga Sewa</span>
                                    <span class="criteria-value">{{ item.harga_sewa }}</span>
                                </div>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-icon">🚗</span>
                                <div class="criteria-info">
                                    <span class="criteria-label">Jumlah Kendaraan</span>
                                    <span class="criteria-value">{{ item.jumlah_kendaraan }}</span>
                                </div>
                            </div>
                            <div class="criteria-item">
                                <span class="criteria-icon">📏</span>
                                <div class="criteria-info">
                                    <span class="criteria-label">Jarak Kompetitor</span>
                                    <span class="criteria-value">{{ item.jarak_kompetitor }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="recommendation-tag" :class="{
                            'high': index === 0,
                            'medium': index === 1 || index === 2,
                            'low': index > 2
                        }">
                            {{ index === 0 ? 'Sangat Direkomendasikan' : 
                               index === 1 || index === 2 ? 'Direkomendasikan' : 
                               'Cukup Direkomendasikan' }}
                        </div>
                    </div>
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

.result-container {
    width: 100%;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* MODERN HEADER */
.result-header {
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

.result-header h1 {
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

.nav-link:hover {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.nav-link.active {
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

.avatar:hover {
    transform: scale(1.05);
}

/* MAIN CONTENT */
.result-main {
    padding: 30px;
    min-height: calc(100vh - 70px);
}

/* PAGE HEADER */
.page-header {
    max-width: 1400px;
    margin: 0 auto 30px;
}

.header-content {
    background: white;
    color: #1e293b;
    border-radius: 20px;
    padding: 24px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.title-section {
    display: flex;
    align-items: center;
    gap: 16px;
}

.title-icon {
    font-size: 48px;
}

.title-section h1 {
    font-size: 28px;
    color: #1e293b;
    margin-bottom: 5px;
}

.title-section p {
    color: #64748b;
    font-size: 14px;
}

.stats-badge {
    text-align: center;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    color: white;
}

.stat-count {
    font-size: 32px;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
}

/* LOADING STATE */
.loading-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.loading-card {
    background: white;
    color: #1e293b;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ERROR STATE */
.error-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.error-card {
    background: white;
    color: #1e293b;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.error-icon {
    font-size: 60px;
    margin-bottom: 20px;
}

.error-card h3 {
    color: #dc2626;
    margin-bottom: 10px;
}

.retry-btn {
    margin-top: 20px;
    padding: 10px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.retry-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* EMPTY STATE */
.empty-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.empty-card {
    background: white;
    color: #1e293b;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    font-size: 60px;
    margin-bottom: 20px;
}

.empty-card h3 {
    color: #1e293b;
    margin-bottom: 10px;
}

.process-link {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.process-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* RANKING GRID */
.ranking-grid {
    max-width: 1400px;
    margin: 0 auto;
}

/* PODIUM SECTION */
.ranking-highlight {
    background: white;
    color: #1e293b;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.highlight-header {
    text-align: center;
    margin-bottom: 30px;
}

.highlight-header h3 {
    color: #1e293b;
    font-size: 20px;
}

.podium {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
}

.podium-item {
    text-align: center;
    padding: 20px;
    border-radius: 16px;
    background: #f8fafc;
    transition: transform 0.3s ease;
}

.podium-item:hover {
    transform: translateY(-5px);
}

.podium-item.first {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    padding: 30px 20px;
    min-width: 200px;
    position: relative;
}

.podium-item.second {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    min-width: 180px;
}

.podium-item.third {
    background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
    min-width: 180px;
}

.podium-rank {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 10px;
}

.podium-medal {
    font-size: 48px;
    margin: 10px 0;
}

.podium-name {
    font-weight: 600;
    color: #1e293b;
    margin: 10px 0;
}

.podium-score {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

.crown {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 32px;
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-10px); }
}

/* RANKINGS LIST */
.rankings-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
}

.ranking-card {
    background: white;
    color: #1e293b;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    position: relative;
}

.ranking-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.ranking-card.top-three {
    border: 2px solid transparent;
    background: linear-gradient(white, white) padding-box,
                linear-gradient(135deg, #667eea, #764ba2) border-box;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
}

.rank-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.rank-number {
    font-size: 32px;
    font-weight: 700;
}

.rank-medal {
    font-size: 24px;
}

.rank-badge.rank-gold .rank-number {
    color: #f59e0b;
}

.rank-badge.rank-silver .rank-number {
    color: #94a3b8;
}

.rank-badge.rank-bronze .rank-number {
    color: #cd7f32;
}

.rank-badge.rank-default .rank-number {
    color: #64748b;
}

.score-wrapper {
    position: relative;
}

.score-circle {
    position: relative;
    width: 60px;
    height: 60px;
}

.progress-ring-circle {
    transition: stroke-dashoffset 0.5s ease;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    text-align: center;
}

.card-body {
    padding: 20px;
}

.location-name {
    font-size: 20px;
    color: #1e293b;
    margin-bottom: 15px;
    font-weight: 600;
}

.criteria-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.criteria-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    background: #f8fafc;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.criteria-item:hover {
    background: #f1f5f9;
    transform: translateX(5px);
}

.criteria-icon {
    font-size: 20px;
}

.criteria-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.criteria-label {
    font-size: 13px;
    color: #64748b;
}

.criteria-value {
    font-weight: 600;
    color: #1e293b;
}

.card-footer {
    padding: 15px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.recommendation-tag {
    text-align: center;
    padding: 8px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
}

.recommendation-tag.high {
    background: #dcfce7;
    color: #166534;
}

.recommendation-tag.medium {
    background: #dbeafe;
    color: #1e40af;
}

.recommendation-tag.low {
    background: #fef3c7;
    color: #92400e;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .result-header {
        padding: 0 16px;
    }

    .nav-link span {
        display: none;
    }

    .nav-link {
        padding: 8px 12px;
    }

    .result-main {
        padding: 20px;
    }

    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .title-section {
        flex-direction: column;
    }

    .rankings-list {
        grid-template-columns: 1fr;
    }

    .podium-item.first,
    .podium-item.second,
    .podium-item.third {
        min-width: 150px;
    }

    .podium-name {
        font-size: 14px;
    }
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ranking-card {
    animation: slideIn 0.5s ease forwards;
}

.ranking-card:nth-child(1) { animation-delay: 0.1s; }
.ranking-card:nth-child(2) { animation-delay: 0.2s; }
.ranking-card:nth-child(3) { animation-delay: 0.3s; }
.ranking-card:nth-child(4) { animation-delay: 0.4s; }
.ranking-card:nth-child(5) { animation-delay: 0.5s; }
.ranking-card:nth-child(6) { animation-delay: 0.6s; }
</style>