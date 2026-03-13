<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sondeo_votes', function (Blueprint $table) {
            $table->char('fingerprint_hash', 64)->nullable()->after('candidate_id');
        });
        Schema::table('sondeo_votes', function (Blueprint $table) {
            $table->unique(['campaign_id', 'fingerprint_hash'], 'sv_camp_fp_uq');
        });
    }

    public function down(): void
    {
        Schema::table('sondeo_votes', function (Blueprint $table) {
            $table->dropUnique('sv_camp_fp_uq');
            $table->dropColumn('fingerprint_hash');
        });
    }
};
