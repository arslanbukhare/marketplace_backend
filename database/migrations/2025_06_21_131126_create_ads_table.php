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
        Schema::create('ads', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        $table->foreignId('subcategory_id')->nullable()->constrained()->onDelete('set null');
        $table->string('title');
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->string('city');
        $table->string('address')->nullable();
        $table->string('contact_number')->nullable();
        $table->boolean('show_contact_number')->default(true);
        $table->boolean('is_featured')->default(false);
        $table->timestamp('featured_expires_at')->nullable();
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
