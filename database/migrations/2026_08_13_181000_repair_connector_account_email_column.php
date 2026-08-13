<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('connector_accounts', 'email')) {
            Schema::table('connector_accounts', function (Blueprint $table) {
                $table->string('email')->nullable()->after('provider_account_id');
            });
        }
    }

    public function down(): void
    {
        // The column is part of the foundation schema on fresh installations.
        // Do not remove it when rolling back this compatibility-only migration.
    }
};
