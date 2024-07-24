<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ShippingInfo;
use App\Models\Coupon;
use App\Models\PaymentInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

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
            'shipping_amount' => 'required|numeric',
        ]);

        Session::put('shipping_info', $request->input('shipping_info'));
        Session::put('shipping_amount', $request->input('shipping_amount'));
        Log::info('Shipping Info Stored in Session:', Session::all());

        $user = auth()->user();
        $cart = Cart::with('products', 'products.media')->where('user_id', $user->id)->first();

        if (!$cart || $cart->products->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 400);
        }

        $amount = $cart->products->sum(function($product) {
            return $product->pivot->quantity * $product->price;
        }) + ($request->shipping_amount);

        // $amount = 1;

        $phone = $user->mobile;
        $accountReference = 'Order-' . Str::uuid();
        $transactionDesc = 'Payment for Order ' . $accountReference;

        // M-Pesa STK Push URL
        $url = env('MPESA_ENVIRONMENT') === 0
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $response = $this->initiateStkPush($amount, $phone, $accountReference, $transactionDesc);

        return response()->json(['response' => $response->json()]);
    }

    private function initiateStkPush($amount, $phone, $accountReference, $transactionDesc)
    {
        $timeStamp = now()->format('YmdHis');
        $password = env('MPESA_STK_SHORTCODE') . env('MPESA_PASSKEY') . $timeStamp;
        $access_token = $this->getAccessToken();
        $hashedPassword = base64_encode($password);

        $body = [
            "BusinessShortCode" => env('MPESA_STK_SHORTCODE'),
            "Password" => $hashedPassword,
            "Timestamp" => $timeStamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $this->formatPhoneNumber($phone),
            "PartyB" => env('MPESA_STK_SHORTCODE'),
            "PhoneNumber" => $this->formatPhoneNumber($phone),
            "CallBackURL" => env('MPESA_TEST_URL') . '/api/confirmation',
            "AccountReference" => $accountReference,
            "TransactionDesc" => $transactionDesc
        ];

        $url = env('MPESA_ENVIRONMENT') === '0' 
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $response =Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json'
        ])->post($url, $body);
        return $response;
    }

    private function getAccessToken()
    {
        $url = env('MPESA_ENVIRONMENT') === 0
            ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json; charset=utf8',
        ])->withBasicAuth(env('MPESA_CONSUMER_KEY'), env('MPESA_CONSUMER_SECRET'))
          ->get($url);

        if ($response->successful()) {
            $responseData = $response->json();
            return $responseData['access_token'] ?? null;
        }

        return null;
    }

    private function formatPhoneNumber($phone)
    {
        $phone = (substr($phone, 0, 1) == "+") ? str_replace("+", "", $phone) : $phone;
        $phone = (substr($phone, 0, 1) == "0") ? preg_replace("/^0/", "254", $phone) : $phone;
        $phone = (substr($phone, 0, 1) == "7") ? "254{$phone}" : $phone;
        return $phone;
    }
}
