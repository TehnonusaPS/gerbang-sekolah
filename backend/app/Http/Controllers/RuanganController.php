<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RuanganController extends Controller
{
    public function index()
    {
        $ruangans = \App\Models\Ruangan::with('school')->get();
        return response()->json($ruangans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:ruangans,code',
            'name' => 'required|string',
            'category' => 'required|string',
            'building' => 'required|string',
            'capacity' => 'required|integer',
            'area' => 'required|numeric',
            'facilities' => 'nullable|array',
            'school_id' => 'required|exists:schools,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('ruangans', 'public');
        }

        $ruangan = \App\Models\Ruangan::create($validated);
        return response()->json($ruangan, 201);
    }

    public function show($id)
    {
        $ruangan = \App\Models\Ruangan::with('school')->findOrFail($id);
        return response()->json($ruangan);
    }

    public function update(Request $request, $id)
    {
        $ruangan = \App\Models\Ruangan::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|unique:ruangans,code,' . $ruangan->id,
            'name' => 'sometimes|required|string',
            'category' => 'sometimes|required|string',
            'building' => 'sometimes|required|string',
            'capacity' => 'sometimes|required|integer',
            'area' => 'sometimes|numeric',
            'facilities' => 'nullable|array',
            'school_id' => 'sometimes|required|exists:schools,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            if ($ruangan->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($ruangan->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($ruangan->foto);
            }
            $validated['foto'] = $request->file('foto')->store('ruangans', 'public');
        }

        $ruangan->update($validated);
        return response()->json($ruangan);
    }

    public function destroy($id)
    {
        $ruangan = \App\Models\Ruangan::findOrFail($id);

        if ($ruangan->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($ruangan->foto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($ruangan->foto);
        }

        $ruangan->delete();
        return response()->json(null, 204);
    }
}
