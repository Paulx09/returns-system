<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalOrderCache extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'external_orders_cache';
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'order_number',
        'customer_full_name',
        'customer_email',
        'customer_dni',
        'order_date',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }
}
