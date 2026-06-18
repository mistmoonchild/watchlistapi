<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMovies;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProcessMoviesCommand extends Command
{
    protected $signature = 'movies:process {--limit=10000 : Number of movies to fetch}';
    protected $description = 'Fetch movies from OMDb API and process them';

    public function handle(): void
    {
        $limit = $this->option('limit');
        $imdbIds = [];

        $searches = [
            'action', 'drama', 'thriller', 'comedy', 'horror',
            'adventure', 'romance', 'sci-fi', 'fantasy', 'mystery',
            'crime', 'animation', 'documentary', 'war', 'western',
            'superhero', 'marvel', 'dc', 'star', 'the',
            'love', 'dark', 'matrix', 'avengers', 'harry',
            'lord', 'game', 'mission', 'fast', 'spider',
            'iron', 'captain', 'thor', 'batman', 'superman',
            'wonder', 'aquaman', 'flash', 'new', 'best',
        ];

        $apiKey = config('app.services.omdb.api_key');
        $baseUrl = config('app.services.omdb.base_url', 'http://www.omdbapi.com/');

        $this->info("Fetching up to $limit movies...");

        foreach ($searches as $search) {
            for ($page = 1; $page <= 20; $page++) {
                $this->info("Searching: $search (page $page)");

                $response = Http::get($baseUrl, [
                    'apikey' => $apiKey,
                    's' => $search,
                    'type' => 'movie',
                    'page' => $page,
                ]);

                if ($response->failed()) {
                    continue;
                }

                $data = $response->json();

                if (!isset($data['Search']) || empty($data['Search'])) {
                    break;
                }

                foreach ($data['Search'] as $movie) {
                    $imdbIds[] = $movie['imdbID'];
                }

                if (count($imdbIds) >= $limit) {
                    break 2;
                }
            }
        }

        $imdbIds = array_slice(array_unique($imdbIds), 0, $limit);

        $this->info('Found ' . count($imdbIds) . ' unique movies');

        foreach (array_chunk($imdbIds, 100) as $batch) {
            dispatch(new ProcessMovies($batch));
        }

        $this->info('✓ Queued ' . count($imdbIds) . ' movies for processing');
    }
}
