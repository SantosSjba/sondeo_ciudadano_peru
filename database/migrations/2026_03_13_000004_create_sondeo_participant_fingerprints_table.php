<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sondeo_participant_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('sondeo_campaigns')->cascadeOnDelete();
            $table->char('fingerprint_hash', 64);
            $table->timestamps();
            $table->unique(['campaign_id', 'fingerprint_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sondeo_participant_fingerprints');
    }
};
