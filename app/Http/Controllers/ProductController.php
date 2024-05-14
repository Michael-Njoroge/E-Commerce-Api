<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(20);
        return $this->sendResponse($result=$products, $message="Products retrieved successfully");

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
            'slug' => 'required|string',
            'price' => 'required',
            'category' => 'required|string',
            'brand' => 'required|string',
            'quantity' => 'required|integer',
            'sold' => 'integer|nullable',
            'images' => 'nullable|json',
            'color' => 'nullable|json',
            'tag' => 'nullable|json',
        ]);

        $product = Product::where('slug',$data['slug'])->first();

        if($product){
            return $this->sendError($error = 'Product with this slug already exists', $code = 403);
        }

        $productSaved = Product::create($data);
        $user = $request->user();
        $user->wishlist = $productSaved->id;
        $user->save();

        return $this->sendResponse($result = $productSaved, $message = 'Product created successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
