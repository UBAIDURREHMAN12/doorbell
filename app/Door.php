<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Door extends Model
{
    protected $table = 'doors';

    protected $fillable = ['user_id', 'invited_users', 'location', 'latitude', 'longitude', 'name', 'image',
        'zone_of_operation', 'ring_limit', 'ring_tone', 'door_type', 'is_listed', 'is_notify', 'msg_in_operational_houres',
        'msg_in_off_houres'];

    public function doordays()
    {
        return $this->hasMany(DoorDay::class, 'door_id', 'id');
    }

}
