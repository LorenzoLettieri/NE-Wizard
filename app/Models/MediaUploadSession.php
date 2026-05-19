<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUploadSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'context',
        'mediable_type',
        'mediable_id',
        'form_token',
        'original_name',
        'mime_type',
        'size',
        'chunk_size',
        'chunk_count',
        'received_chunks',
        'status',
        'final_path',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'chunk_size' => 'integer',
            'chunk_count' => 'integer',
            'received_chunks' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
