<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->unsignedTinyInteger('type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('uploaded_at', precision: 6);
            $table->timestamp('expires_at', precision: 6);

            $table->index(['uploaded_at', 'id'], 'stored_files_uploaded_at_id_index');
            $table->index('expires_at', 'stored_files_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
