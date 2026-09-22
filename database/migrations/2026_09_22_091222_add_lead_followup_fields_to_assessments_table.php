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
            // Optional free-text note from the "Zatražite puni audit" /
            // "Zakažite sastanak" contact form on a quick-scan's results
            // page. Kept per-assessment (not on companies) since a company
            // could have more than one quick scan over time.
            $table->text('lead_message')->nullable()->after('quick_channel_relevance');

            // Set when the visitor actually asks to be contacted — lets the
            // Filament leads list distinguish "just took the quiz" from
            // "wants a follow-up".
            $table->timestamp('contact_requested_at')->nullable()->after('lead_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['lead_message', 'contact_requested_at']);
        });
    }
};
