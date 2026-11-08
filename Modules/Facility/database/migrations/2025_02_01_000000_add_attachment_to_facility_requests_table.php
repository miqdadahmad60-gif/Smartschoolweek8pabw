<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom untuk file surat/bukti pengajuan fasilitas (input upload modul AJAX).
 * attachment_path : path relatif pada disk public, mis. facility-attachments/xxxx.pdf
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('facility_requests', 'attachment_path')) {
            Schema::table('facility_requests', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('teacher_note');
                $table->string('attachment_name')->nullable()->after('attachment_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('facility_requests', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
