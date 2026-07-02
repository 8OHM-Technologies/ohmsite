<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => Product::with(['brands', 'category'])->latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'brands' => Brand::all(),
            'categories' => Category::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'brand_ids' => 'required|array|min:1',
            'brand_ids.*' => 'exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'colors' => 'nullable|array',
            'sizes' => 'nullable|array',
            'image' => 'required|image|max:2048',
            'gallery_images' => 'nullable|array|max:4',
            'gallery_images.*' => 'image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products/main', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $gallery = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $path = $file->store('products/gallery', 'public');
                $gallery[] = '/storage/' . $path;
            }
        }
        $validated['images'] = $gallery;

        $product = Product::create($validated);
        $product->brands()->sync($request->brand_ids);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => $product->load('brands'),
            'brands' => Brand::all(),
            'categories' => Category::all()
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'brand_ids' => 'required|array|min:1',
            'brand_ids.*' => 'exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'colors' => 'nullable|array',
            'sizes' => 'nullable|array',
            'image' => 'nullable|image|max:2048',
            'gallery_images' => 'nullable|array|max:4',
            'gallery_images.*' => 'image|max:2048',
        ]);

        // Remove images from validated data to prevent overwriting with null
        unset($validated['image']);
        unset($validated['gallery_images']);

        if ($request->hasFile('image')) {
            // Delete old main image if exists
            if ($product->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->image));
            }
            $path = $request->file('image')->store('products/main', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        if ($request->hasFile('gallery_images')) {
            // Delete old gallery images
            if ($product->images) {
                foreach ($product->images as $img) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $img));
                }
            }
            $gallery = [];
            foreach ($request->file('gallery_images') as $file) {
                $path = $file->store('products/gallery', 'public');
                $gallery[] = '/storage/' . $path;
            }
            $validated['images'] = $gallery;
        }

        $product->update($validated);
        $product->brands()->sync($request->brand_ids);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete images
        if ($product->image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $product->image));
        }
        if ($product->images) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $img));
            }
        }
        
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
