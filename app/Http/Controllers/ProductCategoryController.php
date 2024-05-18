<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCategoryResource;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProductCategory::paginate(20);

        return $this->sendResponse(ProductCategoryResource::collection($categories)
                ->response()
                ->getData(true), "Product categories retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|unique:product_categories,title',
        ]);

        $category = ProductCategory::create($data);

        return $this->sendResponse(ProductCategoryResource::make($category)
                ->response()
                ->getData(true), "Product category created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        if($productCategory){
            return $this->sendResponse(ProductCategoryResource::make($productCategory)
                ->response()
                ->getData(true), "Product category retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        if($productCategory){
            $productCategory->update($request->all());
            $updatedCategory = ProductCategory::findOrFail($productCategory->id);

            return $this->sendResponse(ProductCategoryResource::make($updatedCategory)
                ->response()
                ->getData(true), "Product category updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        if($productCategory){
            $productCategory->delete();

            return $this->sendResponse([], "Product category deleted successfully" );
        }
    }
}
