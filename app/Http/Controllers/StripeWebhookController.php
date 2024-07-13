<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\ShippingInfo;
use App\Models\PaymentInfo;
use App\Models\Cart;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $this->createOrder($session);
        }

        return response()->json(['status' => 'success']);
    }

    private function createOrder($session)
    {
        DB::beginTransaction();

        try {
            $userId = $session->metadata->user_id;
            $shippingInfo = json_decode($session->metadata->shipping_info, true);
            $paymentId = $session->payment_intent;
            $amountTotal = $session->amount_total / 100;
            // $shippingAmount = $session->shipping_cost ? $session->shipping_cost->amount_total / 100 : 0;
            // $discountAmount = isset($session->total_details->amount_discount) ? $session->total_details->amount_discount / 100 : 0;

            $shipping = ShippingInfo::create($shippingInfo);

            $payment = PaymentInfo::create([
                'stripe_payment_id' => $paymentId,
                'amount' => $amountTotal,
            ]);

            $order = Order::create([
                'user_id' => $userId,
                'shipping_info_id' => $shipping->id,
                'payment_info_id' => $payment->id,
                'total_price' => $amountTotal,
                // 'total_price_after' => $amountTotal + $shippingAmount,
                // 'shipping_amount' => $shippingAmount,
                // 'discount_amount' => $discountAmount,
                'order_status' => 'Processing',
            ]);

            $cart = Cart::with('products')->where('user_id', $userId)->first();
            foreach ($cart->products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'color_id' => $product->pivot->color,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                ]);

                $product->decrement('quantity', $product->pivot->quantity);
                $product->increment('sold', $product->pivot->quantity);
            }

            $cart->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

