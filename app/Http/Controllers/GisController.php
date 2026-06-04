<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class GisController extends Controller
{
    /**
     * Fungsi Helper universal untuk mengambil data dari Supabase berdasarkan nama tabel/view.
     */
    private function fetchFromSupabase($viewName)
    {
        try {
            $url = env('SUPABASE_URL') . '/rest/v1/' . $viewName;

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_ANON_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
            ])->get($url);

            return $response->json() ?? [];

        } catch (\Exception $e) {

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 1. Halaman Landing Page
     */
    public function home()
    {
        return Inertia::render('Index');
    }

    /**
     * 2. Halaman Dashboard (Peta & Statistik)
     */
    public function index()
    {
        // Ambil data lokasi bengkel
        $locations = $this->fetchFromSupabase('v_lokasi_peta');

        // Ambil data rekomendasi
        $recommendations = $this->fetchFromSupabase('v_rekomendasi_bengkel_saw');

        return Inertia::render('Dashboard', [
            'locations' => $locations,
            'recommendations' => $recommendations,
            'totalBengkel' => count($recommendations),
        ]);
    }
public function getJalan()
{
    $data = $this->fetchFromSupabase('v_namajalan_utama');

    $features = collect($data)->map(function ($jalan) {

        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $jalan['id'],
                'nama_jalan' => $jalan['nama_jalan'],
                'jenis' => $jalan['jenis'],
                'panjang' => $jalan['panjang'],
                
            ],
            'geometry' => $jalan['geom']
        ];
    });

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
}
    public function getRecommendations()
{
    $data = $this->fetchFromSupabase(
        'v_rekomendasi_bengkel_saw'
    );

    return response()->json($data);
}
    
        
    

public function getwilayah()
{
    $data = $this->fetchFromSupabase('v_wilayah_geojson');

    if (isset($data['error'])) {
        return response()->json(['type' => 'FeatureCollection', 'features' => []]);
    }

    $features = collect($data)->map(function ($wilayah) {
        // Ambil data mentah geometry dari database
        $geomMentah = $wilayah['geometry'] ?? $wilayah['geom'] ?? null;

        if (is_string($geomMentah)) {
            $geomMentah = json_decode($geomMentah, true);
        }

        // PERBAIKAN: Membalikkan koordinat dari [lat, lng] menjadi [lng, lat]
        $formattedCoordinates = [];
        if (is_array($geomMentah)) {
            foreach ($geomMentah as $coord) {
                // Pastikan koordinat memiliki isi [lat, lng]
                if (isset($coord[0]) && isset($coord[1])) {
                    // Dibalik menjadi [longitude, latitude] sesuai standar GeoJSON
                    $formattedCoordinates[] = [(float)$coord[1], (float)$coord[0]];
                }
            }
        }

        // 🟢 KALKULASI LUAS SECARA DINAMIS MENGGUNAKAN POSTGIS LOKAL
        $luasHektar = 0;
        if (!empty($formattedCoordinates)) {
            // 1. Susun array koordinat menjadi format text WKT (Well-Known Text) POLYGON
            $wktCoordinates = collect($formattedCoordinates)
                ->map(fn($c) => $c[0] . ' ' . $c[1])
                ->implode(', ');
            
            // 2. Pastikan poligon tertutup sempurna (titik akhir harus sama dengan titik awal)
            $firstCoord = $formattedCoordinates[0][0] . ' ' . $formattedCoordinates[0][1];
            $wktPolygon = "POLYGON(($wktCoordinates, $firstCoord))";

            try {
                // 3. Hitung luas menggunakan PostGIS lokal via DB::select dengan parameter binding yang aman
                // CASTING ke geography dilakukan agar output ST_Area otomatis dalam satuan Meter Persegi (m²), lalu dibagi 10.000 untuk Hektar
                $queryLuas = DB::select("
                    SELECT (ST_Area(ST_GeomFromText(?, 4326)::geography) / 10000) as luas
                ", [$wktPolygon]);

                $luasHektar = $queryLuas[0]->luas ?? 0;
            } catch (\Exception $e) {
                // Jika database lokal bermasalah atau belum mendukung PostGIS, set default ke 0
                $luasHektar = 0;
            }
        }

        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $wilayah['id'] ?? null,
                'nama' => $wilayah['nama'] ?? $wilayah['nama_wilayah'] ?? 'Wilayah Kecamatan',
                
                // 🟢 Masukkan data properti luas_ha yang sudah dibulatkan 2 angka di belakang koma
                'luas_ha' => round($luasHektar, 2),
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$formattedCoordinates] // Harus dibungkus array 3 dimensi [[ [lng, lat], [lng, lat] ]]
            ]
        ];
    })->filter(function ($f) {
        return !empty($f['geometry']['coordinates'][0]);
    })->values();

    return response()->json([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);
}
    /**
     * 4. Halaman Hasil Ranking
     */
   public function result()
{
    // Ambil data kandidat lokasi
    $data = $this->fetchFromSupabase('v_rekomendasi_bengkel_saw');

    $rankingData = [];

    foreach ($data as $item) {

        // contoh score dummy sementara
        $score = rand(60, 100) / 100;

        $rankingData[] = [
            'nama_lokasi' => $item['nama_lokasi'] ?? 'Lokasi',
            'score' => $score,
            'kepadatan_penduduk' => $item['kepadatan_penduduk'] ?? 0,
            'harga_sewa' => $item['harga_sewa'] ?? 0,
            'jumlah_kendaraan' => $item['jumlah_kendaraan'] ?? 0,
            'jarak_kompetitor' => $item['jarak_kompetitor'] ?? 0,
        ];
    }

    // sorting ranking
    usort($rankingData, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return Inertia::render('Result', [
        'rankingData' => $rankingData
    ]);
}
    public function getLocations()
    {
        $data = $this->fetchFromSupabase('v_lokasi_peta');

        return response()->json($data);
    }

    /**
     * API - Ambil data kompetitor
     */
    public function getCompetitors()
    {
        $data = $this->fetchFromSupabase('v_lokasi_peta');

        return response()->json($data);
    }
}