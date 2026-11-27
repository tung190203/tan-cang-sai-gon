<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('tenant')->hasTable('port_locations')) {
            Schema::connection('tenant')->table('port_locations', function (Blueprint $table) {
                if (! Schema::connection('tenant')->hasColumn('port_locations', 'name_en')) {
                    $table->string('name_en')->nullable()->after('name');
                }
                if (! Schema::connection('tenant')->hasColumn('port_locations', 'short_name_en')) {
                    $table->string('short_name_en', 100)->nullable()->after('short_name');
                }
                if (! Schema::connection('tenant')->hasColumn('port_locations', 'popup_rows_en')) {
                    $table->json('popup_rows_en')->nullable()->after('popup_rows');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('port_locations')) {
            Schema::connection('tenant')->table('port_locations', function (Blueprint $table) {
                $columns = array_filter(['name_en', 'short_name_en', 'popup_rows_en'], function ($col) {
                    return Schema::connection('tenant')->hasColumn('port_locations', $col);
                });
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
