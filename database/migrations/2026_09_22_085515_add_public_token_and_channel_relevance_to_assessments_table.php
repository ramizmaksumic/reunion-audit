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
        Schema::table('assessments', function (Blueprint $table) {
            // Unguessable id for the public quick-scan results page — never
            // the sequential `id`, so one lead can't browse another's report.
            $table->uuid('public_token')->nullable()->unique()->after('id');

            // Per-channel (web/gbp/social) relevance the visitor picks during
            // a quick scan's intro step: 'critical'/'recommended'/
            // 'not_relevant', mirroring CompanyChannelRelevance::RELEVANCE_LEVELS.
            // Kept as its own JSON blob rather than rows in
            // company_channel_relevance — that table's channel_key taxonomy
            // (11 granular channels) belongs to the full audit's methodology,
            // while quick-audit criteria only ever tag `channel` as one of
            // web/gbp/social.
            $table->json('quick_channel_relevance')->nullable()->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'quick_channel_relevance']);
        });
    }
};
