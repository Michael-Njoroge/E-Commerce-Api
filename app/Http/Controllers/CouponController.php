<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Http\Resources\CouponResource;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coupons = Coupon::paginate(20);

        return $this->sendResponse(CouponResource::collection($coupons)
                ->response()
                ->getData(true), "Coupons retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:coupons,name',
            'expiry' => 'required|date_format:Y-m-d',
            'discount' => 'required|numeric'
        ]);

        $data['name'] = strtoupper($data['name']);

        $coupon = Coupon::create($data);

        return $this->sendResponse(CouponResource::make($coupon)
                ->response()
                ->getData(true), "Coupon created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Coupon $coupon)
    {
        if($coupon){
            return $this->sendResponse(CouponResource::make($coupon)
                ->response()
                ->getData(true), "Coupon retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Coupon $coupon)
    {
        if($coupon){
            $coupon->update($request->all());
            $updatedCoupon = Coupon::findOrFail($coupon->id);

            return $this->sendResponse(CouponResource::make($updatedCoupon)
                ->response()
                ->getData(true), "Coupon updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        if($coupon){
            $coupon->delete();

            return $this->sendResponse([], "Coupon deleted successfully" );
        }
    }
}
