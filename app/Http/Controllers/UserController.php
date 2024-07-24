<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Models\ShippingInfo;
use App\Models\PaymentInfo;
use App\Models\Coupon;
use App\Http\Resources\UserResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartProductResource;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::paginate(20);

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
        return $this->sendResponse(UserResource::make($user)
                ->response()
                ->getData(true), "User retrieved successfully" );
        }

        return $this->sendError($error="User not found");
       
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if($user){
            $user->update($request->all());
            $updatedUser = User::findOrFail($user->id);
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

            return $this->sendResponse(UserResource::make($updatedUser)
                ->response()
                ->getData(true), "User address added successfully");
        }
    }

    //Add To Cart
    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product' => 'required|uuid|exists:products,id',
            'color' => 'required|uuid|exists:colors,id',
            'quantity' => 'required|numeric',
        ]);

        $user = auth()->user();

        $product = Product::find($data['product']);

        if (!in_array($data['color'], $product->color)) {
            return $this->sendError('Selected color is not available for this product');
        }

        $quantity = $data['quantity'];
        $color = $data['color'];
        $price = $product->price;

        if ($quantity > $product->quantity) {
            return $this->sendError('Requested quantity exceeds available stock');
        }

        $cartTotal = $price * $quantity;

        $cart = Cart::firstOrCreate([
        'user_id' => $user->id,
        ], [
            'cart_total' => 0
        ]);

        $existingCartItem = $cart->products()->where('product_id', $product->id)->where('cart_product.color', $color)->first();

        if ($existingCartItem) {
            $newQuantity = $existingCartItem->pivot->quantity + $quantity;

            if ($newQuantity > $product->quantity) {
                return $this->sendError('Your cart quantity exceeds available stock');
            }

            $existingCartItem->pivot->quantity = $newQuantity;
            $existingCartItem->pivot->price += $cartTotal;
            $existingCartItem->pivot->save();
        } else {
            $cart->products()->attach($product->id, [
                'quantity' => $quantity,
                'color' => $color,
                'price' => $price
            ]);
        }

        $cart->cart_total += $cartTotal;
        $cart->save();

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
        return $this->sendResponse(CartResource::make($cart)
                ->response()
                ->getData(true),"User cart retrieved successfully");
        } else {
            return $this->sendError("Your cart is empty");
        }
    }

  // Remove a product from the cart
    public function removeProductFromCart(Request $request)
    {
        $data = $request->validate([
            'product' => 'required|uuid|exists:products,id',
            'color' => 'required|uuid|exists:colors,id',
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return $this->sendError("User cart is empty");
        }

        $product = Product::find($data['product']);
        if (!in_array($data['color'], $product->color)) {
            return $this->sendError('Selected color is not available for this product');
        }

        $color = $data['color'];

        $cartItem = $cart->products()->where('product_id', $product->id)->where('cart_product.color', $color)->first();

        if (!$cartItem) {
            return $this->sendError("Product not found in cart");
        }

        $cartTotalReduction = $cartItem->pivot->price * $cartItem->pivot->quantity;

        // Detach the product from the cart
        $cart->products()->detach($product->id);

        // Update the cart total
        $cart->cart_total -= $cartTotalReduction;
        $cart->save();

        // $cart->load(['user','products']);

        return $this->sendResponse(CartResource::make($cart)
                ->response()
                ->getData(true),"Product removed from cart successfully");
    }

    // Update cart product quantity
    public function updateProductQuantity(Request $request)
    {
        $data = $request->validate([
            'product' => 'required|uuid|exists:products,id',
            'color' => 'required|uuid|exists:colors,id',
            'quantity' => 'required|numeric',
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return $this->sendError("User cart is empty");
        }

        $product = Product::find($data['product']);
        if (!in_array($data['color'], $product->color)) {
            return $this->sendError('Selected color is not available for this product');
        }

        $color = $data['color'];
        $quantity = $data['quantity'];

        if ($quantity > $product->quantity) {
                return $this->sendError('The quantity exceeds available stock');
        }

        $cartItem = $cart->products()->where('product_id', $product->id)->where('cart_product.color', $color)->first();

        if (!$cartItem) {
            return $this->sendError("Product not found in cart");
        }

        // Calculate the old total price of this item in the cart
        $oldTotal = $cartItem->pivot->price * $cartItem->pivot->quantity;

        $cartItem->pivot->quantity = $quantity;
        $cartItem->pivot->save();

        // Calculate the new total price of this item in the cart
        $newTotal = $cartItem->pivot->price * $quantity;

        // Update the cart total
        $cart->cart_total = $cart->cart_total - $oldTotal + $newTotal;
        $cart->save();

        
        return $this->sendResponse(CartResource::make($cart)
                ->response()
                ->getData(true),"Product quantity updated successfully");
    }


    //Empty user cart
    public function emptyUserCart()
    {
        $user = auth()->user();
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

    //Get user orders
    public function getUserOrders(User $user)
    {
        $orders = Order::where('user_id', $user->id)->with(['items', 'items.product', 'items.color', 'shippingInfo', 'paymentInfo'])->get();
         // dd($orders->toArray());

        return $this->sendResponse(OrderResource::collection($orders)
                ->response()
                ->getData(true), "User orders retrieved successfully" );
    }

    //Get all orders
    public function getAllOrders()
    {
        // Fetch all orders with relationships
        $allOrders = Order::with(['items', 'user', 'items.product', 'items.color', 'shippingInfo', 'paymentInfo'])->get();

        // Group orders by users
        $groupedOrders = $allOrders->groupBy('user_id')->map(function ($orders, $userId) {
            return [
                'orders' => OrderResource::collection($orders)->resolve(),
            ];
        })->values();
        return $this->sendResponse(['data' => $groupedOrders], "All orders retrieved successfully");
    }


    //Get orders month wise
    public function getOrdersMonthWise()
    {
        $monthNames = ["January","February","March","April","May","June","July",
            "August","September","October","November","December"];
        $ordersMonthWise = [];
        for ($i = 0; $i < 12; $i++) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = Carbon::now()->subMonths($i)->endOfMonth();
            $monthName = $monthNames[$startOfMonth->month - 1] . " " . $startOfMonth->year;

            $orders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->select(DB::raw('SUM(total_price_after) as amount'))
                    ->first();
            $orderCount = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();
            $amount = round($orders->amount ?? 0);

        if ($amount > 0 || $orderCount > 0) {
                $data[] = [
                    'month' => $monthName,
                    'amount' => $amount,
                    'count' => $orderCount,
                ];
            }
        }
         return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Monthly data successfully',

        ]);
    }

    //Get yearly total orders
 public function getYearlyTotalOrders()
{
    $currentYear = Carbon::now()->year;
    $startOfYear = Carbon::now()->startOfYear();
    $endOfYear = Carbon::now()->endOfYear();

    // Fetch distinct years with orders
    $yearsWithData = Order::selectRaw('YEAR(created_at) as year')
        ->groupBy('year')
        ->get()
        ->pluck('year');

    $responses = [];
    foreach ($yearsWithData as $year) {
        $startOfThisYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfThisYear = Carbon::createFromDate($year, 12, 31)->endOfYear();

        $totalOrders = Order::whereBetween('created_at', [$startOfThisYear, $endOfThisYear])->count();
        $totalAmount = Order::whereBetween('created_at', [$startOfThisYear, $endOfThisYear])->sum('total_price_after');

        $responses[] = [
            'year' => $year,
            'total_orders' => $totalOrders,
            'total_amount' => round($totalAmount),
        ];
    }

    return response()->json([
        'success' => true,
        'data' =>$responses,
        'message' => 'Yearly data successfully',
        ]);
}

    //Update Order Status
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:Ordered,Processing,Dispatched,Cancelled,Delivered'
        ]);
        if($order){
            $order->order_status = $request->status;
            $order->save();
            $updatedOrder = Order::where('id', $order->id)->first();
            $updatedOrder->load(['items', 'user', 'items.product', 'items.color', 'shippingInfo', 'paymentInfo']);

            return $this->sendResponse(OrderResource::make($updatedOrder)
                ->response()
                ->getData(true), "Order status updated successfully" );
        }
    }

}