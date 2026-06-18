<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllMovie extends Model
{
    use HasFactory;

    protected $table = 'all_movies';

    protected $fillable = [
        'external_id',
        'title',
        'description',
        'poster_url',
        'release_year',
    ];
}
