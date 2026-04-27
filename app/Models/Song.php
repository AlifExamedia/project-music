<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    protected $fillable = [
        'filename',
        'title',
        'artist_id',
        'album_id',
        'genre_id',
        'year',
        'track',
        'duration',
        'cover_art_path',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

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
