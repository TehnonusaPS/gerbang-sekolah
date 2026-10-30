<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Foundation;
use App\Models\School;

class LandingPageConfigController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->file('image')) {
            $path = $request->file('image')->store('landing-pages', 'public');
            
            // Return full url to the client
            return response()->json([
                'url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json(['error' => 'File gambar gagal diunggah.'], 400);
    }

    public function getPublicLandingPageBySlug($slug)
    {
        // Cari di tabel Foundations
        $foundation = Foundation::where('landing_page_config->slug', $slug)->first();
        if ($foundation) {
            $config = json_decode($foundation->landing_page_config, true);
            
            // Verifikasi status Web Published (Akses publik murni diatur dari sini)
            if (!isset($config['is_published']) || !$config['is_published']) {
                return response()->json(['error' => 'Halaman tidak aktif atau belum dipublikasikan.'], 403);
            }

            // Inject Data Profil Utama
            $config['meta_title'] = $foundation->name;
            $config['school_name'] = $foundation->name;
            $config['type'] = 'Yayasan';
            $config['contact_address'] = $foundation->address;
            $config['contact_email'] = $foundation->email;
            $config['contact_phone'] = $foundation->phone;
            
            return response()->json($config);
        }

        // Jika tidak ada di Foundations, cari di Schools
        $school = School::where('landing_page_config->slug', $slug)->first();
        if ($school) {
            $config = json_decode($school->landing_page_config, true);
            
            // Verifikasi status Web Published (Akses publik murni diatur dari sini)
            if (!isset($config['is_published']) || !$config['is_published']) {
                return response()->json(['error' => 'Halaman tidak aktif atau belum dipublikasikan.'], 403);
            }
            
            // Inject Data Profil Utama
            $config['meta_title'] = $school->name;
            $config['school_name'] = $school->name;
            $config['type'] = 'Sekolah';
            $config['contact_address'] = $school->address;
            $config['contact_email'] = $school->email;
            $config['contact_phone'] = $school->phone;
            $config['social_instagram'] = $school->instagram ?? ($config['social_instagram'] ?? '');
            $config['social_facebook'] = $school->facebook ?? ($config['social_facebook'] ?? '');

            return response()->json($config);
        }

        return response()->json(['error' => 'Halaman tidak ditemukan.'], 404);
    }


    public function getFoundationsList()
    {
        $foundations = Foundation::with('schools:id,foundation_id,name,level')
            ->select('id', 'name', 'address', 'phone', 'email', 'deed_number', 'landing_page_enabled', 'landing_page_theme', 'landing_page_config', 'updated_at')
            ->get();
        return response()->json($foundations);
    }

    public function getSchoolsList()
    {
        // Untuk sekolah, bisa di-join dengan foundation bila perlu, tapi standar saja:
        $schools = School::with('foundation:id,name')->select('id', 'foundation_id', 'name', 'address', 'phone', 'email', 'instagram', 'facebook', 'npsn', 'landing_page_enabled', 'landing_page_theme', 'landing_page_config', 'updated_at')->get();
        return response()->json($schools);
    }

    public function getFoundationConfig($id)
    {
        $foundation = Foundation::findOrFail($id);
        return response()->json([
            'id'                   => $foundation->id,
            'name'                 => $foundation->name,
            'legal_number'         => $foundation->deed_number ?? '',
            'logo'                 => $foundation->logo ?? '',
            'landing_page_enabled' => (bool)$foundation->landing_page_enabled,
            'landing_page_theme'   => $foundation->landing_page_theme,
            'landing_page_config'  => $foundation->landing_page_config ? json_decode($foundation->landing_page_config) : null
        ]);
    }

    public function updateFoundationConfig(Request $request, $id)
    {
        $foundation = Foundation::findOrFail($id);
        
        $request->validate([
            'landing_page_enabled' => 'boolean',
            'landing_page_theme' => 'nullable|string',
            'landing_page_config' => 'nullable' // bisa array/object
        ]);

        $foundation->landing_page_enabled = $request->input('landing_page_enabled', $foundation->landing_page_enabled);
        $foundation->landing_page_theme = $request->input('landing_page_theme', $foundation->landing_page_theme);
        
        if ($request->has('landing_page_config')) {
            $configArr = is_array($request->landing_page_config) 
                ? $request->landing_page_config 
                : json_decode($request->landing_page_config, true);
            
            // Sinkronisasi data kembali ke tabel Foundation
            $foundation->email = $configArr['contact_email'] ?? $foundation->email;
            $foundation->phone = $configArr['contact_phone'] ?? $foundation->phone;
            $foundation->address = $configArr['contact_address'] ?? $foundation->address;

            $configArr['slug'] = !empty($configArr['slug']) 
                ? \Illuminate\Support\Str::slug($configArr['slug']) 
                : \Illuminate\Support\Str::slug($foundation->name);
            $foundation->landing_page_config = json_encode($configArr);
        }

        $foundation->save();

        return response()->json([
            'message' => 'Konfigurasi landing page Yayasan berhasil disimpan.',
            'data' => [
                'id'                   => $foundation->id,
                'name'                 => $foundation->name,
                'legal_number'         => $foundation->deed_number ?? '',
                'logo'                 => $foundation->logo ?? '',
                'landing_page_enabled' => (bool)$foundation->landing_page_enabled,
                'landing_page_theme'   => $foundation->landing_page_theme,
                'landing_page_config'  => $foundation->landing_page_config
                    ? json_decode($foundation->landing_page_config)
                    : null,
            ]
        ]);
    }

    public function getSchoolConfig($id)
    {
        $school = School::findOrFail($id);
        return response()->json([
            'id'                   => $school->id,
            'name'                 => $school->name,
            'legal_number'         => $school->npsn ?? '',
            'logo'                 => $school->logo ?? '',
            'landing_page_enabled' => (bool)$school->landing_page_enabled,
            'landing_page_theme'   => $school->landing_page_theme,
            'landing_page_config'  => $school->landing_page_config ? json_decode($school->landing_page_config) : null
        ]);
    }

    public function updateSchoolConfig(Request $request, $id)
    {
        $school = School::findOrFail($id);
        
        $request->validate([
            'landing_page_enabled' => 'boolean',
            'landing_page_theme' => 'nullable|string',
            'landing_page_config' => 'nullable' // bisa array/object
        ]);

        $school->landing_page_enabled = $request->input('landing_page_enabled', $school->landing_page_enabled);
        $school->landing_page_theme = $request->input('landing_page_theme', $school->landing_page_theme);
        
        if ($request->has('landing_page_config')) {
            $configArr = is_array($request->landing_page_config) 
                ? $request->landing_page_config 
                : json_decode($request->landing_page_config, true);
            
            // Sinkronisasi data kembali ke tabel School
            $school->email = $configArr['contact_email'] ?? $school->email;
            $school->phone = $configArr['contact_phone'] ?? $school->phone;
            $school->address = $configArr['contact_address'] ?? $school->address;
            $school->instagram = $configArr['social_instagram'] ?? $school->instagram;
            $school->facebook = $configArr['social_facebook'] ?? $school->facebook;

            $configArr['slug'] = !empty($configArr['slug'])
                ? \Illuminate\Support\Str::slug($configArr['slug'])
                : \Illuminate\Support\Str::slug($school->name);
            $school->landing_page_config = json_encode($configArr);
        }

        $school->save();

        return response()->json([
            'message' => 'Konfigurasi landing page Sekolah berhasil disimpan.',
            'data' => [
                'id'                   => $school->id,
                'name'                 => $school->name,
                'legal_number'         => $school->npsn ?? '',
                'logo'                 => $school->logo ?? '',
                'landing_page_enabled' => (bool)$school->landing_page_enabled,
                'landing_page_theme'   => $school->landing_page_theme,
                'landing_page_config'  => $school->landing_page_config
                    ? json_decode($school->landing_page_config)
                    : null,
            ]
        ]);
    }
}
