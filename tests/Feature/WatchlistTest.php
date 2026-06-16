<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WatchlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_movie_to_watchlist_and_fetch_from_omdb()
    {
        $user = User::factory()->create();

        Http::fake([
            'omdbapi.com/*' => Http::response([
                'imdbID' => 'tt1234567',
                'Title' => 'Test Movie - The Matrix',
                'Plot' => 'A computer hacker learns from mysterious rebels about the true nature of his reality.',
                'Poster' => 'https://example.com/poster.jpg',
                'Year' => '1999'
            ], 200)
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/watchlist', [
            'omdb_id' => 'tt1234567',
            'status' => 'to_watch',
            'rating' => 5,
            'personal_notes' => 'Must watch this weekend!'
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'to_watch');
        $response->assertJsonPath('data.movie.title', 'Test Movie - The Matrix');

        $this->assertDatabaseHas('movies', [
            'external_id' => 'tt1234567',
            'title' => 'Test Movie - The Matrix'
        ]);

        $this->assertDatabaseHas('watchlist_items', [
            'user_id' => $user->id,
            'status' => 'to_watch',
            'rating' => 5,
        ]);
    }
    
    public function test_user_cannot_delete_other_peoples_movies()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $movie = Movie::create([
            'external_id' => 'tt0000000',
            'title' => 'Some Movie'
        ]);
        
        $watchlistItem = $user1->watchlistItems()->create([
            'movie_id' => $movie->id,
            'status' => 'to_watch'
        ]);

        $response = $this->actingAs($user2, 'sanctum')
                         ->deleteJson("/api/watchlist/{$watchlistItem->id}");

        $response->assertStatus(403);
    }
}