<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSetting;
use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminHomeController extends Controller
{
    public function edit()
    {
        return Inertia::render('Admin/Home/Edit', [
            'heroSlider' => HomeSetting::where('key', 'hero_slider')->first(),
            'aboutUs' => HomeSetting::where('key', 'about_us')->first(),
            'products' => Product::all(),
            'brands' => Brand::all(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'hero_slider' => 'nullable|array',
            'hero_images.*' => 'nullable|image|max:2048',
            'about_us' => 'nullable|array',
            'about_us_image' => 'nullable|image|max:2048',
        ]);

        if ($request->has('hero_slider')) {
            $heroSlider = $request->hero_slider;
            
            // Handle slide image uploads
            if ($request->hasFile('hero_images')) {
                foreach ($request->file('hero_images') as $index => $file) {
                    if (isset($heroSlider[$index])) {
                        // Store in 'public' disk under 'home/hero'
                        $path = $file->store('home/hero', 'public');
                        $heroSlider[$index]['image'] = '/storage/' . $path;
                    }
                }
            }

            HomeSetting::updateOrCreate(
                ['key' => 'hero_slider'],
                ['value' => $heroSlider]
            );
        }

        if ($request->has('about_us')) {
            $aboutUsData = $request->about_us;
            
            if ($request->hasFile('about_us_image')) {
                // Store in 'public' disk under 'home'
                $path = $request->file('about_us_image')->store('home', 'public');
                $aboutUsData['image'] = '/storage/' . $path;
            } else {
                $existing = HomeSetting::where('key', 'about_us')->first();
                if ($existing && isset($existing->value['image'])) {
                    $aboutUsData['image'] = $existing->value['image'];
                }
            }

            HomeSetting::updateOrCreate(
                ['key' => 'about_us'],
                ['value' => $aboutUsData]
            );
        }

        return redirect()->back()->with('success', 'Home settings updated successfully.');
    }
}
