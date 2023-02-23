<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DoorDay extends Model
{
    protected $table = 'days_timings';

    protected $fillable = ['door_id', 'day_id', 'from_hour', 'to_hour'];


}