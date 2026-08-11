<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $t) {
            $t->id();

            // Whose "drive" this entry lives in. Every query is scoped by this column;
            // non-admins are hard-locked to their own id, admins can switch.
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Virtual path, e.g. "/Reports/Q1.pdf". This is the literal "id" the SVAR
            // File Manager uses on the frontend — it derives parent/name by splitting
            // on "/", so the path IS the identity, not a display convenience.
            // Keep indexed paths below MySQL/InnoDB's 3072-byte key limit.
            // With utf8mb4, 700 chars plus the 8-byte owner_id uses at most
            // 2808 bytes for each composite index.
            $t->string('path', 700);
            $t->string('name');
            $t->string('parent_path', 700);
            $t->enum('type', ['file', 'folder']);

            // Physical storage is decoupled from the virtual path/name so renaming or
            // moving a file is a pure DB update — no filesystem move needed.
            $t->string('disk')->default('documents');
            $t->string('storage_path')->nullable();
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('size')->default(0);

            $t->timestamps();

            $t->unique(['owner_id', 'path']);
            $t->index(['owner_id', 'parent_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
