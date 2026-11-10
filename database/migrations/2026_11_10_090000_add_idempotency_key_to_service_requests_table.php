<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // The inbound webhook message ID whose Claude tool call produced this
            // request — lets a retried/released job re-run the same turn without
            // creating a second request for the same confirmed submission.
            $table->string('source_message_id')->nullable()->after('whatsapp_account_id');
            $table->unique(['whatsapp_account_id', 'source_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_account_id', 'source_message_id']);
            $table->dropColumn('source_message_id');
        });
    }
};
