<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalogue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            // Which service's "menu"/"catalogue" keyword surfaces this entry — e.g. a
            // restaurant's dish only shows up under its ordering service, not under an
            // unrelated enquiry service on the same account.
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('catalogue_category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            // Minor units (e.g. cents) so pricing never relies on float arithmetic.
            // Nullable: some entries (a doctor, a course) are listed without a price.
            $table->unsignedInteger('price_minor_units')->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('catalogue_enabled')->default(false)->after('tool_fields');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('catalogue_enabled');
        });

        Schema::dropIfExists('catalogue_entries');
        Schema::dropIfExists('catalogue_categories');
    }
};
