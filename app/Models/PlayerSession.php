<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSession extends Model
{
    protected $fillable = [
        'user_id',
        'current_song_id',
        'current_time',
        'volume',
        'muted',
        'shuffle',
        'loop',
        'left_sidebar_open',
        'right_sidebar_open',
        'view_mode',
    ];

    protected $casts = [
        'muted'              => 'boolean',
        'shuffle'            => 'boolean',
        'left_sidebar_open'  => 'boolean',
        'right_sidebar_open' => 'boolean',
    ];

    public function currentSong(): BelongsTo
    {
        return $this->belongsTo(Song::class, 'current_song_id');
    }
}
