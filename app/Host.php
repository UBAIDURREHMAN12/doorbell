<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    protected $table = 'hosts';

    protected $fillable = ['owner_id', 'added_person_id', 'added_person_email', 'door_id'];


}
