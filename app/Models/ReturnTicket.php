<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnTicket extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $primaryKey = 'ticket_id';

    public const STATUSES = [
        'received',
        'under_review',
        'approved',
        'rejected',
        'more_information_requested',
        'closed',
    ];

    protected $fillable = [
        'tracking_code',
        'order_id',
        'current_status',
        'customer_comment',
        'created_by_user_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ExternalOrderCache::class, 'order_id', 'order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'ticket_id', 'ticket_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class, 'ticket_id', 'ticket_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id', 'ticket_id');
    }
}
