<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\RatingResource;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
        // Filtering products
        $query = Product::query();

        // Sorting products
        $sortBy = $request->query('sort', '-created_at');
        $sortFields = explode(',', $sortBy);
        
        foreach ($sortFields as $field) {
            // Determine sorting direction (ascending or descending)
            $direction = Str::startsWith($field, '-') ? 'desc' : 'asc';
            $field = ltrim($field, '-');

            // Apply sorting to the query
            $query->orderBy($field, $direction);
        }

        // Paginating products
        $page = $request->query('page');
        $limit = $request->query('limit', 10);
        $products = $query->paginate($limit);
        $products = $query->with(['media', 'brand','category','ratings.user'])->paginate($limit);

        // Remove fields from the request query parameters
        $excludeFields = ['page', 'sort', 'limit'];
        foreach ($excludeFields as $field) {
            $request->query->remove($field);
        }

        // Convert remaining query parameters to Eloquent query
        foreach ($request->query() as $key => $value) {
            // Apply operators for comparison (e.g., $gte, $gt, $lte, $lt)
            if (in_array($key, ['gte', 'gt', 'lte', 'lt'])) {
                $key = str_replace(['gte', 'gt', 'lte', 'lt'], ['$gte', '$gt', '$lte', '$lt'], $key);
            }

            // Add conditions to the query
            $query->where($key, $value);

        }

        return $this->sendResponse(ProductResource::collection($products)
                ->response()
                ->getData(true), "Products retrieved successfully" );
        }
        catch (\Exception $e) {
        // Handle exceptions
        return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required',
            'category' => 'required|uuid|exists:product_categories,id',
            'brand' => 'required|uuid|exists:brands,id',
            'quantity' => 'required|integer',
            'color' => 'nullable|array',
            'color.*' => 'uuid|exists:colors,id',
            'tags' => 'required|string',
            'media_ids' => 'nullable|array', 
            'media_ids.*' => 'uuid|exists:media,id',
        ]);

        $slug = Str::slug($data['title']);
        $data['slug'] = $slug;
        // dd($slug);
        $product = Product::where('slug', $slug)->first();

        if($product){
            return $this->sendError($error = 'Product with this slug already exists', $code = 403);
        }

        $mediaData = $request->input('media_ids');

        unset($data['media_ids']);

        $productSaved = Product::create($data);

        if(!empty($mediaData)) {
            Media::whereIn('id', $mediaData)
                ->update(['medially_id' => $productSaved->id, 'medially_type' => Product::class]);
        }
        $productSaved->load(['media', 'ratings.user']);

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
            $product->load(['media', 'ratings.user']);
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
        $updatedProduct->load(['media', 'ratings.user']);

        return $this->sendResponse(ProductResource::make($updatedProduct)->response()->getData(true), "Product updated successfully");
        }
        return $this->sendError($error="Product not found");

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if($product) {
        
        $mediaItems = Media::where('medially_id', $product->id)
                       ->where('medially_type', Product::class)
                       ->get();

        foreach ($mediaItems as $mediaItem) {
            try {
                Cloudinary::destroy($mediaItem->public_id);
                $mediaItem->delete();
            } catch (\Exception $e) {
                return $this->sendError($error = "Failed to delete image from Cloudinary");
            }
        }

        $product->delete();
        return $this->sendResponse([], 'Product deleted successfully');
        }
        
        return $this->sendError('Product not found', 404);

    }

    //Add to whishlist
    public function addToWishlist(Product $product)
    {
        $user = auth()->user();

        if ($user->wishlist()->where('product_id', $product->id)->exists()) {
            // Remove product from wishlist
            $user->wishlist()->detach($product->id);

            $user->load('wishlist');

            return $this->sendResponse(UserResource::make($user)
                    ->response()
                    ->getData(true), "Product removed from wishlist successfully" );
        } else {
            // Add product to wishlist
            $user->wishlist()->attach($product->id);

            $user->load(['wishlist','wishlist.media','ratings']);

            return $this->sendResponse(UserResource::make($user)
                    ->response()
                    ->getData(true), "Product added to wishlist successfully" );
        }
    }

  //Rate Product
  public function rateProduct(Request $request, Product $product)
    {
        $request->validate([
            'star' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $user = auth()->user();

        $existingRating = $product->ratings()->where('user_id', $user->id)->first();

        if ($existingRating) {
            $existingRating->update([
                'star' => $request->star,
                'comment' => $request->comment,
            ]);
            $message = "Rating updated successfully";
        } else {
            Rating::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'star' => $request->star,
                'comment' => $request->comment,
            ]);
            $message = "Rating added successfully";
        }

        $totalRating = floor($product->ratings()->avg('star'));
        // dd($product->load(['wishlist.media','ratings']));

        $product->update(['total_ratings' => $totalRating]);
        $product->load(['media','ratings.user']);

        return $this->sendResponse(ProductResource::make($product)
                    ->response()
                    ->getData(true), $message );
    }
}
