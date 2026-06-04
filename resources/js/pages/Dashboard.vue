<script setup>

/*
|--------------------------------------------------------------------------
| IMPORT
|--------------------------------------------------------------------------
*/

import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'

/*
|--------------------------------------------------------------------------
| LEAFLET
|--------------------------------------------------------------------------
*/

import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

/*
|--------------------------------------------------------------------------
| TURF
|--------------------------------------------------------------------------
*/

import * as turf from '@turf/turf'

/*
|--------------------------------------------------------------------------
| NAVIGATION ACTIVE
|--------------------------------------------------------------------------
*/

const isActive = (page) => {
    const currentUrl = usePage().url
    if (page === 'dashboard') return currentUrl === '/dashboard'
    if (page === 'crud') return currentUrl === '/crud' || currentUrl === '/locations' || currentUrl === '/lokasi'
    return false
}

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

let map = null
let bengkelMarkers = []
let fasumMarkersArray = []
let recommendationMarkers = []
let bufferLayers = []
let wilayahLayers = []
let jalanLayers = []
let selectedMarkerLayer = null
let measureLine = null
let measurePoints = []
let spatialAnalysisLayers = []

// Geolocation state
let userMarker = null
let userLocationCircle = null
const userLocation = ref(null)
const isLocating = ref(false)
const locationError = ref(null)
const nearestLocations = ref({
    bengkel: [],
    fasum: [],
    recommendations: []
})
const showNearestPanel = ref(true)

const bengkel = ref([])
const fasum = ref([])
const recommendations = ref([])
const wilayahData = ref([])
const aturanSIG = ref([])
const jalanData = ref([])

const loading = ref(false)
const bufferRadius = ref(0.5)
const searchQuery = ref('')
const showLegend = ref(true)
const showAnalysisPanel = ref(true)
const selectedLocation = ref(null)
const spatialAnalysis = ref(null)
const measureMode = ref(false)
const measureDistance = ref(null)
const activeTab = ref('analysis')
const mapZoom = ref(13)
const mapCenter = ref([3.5952, 98.6638])
const isAnimating = ref(false)
const showWelcomeToast = ref(true)

// State untuk analisis spasial lanjutan
const pointInPolygonResult = ref(null)
const pointInLineResult = ref(null)
const lineInPolygonResult = ref(null)
const selectedRoad = ref(null)
const selectedPolygon = ref(null)
const analysisMode = ref('none')

const mapLayers = ref({
    bengkel: true,
    fasum: true,
    recommendations: true,
    bufferZone: true,
    wilayah: true,
    jalan: true,
    analysisResults: true
})

// Tips
const tips = [
    { icon: "🔍", text: "Cari lokasi dengan mengetik nama di kotak pencarian" },
    { icon: "📍", text: "Klik marker untuk melihat analisis lengkap lokasi" },
    { icon: "📏", text: "Gunakan alat ukur untuk menghitung jarak antar titik" },
    { icon: "🗺️", text: "Aktifkan/nonaktifkan layer melalui menu Layer Control" },
    { icon: "⭐", text: "Marker rekomendasi memiliki skor warna (hijau=terbaik)" },
    { icon: "🔷", text: "Gunakan analisis Point in Polygon untuk cek lokasi dalam wilayah" },
    { icon: "🛣️", text: "Point in Line: hitung jarak titik ke jalan terdekat" },
    { icon: "📐", text: "Line in Polygon: cek apakah jalan melewati wilayah tertentu" },
    { icon: "📍", text: "Klik 'Lokasi Saya' untuk melihat posisi Anda dan jarak terdekat" }
]

const currentTip = ref(tips[0])
let tipInterval = null

/*
|--------------------------------------------------------------------------
| FETCH DATA
|--------------------------------------------------------------------------
*/

const fetchAturanSIG = async () => {
    try {
        const response = await axios.get('/api/aturan-sig')
        aturanSIG.value = response.data
        const aturanBuffer = aturanSIG.value.find(item => item.nama_kriteria === 'Jarak Pesaing')
        if (aturanBuffer && aturanBuffer.radius_buffer) {
            bufferRadius.value = aturanBuffer.radius_buffer / 1000
        }
    } catch (error) {
        console.error('❌ Gagal fetch aturan SIG:', error)
    }
}

const fetchBengkelDanFasum = async () => {
    try {
        loading.value = true
        const response = await axios.get('/api/locations')
        let data = response.data
        if (Array.isArray(data)) {
            bengkel.value = data
                .filter(loc => loc.kategori === 'bengkel' || loc.kategori === 'kompetitor')
                .map(loc => ({
                    id: loc.id,
                    nama: loc.nama || loc.nama_lokasi || 'Bengkel',
                    jalan: loc.jalan || loc.alamat || '-',
                    kategori: 'bengkel',
                    is_resmi: loc.is_resmi || false,
                    luas_lahan: loc.luas_lahan,
                    waktu_buka: loc.waktu_buka || '08:00',
                    waktu_tutup: loc.waktu_tutup || '17:00',
                    hari_libur: loc.hari_libur || 'Minggu',
                    latitude: loc.latitude || loc.geom?.coordinates?.[1] || loc.geometry?.coordinates?.[1],
                    longitude: loc.longitude || loc.geom?.coordinates?.[0] || loc.geometry?.coordinates?.[0]
                })).filter(loc => loc.latitude && loc.longitude)
            
            fasum.value = data
                .filter(loc => loc.kategori === 'fasum')
                .map(loc => ({
                    id: loc.id,
                    nama: loc.nama || 'Fasilitas Umum',
                    jalan: loc.jalan || loc.alamat || '-',
                    kategori: 'fasum',
                    latitude: loc.latitude || loc.geom?.coordinates?.[1] || loc.geometry?.coordinates?.[1],
                    longitude: loc.longitude || loc.geom?.coordinates?.[0] || loc.geometry?.coordinates?.[0]
                })).filter(loc => loc.latitude && loc.longitude)
        }
    } catch (error) {
        console.error('❌ Gagal fetch locations:', error)
    } finally {
        loading.value = false
    }
}

const fetchRecommendations = async () => {
    try {
        const response = await axios.get('/api/recommendations')
        let data = response.data
        if (Array.isArray(data)) {
            recommendations.value = data.map(rec => ({
                ...rec,
                nama: rec.nama || rec.nama_lokasi || 'Lokasi Rekomendasi',
                skor_akhir: rec.skor_akhir || rec.score || 0
            }))
        }
    } catch (error) {
        console.error('❌ Gagal fetch recommendations:', error)
    }
}

const fetchWilayah = async () => {
    try {
        const response = await axios.get('/api/wilayah')
        let data = response.data
        if (Array.isArray(data)) {
            wilayahData.value = data
        } else if (data?.features) {
            wilayahData.value = data.features
        } else {
            wilayahData.value = []
        }
    } catch (error) {
        console.error('❌ Gagal fetch wilayah:', error)
    }
}

const fetchJalan = async () => {
    try {
        const response = await axios.get('/api/jalan')
        let data = response.data
        if (data?.features) {
            jalanData.value = data.features
        } else if (Array.isArray(data)) {
            jalanData.value = data
        } else {
            jalanData.value = []
        }
    } catch (error) {
        console.error('❌ Gagal fetch jalan:', error)
    }
}

/*
|--------------------------------------------------------------------------
| GEOLOCATION & NEAREST LOCATIONS
|--------------------------------------------------------------------------
*/

// Hitung jarak antara dua koordinat (dalam KM)
const calculateDistanceBetweenPoints = (lat1, lng1, lat2, lng2) => {
    const from = turf.point([lng1, lat1])
    const to = turf.point([lng2, lat2])
    return turf.distance(from, to, { units: 'kilometers' })
}

// Dapatkan lokasi pengguna saat ini
const getUserLocation = () => {
    if (!navigator.geolocation) {
        locationError.value = "Browser Anda tidak mendukung Geolocation"
        return
    }
    
    isLocating.value = true
    locationError.value = null
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords
            userLocation.value = {
                lat: latitude,
                lng: longitude,
                accuracy: position.coords.accuracy,
                timestamp: new Date().toLocaleString()
            }
            
            // Tampilkan marker di peta
            addUserMarkerToMap(latitude, longitude, position.coords.accuracy)
            
            // Hitung jarak terdekat
            calculateNearestLocations(latitude, longitude)
            
            isLocating.value = false
            
            // Tampilkan toast sukses
            showWelcomeToast.value = true
            setTimeout(() => {
                showWelcomeToast.value = false
            }, 4000)
        },
        (error) => {
            isLocating.value = false
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    locationError.value = "Izin lokasi ditolak. Aktifkan untuk melihat jarak terdekat."
                    break
                case error.POSITION_UNAVAILABLE:
                    locationError.value = "Informasi lokasi tidak tersedia"
                    break
                case error.TIMEOUT:
                    locationError.value = "Waktu permintaan lokasi habis"
                    break
                default:
                    locationError.value = "Gagal mendapatkan lokasi"
            }
            console.error("Geolocation error:", error)
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    )
}

// Tambah marker user ke peta
const addUserMarkerToMap = (lat, lng, accuracy) => {
    if (!map) return
    
    // Hapus marker lama jika ada
    if (userMarker) {
        map.removeLayer(userMarker)
    }
    if (userLocationCircle) {
        map.removeLayer(userLocationCircle)
    }
    
    // Buat icon khusus untuk user
    const userIcon = L.divIcon({
        className: 'user-marker',
        html: `
            <div class="user-marker-content">
                <div class="user-pulse"></div>
                <div class="user-icon">📍</div>
                <div class="user-label">Anda di sini</div>
            </div>
        `,
        iconSize: [60, 60],
        popupAnchor: [0, -30]
    })
    
    userMarker = L.marker([lat, lng], { icon: userIcon }).addTo(map)
    
    // Popup informasi
    userMarker.bindPopup(`
        <div class="custom-popup">
            <div class="popup-header" style="border-left-color: #10b981;">
                <span class="popup-icon">📍</span>
                <div>
                    <h4>Lokasi Anda Saat Ini</h4>
                    <span class="popup-badge">Posisi Real-time</span>
                </div>
            </div>
            <div class="popup-body">
                <div class="info-row">
                    <span class="info-label">📐 Akurasi:</span>
                    <span class="info-value">${accuracy ? Math.round(accuracy) : '?'} meter</span>
                </div>
                <div class="info-row">
                    <span class="info-label">🕐 Waktu:</span>
                    <span class="info-value">${new Date().toLocaleTimeString()}</span>
                </div>
            </div>
        </div>
    `, { className: 'modern-popup' })
    
    // Lingkaran akurasi
    if (accuracy) {
        userLocationCircle = L.circle([lat, lng], {
            radius: accuracy,
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.1,
            weight: 2,
            dashArray: '5, 5'
        }).addTo(map)
    }
    
    // Fly ke lokasi user
    map.flyTo([lat, lng], 15, { duration: 1.5 })
}

// Hitung lokasi terdekat dari posisi user
const calculateNearestLocations = (userLat, userLng) => {
    // Hitung jarak ke semua bengkel
    const bengkelWithDistance = bengkel.value.map(b => ({
        ...b,
        distance: calculateDistanceBetweenPoints(userLat, userLng, b.latitude, b.longitude)
    })).sort((a, b) => a.distance - b.distance).slice(0, 5)
    
    // Hitung jarak ke semua fasum
    const fasumWithDistance = fasum.value.map(f => ({
        ...f,
        distance: calculateDistanceBetweenPoints(userLat, userLng, f.latitude, f.longitude)
    })).sort((a, b) => a.distance - b.distance).slice(0, 5)
    
    // Hitung jarak ke semua rekomendasi
    const recommendationsWithDistance = recommendations.value.map(r => {
        let lat = r.latitude
        let lng = r.longitude
        if (!lat && r.geometry_json) {
            try {
                const geo = typeof r.geometry_json === 'string' ? JSON.parse(r.geometry_json) : r.geometry_json
                lng = geo.coordinates?.[0]
                lat = geo.coordinates?.[1]
            } catch(e) {}
        }
        return {
            ...r,
            latitude: lat,
            longitude: lng,
            distance: lat && lng ? calculateDistanceBetweenPoints(userLat, userLng, lat, lng) : Infinity
        }
    }).filter(r => r.distance !== Infinity).sort((a, b) => a.distance - b.distance).slice(0, 5)
    
    nearestLocations.value = {
        bengkel: bengkelWithDistance,
        fasum: fasumWithDistance,
        recommendations: recommendationsWithDistance
    }
}

// Refresh lokasi terdekat (dipanggil saat data berubah)
const refreshNearestLocations = () => {
    if (userLocation.value) {
        calculateNearestLocations(userLocation.value.lat, userLocation.value.lng)
    }
}

// Pilih lokasi dari daftar terdekat
const selectNearestLocation = (location) => {
    if (location.latitude && location.longitude) {
        performComprehensiveAnalysis(location)
        if (map) {
            map.flyTo([location.latitude, location.longitude], 16, { duration: 1 })
        }
    }
}

/*
|--------------------------------------------------------------------------
| SPATIAL ANALYSIS FUNCTIONS
|--------------------------------------------------------------------------
*/

// ==================== POINT IN POLYGON ====================
const checkPointInPolygon = (lat, lng, pointName = null) => {
    const point = turf.point([lng, lat])
    let results = []
    let details = []
    
    wilayahData.value.forEach(wilayah => {
        try {
            let geometry = wilayah.geometry || wilayah.geom
            if (typeof geometry === 'string') geometry = JSON.parse(geometry)
            if (!geometry?.coordinates) return
            
            const isInside = turf.booleanPointInPolygon(point, geometry)
            const namaWilayah = wilayah.nama || wilayah.nama_wilayah || 'Wilayah'
            
            let luas = 0
            try {
                const polygon = turf.polygon(geometry.coordinates)
                luas = (turf.area(polygon) / 10000).toFixed(2)
            } catch(e) {}
            
            if (isInside) {
                results.push(namaWilayah)
                details.push({
                    nama: namaWilayah,
                    luas: luas,
                    status: 'Berada di dalam',
                    koordinat: `${lat}, ${lng}`
                })
            }
        } catch(e) {}
    })
    
    pointInPolygonResult.value = {
        pointName: pointName || `Titik (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
        latitude: lat,
        longitude: lng,
        isInside: results.length > 0,
        wilayahList: results,
        details: details,
        totalWilayah: wilayahData.value.length,
        timestamp: new Date().toLocaleString()
    }
    
    visualizePointInPolygon(lat, lng, results)
    
    return results
}

const visualizePointInPolygon = (lat, lng, wilayahList) => {
    if (!map) return
    
    spatialAnalysisLayers.forEach(layer => {
        if (map.hasLayer(layer)) map.removeLayer(layer)
    })
    spatialAnalysisLayers = []
    
    const pointIcon = L.divIcon({
        className: 'analysis-point-marker',
        html: `<div class="analysis-point"><span>📍</span><div class="point-label">Titik Analisis</div></div>`,
        iconSize: [40, 40],
        popupAnchor: [0, -20]
    })
    
    const pointMarker = L.marker([lat, lng], { icon: pointIcon }).addTo(map)
    pointMarker.bindPopup(`
        <div class="analysis-popup">
            <h4>📍 Point in Polygon Analysis</h4>
            <p><strong>Lokasi:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}</p>
            <p><strong>Status:</strong> ${wilayahList.length > 0 ? '✅ Berada di dalam wilayah' : '❌ Tidak berada di wilayah manapun'}</p>
            ${wilayahList.length > 0 ? `<p><strong>Wilayah:</strong> ${wilayahList.join(', ')}</p>` : ''}
        </div>
    `).openPopup()
    
    spatialAnalysisLayers.push(pointMarker)
    
    wilayahData.value.forEach(wilayah => {
        try {
            let geometry = wilayah.geometry || wilayah.geom
            if (typeof geometry === 'string') geometry = JSON.parse(geometry)
            if (!geometry?.coordinates) return
            
            const point = turf.point([lng, lat])
            const isInside = turf.booleanPointInPolygon(point, geometry)
            const namaWilayah = wilayah.nama || wilayah.nama_wilayah || 'Wilayah'
            
            if (isInside && wilayahList.includes(namaWilayah)) {
                const highlightLayer = L.geoJSON(geometry, {
                    style: {
                        color: '#10b981',
                        weight: 4,
                        fillColor: '#10b981',
                        fillOpacity: 0.3,
                        dashArray: null
                    }
                }).addTo(map)
                
                spatialAnalysisLayers.push(highlightLayer)
            }
        } catch(e) {}
    })
    
    map.flyTo([lat, lng], 14, { duration: 1 })
}

// ==================== POINT IN LINE (Jarak ke Jalan Terdekat) ====================
const checkPointToNearestRoad = (lat, lng, pointName = null) => {
    const point = turf.point([lng, lat])
    let nearestDistance = Infinity
    let nearestRoadName = null
    let nearestRoadJenis = null
    let nearestRoadGeometry = null
    let allRoadsDistance = []
    
    jalanData.value.forEach(jalan => {
        try {
            let geo = jalan.geometry || jalan.geom
            if (typeof geo === 'string') geo = JSON.parse(geo)
            if (!geo) return
            
            let distance = null
            let roadGeometry = null
            
            if (geo.type === 'LineString') {
                const line = turf.lineString(geo.coordinates)
                distance = turf.pointToLineDistance(point, line, { units: 'kilometers' })
                roadGeometry = geo.coordinates
            } else if (geo.type === 'MultiLineString') {
                let minDist = Infinity
                let minGeo = null
                geo.coordinates.forEach(coords => {
                    const line = turf.lineString(coords)
                    const dist = turf.pointToLineDistance(point, line, { units: 'kilometers' })
                    if (dist < minDist) {
                        minDist = dist
                        minGeo = coords
                    }
                })
                distance = minDist
                roadGeometry = minGeo
            }
            
            const roadName = jalan.properties?.nama_jalan || jalan.nama_jalan || 'Jalan'
            const roadType = jalan.properties?.jenis || jalan.jenis || 'Jalan Lokal'
            
            allRoadsDistance.push({
                name: roadName,
                type: roadType,
                distance: distance,
                isNearest: distance === nearestDistance
            })
            
            if (distance !== null && distance < nearestDistance) {
                nearestDistance = distance
                nearestRoadName = roadName
                nearestRoadJenis = roadType
                nearestRoadGeometry = roadGeometry
            }
        } catch(e) {}
    })
    
    allRoadsDistance.sort((a, b) => a.distance - b.distance)
    const top5Roads = allRoadsDistance.slice(0, 5)
    
    pointInLineResult.value = {
        pointName: pointName || `Titik (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
        latitude: lat,
        longitude: lng,
        nearestRoad: {
            name: nearestRoadName,
            type: nearestRoadJenis,
            distance: nearestDistance !== Infinity ? nearestDistance.toFixed(3) : null
        },
        allRoads: top5Roads.map(r => ({
            name: r.name,
            type: r.type,
            distance: r.distance.toFixed(3)
        })),
        totalRoadsAnalyzed: jalanData.value.length,
        timestamp: new Date().toLocaleString()
    }
    
    visualizePointToNearestRoad(lat, lng, nearestRoadGeometry, nearestDistance)
    
    return {
        distance: nearestDistance !== Infinity ? nearestDistance : null,
        roadName: nearestRoadName,
        roadType: nearestRoadJenis
    }
}

const visualizePointToNearestRoad = (lat, lng, roadGeometry, distance) => {
    if (!map) return
    
    spatialAnalysisLayers.forEach(layer => {
        if (map.hasLayer(layer)) map.removeLayer(layer)
    })
    spatialAnalysisLayers = []
    
    const pointIcon = L.divIcon({
        className: 'analysis-point-marker',
        html: `<div class="analysis-point blue"><span>📍</span><div class="point-label">Titik Analisis</div></div>`,
        iconSize: [40, 40],
        popupAnchor: [0, -20]
    })
    
    const pointMarker = L.marker([lat, lng], { icon: pointIcon }).addTo(map)
    
    if (roadGeometry && distance) {
        const point = turf.point([lng, lat])
        const line = turf.lineString(roadGeometry)
        const nearestPoint = turf.nearestPointOnLine(line, point)
        const nearestCoords = nearestPoint.geometry.coordinates
        
        const lineCoords = [[lat, lng], [nearestCoords[1], nearestCoords[0]]]
        const connectingLine = L.polyline(lineCoords, {
            color: '#ef4444',
            weight: 3,
            dashArray: '5, 10',
            opacity: 0.8
        }).addTo(map)
        
        spatialAnalysisLayers.push(connectingLine)
        
        const nearestMarker = L.marker([nearestCoords[1], nearestCoords[0]], {
            icon: L.divIcon({
                html: `<div style="background:#ef4444;width:10px;height:10px;border-radius:50%;border:2px solid white"></div>`,
                iconSize: [10, 10]
            })
        }).addTo(map)
        
        spatialAnalysisLayers.push(nearestMarker)
        
        pointMarker.bindPopup(`
            <div class="analysis-popup">
                <h4>🛣️ Point to Line Analysis</h4>
                <p><strong>Lokasi:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}</p>
                <p><strong>Jarak ke jalan terdekat:</strong> <span class="distance-value">${distance.toFixed(3)} KM</span></p>
                <p><strong>Garis merah</strong> menunjukkan jarak terpendek ke jalan</p>
            </div>
        `).openPopup()
    } else {
        pointMarker.bindPopup(`
            <div class="analysis-popup">
                <h4>🛣️ Point to Line Analysis</h4>
                <p><strong>Lokasi:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}</p>
                <p><strong>Status:</strong> ❌ Tidak ditemukan jalan terdekat</p>
            </div>
        `).openPopup()
    }
    
    spatialAnalysisLayers.push(pointMarker)
    
    if (roadGeometry) {
        const roadLayer = L.polyline(roadGeometry.map(coord => [coord[1], coord[0]]), {
            color: '#f59e0b',
            weight: 5,
            opacity: 0.9,
            className: 'highlighted-road'
        }).addTo(map)
        
        spatialAnalysisLayers.push(roadLayer)
    }
    
    map.flyTo([lat, lng], 14, { duration: 1 })
}

// ==================== LINE IN POLYGON ====================
const checkLineInPolygon = (roadGeo, roadName, polygonGeo, polygonName) => {
    try {
        const line = roadGeo.type === 'LineString' 
            ? turf.lineString(roadGeo.coordinates)
            : turf.multiLineString(roadGeo.coordinates)
        const polygon = turf.polygon(polygonGeo.coordinates)
        
        const isCompletelyInside = turf.booleanWithin(line, polygon)
        
        const intersection = turf.lineIntersect(line, polygon)
        const intersectionPoints = intersection.features.length
        
        let lengthInside = 0
        let totalLength = 0
        
        try {
            totalLength = turf.length(line, { units: 'kilometers' })
            
            if (isCompletelyInside) {
                lengthInside = totalLength
            } else if (intersectionPoints > 0) {
                const splitLine = turf.lineSplit(line, polygon)
                if (splitLine.features) {
                    splitLine.features.forEach(feature => {
                        try {
                            const isInside = turf.booleanPointInPolygon(
                                turf.point(feature.geometry.coordinates[0]),
                                polygon
                            )
                            if (isInside) {
                                lengthInside += turf.length(feature, { units: 'kilometers' })
                            }
                        } catch(e) {}
                    })
                }
            }
        } catch(e) {}
        
        const percentageInside = totalLength > 0 ? (lengthInside / totalLength * 100).toFixed(1) : 0
        
        lineInPolygonResult.value = {
            roadName: roadName,
            polygonName: polygonName,
            isCompletelyInside: isCompletelyInside,
            intersectionPoints: intersectionPoints,
            totalLength: totalLength.toFixed(3),
            lengthInside: lengthInside.toFixed(3),
            percentageInside: percentageInside,
            timestamp: new Date().toLocaleString()
        }
        
        visualizeLineInPolygon(roadGeo, polygonGeo, roadName, polygonName, intersection)
        
        return {
            isInside: isCompletelyInside,
            intersectionPoints: intersectionPoints,
            lengthInside: lengthInside.toFixed(3),
            percentageInside: percentageInside
        }
    } catch(e) {
        console.error('Line in Polygon error:', e)
        lineInPolygonResult.value = {
            error: true,
            message: e.message
        }
        return { isInside: false, intersectionPoints: 0, lengthInside: 0, percentageInside: 0 }
    }
}

const visualizeLineInPolygon = (roadGeo, polygonGeo, roadName, polygonName, intersection) => {
    if (!map) return
    
    spatialAnalysisLayers.forEach(layer => {
        if (map.hasLayer(layer)) map.removeLayer(layer)
    })
    spatialAnalysisLayers = []
    
    const polygonLayer = L.geoJSON(polygonGeo, {
        style: {
            color: '#3b82f6',
            weight: 3,
            fillColor: '#60a5fa',
            fillOpacity: 0.2
        }
    }).addTo(map)
    
    spatialAnalysisLayers.push(polygonLayer)
    
    const roadCoords = roadGeo.coordinates.map(coord => [coord[1], coord[0]])
    const roadLayer = L.polyline(roadCoords, {
        color: '#f59e0b',
        weight: 4,
        opacity: 0.9
    }).addTo(map)
    
    spatialAnalysisLayers.push(roadLayer)
    
    if (intersection && intersection.features) {
        intersection.features.forEach(feature => {
            const coord = feature.geometry.coordinates
            const marker = L.marker([coord[1], coord[0]], {
                icon: L.divIcon({
                    html: `<div style="background:#ef4444;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.3)"></div>`,
                    iconSize: [14, 14]
                })
            }).addTo(map)
            spatialAnalysisLayers.push(marker)
        })
    }
    
    const bounds = polygonLayer.getBounds()
    const center = bounds.getCenter()
    
    const infoMarker = L.marker([center.lat, center.lng], {
        icon: L.divIcon({
            className: 'info-marker',
            html: `<div class="analysis-info-bubble">📊 ${lineInPolygonResult.value?.percentageInside || 0}% jalan di dalam wilayah</div>`,
            iconSize: [200, 30]
        })
    }).addTo(map)
    
    spatialAnalysisLayers.push(infoMarker)
    
    map.fitBounds(bounds)
}

// ==================== KOMPREHENSIF ANALISIS ====================
const performComprehensiveAnalysis = (location) => {
    isAnimating.value = true
    
    const lat = location.latitude
    const lng = location.longitude
    
    const wilayahList = checkPointInPolygon(lat, lng, location.nama)
    const roadInfo = checkPointToNearestRoad(lat, lng, location.nama)
    
    let nearestBengkelDistance = Infinity
    let nearestBengkelName = null
    
    bengkel.value.forEach(b => {
        if (b.id !== location.id) {
            const dist = parseFloat(calculateDistanceBetweenPoints(lat, lng, b.latitude, b.longitude))
            if (dist < nearestBengkelDistance) {
                nearestBengkelDistance = dist
                nearestBengkelName = b.nama
            }
        }
    })
    
    let nearestFasumDistance = Infinity
    let nearestFasumName = null
    
    fasum.value.forEach(f => {
        const dist = parseFloat(calculateDistanceBetweenPoints(lat, lng, f.latitude, f.longitude))
        if (dist < nearestFasumDistance) {
            nearestFasumDistance = dist
            nearestFasumName = f.nama
        }
    })
    
    let bengkelInBuffer = 0
    bengkel.value.forEach(b => {
        if (b.id !== location.id) {
            const dist = parseFloat(calculateDistanceBetweenPoints(lat, lng, b.latitude, b.longitude))
            if (dist <= bufferRadius.value) {
                bengkelInBuffer++
            }
        }
    })
    
    // Hitung jarak dari user jika ada
    let distanceFromUser = null
    if (userLocation.value) {
        distanceFromUser = parseFloat(calculateDistanceBetweenPoints(
            userLocation.value.lat, userLocation.value.lng, lat, lng
        ))
    }
    
    let recommendationScore = 0
    let recommendationText = ''
    
    if (roadInfo.distance && roadInfo.distance < 0.5) recommendationScore += 30
    if (nearestFasumDistance < 1) recommendationScore += 20
    if (nearestBengkelDistance > 2) recommendationScore += 30
    if (bengkelInBuffer === 0) recommendationScore += 20
    if (distanceFromUser && distanceFromUser < 1) recommendationScore += 10
    
    if (recommendationScore >= 80) recommendationText = 'Sangat Strategis! 🏆'
    else if (recommendationScore >= 60) recommendationText = 'Strategis ⭐'
    else if (recommendationScore >= 40) recommendationText = 'Cukup Strategis 📌'
    else recommendationText = 'Kurang Strategis ⚠️'
    
    spatialAnalysis.value = {
        locationName: location.nama,
        locationType: location.kategori || (location.skor_akhir ? 'rekomendasi' : 'lokasi'),
        coordinates: { lat: lat.toFixed(6), lng: lng.toFixed(6) },
        pointInPolygon: {
            isInside: wilayahList.length > 0,
            wilayahList: wilayahList
        },
        pointToLine: {
            distance: roadInfo.distance !== null ? roadInfo.distance.toFixed(3) : null,
            roadName: roadInfo.roadName,
            roadType: roadInfo.roadType
        },
        nearestBengkel: {
            name: nearestBengkelName,
            distance: nearestBengkelDistance !== Infinity ? nearestBengkelDistance.toFixed(3) : null
        },
        nearestFasum: {
            name: nearestFasumName,
            distance: nearestFasumDistance !== Infinity ? nearestFasumDistance.toFixed(3) : null
        },
        distanceFromUser: distanceFromUser ? distanceFromUser.toFixed(3) : null,
        bengkelInBuffer: bengkelInBuffer,
        recommendationScore: recommendationScore,
        recommendationText: recommendationText,
        jamOperasional: location.waktu_buka && location.waktu_tutup ? `${location.waktu_buka} - ${location.waktu_tutup}` : null,
        hariLibur: location.hari_libur,
        luasLahan: location.luas_lahan
    }
    
    selectedLocation.value = location
    activeTab.value = 'analysis'
    showAnalysisPanel.value = true
    isAnimating.value = false
}

/*
|--------------------------------------------------------------------------
| ANALISIS MODE HANDLERS
|--------------------------------------------------------------------------
*/

const startPointInPolygonMode = () => {
    analysisMode.value = 'point'
    map.getContainer().style.cursor = 'crosshair'
    
    const clickHandler = (e) => {
        const { lat, lng } = e.latlng
        checkPointInPolygon(lat, lng, `Titik yang dipilih`)
        map.off('click', clickHandler)
        map.getContainer().style.cursor = ''
        analysisMode.value = 'none'
    }
    
    map.on('click', clickHandler)
}

const startPointInLineMode = () => {
    analysisMode.value = 'line'
    map.getContainer().style.cursor = 'crosshair'
    
    const clickHandler = (e) => {
        const { lat, lng } = e.latlng
        checkPointToNearestRoad(lat, lng, `Titik yang dipilih`)
        map.off('click', clickHandler)
        map.getContainer().style.cursor = ''
        analysisMode.value = 'none'
    }
    
    map.on('click', clickHandler)
}

const startLineInPolygonMode = () => {
    analysisMode.value = 'polygon'
    alert('Klik pada jalan yang akan dianalisis, lalu klik pada polygon wilayah')
    
    let selectedRoadTemp = null
    let selectedRoadNameTemp = null
    
    const roadClickHandler = (e) => {
        if (e.layer && e.layer.feature) {
            selectedRoadTemp = e.layer.feature.geometry
            selectedRoadNameTemp = e.layer.feature.properties?.nama_jalan || 'Jalan'
            alert(`Jalan "${selectedRoadNameTemp}" dipilih. Sekarang klik pada polygon wilayah.`)
            map.off('click', roadClickHandler)
            
            const polygonClickHandler = (e) => {
                if (e.layer && e.layer.feature) {
                    const polygonGeo = e.layer.feature.geometry
                    const polygonName = e.layer.feature.properties?.nama || 'Wilayah'
                    checkLineInPolygon(selectedRoadTemp, selectedRoadNameTemp, polygonGeo, polygonName)
                    map.off('click', polygonClickHandler)
                    map.getContainer().style.cursor = ''
                    analysisMode.value = 'none'
                }
            }
            map.on('click', polygonClickHandler)
        }
    }
    
    map.on('click', roadClickHandler)
}

const clearAnalysisResults = () => {
    spatialAnalysisLayers.forEach(layer => {
        if (map && map.hasLayer(layer)) map.removeLayer(layer)
    })
    spatialAnalysisLayers = []
    pointInPolygonResult.value = null
    pointInLineResult.value = null
    lineInPolygonResult.value = null
    analysisMode.value = 'none'
    map.getContainer().style.cursor = ''
}

/*
|--------------------------------------------------------------------------
| INIT MAP
|--------------------------------------------------------------------------
*/

const initMap = () => {
    const mapContainer = document.getElementById('map')
    if (!mapContainer) return
    
    delete L.Icon.Default.prototype._getIconUrl
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png'
    })

    map = L.map('map').setView([3.5952, 98.6638], 13)
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        subdomains: 'abcd',
        maxZoom: 19,
        minZoom: 3
    }).addTo(map)
    
    map.on('zoomend', () => {
        mapZoom.value = map.getZoom()
    })
    
    map.on('moveend', () => {
        const center = map.getCenter()
        mapCenter.value = [center.lat, center.lng]
    })
    
    L.control.scale({ metric: true, imperial: false, position: 'bottomright' }).addTo(map)
    
    setTimeout(() => {
        addBengkelToMap()
        addFasumToMap()
        addRecommendationsToMap()
        addBufferZones()
        addWilayahPolygon()
        addJalanToMap()
        
        // Coba dapatkan lokasi user otomatis
        setTimeout(() => {
            getUserLocation()
        }, 1000)
        
        setTimeout(() => {
            showWelcomeToast.value = false
        }, 5000)
    }, 500)
}

/*
|--------------------------------------------------------------------------
| MARKER BENGKEL
|--------------------------------------------------------------------------
*/

const addBengkelToMap = () => {
    if (!map) return
    
    bengkelMarkers.forEach(marker => map.removeLayer(marker))
    bengkelMarkers = []
    
    bengkel.value.forEach(item => {
        if (!item.latitude || !item.longitude) return
        
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="marker-bengkel"><span>🔧</span><div class="marker-label">${item.nama.substring(0, 12)}</div></div>`,
            iconSize: [55, 55],
            popupAnchor: [0, -28]
        })
        
        const marker = L.marker([item.latitude, item.longitude], { icon })
            .bindPopup(`
                <div class="custom-popup">
                    <div class="popup-header bengkel">
                        <span class="popup-icon">🔧</span>
                        <div>
                            <h4>${item.nama}</h4>
                            <span class="popup-badge">Bengkel</span>
                        </div>
                    </div>
                    <div class="popup-body">
                        <div class="info-row"><span class="info-label">📍 Alamat</span><span class="info-value">${item.jalan}</span></div>
                        ${item.luas_lahan ? `<div class="info-row"><span class="info-label">📐 Luas Lahan</span><span class="info-value">${item.luas_lahan} m²</span></div>` : ''}
                        ${item.waktu_buka ? `<div class="info-row"><span class="info-label">⏰ Jam Operasional</span><span class="info-value">${item.waktu_buka} - ${item.waktu_tutup}</span></div>` : ''}
                        ${userLocation.value ? `<div class="info-row"><span class="info-label">📏 Jarak dari Anda</span><span class="info-value highlight">${calculateDistanceBetweenPoints(userLocation.value.lat, userLocation.value.lng, item.latitude, item.longitude).toFixed(2)} KM</span></div>` : ''}
                    </div>
                    <div class="popup-footer">
                        <button class="analyze-btn" onclick="window.analyzeLocation(${item.latitude}, ${item.longitude}, '${item.nama.replace(/'/g, "\\'")}', 'bengkel')">
                            📊 Analisis Lengkap
                        </button>
                    </div>
                </div>
            `, { className: 'modern-popup' })
        
        marker.on('click', () => {
            performComprehensiveAnalysis(item)
        })
        
        if (mapLayers.value.bengkel) marker.addTo(map)
        bengkelMarkers.push(marker)
    })
}

const addFasumToMap = () => {
    if (!map) return
    
    fasumMarkersArray.forEach(marker => map.removeLayer(marker))
    fasumMarkersArray = []
    
    fasum.value.forEach(item => {
        if (!item.latitude || !item.longitude) return
        
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="marker-fasum"><span>🏢</span><div class="marker-label">${item.nama.substring(0, 12)}</div></div>`,
            iconSize: [55, 55],
            popupAnchor: [0, -28]
        })
        
        const marker = L.marker([item.latitude, item.longitude], { icon })
            .bindPopup(`
                <div class="custom-popup">
                    <div class="popup-header fasum">
                        <span class="popup-icon">🏢</span>
                        <div>
                            <h4>${item.nama}</h4>
                            <span class="popup-badge">Fasilitas Umum</span>
                        </div>
                    </div>
                    <div class="popup-body">
                        <div class="info-row"><span class="info-label">📍 Alamat</span><span class="info-value">${item.jalan}</span></div>
                        ${userLocation.value ? `<div class="info-row"><span class="info-label">📏 Jarak dari Anda</span><span class="info-value highlight">${calculateDistanceBetweenPoints(userLocation.value.lat, userLocation.value.lng, item.latitude, item.longitude).toFixed(2)} KM</span></div>` : ''}
                    </div>
                    <div class="popup-footer">
                        <button class="analyze-btn" onclick="window.analyzeLocation(${item.latitude}, ${item.longitude}, '${item.nama.replace(/'/g, "\\'")}', 'fasum')">
                            📊 Analisis Lengkap
                        </button>
                    </div>
                </div>
            `, { className: 'modern-popup' })
        
        marker.on('click', () => {
            performComprehensiveAnalysis(item)
        })
        
        if (mapLayers.value.fasum) marker.addTo(map)
        fasumMarkersArray.push(marker)
    })
}

const getMarkerColor = (score) => {
    if (score >= 0.8) return '#10b981'
    if (score >= 0.6) return '#3b82f6'
    if (score >= 0.4) return '#f59e0b'
    return '#ef4444'
}

const addRecommendationsToMap = () => {
    if (!map) return
    
    recommendationMarkers.forEach(marker => map.removeLayer(marker))
    recommendationMarkers = []
    
    recommendations.value.forEach(rec => {
        let lat = rec.latitude
        let lng = rec.longitude
        
        if (!lat && rec.geometry_json) {
            try {
                const geo = typeof rec.geometry_json === 'string' ? JSON.parse(rec.geometry_json) : rec.geometry_json
                lng = geo.coordinates?.[0]
                lat = geo.coordinates?.[1]
            } catch(e) {}
        }
        
        if (!lat || !lng) return
        
        const score = rec.skor_akhir || rec.score || 0
        const color = getMarkerColor(score)
        
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="marker-recommendation" style="background:${color}"><span>⭐</span><div class="score-badge">${Math.round(score*100)}%</div><div class="marker-label">${rec.nama.substring(0, 10)}</div></div>`,
            iconSize: [60, 60],
            popupAnchor: [0, -30]
        })
        
        const marker = L.marker([lat, lng], { icon })
            .bindPopup(`
                <div class="custom-popup">
                    <div class="popup-header recommendation">
                        <span class="popup-icon">🏆</span>
                        <div>
                            <h4>${rec.nama}</h4>
                            <span class="popup-badge">Rekomendasi</span>
                        </div>
                    </div>
                    <div class="popup-body">
                        <div class="info-row"><span class="info-label">⭐ Skor Akhir</span><span class="info-value highlight">${(score*100).toFixed(1)}%</span></div>
                        <div class="progress-bar-custom"><div class="progress-fill" style="width:${score*100}%; background:${color}"></div></div>
                        ${userLocation.value ? `<div class="info-row"><span class="info-label">📏 Jarak dari Anda</span><span class="info-value highlight">${calculateDistanceBetweenPoints(userLocation.value.lat, userLocation.value.lng, lat, lng).toFixed(2)} KM</span></div>` : ''}
                    </div>
                    <div class="popup-footer">
                        <button class="analyze-btn" onclick="window.analyzeLocation(${lat}, ${lng}, '${rec.nama.replace(/'/g, "\\'")}', 'rekomendasi')">
                            📊 Analisis Lengkap
                        </button>
                    </div>
                </div>
            `, { className: 'modern-popup' })
        
        marker.on('click', () => {
            performComprehensiveAnalysis({ latitude: lat, longitude: lng, nama: rec.nama, skor_akhir: score })
        })
        
        if (mapLayers.value.recommendations) marker.addTo(map)
        recommendationMarkers.push(marker)
    })
}

const addBufferZones = () => {
    if (!map) return
    
    bufferLayers.forEach(layer => map.removeLayer(layer))
    bufferLayers = []
    
    bengkel.value.forEach(item => {
        if (!item.latitude || !item.longitude) return
        
        try {
            const point = turf.point([item.longitude, item.latitude])
            const buffered = turf.buffer(point, bufferRadius.value, { units: 'kilometers' })
            
            const layer = L.geoJSON(buffered, {
                style: {
                    color: '#f59e0b',
                    fillColor: '#fbbf24',
                    fillOpacity: 0.1,
                    weight: 2,
                    dashArray: '8, 6',
                    opacity: 0.8
                }
            })
            
            if (mapLayers.value.bufferZone) layer.addTo(map)
            bufferLayers.push(layer)
        } catch(e) {}
    })
}

const addWilayahPolygon = () => {
    if (!map) return
    
    wilayahLayers.forEach(layer => map.removeLayer(layer))
    wilayahLayers = []
    
    wilayahData.value.forEach(w => {
        try {
            let geometry = w.geometry || w.geom
            if (typeof geometry === 'string') geometry = JSON.parse(geometry)
            if (!geometry?.coordinates) return
            
            const layer = L.geoJSON(geometry, {
                style: {
                    color: '#3b82f6',
                    weight: 2,
                    fillColor: '#60a5fa',
                    fillOpacity: 0.15,
                    className: 'wilayah-polygon'
                },
                onEachFeature: (f, layerItem) => {
                    const nama = w.nama || w.nama_wilayah || 'Wilayah'
                    
                    let luas = 0
                    try {
                        const polygon = turf.polygon(geometry.coordinates)
                        luas = (turf.area(polygon) / 10000).toFixed(2)
                    } catch(e) {}
                    
                    layerItem.bindPopup(`
                        <div class="custom-popup wilayah-popup">
                            <div class="popup-header wilayah">
                                <span class="popup-icon">🗺️</span>
                                <div>
                                    <h4>${nama}</h4>
                                    <span class="popup-badge">Wilayah Kecamatan</span>
                                </div>
                            </div>
                            <div class="popup-body">
                                <div class="info-row"><span class="info-label">📐 Luas Wilayah</span><span class="info-value">${luas} Ha</span></div>
                            </div>
                        </div>
                    `)
                    
                    layerItem.on('mouseover', function() {
                        this.setStyle({ fillOpacity: 0.35, weight: 3, color: '#ef4444' })
                        this.bringToFront()
                    })
                    layerItem.on('mouseout', function() {
                        this.setStyle({ fillOpacity: 0.15, weight: 2, color: '#3b82f6' })
                    })
                }
            })
            
            if (mapLayers.value.wilayah) layer.addTo(map)
            wilayahLayers.push(layer)
        } catch(e) {}
    })
}

const addJalanToMap = () => {
    if (!map) return
    
    jalanLayers.forEach(layer => map.removeLayer(layer))
    jalanLayers = []
    
    jalanData.value.forEach(jalan => {
        try {
            let geo = jalan.geometry || jalan.geom
            if (typeof geo === 'string') geo = JSON.parse(geo)
            if (!geo) return
            
            const layer = L.geoJSON(geo, {
                style: {
                    color: '#94a3b8',
                    weight: 2.5,
                    opacity: 0.7
                },
                onEachFeature: (f, layerItem) => {
                    const namaJalan = jalan.properties?.nama_jalan || jalan.nama_jalan || 'Jalan'
                    layerItem.bindPopup(`
                        <div class="custom-popup">
                            <div class="popup-header jalan">
                                <span class="popup-icon">🛣️</span>
                                <div>
                                    <h4>${namaJalan}</h4>
                                    <span class="popup-badge">Jalan</span>
                                </div>
                            </div>
                        </div>
                    `)
                    
                    layerItem.on('mouseover', function() {
                        this.setStyle({ weight: 4, color: '#ef4444', opacity: 1 })
                    })
                    layerItem.on('mouseout', function() {
                        this.setStyle({ weight: 2.5, color: '#94a3b8', opacity: 0.7 })
                    })
                }
            })
            
            if (mapLayers.value.jalan) layer.addTo(map)
            jalanLayers.push(layer)
        } catch(e) {}
    })
}

/*
|--------------------------------------------------------------------------
| MEASURE TOOL
|--------------------------------------------------------------------------
*/

const startMeasureMode = () => {
    measureMode.value = true
    measurePoints = []
    measureDistance.value = null
    
    if (measureLine) {
        map.removeLayer(measureLine)
        measureLine = null
    }
    
    map.getContainer().style.cursor = 'crosshair'
    
    const tempClickListener = (e) => {
        const { lat, lng } = e.latlng
        const pointsOnly = measurePoints.filter(p => p.lat !== undefined)
        
        if (pointsOnly.length === 0) {
            measurePoints.push({ lat, lng })
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'measure-marker',
                    html: `<div class="measure-point"><span>A</span></div>`,
                    iconSize: [24, 24]
                })
            }).addTo(map)
            measurePoints.push(marker)
            
            L.marker([lat, lng], {
                icon: L.divIcon({
                    html: `<div style="background:#ef4444;width:12px;height:12px;border-radius:50%;border:2px solid white"></div>`,
                    iconSize: [12, 12]
                })
            }).addTo(map)
            
        } else if (pointsOnly.length === 1) {
            const p1 = pointsOnly[0]
            const dist = calculateDistanceBetweenPoints(p1.lat, p1.lng, lat, lng)
            measureDistance.value = dist
            
            const latlngs = [[p1.lat, p1.lng], [lat, lng]]
            measureLine = L.polyline(latlngs, { color: '#ef4444', weight: 3, dashArray: '5, 10' }).addTo(map)
            
            const midLat = (p1.lat + lat) / 2
            const midLng = (p1.lng + lng) / 2
            L.marker([midLat, midLng], {
                icon: L.divIcon({
                    className: 'distance-label',
                    html: `<div class="distance-bubble">${dist} KM</div>`,
                    iconSize: [60, 25]
                })
            }).addTo(measureLine)
            
            L.marker([lat, lng], {
                icon: L.divIcon({
                    html: `<div style="background:#ef4444;width:12px;height:12px;border-radius:50%;border:2px solid white"></div>`,
                    iconSize: [12, 12]
                })
            }).addTo(map)
            
            stopMeasureMode()
        }
    }
    
    map.on('click', tempClickListener)
    measureMode.value = tempClickListener
}

const stopMeasureMode = () => {
    if (measureMode.value && typeof measureMode.value === 'function') {
        map.off('click', measureMode.value)
    }
    measureMode.value = false
    map.getContainer().style.cursor = ''
}

const clearMeasure = () => {
    if (measureLine) {
        map.removeLayer(measureLine)
        measureLine = null
    }
    measurePoints.forEach(p => {
        if (p.remove) p.remove()
    })
    measurePoints = []
    measureDistance.value = null
}

/*
|--------------------------------------------------------------------------
| TOGGLE LAYER
|--------------------------------------------------------------------------
*/

const toggleLayer = (layer) => {
    if (!map) return
    
    if (layer === 'bengkel') {
        bengkelMarkers.forEach(m => mapLayers.value.bengkel ? m.addTo(map) : map.removeLayer(m))
    }
    if (layer === 'fasum') {
        fasumMarkersArray.forEach(m => mapLayers.value.fasum ? m.addTo(map) : map.removeLayer(m))
    }
    if (layer === 'recommendations') {
        recommendationMarkers.forEach(m => mapLayers.value.recommendations ? m.addTo(map) : map.removeLayer(m))
    }
    if (layer === 'bufferZone') {
        bufferLayers.forEach(l => mapLayers.value.bufferZone ? l.addTo(map) : map.removeLayer(l))
    }
    if (layer === 'wilayah') {
        if (mapLayers.value.wilayah) addWilayahPolygon()
        else wilayahLayers.forEach(l => map.removeLayer(l))
    }
    if (layer === 'jalan') {
        if (mapLayers.value.jalan) addJalanToMap()
        else jalanLayers.forEach(l => map.removeLayer(l))
    }
    if (layer === 'analysisResults') {
        if (!mapLayers.value.analysisResults) {
            clearAnalysisResults()
        }
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

const performSearch = () => {
    if (!searchQuery.value.trim() || !map) return
    
    const searchTerm = searchQuery.value.toLowerCase()
    let found = null
    
    const foundBengkel = bengkel.value.find(b => 
        b.nama.toLowerCase().includes(searchTerm) || b.jalan.toLowerCase().includes(searchTerm)
    )
    if (foundBengkel) found = foundBengkel
    
    if (!found) {
        const foundFasum = fasum.value.find(f => 
            f.nama.toLowerCase().includes(searchTerm) || f.jalan.toLowerCase().includes(searchTerm)
        )
        if (foundFasum) found = foundFasum
    }
    
    if (!found) {
        const foundRec = recommendations.value.find(r => 
            r.nama.toLowerCase().includes(searchTerm)
        )
        if (foundRec) found = foundRec
    }
    
    if (found) {
        performComprehensiveAnalysis(found)
        if (map) {
            map.flyTo([found.latitude, found.longitude], 16, { duration: 1 })
        }
    } else {
        alert(`Lokasi "${searchQuery.value}" tidak ditemukan`)
    }
}

const resetMap = () => {
    if (map) {
        map.flyTo([3.5952, 98.6638], 13, { duration: 1.5 })
    }
    clearMeasure()
    clearAnalysisResults()
    spatialAnalysis.value = null
}

/*
|--------------------------------------------------------------------------
| WATCH & TIMERS
|--------------------------------------------------------------------------
*/

watch(bufferRadius, () => {
    if (map) addBufferZones()
})

watch([bengkel, fasum, recommendations], () => {
    refreshNearestLocations()
}, { deep: true })

const startTipRotation = () => {
    let tipIndex = 0
    tipInterval = setInterval(() => {
        tipIndex = (tipIndex + 1) % tips.length
        currentTip.value = tips[tipIndex]
    }, 5000)
}

/*
|--------------------------------------------------------------------------
| MOUNTED
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await fetchAturanSIG()
    await fetchBengkelDanFasum()
    await fetchRecommendations()
    await fetchWilayah()
    await fetchJalan()
    
    setTimeout(() => {
        initMap()
    }, 100)
    
    startTipRotation()
})

onUnmounted(() => {
    if (map) map.remove()
    if (tipInterval) clearInterval(tipInterval)
})

// Expose ke window
if (typeof window !== 'undefined') {
    window.analyzeLocation = (lat, lng, name, type) => {
        performComprehensiveAnalysis({ latitude: lat, longitude: lng, nama: name, kategori: type })
    }
}

</script>

<template>
<div class="dashboard-container">
    <!-- WELCOME TOAST -->
    <div class="welcome-toast" v-if="showWelcomeToast">
        <div class="toast-content">
            <span class="toast-icon">🎉</span>
            <div>
                <h4>Selamat Datang di SIG Bengkel Medan Baru!</h4>
                <p v-if="userLocation">📍 Posisi Anda terdeteksi! Lihat jarak terdekat dari posisi Anda.</p>
                <p v-else>Analisis Spasial Lengkap | Klik "Lokasi Saya" untuk melihat jarak terdekat</p>
            </div>
            <button @click="showWelcomeToast = false" class="toast-close">✕</button>
        </div>
    </div>

    <!-- HEADER -->
    <div class="dashboard-header">
        <div class="logo-section">
            <div class="logo-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="3" fill="currentColor"/>
                </svg>
            </div>
            <div>
                <h1>SIG Bengkel Medan Baru</h1>
                <p class="subtitle">Sistem Informasi Geografis | Analisis Spasial Lanjutan</p>
            </div>
        </div>
        
        <div class="nav-menu">
            <Link href="/dashboard" :class="{ active: isActive('dashboard') }" class="nav-link">
                <span>🏠</span>
                <span>Dashboard</span>
            </Link>
            <Link href="/crud" :class="{ active: isActive('crud') }" class="nav-link">
                <span>📝</span>
                <span>Data Master</span>
            </Link>
        </div>
        
        <div class="user-info">
            <button class="location-btn" @click="getUserLocation" :disabled="isLocating">
                <span>📍</span>
                <span>{{ isLocating ? 'Mencari...' : 'Lokasi Saya' }}</span>
            </button>
            <div class="avatar">👤</div>
        </div>
    </div>

    <div class="dashboard-main">
        <!-- SIDEBAR -->
        <div class="dashboard-sidebar" :class="{ collapsed: !showAnalysisPanel }">
            <button class="sidebar-toggle" @click="showAnalysisPanel = !showAnalysisPanel">
                {{ showAnalysisPanel ? '◀' : '▶' }}
            </button>
            
            <div v-show="showAnalysisPanel" class="sidebar-content">
                <!-- SEARCH -->
                <div class="sidebar-section">
                    <div class="section-header">
                        <span class="section-icon">🔍</span>
                        <h3>Cepat Cari</h3>
                    </div>
                    <div class="search-container">
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            @keyup.enter="performSearch"
                            placeholder="Cari bengkel, fasum, atau rekomendasi..."
                            class="search-input"
                        />
                        <button @click="performSearch" class="search-btn">
                            <span>🔍</span>
                        </button>
                    </div>
                </div>

                <!-- USER LOCATION STATUS -->
                <div class="sidebar-section" v-if="userLocation">
                    <div class="section-header">
                        <span class="section-icon">📍</span>
                        <h3>Posisi Anda Saat Ini</h3>
                    </div>
                    <div class="user-location-card">
                        <div class="location-coord">
                            <span>Latitude:</span>
                            <strong>{{ userLocation.lat.toFixed(6) }}</strong>
                        </div>
                        <div class="location-coord">
                            <span>Longitude:</span>
                            <strong>{{ userLocation.lng.toFixed(6) }}</strong>
                        </div>
                        <div class="location-coord">
                            <span>Akurasi:</span>
                            <strong>{{ Math.round(userLocation.accuracy) }} meter</strong>
                        </div>
                        <div class="location-time">
                            🕐 {{ userLocation.timestamp }}
                        </div>
                    </div>
                </div>

                <div class="sidebar-section" v-else-if="locationError">
                    <div class="error-card">
                        <span>⚠️</span>
                        <p>{{ locationError }}</p>
                        <button @click="getUserLocation" class="retry-btn">Coba Lagi</button>
                    </div>
                </div>

                <!-- JARAK TERDEKAT DARI POSISI ANDA -->
                <div class="sidebar-section" v-if="userLocation && showNearestPanel">
                    <div class="section-header">
                        <span class="section-icon">📏</span>
                        <h3>Jarak Terdekat dari Anda</h3>
                        <button class="collapse-btn" @click="showNearestPanel = false">−</button>
                    </div>
                    
                    <!-- Bengkel Terdekat -->
                    <div class="nearest-group">
                        <div class="nearest-header">
                            <span>🔧 Bengkel Terdekat</span>
                            <span class="nearest-count">{{ nearestLocations.bengkel.length }}</span>
                        </div>
                        <div v-if="nearestLocations.bengkel.length === 0" class="empty-state">
                            Tidak ada data bengkel
                        </div>
                        <div v-for="item in nearestLocations.bengkel" :key="item.id" class="nearest-item" @click="selectNearestLocation(item)">
                            <div class="nearest-rank">{{ item.distance.toFixed(2) }} KM</div>
                            <div class="nearest-info">
                                <div class="nearest-name">{{ item.nama }}</div>
                                <div class="nearest-address">{{ item.jalan }}</div>
                            </div>
                            <div class="nearest-arrow">→</div>
                        </div>
                    </div>

                    <!-- Fasum Terdekat -->
                    <div class="nearest-group">
                        <div class="nearest-header">
                            <span>🏢 Fasilitas Umum Terdekat</span>
                            <span class="nearest-count">{{ nearestLocations.fasum.length }}</span>
                        </div>
                        <div v-if="nearestLocations.fasum.length === 0" class="empty-state">
                            Tidak ada data fasilitas umum
                        </div>
                        <div v-for="item in nearestLocations.fasum" :key="item.id" class="nearest-item" @click="selectNearestLocation(item)">
                            <div class="nearest-rank">{{ item.distance.toFixed(2) }} KM</div>
                            <div class="nearest-info">
                                <div class="nearest-name">{{ item.nama }}</div>
                                <div class="nearest-address">{{ item.jalan }}</div>
                            </div>
                            <div class="nearest-arrow">→</div>
                        </div>
                    </div>

                    <!-- Rekomendasi Terdekat -->
                    <div class="nearest-group">
                        <div class="nearest-header">
                            <span>⭐ Rekomendasi Terdekat</span>
                            <span class="nearest-count">{{ nearestLocations.recommendations.length }}</span>
                        </div>
                        <div v-if="nearestLocations.recommendations.length === 0" class="empty-state">
                            Tidak ada data rekomendasi
                        </div>
                        <div v-for="item in nearestLocations.recommendations" :key="item.id" class="nearest-item" @click="selectNearestLocation(item)">
                            <div class="nearest-rank">{{ item.distance.toFixed(2) }} KM</div>
                            <div class="nearest-info">
                                <div class="nearest-name">{{ item.nama }}</div>
                                <div class="nearest-score" v-if="item.skor_akhir">
                                    Skor: {{ (item.skor_akhir * 100).toFixed(0) }}%
                                </div>
                            </div>
                            <div class="nearest-arrow">→</div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-section" v-if="!userLocation && !locationError && !isLocating">
                    <button class="get-location-btn" @click="getUserLocation">
                        📍 Dapatkan Lokasi Saya
                    </button>
                    <p class="location-info-text">
                        Aktifkan lokasi untuk melihat jarak terdekat dari posisi Anda ke bengkel, fasum, dan rekomendasi.
                    </p>
                </div>

                <!-- TIPS -->
                <div class="sidebar-section tips-section">
                    <div class="section-header">
                        <span class="section-icon">💡</span>
                        <h3>Tips Pintar</h3>
                    </div>
                    <div class="tip-card">
                        <span class="tip-icon">{{ currentTip.icon }}</span>
                        <p>{{ currentTip.text }}</p>
                    </div>
                </div>

                <!-- ANALISIS SPASIAL LANJUTAN -->
                <div class="sidebar-section">
                    <div class="section-header">
                        <span class="section-icon">🔬</span>
                        <h3>Analisis Spasial Lanjutan</h3>
                    </div>
                    <div class="analysis-tools">
                        <button class="analysis-tool-btn point-polygon" @click="startPointInPolygonMode">
                            <span>📍</span> Point in Polygon
                        </button>
                        <button class="analysis-tool-btn point-line" @click="startPointInLineMode">
                            <span>🛣️</span> Point to Line
                        </button>
                        <button class="analysis-tool-btn line-polygon" @click="startLineInPolygonMode">
                            <span>📐</span> Line in Polygon
                        </button>
                        <button class="analysis-tool-btn clear-analysis" @click="clearAnalysisResults">
                            <span>🗑️</span> Bersihkan
                        </button>
                    </div>
                </div>

                <!-- HASIL POINT IN POLYGON -->
                <div v-if="pointInPolygonResult" class="sidebar-section result-card point-polygon-result">
                    <div class="section-header">
                        <span class="section-icon">📍</span>
                        <h3>Point in Polygon</h3>
                    </div>
                    <div class="result-content">
                        <div class="result-status" :class="pointInPolygonResult.isInside ? 'success' : 'danger'">
                            {{ pointInPolygonResult.isInside ? '✅ Berada di dalam wilayah' : '❌ Tidak berada di wilayah manapun' }}
                        </div>
                        <div v-if="pointInPolygonResult.wilayahList.length > 0" class="result-detail">
                            <strong>Wilayah:</strong>
                            <div class="wilayah-tags">
                                <span v-for="wil in pointInPolygonResult.wilayahList" :key="wil" class="wilayah-tag">
                                    {{ wil }}
                                </span>
                            </div>
                        </div>
                        <div class="result-meta">
                            <small>Koordinat: {{ pointInPolygonResult.latitude.toFixed(4) }}, {{ pointInPolygonResult.longitude.toFixed(4) }}</small>
                        </div>
                    </div>
                </div>

                <!-- HASIL POINT IN LINE -->
                <div v-if="pointInLineResult" class="sidebar-section result-card point-line-result">
                    <div class="section-header">
                        <span class="section-icon">🛣️</span>
                        <h3>Point to Line</h3>
                    </div>
                    <div class="result-content">
                        <div class="result-distance">
                            <span class="distance-label">Jarak ke jalan terdekat:</span>
                            <span class="distance-value">{{ pointInLineResult.nearestRoad.distance }} KM</span>
                        </div>
                        <div class="result-detail">
                            <strong>Jalan:</strong> {{ pointInLineResult.nearestRoad.name }}
                            <br>
                            <strong>Jenis:</strong> {{ pointInLineResult.nearestRoad.type }}
                        </div>
                        <div class="result-meta">
                            <small>Dianalisis dari {{ pointInLineResult.totalRoadsAnalyzed }} ruas jalan</small>
                        </div>
                    </div>
                </div>

                <!-- HASIL LINE IN POLYGON -->
                <div v-if="lineInPolygonResult && !lineInPolygonResult.error" class="sidebar-section result-card line-polygon-result">
                    <div class="section-header">
                        <span class="section-icon">📐</span>
                        <h3>Line in Polygon</h3>
                    </div>
                    <div class="result-content">
                        <div class="result-status" :class="lineInPolygonResult.isCompletelyInside ? 'success' : 'warning'">
                            {{ lineInPolygonResult.isCompletelyInside ? '✅ Jalan sepenuhnya di dalam wilayah' : '⚠️ Jalan sebagian di dalam wilayah' }}
                        </div>
                        <div class="result-stats">
                            <div class="stat-item">
                                <span class="stat-label">Panjang total:</span>
                                <span class="stat-value">{{ lineInPolygonResult.totalLength }} KM</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Di dalam wilayah:</span>
                                <span class="stat-value">{{ lineInPolygonResult.lengthInside }} KM</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Persentase:</span>
                                <span class="stat-value">{{ lineInPolygonResult.percentageInside }}%</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Titik potong:</span>
                                <span class="stat-value">{{ lineInPolygonResult.intersectionPoints }} titik</span>
                            </div>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" :style="{ width: lineInPolygonResult.percentageInside + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- HASIL ANALISIS KOMPREHENSIF -->
                <div v-if="spatialAnalysis" class="sidebar-section analysis-panel">
                    <div class="section-header">
                        <span class="section-icon">📊</span>
                        <h3>Analisis Lokasi</h3>
                        <span class="analysis-badge" :class="{
                            'high': spatialAnalysis.recommendationScore >= 80,
                            'mid': spatialAnalysis.recommendationScore >= 60 && spatialAnalysis.recommendationScore < 80,
                            'low': spatialAnalysis.recommendationScore < 60
                        }">
                            {{ spatialAnalysis.recommendationText }}
                        </span>
                    </div>
                    
                    <div class="analysis-location-info">
                        <div class="location-name">
                            <span class="type-icon">
                                {{ spatialAnalysis.locationType === 'bengkel' ? '🔧' : spatialAnalysis.locationType === 'fasum' ? '🏢' : '⭐' }}
                            </span>
                            <strong>{{ spatialAnalysis.locationName }}</strong>
                        </div>
                        <div class="location-coords">
                            <small>{{ spatialAnalysis.coordinates.lat }}, {{ spatialAnalysis.coordinates.lng }}</small>
                        </div>
                        <div v-if="spatialAnalysis.distanceFromUser" class="distance-from-user">
                            📏 <strong>{{ spatialAnalysis.distanceFromUser }} KM</strong> dari posisi Anda
                        </div>
                    </div>
                    
                    <div class="analysis-tabs">
                        <button class="tab-btn" :class="{ active: activeTab === 'analysis' }" @click="activeTab = 'analysis'">
                            📍 Analisis
                        </button>
                        <button class="tab-btn" :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">
                            ℹ️ Informasi
                        </button>
                    </div>
                    
                    <div v-show="activeTab === 'analysis'" class="analysis-content">
                        <div class="analysis-item">
                            <div class="analysis-icon">🗺️</div>
                            <div class="analysis-detail">
                                <div class="analysis-title">Point in Polygon</div>
                                <div class="analysis-value">
                                    <span v-if="spatialAnalysis.pointInPolygon.isInside" class="success-badge">
                                        ✅ {{ spatialAnalysis.pointInPolygon.wilayahList.join(', ') }}
                                    </span>
                                    <span v-else class="warning-badge">⚠️ Tidak dalam wilayah kecamatan</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="analysis-item" v-if="spatialAnalysis.pointToLine.distance">
                            <div class="analysis-icon">🛣️</div>
                            <div class="analysis-detail">
                                <div class="analysis-title">Point to Line (Jarak ke Jalan)</div>
                                <div class="analysis-value">
                                    <strong>{{ spatialAnalysis.pointToLine.roadName }}</strong><br>
                                    <span class="distance-value">{{ spatialAnalysis.pointToLine.distance }} KM</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="analysis-item" v-if="spatialAnalysis.nearestBengkel.distance">
                            <div class="analysis-icon">🔧</div>
                            <div class="analysis-detail">
                                <div class="analysis-title">Kompetitor Terdekat</div>
                                <div class="analysis-value">
                                    <strong>{{ spatialAnalysis.nearestBengkel.name }}</strong><br>
                                    <span class="distance-value">{{ spatialAnalysis.nearestBengkel.distance }} KM</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="analysis-item" v-if="spatialAnalysis.nearestFasum.distance">
                            <div class="analysis-icon">🏢</div>
                            <div class="analysis-detail">
                                <div class="analysis-title">Fasilitas Umum Terdekat</div>
                                <div class="analysis-value">
                                    <strong>{{ spatialAnalysis.nearestFasum.name }}</strong><br>
                                    <span class="distance-value">{{ spatialAnalysis.nearestFasum.distance }} KM</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="analysis-item">
                            <div class="analysis-icon">📊</div>
                            <div class="analysis-detail">
                                <div class="analysis-title">Kompetitor dalam Radius {{ bufferRadius }} KM</div>
                                <div class="analysis-value">
                                    <span :class="spatialAnalysis.bengkelInBuffer === 0 ? 'success-badge' : 'warning-badge'">
                                        {{ spatialAnalysis.bengkelInBuffer }} bengkel
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="score-card">
                            <div class="score-label">Skor Strategis</div>
                            <div class="score-circle">
                                <svg viewBox="0 0 100 100" width="120" height="120">
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                    <circle cx="50" cy="50" r="45" fill="none" 
                                        :stroke="spatialAnalysis.recommendationScore >= 80 ? '#10b981' : spatialAnalysis.recommendationScore >= 60 ? '#3b82f6' : '#f59e0b'"
                                        stroke-width="8"
                                        stroke-dasharray="283"
                                        :stroke-dashoffset="283 - (283 * spatialAnalysis.recommendationScore / 100)"
                                        stroke-linecap="round"
                                        transform="rotate(-90 50 50)"/>
                                </svg>
                                <div class="score-text">
                                    <span class="score-number">{{ spatialAnalysis.recommendationScore }}</span>
                                    <span class="score-percent">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-show="activeTab === 'info'" class="info-content">
                        <div class="info-item" v-if="spatialAnalysis.jamOperasional">
                            <span class="info-icon">⏰</span>
                            <div>
                                <div class="info-label">Jam Operasional</div>
                                <div class="info-value">{{ spatialAnalysis.jamOperasional }}</div>
                            </div>
                        </div>
                        <div class="info-item" v-if="spatialAnalysis.hariLibur">
                            <span class="info-icon">📅</span>
                            <div>
                                <div class="info-label">Hari Libur</div>
                                <div class="info-value">{{ spatialAnalysis.hariLibur }}</div>
                            </div>
                        </div>
                        <div class="info-item" v-if="spatialAnalysis.luasLahan">
                            <span class="info-icon">📐</span>
                            <div>
                                <div class="info-label">Luas Lahan</div>
                                <div class="info-value">{{ spatialAnalysis.luasLahan }} m²</div>
                            </div>
                        </div>
                        <div class="info-tip">
                            <span>💡</span>
                            <p>Klik marker lain untuk membandingkan lokasi</p>
                        </div>
                    </div>
                </div>

                <!-- LAYER CONTROL -->
                <div class="sidebar-section">
                    <div class="section-header">
                        <span class="section-icon">🎮</span>
                        <h3>Layer Control</h3>
                    </div>
                    <div class="layer-controls">
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.bengkel" @change="toggleLayer('bengkel')">
                            <span class="layer-badge bengkel-badge"></span>
                            <span>Bengkel <span class="layer-count">{{ bengkel.length }}</span></span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.fasum" @change="toggleLayer('fasum')">
                            <span class="layer-badge fasum-badge"></span>
                            <span>Fasilitas Umum <span class="layer-count">{{ fasum.length }}</span></span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.bufferZone" @change="toggleLayer('bufferZone')">
                            <span class="layer-badge buffer-badge"></span>
                            <span>Buffer Zone</span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.recommendations" @change="toggleLayer('recommendations')">
                            <span class="layer-badge rec-badge"></span>
                            <span>Rekomendasi <span class="layer-count">{{ recommendations.length }}</span></span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.wilayah" @change="toggleLayer('wilayah')">
                            <span class="layer-badge wilayah-badge"></span>
                            <span>Polygon Wilayah</span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.jalan" @change="toggleLayer('jalan')">
                            <span class="layer-badge jalan-badge"></span>
                            <span>Jaringan Jalan</span>
                        </label>
                        <label class="layer-item">
                            <input type="checkbox" v-model="mapLayers.analysisResults" @change="toggleLayer('analysisResults')">
                            <span class="layer-badge analysis-badge"></span>
                            <span>Hasil Analisis</span>
                        </label>
                    </div>
                </div>

                <!-- BUFFER RADIUS -->
                <div class="sidebar-section">
                    <div class="section-header">
                        <span class="section-icon">📏</span>
                        <h3>Radius Buffer</h3>
                    </div>
                    <div class="buffer-control">
                        <div class="radius-value">
                            <span class="value">{{ bufferRadius }}</span>
                            <span class="unit">KM</span>
                        </div>
                        <input type="range" min="0.1" max="3" step="0.1" v-model="bufferRadius" class="radius-slider">
                        <div class="radius-labels">
                            <span>0.1</span>
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                        </div>
                    </div>
                </div>

                <!-- STATISTIK -->
                <div class="sidebar-section">
                    <div class="section-header">
                        <span class="section-icon">📊</span>
                        <h3>Statistik</h3>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-icon">🔧</span>
                            <div>
                                <div class="stat-number">{{ bengkel.length }}</div>
                                <div class="stat-label">Total Bengkel</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-icon">🏢</span>
                            <div>
                                <div class="stat-number">{{ fasum.length }}</div>
                                <div class="stat-label">Fasilitas Umum</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-icon">⭐</span>
                            <div>
                                <div class="stat-number">{{ recommendations.length }}</div>
                                <div class="stat-label">Rekomendasi</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-icon">🗺️</span>
                            <div>
                                <div class="stat-number">{{ wilayahData.length }}</div>
                                <div class="stat-label">Wilayah</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-icon">🛣️</span>
                            <div>
                                <div class="stat-number">{{ jalanData.length }}</div>
                                <div class="stat-label">Ruas Jalan</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEGEND -->
                <div class="sidebar-section legend-mini" @click="showLegend = !showLegend">
                    <div class="section-header">
                        <span class="section-icon">📖</span>
                        <h3>Legenda</h3>
                        <span class="toggle-icon">{{ showLegend ? '▼' : '▲' }}</span>
                    </div>
                    <div v-show="showLegend" class="legend-items">
                        <div class="legend-row"><div class="legend-color bengkel"></div><span>Bengkel</span></div>
                        <div class="legend-row"><div class="legend-color fasum"></div><span>Fasilitas Umum</span></div>
                        <div class="legend-row"><div class="legend-color rec-high"></div><span>Rekomendasi (80-100%)</span></div>
                        <div class="legend-row"><div class="legend-color buffer"></div><span>Buffer Zone</span></div>
                        <div class="legend-row"><div class="legend-polygon"></div><span>Wilayah Kecamatan</span></div>
                        <div class="legend-row"><div class="legend-line"></div><span>Jalan</span></div>
                        <div class="legend-row"><div class="legend-color analysis-point"></div><span>Titik Analisis</span></div>
                        <div class="legend-row"><div class="legend-color intersection"></div><span>Titik Potong</span></div>
                        <div class="legend-row"><div class="legend-color user-loc"></div><span>Posisi Anda</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAP -->
        <div id="map" class="map-container"></div>
    </div>
    
    <!-- LOADING OVERLAY -->
    <div v-if="loading" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p>Memuat data spasial...</p>
    </div>
    
    <!-- ANIMATION OVERLAY -->
    <div v-if="isAnimating" class="animation-overlay">
        <div class="ripple"></div>
    </div>
</div>

</template>

<style scoped>
/* ============================================ */
/* STYLE LENGKAP (SAMA SEPERTI SEBELUMNYA) */
/* ============================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.dashboard-container {
    width: 100%;
    height: 100vh;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
}

/* WELCOME TOAST */
.welcome-toast {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 1000;
    animation: slideInRight 0.5s ease;
}

.toast-content {
    background: white;
    color: #1e293b;
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    border-left: 4px solid #10b981;
}

.toast-icon {
    font-size: 32px;
}

.toast-content h4 {
    margin: 0 0 4px 0;
    color: #1e293b;
}

.toast-content p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.toast-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #94a3b8;
    padding: 4px;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* HEADER */
.dashboard-header {
    height: 70px;
    background: rgba(255, 255, 255, 0.98);
    color: #1e293b;
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 10;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
}

.dashboard-header h1 {
    font-size: 20px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}

.subtitle {
    font-size: 11px;
    color: #94a3b8;
    margin: 2px 0 0 0;
}

.nav-menu {
    display: flex;
    gap: 8px;
    background: #f8fafc;
    padding: 4px;
    border-radius: 14px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 24px;
    text-decoration: none;
    color: #475569;
    font-weight: 500;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: white;
    color: #667eea;
    transform: translateY(-1px);
}

.nav-link.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.location-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.location-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.location-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.avatar {
    width: 42px;
    height: 42px;
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

/* MAIN */
.dashboard-main {
    display: flex;
    height: calc(100vh - 70px);
    padding: 20px;
    gap: 20px;
    position: relative;
}

/* SIDEBAR */
.dashboard-sidebar {
    width: 420px;
    background: rgba(255, 255, 255, 0.98);
    color: #1e293b;
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 20px;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.dashboard-sidebar.collapsed {
    width: 60px;
    padding: 20px 10px;
}

.sidebar-toggle {
    position: absolute;
    right: -12px;
    top: 20px;
    width: 28px;
    height: 28px;
    background: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 10;
    font-size: 12px;
}

.sidebar-content {
    overflow-y: auto;
}

.dashboard-sidebar::-webkit-scrollbar {
    width: 6px;
}

.dashboard-sidebar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.dashboard-sidebar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* SIDEBAR SECTIONS */
.sidebar-section {
    margin-bottom: 28px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
}

.section-icon {
    font-size: 20px;
}

.section-header h3 {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    flex: 1;
    margin: 0;
}

.collapse-btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #94a3b8;
}

/* USER LOCATION CARD */
.user-location-card {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 16px;
    padding: 12px;
}

.location-coord {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin-bottom: 8px;
}

.location-coord span {
    color: #047857;
}

.location-coord strong {
    color: #064e3b;
}

.location-time {
    font-size: 10px;
    color: #047857;
    text-align: right;
    margin-top: 8px;
}

.error-card {
    background: #fee2e2;
    border-radius: 16px;
    padding: 12px;
    text-align: center;
}

.error-card span {
    font-size: 24px;
    display: block;
    margin-bottom: 8px;
}

.error-card p {
    font-size: 12px;
    color: #dc2626;
    margin-bottom: 12px;
}

.retry-btn {
    padding: 6px 12px;
    background: #dc2626;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 12px;
    cursor: pointer;
}

.get-location-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 12px;
}

.location-info-text {
    font-size: 11px;
    color: #64748b;
    text-align: center;
}

/* NEAREST LOCATIONS */
.nearest-group {
    margin-bottom: 20px;
}

.nearest-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}

.nearest-count {
    background: #e2e8f0;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
}

.empty-state {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    font-size: 12px;
    color: #94a3b8;
}

.nearest-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.nearest-item:hover {
    background: #e0e7ff;
    transform: translateX(4px);
}

.nearest-rank {
    min-width: 55px;
    font-size: 12px;
    font-weight: 700;
    color: #10b981;
    background: #d1fae5;
    padding: 4px 8px;
    border-radius: 20px;
    text-align: center;
}

.nearest-info {
    flex: 1;
}

.nearest-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.nearest-address {
    font-size: 10px;
    color: #64748b;
}

.nearest-score {
    font-size: 10px;
    color: #f59e0b;
    margin-top: 2px;
}

.nearest-arrow {
    color: #94a3b8;
    font-size: 14px;
}

/* SEARCH */
.search-container {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #f8fafc;
    color: #1e293b;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-btn {
    padding: 0 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 14px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* TIPS */
.tips-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 16px;
    padding: 12px 16px;
    margin-bottom: 20px;
}

.tip-card {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tip-icon {
    font-size: 28px;
}

.tip-card p {
    margin: 0;
    font-size: 13px;
    color: #92400e;
    font-weight: 500;
}

/* ANALYSIS TOOLS */
.analysis-tools {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.analysis-tool-btn {
    padding: 12px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
}

.point-polygon {
    background: #10b981;
    color: white;
}

.point-line {
    background: #3b82f6;
    color: white;
}

.line-polygon {
    background: #8b5cf6;
    color: white;
}

.clear-analysis {
    background: #64748b;
    color: white;
}

.analysis-tool-btn:hover {
    transform: translateY(-2px);
    filter: brightness(1.05);
}

/* RESULT CARDS */
.result-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 12px;
    margin-bottom: 16px;
}

.point-polygon-result {
    border-left: 4px solid #10b981;
}

.point-line-result {
    border-left: 4px solid #3b82f6;
}

.line-polygon-result {
    border-left: 4px solid #8b5cf6;
}

.result-status {
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 10px;
    margin-bottom: 10px;
    text-align: center;
}

.result-status.success {
    background: #d1fae5;
    color: #059669;
}

.result-status.danger {
    background: #fee2e2;
    color: #dc2626;
}

.result-status.warning {
    background: #fed7aa;
    color: #c2410c;
}

.result-distance {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    background: white;
    border-radius: 10px;
    margin-bottom: 10px;
}

.distance-label {
    font-size: 12px;
    color: #64748b;
}

.distance-value {
    font-size: 18px;
    font-weight: 700;
    color: #ef4444;
}

.result-detail {
    font-size: 13px;
    color: #1e293b;
    margin-bottom: 10px;
    line-height: 1.5;
}

.result-meta {
    font-size: 10px;
    color: #94a3b8;
    text-align: right;
}

.wilayah-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.wilayah-tag {
    background: #dbeafe;
    color: #2563eb;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
}

.result-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 12px 0;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.stat-label {
    color: #64748b;
}

.stat-value {
    font-weight: 600;
    color: #1e293b;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #8b5cf6, #c084fc);
    border-radius: 10px;
    transition: width 0.5s ease;
}

/* ANALYSIS PANEL */
.analysis-panel {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 20px;
    padding: 0 0 16px 0;
    margin-bottom: 24px;
    overflow: hidden;
}

.analysis-badge {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.analysis-badge.high {
    background: #d1fae5;
    color: #059669;
}

.analysis-badge.mid {
    background: #dbeafe;
    color: #2563eb;
}

.analysis-badge.low {
    background: #fed7aa;
    color: #c2410c;
}

.analysis-location-info {
    padding: 0 16px 12px;
    border-bottom: 1px solid #e2e8f0;
}

.location-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.location-coords {
    margin-top: 6px;
    color: #94a3b8;
    font-size: 11px;
}

.distance-from-user {
    margin-top: 8px;
    font-size: 12px;
    color: #10b981;
    background: #d1fae5;
    padding: 6px 10px;
    border-radius: 10px;
    display: inline-block;
}

.type-icon {
    font-size: 24px;
}

.analysis-tabs {
    display: flex;
    padding: 0 16px;
    gap: 12px;
    margin: 12px 0;
}

.tab-btn {
    flex: 1;
    padding: 8px;
    background: none;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.tab-btn.active {
    background: #667eea;
    color: white;
}

.analysis-content, .info-content {
    padding: 0 16px;
}

.analysis-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.analysis-icon {
    font-size: 24px;
    min-width: 40px;
}

.analysis-detail {
    flex: 1;
}

.analysis-title {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.analysis-value {
    font-size: 13px;
    color: #1e293b;
    line-height: 1.4;
}

.success-badge {
    background: #d1fae5;
    color: #059669;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
}

.warning-badge {
    background: #fed7aa;
    color: #c2410c;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
}

.score-card {
    margin-top: 16px;
    text-align: center;
    padding: 16px;
    background: white;
    border-radius: 16px;
}

.score-label {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 12px;
}

.score-circle {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto;
}

.score-circle svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.score-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.score-number {
    font-size: 32px;
    font-weight: 800;
    color: #1e293b;
}

.score-percent {
    font-size: 14px;
    color: #64748b;
}

/* INFO CONTENT */
.info-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.info-icon {
    font-size: 20px;
}

.info-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.info-tip {
    background: #e0e7ff;
    border-radius: 12px;
    padding: 12px;
    display: flex;
    gap: 10px;
    margin-top: 16px;
    font-size: 12px;
    color: #4338ca;
}

/* LAYER CONTROLS */
.layer-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.layer-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.layer-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.layer-item input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.layer-badge {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.bengkel-badge { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.fasum-badge { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
.buffer-badge { background: #fbbf24; opacity: 0.6; }
.rec-badge { background: #10b981; }
.wilayah-badge { background: #3b82f6; }
.jalan-badge { background: #94a3b8; }
.analysis-badge { background: #ef4444; }

.layer-count {
    background: #e2e8f0;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    margin-left: 6px;
}

/* BUFFER */
.buffer-control {
    background: #f8fafc;
    padding: 16px;
    border-radius: 16px;
}

.radius-value {
    text-align: center;
    margin-bottom: 16px;
}

.value {
    font-size: 36px;
    font-weight: 800;
    color: #667eea;
}

.unit {
    font-size: 14px;
    color: #64748b;
}

.radius-slider {
    width: 100%;
    height: 6px;
    border-radius: 10px;
    background: #e2e8f0;
    -webkit-appearance: none;
}

.radius-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #667eea;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.radius-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 11px;
    color: #94a3b8;
}

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.stat-card {
    background: #f8fafc;
    padding: 10px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 24px;
}

.stat-number {
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
}

.stat-label {
    font-size: 9px;
    color: #64748b;
    text-transform: uppercase;
}

/* LEGEND */
.legend-mini {
    cursor: pointer;
}

.legend-items {
    margin-top: 12px;
}

.legend-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    font-size: 12px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.legend-color.bengkel { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.legend-color.fasum { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
.legend-color.rec-high { background: #10b981; }
.legend-color.buffer { background: #fbbf24; opacity: 0.5; }
.legend-color.analysis-point { background: #ef4444; }
.legend-color.intersection { background: #ef4444; width: 12px; height: 12px; border-radius: 50%; }
.legend-color.user-loc { background: #10b981; }

.legend-polygon {
    width: 20px;
    height: 16px;
    background: #60a5fa;
    opacity: 0.3;
    border: 2px solid #3b82f6;
    border-radius: 4px;
}

.legend-line {
    width: 30px;
    height: 3px;
    background: #94a3b8;
    border-radius: 2px;
}

.toggle-icon {
    font-size: 12px;
    color: #94a3b8;
}

/* MAP */
.map-container {
    flex: 1;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    background: #f0f0f0;
}

/* USER MARKER STYLES */
:deep(.user-marker-content) {
    position: relative;
    cursor: pointer;
}

:deep(.user-pulse) {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 50px;
    height: 50px;
    background: rgba(16, 185, 129, 0.4);
    border-radius: 50%;
    animation: userPulse 1.5s ease-out infinite;
}

:deep(.user-icon) {
    position: relative;
    width: 36px;
    height: 36px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 2px solid white;
    z-index: 1;
}

:deep(.user-label) {
    position: absolute;
    bottom: -22px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    white-space: nowrap;
}

@keyframes userPulse {
    0% {
        width: 36px;
        height: 36px;
        opacity: 0.8;
    }
    70% {
        width: 70px;
        height: 70px;
        opacity: 0;
    }
    100% {
        width: 36px;
        height: 36px;
        opacity: 0;
    }
}

/* MAP MARKER STYLES */
:deep(.custom-marker) {
    background: transparent !important;
}

:deep(.marker-bengkel) {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
}

:deep(.marker-bengkel):hover {
    transform: scale(1.1);
}

:deep(.marker-fasum) {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
}

:deep(.marker-fasum):hover {
    transform: scale(1.1);
}

:deep(.marker-recommendation) {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    animation: float 3s ease-in-out infinite;
}

:deep(.marker-recommendation):hover {
    transform: scale(1.1);
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

:deep(.marker-label) {
    position: absolute;
    bottom: -22px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    white-space: nowrap;
}

:deep(.score-badge) {
    position: absolute;
    bottom: -8px;
    right: -8px;
    background: white;
    color: #1e293b;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 12px;
}

/* Analysis Point Marker */
:deep(.analysis-point-marker) {
    background: transparent;
}

:deep(.analysis-point) {
    width: 40px;
    height: 40px;
    background: #ef4444;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    position: relative;
    animation: pulse 1.5s infinite;
}

:deep(.analysis-point.blue) {
    background: #3b82f6;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
}

:deep(.point-label) {
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    white-space: nowrap;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    70% {
        box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

/* Analysis Info Bubble */
:deep(.analysis-info-bubble) {
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(4px);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Analysis Popup */
.analysis-popup {
    padding: 10px;
    min-width: 250px;
}

.analysis-popup h4 {
    margin: 0 0 10px 0;
    color: #1e293b;
}

.analysis-popup p {
    margin: 6px 0;
    font-size: 12px;
}

/* POPUP */
:deep(.modern-popup) {
    border-radius: 16px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

:deep(.modern-popup .leaflet-popup-content-wrapper) {
    background: white;
    color: #1e293b;
    border-radius: 16px;
    padding: 0;
}

:deep(.modern-popup .leaflet-popup-content) {
    margin: 0;
    min-width: 260px;
}

.custom-popup {
    overflow: hidden;
    border-radius: 16px;
}

.popup-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
}

.popup-header.bengkel { border-left: 4px solid #f59e0b; }
.popup-header.fasum { border-left: 4px solid #3b82f6; }
.popup-header.recommendation { border-left: 4px solid #10b981; }
.popup-header.wilayah { border-left: 4px solid #3b82f6; }
.popup-header.jalan { border-left: 4px solid #94a3b8; }

.popup-icon {
    font-size: 24px;
}

.popup-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

.popup-badge {
    font-size: 10px;
    color: #64748b;
}

.popup-body {
    padding: 12px 16px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 12px;
}

.info-label {
    color: #64748b;
}

.info-value {
    font-weight: 600;
    color: #1e293b;
}

.info-value.highlight {
    color: #10b981;
    font-size: 14px;
}

.progress-bar-custom {
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.popup-footer {
    padding: 10px 16px 12px;
    border-top: 1px solid #f1f5f9;
}

.analyze-btn {
    width: 100%;
    padding: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 12px;
}

.analyze-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* MEASURE TOOL */
:deep(.measure-point) {
    width: 24px;
    height: 24px;
    background: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 12px;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

:deep(.distance-bubble) {
    background: #ef4444;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* LOADING */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255,255,255,0.2);
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-overlay p {
    color: white;
    margin-top: 16px;
    font-size: 14px;
}

/* ANIMATION */
.animation-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 999;
}

.ripple {
    position: absolute;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(102,126,234,0.3) 0%, rgba(102,126,234,0) 70%);
    animation: rippleExpand 1s ease-out;
}

@keyframes rippleExpand {
    from {
        transform: scale(0);
        opacity: 0.8;
    }
    to {
        transform: scale(20);
        opacity: 0;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* HIGHLIGHTED ROAD */
:deep(.highlighted-road) {
    filter: drop-shadow(0 0 4px #f59e0b);
    animation: glow 1s ease-in-out infinite alternate;
}

@keyframes glow {
    from {
        filter: drop-shadow(0 0 2px #f59e0b);
    }
    to {
        filter: drop-shadow(0 0 8px #f59e0b);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .dashboard-sidebar { width: 340px; }
    .dashboard-sidebar.collapsed { width: 50px; }
    .dashboard-header { padding: 0 16px; }
    .nav-link span:last-child { display: none; }
    .logo-section h1 { font-size: 16px; }
    .subtitle { display: none; }
    .stats-grid { grid-template-columns: 1fr; }
    .analysis-tools { grid-template-columns: 1fr; }
    .location-btn span:last-child { display: none; }
    .location-btn { padding: 8px 12px; }
}

:deep(.wilayah-polygon) {
    transition: all 0.3s ease;
}
</style>