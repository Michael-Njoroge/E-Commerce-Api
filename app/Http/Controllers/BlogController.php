<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogResource;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::with(['likedBy', 'dislikedBy', 'media', 'category'])->withCount('likedBy', 'dislikedBy')->paginate(10);
        $blogsResource = BlogResource::collection($blogs);
        $responseData = $blogsResource->response()->getData(true);

        foreach ($responseData['data'] as &$blog) {
            $blog['likes'] = $blog['likes'];
            $blog['dislikes'] = $blog['dislikes'];
            $blog['liked_by'] = UserResource::collection(Blog::find($blog['id'])->likedBy);
            $blog['disliked_by'] = UserResource::collection(Blog::find($blog['id'])->dislikedBy);
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
            "category" => "required|uuid|exists:blog_categories,id",
            'media_ids' => 'nullable|array', 
            'media_ids.*' => 'uuid|exists:media,id',
        ]);

        $mediaData = $request->input('media_ids');

        unset($data['media_ids']);

        $blog = Blog::create($data);
        if(!empty($mediaData)) {
            Media::whereIn('id', $mediaData)
                ->update(['medially_id' => $blog->id, 'medially_type' => Blog::class]);
        }
        $createdBlog = Blog::findOrFail($blog->id);
        $createdBlog->loadCount(['likedBy','dislikedBy']);
        $createdBlog->load('media');

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
        $blog->loadCount(['likedBy','dislikedBy']);
        $blog->load('media');

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
    if ($blog) {
        $mediaData = $request->input('media_ids', []);
        if (!empty($mediaData)) {
            Media::whereIn('id', $mediaData)
                ->update(['medially_id' => $product->id, 'medially_type' => Product::class]);
        }
        $requestData = $request->except('media_ids');

        $blog->update($requestData);

        $updatedBlog = Blog::findOrFail($blog->id);
        $updatedBlog->loadCount(['likedBy', 'dislikedBy']);
        $updatedBlog->load('media');

        return $this->sendResponse(BlogResource::make($updatedBlog)
            ->response()
            ->getData(true), "Blog updated successfully");
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

        $mediaItems = Media::where('medially_id', $blog->id)
                       ->where('medially_type', Blog::class)
                       ->get();

        foreach ($mediaItems as $mediaItem) {
            try {
                Cloudinary::destroy($mediaItem->public_id);
                $mediaItem->delete();
            } catch (\Exception $e) {
                return $this->sendError($error = "Failed to delete image from Cloudinary");
            }
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
                $blog->loadCount(['likedBy','dislikedBy']);
                $blog->load('media');

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog dislike removed successfully" );
            }

            // Check if the user has already liked the blog.If yes, remove the like.
            if($blog->likedBy()->where('user_id', $user->id)->exists()){
                $blog->likedBy()->detach($user->id);
                $blog->is_liked = false;
                $blog->save();
                $blog->loadCount(['likedBy','dislikedBy']);
                $blog->load('media');
                
                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog like removed successfully" );
            }

            //If the user has not yet liked the blog, add the like.
            $blog->likedBy()->attach($user->id);
            $blog->is_liked = true;
            $blog->save();
            $blog->loadCount(['likedBy','dislikedBy']);
            $blog->load('media');

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
                $blog->loadCount(['likedBy','dislikedBy']);
                $blog->load('media');

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog like removed successfully" );
            }

            // Check if the user has already disliked the blog.If yes, remove the dislike.
            if($blog->dislikedBy()->where('user_id', $user->id)->exists()){
                $blog->dislikedBy()->detach($user->id);
                $blog->is_disliked = false;
                $blog->save();
                $blog->loadCount(['likedBy','dislikedBy']);
                $blog->load('media');

                return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog dislike removed successfully" );
            }

            //If the user has not yet disliked the blog, add the dislike.
            $blog->dislikedBy()->attach($user->id);
            $blog->is_disliked = true;
            $blog->save();
            $blog->loadCount(['likedBy','dislikedBy']);
            $blog->load('media');

            return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), "Blog disliked successfully" );
        }
    }
}
