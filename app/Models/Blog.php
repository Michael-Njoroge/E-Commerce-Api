<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory,HasUuids;

    protected $guarded = [];

    public function likedBy()
    {
        return $this->belongsTo(User::class, "likes");
    }

    public function dislikedBy()
    {
        return $this->belongsTo(User::class, "dislikes");
    }
}
