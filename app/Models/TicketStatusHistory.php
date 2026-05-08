<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticket_status_history';
    protected $primaryKey = 'history_id';

    public const CREATED_AT = 'changed_at';
    public const UPDATED_AT = null; // No updated_at

    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'comment',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ReturnTicket::class, 'ticket_id', 'ticket_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'user_id');
    }
}
