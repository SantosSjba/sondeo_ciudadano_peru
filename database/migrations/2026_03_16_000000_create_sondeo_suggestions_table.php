<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sondeo_suggestions', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('contact_email', 255)->nullable();
            $table->char('fingerprint_hash', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sondeo_suggestions');
    }
};
