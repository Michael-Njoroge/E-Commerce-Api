<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Http\Resources\BrandResource;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::paginate(20);

        return $this->sendResponse(BrandResource::collection($brands)
                ->response()
                ->getData(true), "Brands retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|unique:brands,title',
        ]);

        $brand = Brand::create($data);

        return $this->sendResponse(BrandResource::make($brand)
                ->response()
                ->getData(true), "Brand created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        if($brand){
            return $this->sendResponse(BrandResource::make($brand)
                ->response()
                ->getData(true), "Brand retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        if($brand){
            $brand->update($request->all());
            $updatedBrand = Brand::findOrFail($brand->id);

            return $this->sendResponse(BrandResource::make($updatedBrand)
                ->response()
                ->getData(true), "Brand updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        if($brand){
            $brand->delete();

            return $this->sendResponse([], "Brand deleted successfully" );
        }
    }
}

