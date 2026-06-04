<template>
  <div class="crud-container">
    <!-- Header dengan Navigasi -->
    <div class="crud-header">
      <div class="header-left">
        <Link href="/dashboard" class="btn-dashboard">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-7H9v7H4a2 2 0 0 1-2-2z"/>
          </svg>
          <span>Kembali ke Dashboard</span>
        </Link>
        <div class="header-title">
          <h2>Kelola Data Lokasi</h2>
          <p class="subtitle">Sistem SIG Penentuan Lokasi Bengkel</p>
        </div>
      </div>
    </div>

    <div class="crud-content">
      <!-- Sidebar Form -->
      <div class="sidebar">
        <!-- Panel Login Admin (jika belum terautentikasi) -->
        <div v-if="!session" class="login-panel">
          <h3>🔐 LOGIN ADMIN</h3>
          <p class="login-subtitle">Akses khusus admin untuk mengelola data lokasi</p>
          
          <div v-if="loginError" class="login-error">
            {{ loginError }}
          </div>
          
          <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label>Email Admin</label>
              <input v-model="email" type="email" placeholder="admin@example.com" required />
            </div>
            <div class="form-group">
              <label>Password</label>
              <input v-model="password" type="password" placeholder="••••••••" required />
            </div>
            <button type="submit" class="btn-login" :disabled="loginLoading">
              {{ loginLoading ? '⏳ MEMPROSES...' : '🔓 LOGIN ADMIN' }}
            </button>
          </form>
        </div>

        <!-- Form CRUD (jika sudah terautentikasi) -->
        <div v-else>
          <div class="admin-header">
            <span class="admin-badge">🟢 Mode Admin Aktif</span>
            <button @click="handleLogout" class="btn-logout">🔒 LOGOUT</button>
          </div>

          <form @submit.prevent="handleSubmit" class="crud-form">
            <h3>{{ editingId ? '✏️ EDIT DATA LOKASI' : '➕ TAMBAH DATA LOKASI BARU' }}</h3>
            
            <!-- NOTIFIKASI UPDATE -->
            <div v-if="updateStatus" class="notification" :class="updateStatus.type">
              {{ updateStatus.message }}
            </div>
            
            <div class="form-group">
              <label>Nama Lokasi / Bengkel <span class="required">*</span></label>
              <input v-model="form.nama" type="text" placeholder="Masukkan nama bengkel/fasum..." required />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kategori <span class="required">*</span></label>
                <select v-model="form.kategori" required>
                  <option value="bengkel">🏪 Bengkel</option>
                  <option value="fasum">🏢 Fasilitas Umum (Fasum)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status Resmi</label>
                <select v-model="form.is_resmi">
                  <option :value="true">✅ Resmi/Terbuka</option>
                  <option :value="false">❌ Tidak Resmi/Tutup</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Nama Jalan</label>
              <input v-model="form.jalan" type="text" placeholder="Masukkan nama jalan..." />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Luas Lahan (m²)</label>
                <input v-model.number="form.luas_lahan" type="number" step="1" placeholder="Luas lahan dalam m²" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Waktu Buka</label>
                <input v-model="form.waktu_buka" type="time" />
              </div>
              <div class="form-group">
                <label>Waktu Tutup</label>
                <input v-model="form.waktu_tutup" type="time" />
              </div>
            </div>

            <div class="form-group">
              <label>Hari Libur</label>
              <input v-model="form.hari_libur" type="text" placeholder="Misal: Minggu" />
            </div>

            <div class="form-group">
              <label>URL Foto</label>
              <input v-model="form.foto_url" type="url" placeholder="https://example.com/foto.jpg" />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Longitude <span class="required">*</span></label>
                <input v-model.number="form.longitude" type="number" step="any" readonly placeholder="Klik pada peta" required />
              </div>
              <div class="form-group">
                <label>Latitude <span class="required">*</span></label>
                <input v-model.number="form.latitude" type="number" step="any" readonly placeholder="Klik pada peta" required />
              </div>
            </div>

            <div class="form-group" v-if="editingId">
              <label>🔑 ID Data (Sedang Diedit)</label>
              <input :value="editingId" type="text" readonly disabled class="edit-id-input" />
            </div>

            <div class="btn-group">
              <button type="submit" class="btn-submit" :disabled="loading">
                {{ loading ? '⏳ MEMPROSES...' : (editingId ? '🔄 UPDATE LOKASI' : '💾 SIMPAN LOKASI') }}
              </button>
              <button type="button" @click="resetForm" class="btn-reset">🗑️ RESET</button>
              <button type="button" v-if="editingId" @click="cancelEdit" class="btn-cancel">❌ BATAL EDIT</button>
            </div>
          </form>
        </div>

        <!-- DAFTAR DATA LOKASI -->
        <div class="data-list">
          <h3>📋 DAFTAR LOKASI 
            <span class="total-count">{{ locations.length }} data</span>
            <button @click="fetchLocations" class="refresh-btn" title="Refresh data">🔄</button>
          </h3>
          
          <div class="list-header" :class="{ 'with-actions': session }">
            <span>🏷️ NAMA & INFORMASI</span>
            <span>📌 KATEGORI</span>
            <span>⚙️ AKSI</span>
          </div>
          
          <div class="list-items">
            <div v-for="item in locations" :key="item.id" class="list-item" :class="{ 'with-actions': session }">
              <div class="item-info">
                <span class="item-name">{{ item.nama }}</span>
                <div class="item-badges">
                  <span v-if="item.is_resmi" class="badge-resmi">✅ Resmi</span>
                  <span v-else class="badge-nonresmi">❌ Tidak Resmi</span>
                  <span v-if="item.jalan" class="badge-jalan">🛣️ {{ item.jalan }}</span>
                </div>
              </div>
              
              <div class="item-category-tag">
                <span class="tag" :class="item.kategori === 'bengkel' ? 'tag-bengkel' : 'tag-fasum'">
                  {{ item.kategori === 'bengkel' ? '🔧 BENGKEL' : '🏢 FASUM' }}
                </span>
              </div>
              
              <div class="item-actions">
                <button v-if="session" @click="editLocation(item)" class="btn-edit" title="Edit Data">
                  ✏️ EDIT
                </button>
                <button v-if="session" @click="deleteLocation(item.id)" class="btn-delete" title="Hapus Data">
                  🗑️ HAPUS
                </button>
                <button @click="focusOnMap(item)" class="btn-focus" title="Fokus ke Peta">
                  🗺️ FOKUS
                </button>
              </div>
            </div>
            
            <div v-if="locations.length === 0" class="empty-data">
              <p>📭 Belum ada data. Silakan tambah data baru.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- PETA -->
      <div class="map-wrapper">
        <div id="map" class="leaflet-map"></div>
        <div class="map-hint">
          <span>💡 <b>Tips:</b> Klik pada peta untuk mengambil koordinat secara otomatis.</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { createClient } from '@supabase/supabase-js'
import axios from 'axios';

// ============================================
// 📌 SUPABASE CONFIGURATION
// ============================================
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || 'https://your-project.supabase.co'
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'your-anon-key'

const supabase = createClient(supabaseUrl, supabaseAnonKey)

// ============================================
// 📌 STATE MANAGEMENT
// ============================================
const form = reactive({
  nama: '',
  jalan: '',
  kategori: 'bengkel',
  longitude: '',
  latitude: '',
  foto_url: '',
  luas_lahan: '',
  waktu_buka: '',
  waktu_tutup: '',
  hari_libur: '',
  is_resmi: true
})

const locations = ref([])
const editingId = ref(null)
const loading = ref(false)
const updateStatus = ref(null)
const map = ref(null)
const geojsonLayer = ref(null)
const tempMarker = ref(null)
const bengkelMarkers = L.layerGroup()
const fasumMarkers = L.layerGroup()

// ============================================
// 🔑 SUPABASE AUTHENTICATION
// ============================================
const session = ref(null)
const email = ref('')
const password = ref('')
const loginError = ref('')
const loginLoading = ref(false)

const handleLogin = async () => {
  loginLoading.value = true
  loginError.value = ''
  try {
    const { data, error } = await supabase.auth.signInWithPassword({
      email: email.value,
      password: password.value
    })
    if (error) throw error
    session.value = data.session
    updateStatus.value = { type: 'success', message: '🔑 Login berhasil sebagai Admin!' }
    setTimeout(() => { updateStatus.value = null }, 3000)
    email.value = ''
    password.value = ''
  } catch (err) {
    loginError.value = err.message || 'Gagal login. Periksa email & password.'
  } finally {
    loginLoading.value = false
  }
}

const handleLogout = async () => {
  await supabase.auth.signOut()
  session.value = null
  updateStatus.value = { type: 'success', message: '🔒 Keluar dari mode Admin.' }
  setTimeout(() => { updateStatus.value = null }, 3000)
}

// ============================================
// 📌 MARKER ICONS
// ============================================
const createCustomIcon = (color, iconName = '') => {
  return L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-content" style="background: ${color}"><span class="marker-icon">${iconName}</span></div>`,
    iconSize: [40, 40],
    popupAnchor: [0, -20]
  })
}

const bengkelIcon = createCustomIcon('#ef4444', '🔧')
const fasumIcon = createCustomIcon('#3b82f6', '🏢')
const greenIcon = createCustomIcon('#10b981', '📍')

// ============================================
// 📌 FUNGSI MEMBUAT GEOJSON POINT
// ============================================
const createGeoJsonPoint = (longitude, latitude) => {
  return {
    type: 'Point',
    coordinates: [parseFloat(longitude), parseFloat(latitude)]
  }
}

// ============================================
// 📌 READ DATA (SELECT)
// ============================================
const fetchLocations = async () => {
  try {
    console.log('🔄 Mengambil data dari tabel lokasi...')
    
    const { data, error } = await supabase
      .from('lokasi')
      .select('*')
      .order('created_at', { ascending: false })

    if (error) throw error

    locations.value = (data || []).map(item => {
      let longitude = null, latitude = null
      
      if (item.geom && typeof item.geom === 'object') {
        if (item.geom.type === 'Point' && item.geom.coordinates) {
          longitude = item.geom.coordinates[0]
          latitude = item.geom.coordinates[1]
        }
      }
      else if (item.geom && typeof item.geom === 'string') {
        const match = item.geom.match(/POINT\(([-\d.]+)\s+([-\d.]+)\)/i)
        if (match) {
          longitude = parseFloat(match[1])
          latitude = parseFloat(match[2])
        }
      }
      
      return { ...item, longitude, latitude }
    })
    
    console.log(`✅ Berhasil mengambil ${locations.value.length} data lokasi`)
    refreshMapMarkers()
    
  } catch (error) {
    console.error('❌ Error fetch:', error)
    alert('Gagal mengambil data: ' + error.message)
  }
}

// ============================================
// 📌 REFRESH MARKERS
// ============================================
const refreshMapMarkers = () => {
  if (!map.value) return

  bengkelMarkers.clearLayers()
  fasumMarkers.clearLayers()

  locations.value.forEach(item => {
    if (!item.latitude || !item.longitude) return

    const icon = item.kategori === 'bengkel' ? bengkelIcon : fasumIcon
    
    const popupContent = `
      <div class="custom-popup">
        <b>${item.kategori === 'bengkel' ? '🔧' : '🏢'} ${item.nama}</b><br>
        ${item.jalan ? `📍 ${item.jalan}<br>` : ''}
        ${item.luas_lahan ? `📐 Luas: ${item.luas_lahan} m²<br>` : ''}
        ${item.waktu_buka && item.waktu_tutup ? `⏰ Buka: ${item.waktu_buka} - ${item.waktu_tutup}<br>` : ''}
        ${item.is_resmi ? '✅ Status: Resmi' : '❌ Status: Tidak Resmi'}<br>
        <small>🆔 ID: ${item.id}</small>
      </div>
    `
    
    const marker = L.marker([item.latitude, item.longitude], { icon })
      .bindPopup(popupContent, { maxWidth: 300, className: 'modern-popup' })

    if (item.kategori === 'bengkel') {
      marker.addTo(bengkelMarkers)
    } else {
      marker.addTo(fasumMarkers)
    }
  })

  bengkelMarkers.addTo(map.value)
  fasumMarkers.addTo(map.value)
}

// ============================================
// 📌 CREATE DATA (INSERT)
// ============================================
const createLocation = async () => {
  try {
    const geojsonPoint = createGeoJsonPoint(form.longitude, form.latitude)
    
    const newData = {
      nama: form.nama,
      jalan: form.jalan || null,
      kategori: form.kategori,
      geom: geojsonPoint,
      foto_url: form.foto_url || null,
      luas_lahan: form.luas_lahan ? parseInt(form.luas_lahan) : null,
      waktu_buka: form.waktu_buka || null,
      waktu_tutup: form.waktu_tutup || null,
      hari_libur: form.hari_libur || null,
      is_resmi: form.is_resmi,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }

    const { error } = await supabase
      .from('lokasi')
      .insert([newData])

    if (error) throw error

    updateStatus.value = { type: 'success', message: '✅ Data berhasil ditambahkan!' }
    setTimeout(() => { updateStatus.value = null }, 3000)
    
    resetForm()
    await fetchLocations()
    
  } catch (error) {
    console.error('❌ Error create:', error)
    updateStatus.value = { type: 'error', message: '❌ Gagal menambah data: ' + error.message }
    setTimeout(() => { updateStatus.value = null }, 3000)
  }
}

// ============================================
// 📌 UPDATE DATA (UPDATE) - VERSI SEDERHANA
// ============================================
const updateLocation = async () => {
  if (!editingId.value) {
    alert('⚠️ Tidak ada data yang dipilih!')
    return
  }

  loading.value = true
  
  try {
    console.log('🔄 Mencoba update data ID:', editingId.value)
    console.log('📝 Data baru:', {
      nama: form.nama,
      jalan: form.jalan,
      kategori: form.kategori
    })
    
    // BUAT DATA UPDATE TANPA GEOM DULU UNTUK TESTING
    const updateData = {
      nama: form.nama,
      jalan: form.jalan || null,
      kategori: form.kategori,
      foto_url: form.foto_url || null,
      luas_lahan: form.luas_lahan ? parseInt(form.luas_lahan) : null,
      waktu_buka: form.waktu_buka || null,
      waktu_tutup: form.waktu_tutup || null,
      hari_libur: form.hari_libur || null,
      is_resmi: form.is_resmi,
      updated_at: new Date().toISOString()
    }

    console.log('📦 Payload yang dikirim:', updateData)

    // COBA UPDATE TANPA .select() DULU
    const { error } = await supabase
      .from('lokasi')
      .update(updateData)
      .eq('id', editingId.value)

    if (error) {
      console.error('❌ Error dari Supabase:', error)
      throw error
    }

    console.log('✅ Update berhasil dikirim ke Supabase!')
    
    // LANGSUNG REFRESH DATA DARI DATABASE
    await fetchLocations()
    
    // CEK APAKAH DATA BERUBAH
    const updatedItem = locations.value.find(l => l.id === editingId.value)
    if (updatedItem && updatedItem.nama === form.nama) {
      console.log('✅ Verifikasi: Data berhasil diupdate!')
      updateStatus.value = { type: 'success', message: `✅ Data berhasil diupdate menjadi "${form.nama}"!` }
    } else {
      console.warn('⚠️ Data mungkin belum terupdate, coba refresh manual')
      updateStatus.value = { type: 'warning', message: '⚠️ Update terkirim, silakan refresh manual jika belum berubah' }
    }
    
    setTimeout(() => { updateStatus.value = null }, 3000)
    resetForm()
    
  } catch (error) {
    console.error('❌ Error update:', error)
    updateStatus.value = { type: 'error', message: '❌ Gagal update: ' + error.message }
    setTimeout(() => { updateStatus.value = null }, 3000)
  } finally {
    loading.value = false
  }
}

// ============================================
// 📌 DELETE DATA (DELETE)
// ============================================
const deleteLocation = async (id) => {
  const itemToDelete = locations.value.find(item => item.id === id)
  
  if (!itemToDelete) {
    alert('Data tidak ditemukan!')
    return
  }
  
  if (!confirm(`⚠️ Yakin ingin menghapus "${itemToDelete.nama}"?`)) {
    return
  }

  loading.value = true
  
  try {
    const { error } = await supabase
      .from('lokasi')
      .delete()
      .eq('id', id)

    if (error) throw error

    updateStatus.value = { type: 'success', message: `✅ "${itemToDelete.nama}" berhasil dihapus!` }
    setTimeout(() => { updateStatus.value = null }, 3000)
    
    if (editingId.value === id) {
      resetForm()
    }
    
    await fetchLocations()
    
  } catch (error) {
    console.error('❌ Error delete:', error)
    updateStatus.value = { type: 'error', message: '❌ Gagal hapus: ' + error.message }
    setTimeout(() => { updateStatus.value = null }, 3000)
  } finally {
    loading.value = false
  }
}

// ============================================
// 📌 EDIT DATA
// ============================================
const editLocation = (item) => {
  console.log('✏️ EDIT DATA:', item)
  
  editingId.value = item.id
  
  form.nama = item.nama || ''
  form.jalan = item.jalan || ''
  form.kategori = item.kategori || 'bengkel'
  form.longitude = item.longitude || ''
  form.latitude = item.latitude || ''
  form.foto_url = item.foto_url || ''
  form.luas_lahan = item.luas_lahan || ''
  form.waktu_buka = item.waktu_buka || ''
  form.waktu_tutup = item.waktu_tutup || ''
  form.hari_libur = item.hari_libur || ''
  form.is_resmi = item.is_resmi !== undefined ? item.is_resmi : true

  if (map.value && item.latitude && item.longitude) {
    map.value.setView([item.latitude, item.longitude], 16)
    
    if (tempMarker.value) {
      tempMarker.value.setLatLng([item.latitude, item.longitude])
    } else {
      tempMarker.value = L.marker([item.latitude, item.longitude], { icon: greenIcon })
        .addTo(map.value)
        .bindPopup('📍 Lokasi yang sedang diedit')
        .openPopup()
    }
  }

  document.querySelector('.crud-form')?.scrollIntoView({ behavior: 'smooth' })
}

// ============================================
// 📌 HANDLE SUBMIT
// ============================================
const handleSubmit = async () => {
  if (!form.nama.trim()) {
    alert('⚠️ Nama lokasi harus diisi!')
    return
  }
  if (!form.latitude || !form.longitude) {
    alert('⚠️ Silakan klik pada peta untuk menentukan koordinat!')
    return
  }

  if (editingId.value) {
    await updateLocation()
  } else {
    await createLocation()
  }
}

// ============================================
// 📌 CANCEL EDIT
// ============================================
const cancelEdit = () => {
  resetForm()
}

// ============================================
// 📌 FOCUS ON MAP
// ============================================
const focusOnMap = (item) => {
  if (map.value && item.latitude && item.longitude) {
    map.value.setView([item.latitude, item.longitude], 17)
    
    const allMarkers = [...bengkelMarkers.getLayers(), ...fasumMarkers.getLayers()]
    const targetMarker = allMarkers.find(marker => {
      const latLng = marker.getLatLng()
      return Math.abs(latLng.lat - item.latitude) < 0.0001 && 
             Math.abs(latLng.lng - item.longitude) < 0.0001
    })
    
    if (targetMarker) {
      targetMarker.openPopup()
    }
  }
}

// ============================================
// 📌 RESET FORM
// ============================================
const resetForm = () => {
  form.nama = ''
  form.jalan = ''
  form.kategori = 'bengkel'
  form.longitude = ''
  form.latitude = ''
  form.foto_url = ''
  form.luas_lahan = ''
  form.waktu_buka = ''
  form.waktu_tutup = ''
  form.hari_libur = ''
  form.is_resmi = true
  editingId.value = null
  
  if (tempMarker.value && map.value) {
    map.value.removeLayer(tempMarker.value)
    tempMarker.value = null
  }
}

// ============================================
// 📌 FETCH WILAYAH POLYGON
// ============================================
const fetchWilayahPolygon = async () => {
  try {
    const response = await axios.get('/api/wilayah')
    
    if (geojsonLayer.value && map.value) {
      map.value.removeLayer(geojsonLayer.value)
    }
    
    geojsonLayer.value = L.geoJSON(response.data, {
      style: { color: '#2563eb', weight: 3, fillColor: '#3b82f6', fillOpacity: 0.15 }
    }).addTo(map.value)

    if (geojsonLayer.value.getBounds().isValid()) {
      map.value.fitBounds(geojsonLayer.value.getBounds())
    }
    
  } catch (error) {
    console.error('❌ Gagal memuat polygon:', error)
    if (map.value) {
      map.value.setView([3.5952, 98.6638], 13)
    }
  }
}

// ============================================
// 📌 INIT MAP
// ============================================
const initMap = () => {
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png'
  })

  map.value = L.map('map').setView([3.5952, 98.6638], 13)

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(map.value)

  map.value.on('click', (e) => {
    form.latitude = e.latlng.lat
    form.longitude = e.latlng.lng

    if (tempMarker.value) {
      tempMarker.value.setLatLng(e.latlng)
    } else {
      tempMarker.value = L.marker(e.latlng, { icon: greenIcon })
        .addTo(map.value)
        .bindPopup('📍 Titik baru yang dipilih')
        .openPopup()
    }
  })

  fetchWilayahPolygon()
  fetchLocations()
}

// ============================================
// 📌 LIFECYCLE
// ============================================
onMounted(async () => {
  console.log('🚀 Memulai aplikasi CRUD...')
  
  // Ambil session aktif jika ada
  const { data } = await supabase.auth.getSession()
  session.value = data?.session || null
  
  // Listen perubahan status auth
  supabase.auth.onAuthStateChange((_event, _session) => {
    session.value = _session
  })
  
  initMap()
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})
</script>

<style scoped>
/* Style sama seperti sebelumnya + tambahan */
.crud-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  font-family: 'Segoe UI', sans-serif;
  background-color: #f8fafc;
  color: #1e293b;
}

.crud-header {
  background: white;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.btn-dashboard {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.3s ease;
}

.btn-dashboard:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.header-title h2 {
  margin: 0;
  color: #1e293b;
}

.header-title .subtitle {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.875rem;
}

.crud-content {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.sidebar {
  width: 520px;
  background: white;
  padding: 24px;
  overflow-y: auto;
  border-right: 1px solid #e2e8f0;
}

/* NOTIFICATION */
.notification {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-weight: 500;
}

.notification.success {
  background: #d1fae5;
  color: #059669;
  border-left: 4px solid #059669;
}

.notification.error {
  background: #fee2e2;
  color: #dc2626;
  border-left: 4px solid #dc2626;
}

.notification.warning {
  background: #fef3c7;
  color: #d97706;
  border-left: 4px solid #d97706;
}

.crud-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-group {
  flex: 1;
}

label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.required {
  color: #ef4444;
}

input, select {
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.95rem;
  color: #1e293b;
  background-color: white;
}

input[readonly] {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

.edit-id-input {
  background-color: #e2e8f0;
  font-weight: 600;
  color: #1e293b;
}

.btn-group {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

.btn-submit {
  background-color: #2563eb;
  color: white;
  flex: 2;
  padding: 12px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-reset {
  background-color: #e2e8f0;
  color: #475569;
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel {
  background-color: #fef3c7;
  color: #d97706;
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.data-list {
  flex: 1;
  overflow-y: auto;
  margin-top: 16px;
}

.data-list h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.refresh-btn {
  background: #e2e8f0;
  border: none;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
}

.refresh-btn:hover {
  background: #cbd5e1;
}

.total-count {
  font-size: 0.75rem;
  font-weight: normal;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 12px;
}

.list-header {
  display: grid;
  grid-template-columns: 2fr 1fr 0.6fr;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 2px solid #e2e8f0;
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
}

.list-header.with-actions {
  grid-template-columns: 2fr 1fr 1.8fr;
}

.list-items {
  max-height: 450px;
  overflow-y: auto;
}

.list-item {
  display: grid;
  grid-template-columns: 2fr 1fr 0.6fr;
  gap: 8px;
  padding: 12px 8px;
  border-bottom: 1px solid #f1f5f9;
  align-items: center;
}

.list-item.with-actions {
  grid-template-columns: 2fr 1fr 1.8fr;
}

.list-item:hover {
  background: #f8fafc;
}

.item-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e293b;
}

.item-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.badge-resmi {
  font-size: 0.65rem;
  padding: 2px 8px;
  border-radius: 12px;
  background: #d1fae5;
  color: #059669;
}

.badge-nonresmi {
  font-size: 0.65rem;
  padding: 2px 8px;
  border-radius: 12px;
  background: #fee2e2;
  color: #dc2626;
}

.badge-jalan {
  font-size: 0.65rem;
  padding: 2px 8px;
  border-radius: 12px;
  background: #fef3c7;
  color: #d97706;
}

.tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  text-align: center;
}

.tag-bengkel {
  background: #fee2e2;
  color: #dc2626;
}

.tag-fasum {
  background: #dbeafe;
  color: #2563eb;
}

.item-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.btn-edit, .btn-delete, .btn-focus {
  padding: 8px 12px;
  font-size: 0.7rem;
  font-weight: 700;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-edit {
  background: #dbeafe;
  color: #2563eb;
}

.btn-edit:hover {
  background: #bfdbfe;
  transform: scale(1.05);
}

.btn-delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn-delete:hover {
  background: #fecaca;
  transform: scale(1.05);
}

.btn-focus {
  background: #e2e8f0;
  color: #475569;
}

.btn-focus:hover {
  background: #cbd5e1;
  transform: scale(1.05);
}

.empty-data {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
}

.map-wrapper {
  flex: 1;
  position: relative;
}

.leaflet-map {
  width: 100%;
  height: 100%;
}

.map-hint {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: white;
  padding: 12px 16px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  font-size: 0.875rem;
  border-left: 4px solid #3b82f6;
}

:deep(.custom-marker) {
  background: transparent;
}

:deep(.marker-content) {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  border: 2px solid white;
}

:deep(.marker-content:hover) {
  transform: scale(1.1);
}

:deep(.modern-popup) {
  border-radius: 12px;
  color: #1e293b;
}

.custom-popup {
  font-size: 12px;
  padding: 8px;
}

.custom-popup b {
  font-size: 14px;
}

.sidebar::-webkit-scrollbar,
.list-items::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track,
.list-items::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb,
.list-items::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

/* ============================================ */
/* 🔐 ADMIN LOGIN & DASHBOARD STYLES */
/* ============================================ */
.login-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.login-panel h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  text-align: center;
}

.login-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  text-align: center;
  margin: -8px 0 8px;
}

.login-error {
  padding: 10px 12px;
  background-color: #fee2e2;
  color: #dc2626;
  border-left: 4px solid #dc2626;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-login {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  padding: 11px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-login:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  margin-bottom: 20px;
  border-bottom: 1px dashed #cbd5e1;
}

.admin-badge {
  background-color: #d1fae5;
  color: #059669;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.btn-logout {
  background: #fee2e2;
  color: #dc2626;
  border: none;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: #fecaca;
}
</style>