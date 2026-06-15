<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterCommentsTableAddLongText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support MODIFY column; longText is the default text type
            return;
        }
        DB::statement('ALTER TABLE `comments` MODIFY `description` LONGTEXT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
