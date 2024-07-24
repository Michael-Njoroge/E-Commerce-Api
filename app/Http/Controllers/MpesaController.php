<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\PaymentInfo;
use App\Models\ShippingInfo;
use App\Models\Cart;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class MpesaController extends Controller
{
    public function handleCallback(Request $request)
    {
        $payload = $request->all();
        Log::info($payload);

        if ($this->isValidCallback($payload)) {
            $shippingInfo = Session::get('shipping_info');

            if (!$shippingInfo) {
                return $this->sendError($error = 'Shipping information not found.');
            }

            try {
                $this->createOrderFromCallback($payload, $shippingInfo);
                Session::forget('shipping_info');
                Session::forget('applied_coupon');
                Session::forget('shipping_amount');
                return response()->json(['status' => 'success']);
            } catch (\Exception $e) {
                Log::error('Order creation failed: ' . $e->getMessage());
                return $this->sendError($error = 'Order creation failed.');
            }
        }

        return $this->sendError($error = 'Invalid callback.');
    }

    private function isValidCallback($payload)
    {
       return isset($payload['Body']['stkCallback']['ResultCode']) &&
               $payload['Body']['stkCallback']['ResultCode'] == 0;
    }

    private function createOrderFromCallback($payload, $shippingInfo)
    {
        DB::beginTransaction();

        try {
            $result = $payload['Body']['stkCallback']['ResultCode'];
            $amount = $payload['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
            $phone = $payload['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];
            $transactionId = $payload['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
            $paymentDescription = 'Payment for Order';

            $user = User::where('mobile', $phone)->first();

            // Retrieve cart and other necessary details
            $cart = Cart::with('products')->where('user_id', $user->id)->first();

            if (!$cart || $cart->products->isEmpty()) {
                throw new \Exception('Cart is empty or not found.');
            }

            $couponName = Session::get('applied_coupon');
            $shippingAmount = Session::get('shipping_amount', 0);

            if ($couponName) {
                $coupon = Coupon::where('name', $couponName)->first();
                if ($coupon) {
                    $discount = $coupon->discount;
                } else {
                    $discount = 0; 
                }
            } else {
                $discount = 0;
            }

            $cartTotal = $cart->cart_total;
            $totalAfterDiscount = $cartTotal - ($cartTotal * $discount / 100);
            $totalPriceWithShipping = $totalAfterDiscount + $shippingAmount;

            // Save shipping info
            $shipping = ShippingInfo::create($shippingInfo);

            // Save payment info
            $payment = PaymentInfo::create([
                'amount' => $totalPriceWithShipping,
                'payment_method' => 'M-Pesa',
                'transaction_id' => $transactionId,
                'payment_description' => $paymentDescription,
                'phone_number' => $phone,
            ]);

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_info_id' => $shipping->id,
                'payment_info_id' => $payment->id,
                'total_price' => $cartTotal,
                'total_price_after' => $totalAfterDiscount,
                'shipping_amount' => $shippingAmount,
                'order_status' => 'Completed',
            ]);

            // Add order items
            foreach ($cart->products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'color_id' => $product->pivot->color,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                ]);

                // Update product quantities
                $product->decrement('quantity', $product->pivot->quantity);
                $product->increment('sold', $product->pivot->quantity);
            }

            $cart->delete();
            Session::forget('applied_coupon');
            Session::forget('shipping_amount');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}


