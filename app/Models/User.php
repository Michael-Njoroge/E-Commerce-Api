<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable,HasUuids,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     /**
     * Create a password reset token for the user.
     *
     * @return string
     */
    public function createResetPasswordToken()
    {
        $resetToken = Str::random(32);

        $this->password_reset_token = Hash::make($resetToken);
        $this->password_reset_expires = Carbon::now()->addMinutes(10);

        $this->save();

        return $resetToken;
    }

    public function wishlist()
    {
        return $this->belongsToMany(Product::class, 'user_products', 'user_id', 'product_id')
                    ->withTimestamps();;
    }

    public function likedBlogs()
    {
        return $this->belongsToMany(Blog::class,"likes");
    }

    public function dislikedBlogs()
    {
        return $this->belongsToMany(Blog::class,"dislikes");
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}