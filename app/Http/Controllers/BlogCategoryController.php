<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use Illuminate\Http\Request;
use App\Http\Resources\BlogCategoryResource;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = BlogCategory::paginate(20);

        return $this->sendResponse(BlogCategoryResource::collection($categories)
                ->response()
                ->getData(true), "Blog categories retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|unique:blog_categories,title',
        ]);

        $category = BlogCategory::create($data);

        return $this->sendResponse(BlogCategoryResource::make($category)
                ->response()
                ->getData(true), "Blog category created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(BlogCategory $blogCategory)
    {
        if($blogCategory){
            return $this->sendResponse(BlogCategoryResource::make($blogCategory)
                ->response()
                ->getData(true), "Blog category retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BlogCategory $blogCategory)
    {
        if($blogCategory){
            $blogCategory->update($request->all());
            $updatedCategory = BlogCategory::findOrFail($blogCategory->id);

            return $this->sendResponse(BlogCategoryResource::make($updatedCategory)
                ->response()
                ->getData(true), "Blog category updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogCategory $blogCategory)
    {
        if($blogCategory){
            $blogCategory->delete();

            return $this->sendResponse([], "Blog category deleted successfully" );
        }
    }
}
