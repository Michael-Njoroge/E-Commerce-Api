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
    // Eager load the likes relationship
    $blogs = Blog::withCount('likedBy')->paginate(10);
    
    // Transform the collection of blogs into BlogResource
    $blogsResource = BlogResource::collection($blogs);
    
    // Extract the data from the resource
    $responseData = $blogsResource->response()->getData(true);

    // Modify each blog item to include the count of likes
    foreach ($responseData['data'] as &$blog) {
        $blog['likes_count'] = $blog['liked_by_count'];
        unset($blog['liked_by_count']);
    }

    // Return the modified response
    return $this->sendResponse($responseData, "Blogs retrieved successfully");
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

    //Like a blog
    public function likeBlog(Blog $blog)
    {
        if($blog){
            $user = auth()->user();

            // Check if the user has already disliked the blog.If yes, remove the dislike.
            if($blog->dislikedBy()->where('user_id', $user->id)->exists()){
                $blog->dislikedBy()->detach($user->id);
                $blog->is_disliked = false;
                $blog->save();

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog dislike removed successfully" );
            }

            // Check if the user has already liked the blog.If yes, remove the like.
            if($blog->likedBy()->where('user_id', $user->id)->exists()){
                $blog->likedBy()->detach($user->id);
                $blog->is_liked = false;
                $blog->save();

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog like removed successfully" );
            }

            //If the user has not yet liked the blog, add the like.
            $blog->likedBy()->attach($user->id);
            $blog->is_liked = true;
            $blog->save();

            return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog liked successfully" );
        }
    }

    //Dislike a blog
    public function dislikeBlog(Blog $blog)
    {
        if($blog){
            $user = auth()->user();

            // Check if the user has already liked the blog.If yes, remove the like.
            if($blog->likedBy()->where('user_id', $user->id)->exists()){
                $blog->likedBy()->detach($user->id);
                $blog->is_liked = false;
                $blog->save();

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog like removed successfully" );
            }

            // Check if the user has already disliked the blog.If yes, remove the dislike.
            if($blog->dislikedBy()->where('user_id', $user->id)->exists()){
                $blog->dislikedBy()->detach($user->id);
                $blog->is_disliked = false;
                $blog->save();

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog dislike removed successfully" );
            }

            //If the user has not yet disliked the blog, add the dislike.
            $blog->dislikedBy()->attach($user->id);
            $blog->is_disliked = true;
            $blog->save();

            return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog disliked successfully" );
        }
    }
}
