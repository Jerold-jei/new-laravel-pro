<?php

namespace App\Model;

use App\SavedAppalam;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        
        'user_id' => 'integer',
        
        'delivery_address' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function product()
    {
        return $this->hasMany(Product::class,'product_id');
    }

   
    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saved_appalam()
    {
        return $this->belongsTo(SavedAppalam::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function delivery_address()
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=' , 'pos');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=' , 'pos');
    }
}
