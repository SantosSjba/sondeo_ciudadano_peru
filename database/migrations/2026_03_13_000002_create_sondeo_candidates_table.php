<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sondeo_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('sondeo_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('party_name')->nullable();
            $table->string('short_label', 32)->nullable();
            $table->string('photo_url')->nullable();
            $table->string('party_logo_url')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['campaign_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sondeo_candidates');
    }
};
