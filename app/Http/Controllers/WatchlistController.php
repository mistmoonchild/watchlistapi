<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\WatchlistItem;
use App\Services\MovieDatabaseService;
use App\Http\Requests\StoreWatchlistItemRequest;
use App\Http\Requests\UpdateWatchlistItemRequest;
use App\Http\Resources\WatchlistItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WatchlistController extends Controller
{
    public function __construct(private MovieDatabaseService $movieService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->watchlistItems()->with('movie');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate(10);

        return WatchlistItemResource::collection($items);
    }

    public function store(StoreWatchlistItemRequest $request): JsonResponse|WatchlistItemResource
    {
        $imdb_id = $request->validated('imdb_id');

        $movie = Movie::where('external_id', $imdb_id)->first();

        if (!$movie) {
            $movieData = $this->movieService->getMovieDetails($imdb_id);
            
            if (!$movieData) {
                return response()->json(['message' => 'Movie not found on OMDb.'], 404);
            }
            
            $movie = Movie::create($movieData);
        }

        $exists = $request->user()->watchlistItems()->where('movie_id', $movie->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Movie is already in your watchlist.'], 409);
        }

        $watchlistItem = $request->user()->watchlistItems()->create([
            'movie_id' => $movie->id,
            'status' => $request->validated('status', 'to_watch'),
            'rating' => $request->validated('rating'),
            'personal_notes' => $request->validated('personal_notes'),
        ]);

        $watchlistItem->load('movie');

        return new WatchlistItemResource($watchlistItem);
    }

    public function show(WatchlistItem $watchlist): JsonResponse|WatchlistItemResource
    {
        if ($watchlist->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $watchlist->load('movie');
        return new WatchlistItemResource($watchlist);
    }

    public function update(UpdateWatchlistItemRequest $request, WatchlistItem $watchlist): JsonResponse|WatchlistItemResource
    {
        if ($watchlist->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $watchlist->update($request->validated());

        return new WatchlistItemResource($watchlist);
    }

    public function destroy(WatchlistItem $watchlist): JsonResponse
    {
        if ($watchlist->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $watchlist->delete();

        return response()->json(null, 204); 
    }
}