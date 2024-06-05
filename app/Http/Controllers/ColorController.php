<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Http\Resources\ColorResource;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $colors = Color::paginate(20);

        return $this->sendResponse(ColorResource::collection($colors)
                ->response()
                ->getData(true), "Colors retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|unique:colors,title',
        ]);

        $color = Color::create($data);

        return $this->sendResponse(ColorResource::make($color)
                ->response()
                ->getData(true), "Color created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Color $color)
    {
        if($color){
            return $this->sendResponse(ColorResource::make($color)
                ->response()
                ->getData(true), "Color retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Color $color)
    {
        if($color){
            $color->update($request->all());
            $updatedColor = Color::findOrFail($color->id);

            return $this->sendResponse(ColorResource::make($updatedColor)
                ->response()
                ->getData(true), "Color updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Color $color)
    {
        if($color){
            $color->delete();

            return $this->sendResponse([], "Color deleted successfully" );
        }
    }
}

