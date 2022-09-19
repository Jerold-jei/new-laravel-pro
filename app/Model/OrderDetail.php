<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\SavedAppalam;
use App\Category;

class OrderDetail extends Model
{
    protected $casts = [
        'product_id' => 'integer',
        'order_id' => 'integer',
        'parent_id' => 'integer',
        'img_id' => 'integer',
        'price' => 'float',
        'discount_on_product' => 'float',
        'quantity' => 'integer',
        'tax_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class,'parent_id');
    }

    public function saved_appalam()
    {
        return $this->belongsTo(SavedAppalam::class, 'img_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function review()
    {
        return $this->belongsTo(Review::class, 'order_id','order_id');
    }
}
