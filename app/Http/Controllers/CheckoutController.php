<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ShippingInfo;
use App\Models\Coupon;
use App\Models\PaymentInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
         $request->validate([
            'shipping_info' => 'required|array',
            'shipping_info.firstname' => 'required|string',
            'shipping_info.lastname' => 'required|string',
            'shipping_info.address' => 'required|string',
            'shipping_info.city' => 'required|string',
            'shipping_info.country' => 'required|string',
            'shipping_info.state' => 'required|string',
            'shipping_info.pincode' => 'required|string',
            'shipping_amount' => 'required|string',
            'shipping_amount' => 'required|numeric',
        ]);

        $user = auth()->user();
        $cart = Cart::with('products','products.media')->where('user_id', $user->id)->first();

        if (!$cart || $cart->products->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));
        
         $lineItems = $cart->products->map(function($product) {
            $imageUrl = $product->media->isNotEmpty() ? $product->media->first()->file_url : '';
            // dd($product->pivot->quantity);
            // dd($product->media);

            return [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product['title'],
                        'images' => [$imageUrl],
                    ],
                     'unit_amount' => $product['price'] * 100,
                ],
                'quantity' => $product->pivot->quantity,
            ];
        })->toArray();

        $shippingAmount = $request->shipping_amount; 

        // Add shipping as a line item
        $lineItems[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Shipping',
                ],
                'unit_amount' => $shippingAmount * 100,
            ],
            'quantity' => 1,
        ];

        try {
            $checkoutSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => env('BASE_URL') . '/checkout-success',
                'cancel_url' => env('BASE_URL') . '/checkout',
                'metadata' => [
                    'user_id' => $user->id,
                    'shipping_info' => json_encode($request->shipping_info),
                ],
            ]);

            return response()->json(['id' => $checkoutSession->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
