<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MovieDatabaseService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = app('config')->get('services.omdb.base_url', 'http://www.omdbapi.com/');
        $this->apiKey = app('config')->get('services.omdb.api_key');
    }

    /**
     * Fetch movie details from OMDb by IMDb ID.
     */
    public function getMovieDetails(string $imdbId): ?array
    {
        $response = Http::get($this->baseUrl, [
            'apikey' => $this->apiKey,
            'i'      => $imdbId,
            'plot'   => 'full' 
        ]);

        if ($response->failed()) {
            return null; 
        }

        $data = $response->json();

        if (isset($data['Response']) && $data['Response'] === 'False') {
            return null;
        }

        return [
            'external_id'  => $data['imdbID'],
            'title'        => $data['Title'],
            'description'  => $data['Plot'] !== 'N/A' ? $data['Plot'] : null,
            'poster_url'   => $data['Poster'] !== 'N/A' ? $data['Poster'] : null,
            'release_year' => isset($data['Year']) ? substr($data['Year'], 0, 4) : null,
        ];
    }
}