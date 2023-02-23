<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'devices_tokens';
    protected $fillable = ['user_id', 'device_id', 'fcm_token'];
}
