<?php

namespace App\Models;

use Database\Factories\ExternalOrderCacheFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExternalOrderCache extends Model
{
    /** @use HasFactory<ExternalOrderCacheFactory> */
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

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    /** @return HasMany<ReturnTicket, $this> */
    public function returnTickets(): HasMany
    {
        return $this->hasMany(ReturnTicket::class, 'order_id', 'order_id');
    }

    /** @return HasOne<ReturnTicket, $this> */
    public function activeReturnTicket(): HasOne
    {
        return $this->hasOne(ReturnTicket::class, 'order_id', 'order_id')
            ->where('current_status', '!=', 'closed');
    }

    /** @return HasOne<ReturnTicket, $this> */
    public function latestReturnTicket(): HasOne
    {
        return $this->hasOne(ReturnTicket::class, 'order_id', 'order_id')
            ->latest('created_at');
    }
}
