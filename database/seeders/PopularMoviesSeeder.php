<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Jobs\ProcessMovies;
use Illuminate\Support\Facades\Http;

class PopularMoviesSeeder extends Seeder
{
    public function run(): void
    {
        $imdbIds = [];

        $searches = [
            'action', 'drama', 'thriller', 'comedy', 'horror',
            'adventure', 'romance', 'sci-fi', 'fantasy', 'mystery',
            'crime', 'animation', 'documentary', 'war', 'western',
            'superhero', 'marvel', 'dc', 'star', 'the',
            'love', 'dark', 'dark knight', 'matrix', 'avengers',
            'harry', 'lord', 'game', 'mission', 'fast',
            'john', 'james', 'jason', 'jack', 'david',
            'spider', 'iron', 'captain', 'thor', 'hulk',
            'batman', 'superman', 'wonder', 'aquaman', 'flash',
            'new', 'best', 'top', 'great', 'amazing',
            'amazing', 'fantastic', 'incredible', 'ultimate', 'final',
            'death', 'life', 'time', 'world', 'end',
            'beginning', 'rise', 'fall', 'war', 'peace',
            'love', 'hate', 'good', 'evil', 'light',
            'dark', 'night', 'day', 'forever', 'always',
        ];

        $baseUrl = config('app.services.omdb.base_url', 'http://www.omdbapi.com/');
        $apiKey = config('app.services.omdb.api_key');

        foreach ($searches as $search) {
            for ($page = 1; $page <= 20; $page++) {
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

                if (count($imdbIds) >= 10000) {
                    break 2;
                }
            }
        }

        $imdbIds = array_slice(array_unique($imdbIds), 0, 10000);

        foreach (array_chunk($imdbIds, 100) as $batch) {
            dispatch(new ProcessMovies($batch));
        }

        $this->command->info('Queued ' . count($imdbIds) . ' movies for processing');
    }
}

