<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateOrthancSyncState extends Migration
{
    public function up()
    {
        Schema::create('orthanc_sync_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_change_id')->default(0);
            $table->timestamps();
        });

        // single-row table: this seeds the one cursor row the sync command reads/updates.
        DB::table('orthanc_sync_state')->insert(['last_change_id' => 0]);
    }

    public function down()
    {
        Schema::dropIfExists('orthanc_sync_state');
    }
}
