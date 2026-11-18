<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('port_locations')) {
            Schema::connection('tenant')->create('port_locations', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->string('short_name', 100);
                $table->string('region', 20)->default('nam')->index();
                $table->boolean('is_hq')->default(false);
                $table->boolean('has_tour')->default(false);
                $table->string('tour_url', 500)->nullable();
                $table->string('image_path', 500)->nullable();
                $table->json('popup_rows')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('map_layers')) {
            Schema::connection('tenant')->create('map_layers', function (Blueprint $table) {
                $table->id();
                $table->string('layer_key', 50)->unique();
                $table->string('name', 120);
                $table->boolean('default_enabled')->default(false);
                $table->boolean('is_visible')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('map_layers');
        Schema::connection('tenant')->dropIfExists('port_locations');
    }
};
