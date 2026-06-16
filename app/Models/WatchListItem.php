<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchListItem extends Model
{
    protected $fillable = ['user_id', 'movie_id', 'status', 'rating', 'personal_notes'];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
