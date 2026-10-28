<?php

namespace App\Http\Controllers\Api;

use App\Models\Perpustakaan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PerpustakaanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perpustakaans = Perpustakaan::with('school')->get();
        return response()->json($perpustakaans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:perpustakaans,isbn|max:255',
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
            'rack_location' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('perpustakaan_fotos', 'public');
        }

        $perpustakaan = Perpustakaan::create($validated);
        return response()->json($perpustakaan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Perpustakaan $perpustakaan)
    {
        $perpustakaan->load('school');
        return response()->json($perpustakaan);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Perpustakaan $perpustakaan)
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|required|exists:schools,id',
            'name' => 'sometimes|required|string|max:255',
            'isbn' => 'nullable|string|max:255|unique:perpustakaans,isbn,' . $perpustakaan->id,
            'category' => 'nullable|string|max:255',
            'stock' => 'sometimes|required|integer|min:0',
            'rack_location' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            if ($perpustakaan->foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($perpustakaan->foto);
            }
            $validated['foto'] = $request->file('foto')->store('perpustakaan_fotos', 'public');
        }

        $perpustakaan->update($validated);
        return response()->json($perpustakaan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Perpustakaan $perpustakaan)
    {
        $perpustakaan->delete();
        return response()->json(null, 204);
    }
}
