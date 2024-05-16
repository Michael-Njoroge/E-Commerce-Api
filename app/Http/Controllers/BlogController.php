<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::with('likedBy','dislikedBy')->paginate(10);
        return $this->sendResponse(BlogResource::collection($blogs)
                ->response()
                ->getData(true), "Blogs retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "title"=> "string|required",
            "description" => "required|string",
            "category" => "required|string"
        ]);

        $blog = Blog::create($data);
        $createdBlog = Blog::findOrFail($blog->id);

        return $this->sendResponse(BlogResource::make($createdBlog)
                ->response()
                ->getData(true), "Blog created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        if($blog){
         return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog retrieved successfully" );
        }
        return $this->sendError($error = "Blog is not found");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        if($blog){
            $blog->update($request->all());
            $updatedBlog = Blog::findOrFail($blog->id);

            return $this->sendResponse(BlogResource::make($updatedBlog)
                ->response()
                ->getData(true), "Blog updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        $blog = Blog::where('id',$blog->id)->first();

        if(!$blog){
            return $this->sendError($error="Blog not found");
        }
        
        $blog->delete();
        return $this->sendResponse($result='', $message="Blog deleted successfully");
    }
}
