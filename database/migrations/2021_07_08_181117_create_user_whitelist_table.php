<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserWhitelistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("user_whitelist")) {
            Schema::create('user_whitelist', function (Blueprint $table) {
                $table->integer('whitelist_id')->nullable(false)->autoIncrement();
            });
        }
        if (!Schema::hasColumn("user_whitelist", "steam_identifier")) {
            Schema::table("user_whitelist", function (Blueprint $table) {
                $table->string('steam_identifier', 50)->nullable()->default(null);
                $table->index('steam_identifier');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_whitelist');
    }
}
