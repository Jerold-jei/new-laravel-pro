<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SavedAppalam extends Model
{
   
    protected $table = 'saved_appalam';
    
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',        
        'order_id' => 'integer'
    ];

    // protected $fillable = [
    //                         'image'
    //                       ];

    public function customer()
    {
        return $this->hasMany(User::class,'user_id');
    }

    public function order()
    {
        return $this->hasMany(Order::class,'order_id');
    }
}
