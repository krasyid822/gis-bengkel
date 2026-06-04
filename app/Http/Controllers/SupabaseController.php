<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseController extends Controller
{
    protected $supabaseUrl;
    protected $supabaseKey;
    
    public function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->supabaseKey = env('SUPABASE_ANON_KEY');
    }
    
    public function getLocations()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/v_lokasi_peta?select=*');
            
            if ($response->successful()) {
                $locations = $response->json();
                $transformed = array_map(function($location) {
                    if (isset($location['geom']) && !empty($location['geom'])) {
                        preg_match('/POINT\(([^ ]+) ([^)]+)\)/', $location['geom'], $matches);
                        if (count($matches) === 3) {
                            $location['longitude'] = floatval($matches[1]);
                            $location['latitude'] = floatval($matches[2]);
                        }
                    }
                    return $location;
                }, $locations);
                
                return response()->json($transformed);
            }
            return response()->json(['error' => 'Failed to fetch locations'], $response->status());
        } catch (\Exception $e) {
            Log::error('Supabase locations error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    public function getCompetitors()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/v_lokasi_peta?select=*&kategori=eq.Kompetitor');
            
            if ($response->successful()) {
                $competitors = $response->json();
                $transformed = array_map(function($competitor) {
                    if (isset($competitor['geom']) && !empty($competitor['geom'])) {
                        preg_match('/POINT\(([^ ]+) ([^)]+)\)/', $competitor['geom'], $matches);
                        if (count($matches) === 3) {
                            $competitor['longitude'] = floatval($matches[1]);
                            $competitor['latitude'] = floatval($matches[2]);
                        }
                    }
                    return $competitor;
                }, $competitors);
                
                return response()->json($transformed);
            }
            return response()->json(['error' => 'Failed to fetch competitors'], $response->status());
        } catch (\Exception $e) {
            Log::error('Supabase competitors error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    public function getBoundary()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/v_lokasi_peta?select=geom&kategori=eq.Batas_Wilayah');
            
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['geom'])) {
                    // Convert WKT to GeoJSON
                    $geojson = $this->wktToGeoJson($data[0]['geom']);
                    return response()->json($geojson);
                }
            }
            return response()->json(null, 404);
        } catch (\Exception $e) {
            Log::error('Supabase boundary error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    public function getRoads()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/v_lokasi_peta?select=geom&kategori=eq.Jaringan_Jalan');
            
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['geom'])) {
                    $geojson = $this->wktToGeoJson($data[0]['geom']);
                    return response()->json($geojson);
                }
            }
            return response()->json(null, 404);
        } catch (\Exception $e) {
            Log::error('Supabase roads error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    public function getRecommendations()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/v_rekomendasi_bengkel?select=*&order=skor_akhir.desc');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Supabase recommendations error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
    
    public function saveRanking(Request $request)
    {
        try {
            $rankingData = $request->all();
            
            Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->delete($this->supabaseUrl . '/rest/v1/ranking_data?location_id=not.is.null');
            
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/rest/v1/ranking_data', $rankingData);
            
            if ($response->successful()) {
                return response()->json(['message' => 'Ranking saved successfully']);
            }
            return response()->json(['error' => 'Failed to save ranking'], $response->status());
        } catch (\Exception $e) {
            Log::error('Save ranking error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    
    public function getRanking()
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer ' . $this->supabaseKey,
            ])->get($this->supabaseUrl . '/rest/v1/ranking_data?select=*');
            
            if ($response->successful()) {
                $data = $response->json();
                $rankingMap = [];
                foreach ($data as $item) {
                    $rankingMap[$item['location_id']] = [
                        'score' => $item['score'],
                        'rank' => $item['rank']
                    ];
                }
                return response()->json($rankingMap);
            }
            return response()->json([]);
        } catch (\Exception $e) {
            Log::error('Get ranking error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
    
    private function wktToGeoJson($wkt)
    {
        // Simple WKT to GeoJSON converter
        // For POLYGON and MULTILINESTRING
        if (strpos($wkt, 'POLYGON') !== false) {
            preg_match('/POLYGON\(\((.*?)\)\)/', $wkt, $matches);
            if (isset($matches[1])) {
                $points = explode(',', $matches[1]);
                $coordinates = [];
                foreach ($points as $point) {
                    $coords = explode(' ', trim($point));
                    $coordinates[] = [floatval($coords[0]), floatval($coords[1])];
                }
                return [
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Polygon',
                                'coordinates' => [$coordinates]
                            ],
                            'properties' => []
                        ]
                    ]
                ];
            }
        } elseif (strpos($wkt, 'MULTILINESTRING') !== false) {
            preg_match('/MULTILINESTRING\((.*?)\)/', $wkt, $matches);
            if (isset($matches[1])) {
                $lines = explode('),(', $matches[1]);
                $coordinates = [];
                foreach ($lines as $line) {
                    $line = str_replace(['(', ')'], '', $line);
                    $points = explode(',', $line);
                    $lineCoords = [];
                    foreach ($points as $point) {
                        $coords = explode(' ', trim($point));
                        $lineCoords[] = [floatval($coords[0]), floatval($coords[1])];
                    }
                    $coordinates[] = $lineCoords;
                }
                return [
                    'type' => 'FeatureCollection',
                    'features' => [
                        [
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'MultiLineString',
                                'coordinates' => $coordinates
                            ],
                            'properties' => []
                        ]
                    ]
                ];
            }
        }
        
        return null;
    }
}