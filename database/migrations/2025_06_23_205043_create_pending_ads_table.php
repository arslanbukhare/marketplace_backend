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
        Schema::create('pending_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->boolean('show_contact_number')->default(true);
            $table->unsignedBigInteger('featured_plan_id')->nullable();

            $table->json('dynamic_fields')->nullable();
            $table->json('images')->nullable(); // store filenames temporarily

            $table->string('status')->default('pending'); // 'pending', 'expired', etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_ads');
    }
};
