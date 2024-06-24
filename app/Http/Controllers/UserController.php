<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Models\Coupon;
use App\Http\Resources\UserResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartProductResource;
use App\Http\Resources\OrderResource;
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
        $cart = Cart::with('products','products.media')->where('user_id', $user->id)->first();

        if ($cart) {
        return $this->sendResponse(
            CartResource::make($cart)
                ->response()
                ->getData(true),
            "User cart retrieved successfully"
        );
        } else {
            return $this->sendError("User cart is empty");
        }
    }

    //Empty user cart
    public function emptyUserCart()
    {
        $id = auth()->id();
        $user = User::where('id', $id)->first();
        Cart::where('user_id', $user->id)->delete();

       return $this->sendResponse([], "User cart emptied successfully" );
    }

    //Apply coupon
    public function applyCoupon(Request $request)
    {
        $id = auth()->id();
        $data = $request->validate([
            'coupon' => 'required|string|exists:coupons,name'
        ]);
        try {
            $coupon = Coupon::where('name', $data['coupon'])->firstOrFail();

            $user = User::where('id', $id)->first();
            $cart = Cart::where('user_id', $user->id)->firstOrFail();
            $cartTotal = $cart->cart_total;

            $totalAfterDiscount = $cartTotal - ($cartTotal * $coupon->discount / 100);
            // dd($totalAfterDiscount);
            $cart->update(['cart_total' => $totalAfterDiscount]);

            $updatedCart = Cart::where('user_id', $user->id)->first();
            $updatedCart->load('products');

            return $this->sendResponse(CartResource::make($updatedCart)
                ->response()
                ->getData(true), "Coupon applied successfully" );
        }
        catch (\Exception $e) {
            return $this->sendError($error, 'Invalid Coupon');
        }
    }

    //Create order
    public function createOrder(Request $request)
    {
        $request->validate([
            'COD' => 'required|boolean',
        ]);

        if (!$request->COD) {
            return $this->sendError($error = 'Create cash order failed');
        }

        $user = auth()->user();
        $cart = Cart::with('products')->where('user_id', $user->id)->first();

         if (!$cart || $cart->products->isEmpty()) {
            return $this->sendError('Your cart is empty.');
        }
        $finalAmount = $cart->cart_total;
        // dd($cart);

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

        foreach ($cart->products as $product) {

            if ($order->products->contains($product->id)) {
                $order->products()->updateExistingPivot($product->id, [
                    'count' => $product->pivot->count,
                    'color' => $product->pivot->color,
                    'price' => $product->pivot->price,
                    'updated_at' => now()
                ]);
            } else {
                $order->products()->attach($product->id, [
                    'id' => Str::uuid(),
                    'count' => $product->pivot->count,
                    'color' => $product->pivot->color,
                    'price' => $product->pivot->price
                ]);
            }

            $product->decrement('quantity', $product->pivot->count);
            $product->increment('sold', $product->pivot->count);
        }

        $cart->delete();
        $order->load('user','products');
        return $this->sendResponse(OrderResource::make($order)
                ->response()
                ->getData(true), "Order made successfully" );
    }

    //Get orders
    public function getOrders()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)->get();
        $orders->load('products');

        return $this->sendResponse(OrderResource::collection($orders)
                ->response()
                ->getData(true), "Orders retrieved successfully" );
    }

     //Get all orders
    public function getAllOrders()
    {
        $allOrders = Order::paginate(20);

        $allOrders->load(['products','user']);

        return $this->sendResponse(OrderResource::collection($allOrders)
                ->response()
                ->getData(true), "All orders retrieved successfully" );
    }

    //Update Order Status
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:Processing,Dispatched,Cancelled,Delivered'
        ]);
        if($order){
            $order->order_status = $request->status;
            
            $paymentIntent = json_decode($order->payment_intent, true);
            $paymentIntent['status'] = $request->status;
            $order->payment_intent = json_encode($paymentIntent);
            $order->save();

            $updatedOrder = Order::where('id', $order->id)->first();
            $updatedOrder->load('products');

            return $this->sendResponse(OrderResource::make($updatedOrder)
                ->response()
                ->getData(true), "Order status updated successfully" );
        }
    }

}