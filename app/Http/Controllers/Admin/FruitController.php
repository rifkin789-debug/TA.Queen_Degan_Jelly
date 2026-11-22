<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fruit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FruitController extends Controller
{
    public function index(): View
    {
        $fruits = Fruit::query()->orderBy('nama_buah')->paginate(20);

        return view('admin.fruits.index', compact('fruits'));
    }

    public function create(): View
    {
        return view('admin.fruits.form', ['fruit' => new Fruit]);
    }

    public function store(Request $request): RedirectResponse
    {
        Fruit::query()->create($this->validated($request));

        return redirect()->route('admin.buah.index')->with('success', 'Buah berhasil ditambahkan.');
    }

    public function edit(Fruit $buah): View
    {
        return view('admin.fruits.form', ['fruit' => $buah]);
    }

    public function update(Request $request, Fruit $buah): RedirectResponse
    {
        $data = $this->validated($request, $buah);

        DB::transaction(function () use ($buah, $data): void {
            $lockedFruit = Fruit::query()->lockForUpdate()->findOrFail($buah->id);
            $lockedFruit->update($data);
        }, attempts: 5);

        return redirect()->route('admin.buah.index')->with('success', 'Buah berhasil diperbarui.');
    }

    public function toggleStatus(Fruit $buah): RedirectResponse
    {
        $isActive = DB::transaction(function () use ($buah): bool {
            $lockedFruit = Fruit::query()->lockForUpdate()->findOrFail($buah->id);
            $isActive = ! $lockedFruit->aktif;
            $lockedFruit->update(['aktif' => $isActive]);

            return $isActive;
        }, attempts: 5);

        return back()->with(
            'success',
            $isActive ? 'Buah berhasil diaktifkan.' : 'Buah berhasil dinonaktifkan.'
        );
    }

    /**
     * @return array{nama_buah: string, harga_porsi: int|string, stok_porsi: int|string, aktif: bool}
     */
    private function validated(Request $request, ?Fruit $fruit = null): array
    {
        $data = $request->validate([
            'nama_buah' => [
                'required',
                'string',
                'max:255',
                Rule::unique('buah', 'nama_buah')->ignore($fruit?->id),
            ],
            'harga_porsi' => ['required', 'integer', 'min:0'],
            'stok_porsi' => ['required', 'integer', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        return $data + ['aktif' => $request->boolean('aktif')];
    }
}
