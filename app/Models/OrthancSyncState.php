<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrthancSyncState extends Model
{
    protected $table = 'orthanc_sync_state';
    protected $guarded = [];
}
