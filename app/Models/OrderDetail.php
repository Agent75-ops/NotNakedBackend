<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_at',
        'canceled_at',
        'order_id',
        'product_id',
        'ordered_quantity',
        'color_id',
        'size',
        'product_total_price',
        'amount_total',
        'amount_tax',
        'amount_subtotal',
        'amount_unit',
        'amount_discount',
        'sale_id'
    ];
    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function color(){
        return $this->belongsTo(Color::class);
    }
    public function size(){
        return $this->belongsTo(Size::class);
    }

    //ACCESSORS

    
}

