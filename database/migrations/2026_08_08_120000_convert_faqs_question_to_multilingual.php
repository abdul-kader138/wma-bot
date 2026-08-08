<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('question', 'question_old');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->json('question')->nullable()->after('service');
        });

        DB::table('faqs')->get(['id', 'question_old'])->each(function ($row) {
            DB::table('faqs')->where('id', $row->id)->update([
                'question' => json_encode(['en' => $row->question_old]),
            ]);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('question_old');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->renameColumn('question', 'question_json');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question')->nullable()->after('service');
        });

        DB::table('faqs')->get(['id', 'question_json'])->each(function ($row) {
            $data = json_decode($row->question_json, true) ?? [];
            DB::table('faqs')->where('id', $row->id)->update([
                'question' => $data['en'] ?? (array_values($data)[0] ?? ''),
            ]);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('question_json');
        });
    }
};
