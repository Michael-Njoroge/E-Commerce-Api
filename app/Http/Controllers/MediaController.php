<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\BlogResource;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Models\Media;
use App\Models\Product;
use App\Models\Blog;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'model_type' => 'required|string',
            'model_id' => 'required|string',
        ]);

        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');
        $folder = $modelType === 'product' ? 'products' : 'blogs';
        $mediaEntries = [];

       foreach ($request->file('files') as $file) {

            $file_type = $file->getMimeType();
            $file_size = $file->getSize();
            $file_name = $file->getClientOriginalName();
            $file_extension = time(). '.' .$file->getClientOriginalExtension();
            $tempPath = storage_path('app/public/temp/' . $file_extension);
            $file->move(storage_path('app/public/temp'), $file_extension);

            $file_manager = new ImageManager(new Driver());
            $thumbImage = $file_manager->read($tempPath);
            $thumbImage->resize(300, 300);

            $thumbImage->save($tempPath);
            
            // Upload to Cloudinary
            $uploadedFileUrl = Cloudinary::upload($tempPath, [
                'folder' => $folder,
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ])->getSecurePath();

            // Delete the temporary file
            Storage::delete('public/temp/' . $file_extension);

            // Save the file info to the media table
            $media = new Media();
            $media->file_url = $uploadedFileUrl;
            $media->file_name = $file_name;
            $media->file_type = $file_type;
            $media->size = $file_size;
             $media->medially_id = $modelId;
            $media->medially_type = $modelType === 'product' ? Product::class : Blog::class;
            $media->save();

            $mediaEntries[] = $media;
        }

        if ($modelType === 'product') {
            $product = Product::findOrFail($modelId);
            $product->media()->saveMany($mediaEntries);
            $product->load('media');
                
            return $this->sendResponse(ProductResource::make($product)
                ->response()
                ->getData(true), 'Product image uploaded successfully');

        } elseif ($modelType === 'blog') {
            $blog = Blog::findOrFail($modelId);
            $blog->media()->saveMany($mediaEntries);
            $blog->load('media');
            $blog->loadCount('likedBy');
            $blog->loadCount('dislikedBy');

            return $this->sendResponse(BlogResource::make($blog)
                ->response()
                ->getData(true), 'Blog image uploaded successfully');

        } else {
            return $this->sendError($error = 'Invalid model type');
        }
    }
}
