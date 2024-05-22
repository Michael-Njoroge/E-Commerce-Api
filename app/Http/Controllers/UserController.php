<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Http\Resources\UserResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with(['wishlist','wishlist.media','ratings.product'])->paginate(20);

        return $this->sendResponse(UserResource::collection($users)
                ->response()
                ->getData(true), "Users retrieved successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if($user){
        $user->load(['wishlist','wishlist.media', 'ratings.product']);
        return $this->sendResponse(UserResource::make($user)
                ->response()
                ->getData(true), "User retrieved successfully" );
        }

        return $this->sendError($error="User not found");
       
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if($user){
            $user->update($request->all());
            $updatedUser = User::findOrFail($user->id);
            $updatedUser->load(['wishlist','wishlist.media', 'ratings.product']);
            return $this->sendResponse(UserResource::make($updatedUser)
                ->response()
                ->getData(true), "User updated successfully" );
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user = User::where('id',$user->id)->first();

        if(!$user){
            return $this->sendError($error="User not found");
        }
        
        $user->delete();
        return $this->sendResponse($result='', $message="User deleted successfully");
    }

    //Change role
    public function role(User $user)
    {
       $user = User::find($user->id);

        if(!$user){
            return $this->sendError($error="User not found");
        }

        $newRole = ($user->role === 'admin') ? 'user' : 'admin';
        $user->update(['role' => $newRole]);
        $user->load(['wishlist','wishlist.media', 'ratings.product']);

        $message = 'User role was changed into ' . ($user->role === 'admin' ? 'Admin' : 'Regular User');

        return $this->sendResponse(UserResource::make($user)
                ->response()
                ->getData(true), $message );

    }

    //Block/Unblock
    public function blockUnblock(User $user)
    {

        $user->is_blocked = !$user->is_blocked;
        $user->save();
        $user->load(['wishlist','wishlist.media', 'ratings.product']);

        $message = 'User was ' . ($user->is_blocked ? 'Blocked' : 'Unblocked');

       return $this->sendResponse(UserResource::make($user)
                ->response()
                ->getData(true), $message);
    }

    //Save address
    public function saveAddress(Request $request, User $user)
    {
        $data = $request->validate([
            'address' => 'required|string'
        ]);

        if($user){
            $user->update($data);
            $updatedUser = User::findOrFail($user->id);
            $updatedUser->load(['wishlist','wishlist.media', 'ratings.product']);

            return $this->sendResponse(UserResource::make($updatedUser)
                ->response()
                ->getData(true), "User address added successfully");
        }
    }

    //Add To Cart
    public function addToCart(Request $request, Product $product)
    {
        $request->validate([
            'cart' => 'required|array'
        ]);

        $user = auth()->user();

        // Clear existing cart
        Cart::where('user_id', $user->id)->delete();

        $products = [];
        $cartTotal = 0;


        foreach ($request->cart as $cartItem) {
            $count = $cartItem['count'];
            $color = $cartItem['color'];
            $price = $product->price;

            $products[] = [
                'id' => Str::uuid(),
                'product_id' => $product->id,
                'count' => $count,
                'color' => $color,
                'price' => $price
            ];

            $cartTotal += $price * $count;
        }

        $cart = Cart::create([
            'user_id' => $user->id,
            'cart_total' => $cartTotal
        ]);

        $cart->products()->attach($products);
        $cart->load(['user','products']);

        return $this->sendResponse(CartResource::make($cart)
                ->response()
                ->getData(true), "Product added to cart successfully" );
    }

    //Get user cart
    public function getUserCart()
    {
        $user = auth()->user();
        $cart = Cart::with('products')->where('user_id', $user->id)->first();

       return $this->sendResponse(CartResource::make($cart)
                ->response()
                ->getData(true), "User cart retrieved successfully" );
    }

    //Create order
    public function createOrder(Request $request)
    {
        $request->validate([
            'COD' => 'required|boolean'
        ]);

        if (!$request->COD) {
            return $this->sendError($error = 'Create cash order failed');
        }

        $user = auth()->user();
        $cart = Cart::with('products')->where('user_id', $user->id)->first();
        $finalAmount = $cart->cart_total;

        $order = Order::create([
            'user_id' => $user->id,
            'payment_intent' => json_encode([
                'id' => uniqid(),
                'method' => 'COD',
                'amount' => $finalAmount,
                'status' => 'Cash on Delivery',
                'created' => now(),
                'currency' => 'usd'
            ]),
            'order_status' => 'Cash on Delivery'
        ]);

        $order->products()->attach($cart->products->pluck('id')->toArray());

        foreach ($cart->products as $product) {
            $product->decrement('quantity', $product->pivot->count);
            $product->increment('sold', $product->pivot->count);
        }

        $cart->delete();

        return response()->json($order, 201);
    }

    //Get orders
    public function getOrders()
    {
        $user = auth()->user();
        $orders = Order::with('products')->where('user_id', $user->id)->get();

        return response()->json($orders);
    }

}