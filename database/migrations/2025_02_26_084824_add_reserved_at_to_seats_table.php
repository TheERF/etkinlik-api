<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->timestamp('reserved_at')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropColumn('reserved_at');
        });
    }
};
