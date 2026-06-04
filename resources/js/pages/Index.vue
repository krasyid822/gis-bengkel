<template>
    <div class="min-h-screen bg-white font-sans overflow-x-hidden">
        
        <!-- Subtle Background Pattern -->
        <div class="fixed inset-0 z-0 opacity-[0.03] pointer-events-none">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23000000\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E'); background-repeat: repeat;"></div>
        </div>

        <!-- Modern Navigation -->
        <nav class="relative z-20 bg-white/90 backdrop-blur-md border-b border-gray-100 sticky top-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-3 cursor-pointer group">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-all">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-800 text-lg">SIG Bengkel</span>
                            <span class="text-blue-600 font-semibold text-lg">Medan Baru</span>
                        </div>
                    </div>
                    <div class="hidden md:flex space-x-8">
                        <a href="#" @click.prevent="scrollToSection('home')" class="text-gray-600 hover:text-blue-600 transition-colors text-sm font-medium">Beranda</a>
                        <a href="#" @click.prevent="scrollToSection('features')" class="text-gray-600 hover:text-blue-600 transition-colors text-sm font-medium">Fitur</a>
                        <a href="#" @click.prevent="scrollToSection('about')" class="text-gray-600 hover:text-blue-600 transition-colors text-sm font-medium">Tentang</a>
                        <a href="#" @click.prevent="scrollToSection('team')" class="text-gray-600 hover:text-blue-600 transition-colors text-sm font-medium">Tim</a>
                    </div>
                    <Link href="/dashboard" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium hover:from-blue-700 hover:to-blue-800 transition-all shadow-sm hover:shadow-md">
                        Buka Peta
                    </Link>
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            <!-- Hero Section -->
            <div id="home" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="animate-fade-in-up">
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium mb-6">
                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                            Sistem Informasi Geografis
                        </div>
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight tracking-tight">
                            Temukan Lokasi 
                            <span class="text-blue-600">Strategis</span>
                            <br>
                            Bengkel Motor
                        </h1>
                        <p class="text-gray-500 text-lg mt-6 leading-relaxed">
                            Sistem informasi geografis untuk analisis dan rekomendasi lokasi bengkel motor terbaik di Kecamatan Medan Baru menggunakan metode SAW (Simple Additive Weighting).
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 mt-8">
                            <Link href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                                Buka Peta Interaktif
                            </Link>
                            <a href="#features" @click.prevent="scrollToSection('features')" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:border-blue-600 hover:text-blue-600 transition-all">
                                Pelajari Fitur
                            </a>
                        </div>
                        <div class="flex items-center gap-8 mt-10 pt-6 border-t border-gray-100">
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 border-2 border-white flex items-center justify-center text-xs font-bold text-blue-600">RK</div>
                                <div class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-white flex items-center justify-center text-xs font-bold text-indigo-600">PA</div>
                                <div class="w-8 h-8 rounded-full bg-purple-100 border-2 border-white flex items-center justify-center text-xs font-bold text-purple-600">MR</div>
                                <div class="w-8 h-8 rounded-full bg-green-100 border-2 border-white flex items-center justify-center text-xs font-bold text-green-600">AN</div>
                                <div class="w-8 h-8 rounded-full bg-orange-100 border-2 border-white flex items-center justify-center text-xs font-bold text-orange-600">KG</div>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-semibold text-gray-700">{{ totalBengkel }}+</span> Lokasi Terdata
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-semibold text-gray-700">{{ totalKriteria }}</span> Kriteria Analisis
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-semibold text-gray-700">{{ akurasiSistem }}%</span> Akurasi
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Image - Modern Workshop -->
                    <div class="relative animate-fade-in-up-delay">
                        <div class="absolute -top-4 -right-4 w-32 h-32 bg-blue-100 rounded-full blur-3xl opacity-60 -z-10"></div>
                        <div class="absolute -bottom-4 -left-4 w-40 h-40 bg-indigo-100 rounded-full blur-3xl opacity-60 -z-10"></div>
                        <div class="bg-gray-50 rounded-2xl overflow-hidden shadow-xl border border-gray-100">
                            <img src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=800&h=600&fit=crop" alt="Modern Motorcycle Workshop" class="w-full h-80 object-cover">
                            <div class="p-5 bg-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">Workshop Area</p>
                                        <p class="font-semibold text-gray-800">Visualisasi Lokasi Bengkel</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        <span class="text-xs text-gray-500">Live Map</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -bottom-5 -left-5 bg-white rounded-xl shadow-lg px-4 py-2 flex items-center gap-3 border border-gray-100">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Akurasi Rekomendasi</p>
                                <p class="font-bold text-gray-800">{{ akurasiSistem }}% <span class="text-xs font-normal text-gray-500">dengan SAW</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div id="features" class="bg-gray-50 py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-14">
                        <span class="text-blue-600 text-sm font-semibold uppercase tracking-wide">Fitur Unggulan</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                            Dirancang untuk Analisis <span class="text-blue-600">Optimal</span>
                        </h2>
                        <p class="text-gray-500 max-w-2xl mx-auto">
                            Sistem kami menyediakan berbagai fitur canggih untuk membantu Anda menemukan lokasi bengkel terbaik
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="group bg-white rounded-xl p-7 shadow-sm border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5 group-hover:bg-blue-100 transition-all">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Visualisasi Peta</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">
                                Peta interaktif dengan {{ totalBengkel }} marker lokasi, buffer zone, dan polygon wilayah untuk analisis spasial yang akurat.
                            </p>
                        </div>
                        <div class="group bg-white rounded-xl p-7 shadow-sm border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5 group-hover:bg-blue-100 transition-all">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Analisis Buffer Zone</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">
                                Analisis radius persaingan dengan {{ totalKompetitor }} kompetitor menggunakan buffer yang dapat diatur 0.1-3 kilometer.
                            </p>
                        </div>
                        <div class="group bg-white rounded-xl p-7 shadow-sm border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-1">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5 group-hover:bg-blue-100 transition-all">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Perankingan Lokasi</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">
                                Sistem memberikan {{ totalRekomendasi }} rekomendasi lokasi terbaik berdasarkan perhitungan multi-kriteria yang akurat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About Section -->
            <div id="about" class="py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-2 gap-16 items-center">
                        <div class="order-2 md:order-1">
                            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wide">Tentang Sistem</span>
                            <h2 class="text-3xl font-bold text-gray-900 mt-2 mb-5">
                                SIG Bengkel Medan Baru
                            </h2>
                            <p class="text-gray-500 leading-relaxed mb-6">
                                Sistem Informasi Geografis ini dirancang khusus untuk membantu pengusaha dan masyarakat dalam menemukan lokasi strategis untuk membuka bengkel motor di Kecamatan Medan Baru. 
                                Terdapat <span class="font-semibold text-blue-600">{{ totalBengkel }} lokasi bengkel</span> yang terdata dalam sistem kami.
                            </p>
                            <div class="space-y-4 mb-8">
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 mt-0.5 text-blue-600">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 text-sm">Terintegrasi dengan data geospasial Kecamatan Medan Baru</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 mt-0.5 text-blue-600">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 text-sm">Menggunakan metode SAW dengan {{ totalKriteria }} kriteria penilaian</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-5 h-5 mt-0.5 text-blue-600">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 text-sm">Visualisasi peta yang mudah dipahami dan interaktif</p>
                                </div>
                            </div>
                            <Link href="/dashboard" class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all">
                                Jelajahi Peta
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </Link>
                        </div>
                        <div class="order-1 md:order-2">
                            <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-100">
                                <img src="https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=600&h=450&fit=crop" alt="Motorcycle Workshop" class="w-full h-72 object-cover">
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-5">
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <span class="text-2xl font-bold text-blue-600">{{ totalBengkel }}+</span>
                                    <p class="text-xs text-gray-500">Lokasi Terdata</p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <span class="text-2xl font-bold text-blue-600">{{ totalKriteria }}</span>
                                    <p class="text-xs text-gray-500">Kriteria Analisis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SAW Method Explanation -->
            <div class="bg-blue-50 py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <span class="text-blue-600 text-sm font-semibold uppercase tracking-wide">Metode SAW</span>
                        <h2 class="text-3xl font-bold text-gray-900 mt-2 mb-4">
                            Cara Kerja <span class="text-blue-600">Simple Additive Weighting</span>
                        </h2>
                        <p class="text-gray-500 max-w-2xl mx-auto">
                            Metode SAW melakukan normalisasi matriks keputusan dan perankingan berdasarkan {{ totalKriteria }} kriteria dengan bobot yang telah ditentukan
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-blue-600">1</span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2">Identifikasi Kriteria</h3>
                            <p class="text-xs text-gray-500">Menentukan {{ totalKriteria }} kriteria dengan bobot masing-masing</p>
                        </div>
                        <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-blue-600">2</span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2">Normalisasi Matriks</h3>
                            <p class="text-xs text-gray-500">Mengubah nilai kriteria ke skala 0-1</p>
                        </div>
                        <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-blue-600">3</span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2">Perhitungan Skor</h3>
                            <p class="text-xs text-gray-500">Mengalikan nilai normalisasi dengan bobot</p>
                        </div>
                        <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-xl font-bold text-blue-600">4</span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2">Perankingan</h3>
                            <p class="text-xs text-gray-500">Mengurutkan {{ totalBengkel }} lokasi berdasarkan skor tertinggi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Section with Photos -->
            <div id="team" class="py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-14">
                        <span class="text-blue-600 text-sm font-semibold uppercase tracking-wide">Tim Pengembang</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                            Dibangun oleh <span class="text-blue-600">Profesional</span>
                        </h2>
                        <p class="text-gray-500 max-w-2xl mx-auto">
                            Mahasiswa Teknologi Rekayasa Perangkat Lunak yang berdedikasi
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div v-for="member in teamMembers" :key="member.name" class="group bg-white rounded-xl p-6 text-center shadow-sm border border-gray-100 hover:shadow-xl transition-all hover:-translate-y-2">
                            <div class="relative w-28 h-28 mx-auto mb-4">
                                <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 opacity-0 group-hover:opacity-100 transition-opacity blur-lg"></div>
                                <img 
                                    :src="member.photo" 
                                    :alt="member.name"
                                    class="relative w-full h-full rounded-full object-cover border-4 border-white shadow-md group-hover:scale-105 transition-transform duration-300"
                                />
                                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-2 border-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                    <span class="text-white text-xs">✓</span>
                                </div>
                            </div>
                            <h3 class="font-semibold text-gray-800 text-lg">{{ member.name }}</h3>
                            <p class="text-xs text-blue-600 font-semibold mt-0.5">{{ member.role }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ member.desc }}</p>
                            <div class="mt-4 flex justify-center gap-3">
                                <a href="#" class="text-gray-400 hover:text-blue-500 transition-all text-sm">LinkedIn</a>
                                <a href="#" class="text-gray-400 hover:text-blue-500 transition-all text-sm">GitHub</a>
                                <a href="#" class="text-gray-400 hover:text-blue-500 transition-all text-sm">Email</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Banner -->
            <div class="py-20 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="bg-white rounded-2xl border border-gray-200 p-10 md:p-16 text-center shadow-sm">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-50 rounded-full mb-6">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">
                            Siap Menemukan Lokasi Strategis?
                        </h2>
                        <p class="text-gray-500 mb-8 max-w-2xl mx-auto">
                            Gunakan sistem kami untuk menganalisis dan menentukan lokasi bengkel terbaik di Kecamatan Medan Baru. 
                            Tersedia <span class="font-semibold text-blue-600">{{ totalBengkel }} lokasi</span> yang siap dianalisis.
                        </p>
                        <Link href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow-md">
                            Mulai Analisis Sekarang
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="border-t border-gray-200 py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-4 gap-8">
                        <div>
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-800">SIG Bengkel Medan Baru</span>
                            </div>
                            <p class="text-gray-500 text-sm leading-relaxed">
                                Sistem Informasi Geografis untuk analisis lokasi bengkel motor di Kecamatan Medan Baru.
                            </p>
                            <div class="flex gap-4 mt-5">
                                <span class="text-gray-400 cursor-pointer hover:text-blue-600 transition-all text-xl">📘</span>
                                <span class="text-gray-400 cursor-pointer hover:text-blue-600 transition-all text-xl">🐦</span>
                                <span class="text-gray-400 cursor-pointer hover:text-blue-600 transition-all text-xl">📷</span>
                                <span class="text-gray-400 cursor-pointer hover:text-blue-600 transition-all text-xl">🔗</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-4 text-sm">Tautan</h4>
                            <ul class="space-y-2 text-sm">
                                <li><a href="#" @click.prevent="scrollToSection('home')" class="text-gray-500 hover:text-blue-600 transition-colors">Beranda</a></li>
                                <li><a href="#" @click.prevent="scrollToSection('features')" class="text-gray-500 hover:text-blue-600 transition-colors">Fitur</a></li>
                                <li><a href="#" @click.prevent="scrollToSection('about')" class="text-gray-500 hover:text-blue-600 transition-colors">Tentang</a></li>
                                <li><a href="#" @click.prevent="scrollToSection('team')" class="text-gray-500 hover:text-blue-600 transition-colors">Tim</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-4 text-sm">Teknologi</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="text-gray-500 flex items-center gap-2"><span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span> Vue.js 3</li>
                                <li class="text-gray-500 flex items-center gap-2"><span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span> Leaflet.js</li>
                                <li class="text-gray-500 flex items-center gap-2"><span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span> Tailwind CSS</li>
                                <li class="text-gray-500 flex items-center gap-2"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Supabase</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-4 text-sm">Kontak</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="text-gray-500 flex items-center gap-2">📧 sig.bengkel@example.com</li>
                                <li class="text-gray-500 flex items-center gap-2">📞 (061) 1234-5678</li>
                                <li class="text-gray-500 flex items-start gap-2">📍 Medan Baru, Sumatera Utara</li>
                            </ul>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 mt-10 pt-8 text-center text-gray-400 text-xs">
                        <p>&copy; 2026 SIG Bengkel Medan Baru. All rights reserved.</p>
                        <p class="mt-2">Dibangun dengan <span class="text-red-500">❤️</span> untuk Kecamatan Medan Baru</p>
                    </div>
                </div>
            </footer>
        </main>
        
        <!-- Loading Overlay -->
        <div v-if="loading" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="text-center">
                <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-gray-600">Memuat data...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

// State
const loading = ref(true);
const totalBengkel = ref(0);
const totalKompetitor = ref(0);
const totalFasum = ref(0);
const totalRekomendasi = ref(0);
const totalKriteria = ref(5);
const akurasiSistem = ref(95);

// Team Members Data with Photos
const teamMembers = ref([
    { 
        name: 'Rasyid Kurniawan', 
        role: 'Project Leader', 
        icon: '👨‍💻', 
        color: 'from-blue-400 to-blue-600', 
        desc: 'Koordinasi tim & manajemen proyek',
        photo: '/team/Rasyid.jpeg'
    },
    { 
        name: 'Putri Aprilia', 
        role: 'Web Dev', 
        icon: '🎨', 
        color: 'from-pink-400 to-pink-600', 
        desc: 'Website development',
        photo: '/team/Putri.jpeg'
    },
    { 
        name: 'M.Rizky Andika', 
        role: 'Tester', 
        icon: '⚙️', 
        color: 'from-green-400 to-green-600', 
        desc: 'Tester App & Web',
        photo: '/team/Rizky.jpeg'
    },
    { 
        name: 'Azura Tyan Naura', 
        role: 'Data Analyst', 
        icon: '📊', 
        color: 'from-purple-400 to-purple-600', 
        desc: 'Analisis data & perhitungan SAW',
        photo: '/team/Azura.jpeg'
    },
    { 
        name: 'Kevin Goraja', 
        role: 'GIS Specialist', 
        icon: '🗺️', 
        color: 'from-orange-400 to-orange-600', 
        desc: 'Peta & analisis spasial',
        photo: '/team/Kevin.jpeg'
    }
]);

// Fetch data from API
const fetchData = async () => {
    try {
        loading.value = true;
        
        // Fetch locations data
        const locationsResponse = await axios.get('/api/locations');
        const locations = locationsResponse.data || [];
        
        // Count bengkel (including kompetitor)
        const bengkelData = locations.filter(loc => 
            loc.kategori === 'bengkel' || loc.kategori === 'kompetitor'
        );
        totalBengkel.value = bengkelData.length;
        
        // Count kompetitor
        totalKompetitor.value = locations.filter(loc => loc.kategori === 'kompetitor').length;
        
        // Count fasum
        totalFasum.value = locations.filter(loc => loc.kategori === 'fasum').length;
        
        // Fetch recommendations
        try {
            const recResponse = await axios.get('/api/recommendations');
            const recommendations = recResponse.data || [];
            totalRekomendasi.value = recommendations.length;
        } catch (e) {
            console.log('Recommendations not available');
            totalRekomendasi.value = 0;
        }
        
        // Fetch criteria count
        try {
            const kriteriaResponse = await axios.get('/api/kriteria');
            const kriteria = kriteriaResponse.data || [];
            if (kriteria.length > 0) {
                totalKriteria.value = kriteria.length;
            }
        } catch (e) {
            console.log('Kriteria not available, using default 5');
        }
        
        // Fetch SAW settings for accuracy
        try {
            const aturanResponse = await axios.get('/api/aturan-sig');
            const aturan = aturanResponse.data || [];
            const akurasiSetting = aturan.find(item => item.nama_kriteria === 'Akurasi Sistem');
            if (akurasiSetting && akurasiSetting.nilai) {
                akurasiSistem.value = akurasiSetting.nilai;
            }
        } catch (e) {
            console.log('Using default accuracy 95%');
        }
        
    } catch (error) {
        console.error('Error fetching data:', error);
        // Use fallback data
        totalBengkel.value = 25;
        totalKompetitor.value = 8;
        totalFasum.value = 12;
        totalRekomendasi.value = 10;
    } finally {
        loading.value = false;
    }
};

// Smooth scroll for anchor links
const scrollToSection = (sectionId) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

.animate-fade-in-up-delay {
    animation: fadeInUp 0.6s ease-out 0.2s forwards;
    opacity: 0;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

html {
    scroll-behavior: smooth;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>