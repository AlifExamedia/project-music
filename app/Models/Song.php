<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    protected $fillable = [
        'filename',
        'title',
        'artist',
        'album',
        'genre',
        'year',
        'track',
        'duration',
        'cover_art_path',
    ];

    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function playlistSongs(): HasMany
    {
        return $this->hasMany(PlaylistSong::class);
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(QueueItem::class);
    }
}
