<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'evidences';
    protected $primaryKey = 'evidence_id';

    public const CREATED_AT = 'uploaded_at';
    public const UPDATED_AT = null; // No updated_at in evidences table

    protected $fillable = [
        'ticket_id',
        'file_name',
        'file_path',
        'mime_type',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ReturnTicket::class, 'ticket_id', 'ticket_id');
    }
}
