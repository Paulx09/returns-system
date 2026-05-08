<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $primaryKey = 'return_item_id';

    protected $fillable = [
        'ticket_id',
        'order_item_id',
        'reason_id',
        'quantity_to_return',
        'admin_comment',
    ];

    protected $casts = [
        'quantity_to_return' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ReturnTicket::class, 'ticket_id', 'ticket_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'order_item_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(ReturnReason::class, 'reason_id', 'reason_id');
    }
}
