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
        Schema::table('companies', function (Blueprint $table) {
            // Lead-qualification fields from the quick_audit methodology
            // section (not scored — sales context only). market_scope
            // already covers "geografsko tržište", so it's reused as-is.
            $table->string('company_size')->nullable()->after('market_scope');
            $table->string('primary_goal')->nullable()->after('company_size');
            $table->string('primary_acquisition_channel')->nullable()->after('primary_goal');

            // Contact details, captured only once a quick-scan visitor
            // actually asks for a full audit / a call — never required just
            // to see their score.
            $table->string('contact_name')->nullable()->after('primary_acquisition_channel');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_phone')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'company_size',
                'primary_goal',
                'primary_acquisition_channel',
                'contact_name',
                'contact_email',
                'contact_phone',
            ]);
        });
    }
};
