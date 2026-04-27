<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistSong extends Model
{
    protected $fillable = ['playlist_id', 'song_path', 'position'];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }
}
