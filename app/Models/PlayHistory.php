<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayHistory extends Model
{
    protected $table = 'play_history';

    protected $fillable = ['user_id', 'song_id', 'played_at'];
}
