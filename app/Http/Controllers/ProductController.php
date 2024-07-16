<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\RatingResource;
use App\Models\Product;
use App\Models\User;
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
            // Initialize the query with relationships
            $query = Product::with(['media', 'brand', 'category', 'ratings.user']);

             // Filtering
            if ($request->has('brand')) {
                $query->whereHas('brand', function($q) use ($request) {
                    $q->where('title', $request->query('brand'));
                });
            }

            if ($request->has('category')) {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('title', $request->query('category'));
                });
            }

            if ($request->has('tag')) {
                $query->whereJsonContains('tags', $request->query('tag'));
            }

            if ($request->has('color')) {
                $query->whereJsonContains('color', $request->query('color'));
                
            }

            if ($request->has('minPrice')) {
                $query->where('price', '>=', $request->query('minPrice'));
            }

            if ($request->has('maxPrice')) {
                $query->where('price', '<=', $request->query('maxPrice'));
            }

            // Sorting
            if ($request->has('sort')) {
                $sortFields = explode(',', $request->query('sort'));
                foreach ($sortFields as $sortField) {
                    $direction = 'asc';
                    if (substr($sortField, 0, 1) === '-') {
                        $direction = 'desc';
                        $sortField = substr($sortField, 1);
                    }
                    $query->orderBy($sortField, $direction);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Limiting fields
            if ($request->has('fields')) {
                $fields = explode(',', $request->query('fields'));
                $query->select($fields);
            }

            // Pagination
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 20);
            $products = $query->paginate($limit, ['*'], 'page', $page);

            return $this->sendResponse(ProductResource::collection($products)
                ->response()
                ->getData(true), "Products retrieved successfully");

        } catch (\Exception $e) {
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
        $mediaData = $request->input('media_ids', []);
        if (!empty($mediaData)) {
            Media::whereIn('id', $mediaData)
                ->update(['medially_id' => $product->id, 'medially_type' => Product::class]);
        }
        $requestData = $request->except('media_ids');
        
        $product->update($requestData);
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
    public function addToWishlist(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|uuid|exists:products,id' 
        ]);

        $user = auth()->user();
        $product = Product::findOrFail($data['product_id']);
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

    //Get user whishlist
    public function getWishlist()
    {
        $user = auth()->user();
        $wishlist = $user->wishlist()->get();
        $wishlist->load(['media', 'ratings.user']);
        return $this->sendResponse(ProductResource::collection($wishlist)
                    ->response()
                    ->getData(true), "User wishlist retrieved successfully" );
    }

  //Rate Product
  public function rateProduct(Request $request, Product $product)
    {
        $request->validate([
            'star' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'product' => 'required|uuid|exists:products,id'
        ]);

        $user = auth()->user();
        $product = Product::where('id',$request->product)->first();

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
