<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'rating' => $this->rating,
            'personal_notes' => $this->personal_notes,
            'added_at' => $this->created_at->toDateTimeString(),
            
            'movie' => [
                'imdb_id' => $this->movie->external_id,
                'title' => $this->movie->title,
                'description' => $this->movie->description,
                'poster_url' => $this->movie->poster_url,
                'release_year' => $this->movie->release_year,
            ]
        ];
    }
}
