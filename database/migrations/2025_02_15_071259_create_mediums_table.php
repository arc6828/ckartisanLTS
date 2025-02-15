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
        Schema::create('mediums', function (Blueprint $table) {
            $table->id();

            $table->string('guid');
            $table->string('slug')->nullable();
            $table->string('image')->nullable();
            $table->string('first_paragraph',1024)->nullable();
            $table->string('publication')->nullable();
            $table->json('category')->nullable();
            $table->json('data')->nullable();

            // $table->string('title')->nullable();
            // $table->string('link',1024)->nullable();
            // $table->string('category')->nullable();
            // $table->string('creator')->nullable();
            // $table->timestamp('pubDate')->nullable();
            // $table->text('contentEncoded')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediums');
    }
};
