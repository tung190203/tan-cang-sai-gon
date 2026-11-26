<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $defaultIps = [
            ['ip_address' => '127.0.0.1', 'label' => 'Localhost IPv4'],
            ['ip_address' => '::1', 'label' => 'Localhost IPv6'],
            ['ip_address' => '192.168.1.57', 'label' => 'Máy dev local'],
        ];

        foreach ($defaultIps as $entry) {
            DB::table('ip_whitelists')->insert([
                'ip_address' => $entry['ip_address'],
                'label' => $entry['label'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelists');
    }
};
