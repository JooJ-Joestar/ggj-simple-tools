<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mac_address', 64)->nullable()->after('email');
            $table->string('otp_hash', 32)->default('')->after('mac_address');
            $table->boolean('otp_consumed')->default(true)->after('otp_hash');
            $table->timestamp('last_otp')->nullable()->after('otp_consumed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mac_address', 'otp_hash', 'otp_consumed', 'last_otp']);
        });
    }
};
