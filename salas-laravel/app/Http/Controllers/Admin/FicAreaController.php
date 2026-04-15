<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FicArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FicAreaController extends Controller
{
    public function index(): View
    {
        $areas = FicArea::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.fic.areas.index', [
            'areas' => $areas,
            'navCurrent' => 'fic',
        ]);
    }

    public function create(): View
    {
        return view('admin.fic.areas.create', ['navCurrent' => 'fic']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:fic_areas,slug'],
            'kiosk_after_graduation' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['kiosk_after_graduation'] = $request->boolean('kiosk_after_graduation');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        FicArea::query()->create($data);

        return redirect()->route('admin.fic.areas.index')->with('success', 'Área criada.');
    }

    public function edit(FicArea $area): View
    {
        return view('admin.fic.areas.edit', [
            'area' => $area,
            'navCurrent' => 'fic',
        ]);
    }

    public function update(Request $request, FicArea $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:fic_areas,slug,'.$area->id],
            'kiosk_after_graduation' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['kiosk_after_graduation'] = $request->boolean('kiosk_after_graduation');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $area->update($data);

        return redirect()->route('admin.fic.areas.index')->with('success', 'Área atualizada.');
    }
}
