<?php

namespace App\Jobs;

use App\Models\AllMovie;
use App\Services\MovieDatabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMovies implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $imdbIds
    ) {}

    public function handle(MovieDatabaseService $movieService): void
    {
        foreach ($this->imdbIds as $imdbId) {
            $movieData = $movieService->getMovieDetails($imdbId);

            if ($movieData) {
                AllMovie::updateOrCreate(
                    ['external_id' => $movieData['external_id']],
                    $movieData
                );
            }
        }
    }
}
