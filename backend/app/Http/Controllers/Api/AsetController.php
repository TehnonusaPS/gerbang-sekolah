<?php

namespace App\Http\Controllers\Api;

use App\Models\Aset;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asets = Aset::with('school')->get();
        return response()->json($asets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string|max:50|unique:asets,code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:10',
            'room' => 'nullable|string|max:100',
            'condition' => ['nullable', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
            'date' => 'nullable|date',
            'value' => 'nullable|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('aset_fotos', 'public');
        }

        $aset = Aset::create($validated);
        return response()->json($aset, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Aset $aset)
    {
        $aset->load('school');
        return response()->json($aset);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Aset $aset)
    {
        $validated = $request->validate([
            'school_id' => 'sometimes|required|exists:schools,id',
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('asets')->ignore($aset->id)],
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:10',
            'room' => 'nullable|string|max:100',
            'condition' => ['nullable', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
            'date' => 'nullable|date',
            'value' => 'nullable|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            if ($aset->foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($aset->foto);
            }
            $validated['foto'] = $request->file('foto')->store('aset_fotos', 'public');
        }

        $aset->update($validated);
        return response()->json($aset);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Aset $aset)
    {
        if ($aset->foto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($aset->foto);
        }
        $aset->delete();
        return response()->json(null, 204);
    }
}
