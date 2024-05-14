<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(20);
        return $this->sendResponse(ProductResource::collection($products)
                ->response()
                ->getData(true), "Products retrieved successfully" );
    
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'price' => 'required',
            'category' => 'required|string',
            'brand' => 'required|string',
            'quantity' => 'required|integer',
            'images' => 'nullable|json',
            'color' => 'nullable|json',
            'tag' => 'nullable|json',
        ]);

        $slug = Str::slug($data['title']);
        $data['slug'] = $slug;
        // dd($slug);
        $product = Product::where('slug', $slug)->first();

        if($product){
            return $this->sendError($error = 'Product with this slug already exists', $code = 403);
        }

        $productSaved = Product::create($data);

        return $this->sendResponse(ProductResource::make($productSaved)
                    ->response()
                    ->getData(true), "Product created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if($product){
            return $this->sendResponse(ProductResource::make($product)
                    ->response()
                    ->getData(true), "Product retrieved successfully" );
            }
    
        return $this->sendError($error="Product not found");
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        if($product){
        $product->update($request->all());
        $updatedProduct = Product::findOrFail($product->id);

        return $this->sendResponse(ProductResource::make($updatedProduct)->response()->getData(true), "Product updated successfully");
        }
        return $this->sendError($error="Product not found");

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
        $product->delete();
        return $this->sendResponse('', 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Product not found', 404);
        }

    }
}
