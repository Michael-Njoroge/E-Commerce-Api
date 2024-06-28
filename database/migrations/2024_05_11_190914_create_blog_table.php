<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('description');
            $table->foreignUuid('category')->constrained('blog_categories')->onDelete('cascade');
            $table->integer('num_views')->default(0);
            $table->boolean('is_liked')->default(false);
            $table->boolean('is_disliked')->default(false);
            $table->string('author')->default('Admin');
            $table->timestamps();
        });

        Schema::create('blog_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'blog_id']);
        });

        Schema::create('blog_dislikes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'blog_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_dislikes');
        Schema::dropIfExists('blog_likes');
        Schema::dropIfExists('blogs');
    }
};
