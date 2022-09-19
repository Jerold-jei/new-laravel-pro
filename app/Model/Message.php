<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $casts = [
        'conversation_id' => 'integer',
        'customer_id' => 'integer',
        'deliveryman_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
