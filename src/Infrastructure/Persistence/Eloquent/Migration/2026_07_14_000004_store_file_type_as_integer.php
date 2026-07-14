<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->typeIsNumeric()) {
            return;
        }

        DB::table('stored_files')
            ->where('type', 'application/pdf')
            ->update(['type' => '1']);
        DB::table('stored_files')
            ->where('type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->update(['type' => '2']);

        Schema::table('stored_files', function (Blueprint $table): void {
            $table->unsignedTinyInteger('type')->change();
        });
    }

    public function down(): void
    {
        if (! $this->typeIsNumeric()) {
            return;
        }

        Schema::table('stored_files', function (Blueprint $table): void {
            $table->string('type', 127)->change();
        });

        DB::table('stored_files')->where('type', '1')->update(['type' => 'application/pdf']);
        DB::table('stored_files')->where('type', '2')->update([
            'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function typeIsNumeric(): bool
    {
        return in_array(Schema::getColumnType('stored_files', 'type'), ['tinyint', 'integer'], true);
    }
};
