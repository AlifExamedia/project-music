<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueItem extends Model
{
    protected $fillable = [
        'user_id',
        'position',
        'song_id',
    ];

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
