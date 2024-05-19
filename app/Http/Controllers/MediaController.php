<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'model_type' => 'required|string',
            'model_id' => 'required|string',
        ]);

        $file = $request->file('file');

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
            'folder' => 'products',
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

        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');

        if ($modelType === 'product') {
            $product = Product::findOrFail($modelId);
            $product->media()->save($media);
        } elseif ($modelType === 'blog') {
            $blog = Blog::findOrFail($modelId);
            $blog->media()->save($media);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid model type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $media,
            'message' => $modelType === 'product' ? 'Product image uploaded successfully' : 'Blog image uploaded successfully',
        ]);
    }
}
