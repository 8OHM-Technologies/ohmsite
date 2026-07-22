<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatasetController extends Controller
{
    /**
     * Display a listing of the datasets.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Datasets/Index', [
            'datasets' => Dataset::all(),
        ]);
    }

    /**
     * Show the form for creating a new dataset.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Datasets/Create');
    }

    /**
     * Store a newly created dataset in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:datasets,slug',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'demo_data' => 'nullable|json',
        ]);

        if (isset($validated['demo_data'])) {
            $validated['demo_data'] = json_decode($validated['demo_data'], true);
        }

        Dataset::create($validated);

        return redirect()->route('admin.datasets.index')->with('success', 'Dataset created successfully.');
    }

    /**
     * Show the form for editing the specified dataset.
     */
    public function edit(Dataset $dataset): Response
    {
        return Inertia::render('Admin/Datasets/Edit', [
            'dataset' => $dataset,
        ]);
    }

    /**
     * Update the specified dataset in storage.
     */
    public function update(Request $request, Dataset $dataset): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:datasets,slug,'.$dataset->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'demo_data' => 'nullable|json',
        ]);

        if (array_key_exists('demo_data', $validated)) {
            $validated['demo_data'] = $validated['demo_data'] ? json_decode($validated['demo_data'], true) : null;
        }

        $dataset->update($validated);

        return redirect()->route('admin.datasets.index')->with('success', 'Dataset updated successfully.');
    }

    /**
     * Remove the specified dataset from storage.
     */
    public function destroy(Dataset $dataset): RedirectResponse
    {
        $dataset->delete();

        return redirect()->route('admin.datasets.index')->with('success', 'Dataset deleted successfully.');
    }
}
