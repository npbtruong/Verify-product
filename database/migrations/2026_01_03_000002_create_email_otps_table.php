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
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('code_hash');
            $table->string('purpose')->index();
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['email', 'product_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
