<?php

namespace App\Http\Controllers;

use App\Events\WordSearched;
use App\Filters\FiltersStartingWith;
use App\Models\SearchHistory;
use App\Models\User;
use App\Models\Word;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WordController extends Controller
{
    /**
     * Retorna todas as palavras.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse {
        $words = QueryBuilder::for(Word::class)
            ->allowedFilters([
                AllowedFilter::custom('search', new FiltersStartingWith),
            ])
            ->paginate($request->get('per_page', 15))
            ->appends($request->query());

        return response()->json([
            'results' => $words->items(),
            'totalDocs' => $words->total(),
            'page' => $words->currentPage(),
            'totalPages' => $words->lastPage(),
            'hasNext' => $words->hasMorePages(),
            'hasPrev' => $words->onFirstPage() ? false : true
        ]);
    }

    /**
     * Retorna as informações da palavra especificada.
     * 
     * @param string $word
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $word): JsonResponse {
        $searchWord = Word::where('word', $word)->first();

        if (!$searchWord) {
            return response()->json(['message' => 'Palavra não encontrada'], 404);
        }

        event(new WordSearched($searchWord, Auth::user()));

        return response()->json($searchWord);
    }

    /**
     * Salva uma palavra como favorita.
     * 
     * @param string $word
     * @return \Illuminate\Http\Response
     */
    public function favorite(string $word): Response | JsonResponse {
        $user = Auth::user();
        $searchWord = Word::firstWhere('word', $word);

        if (!$searchWord) {
            return response()->json(['message' => 'Palavra não encontrada'], 404);
        } 

        if ($this->isFavorite($user, $searchWord->id)) {
            return response()->json(['message' => 'Palavra já favoritada'], 400);
        }

        $user->favoriteWords()->attach($searchWord->id);

        return response()->noContent();
    }

    /**
     * Exclui uma palavra dos favoritos.
     * 
     * @param string $word
     * @return \Illuminate\Http\Response
     */
    public function unfavorite(string $word): Response | JsonResponse {
        $user = Auth::user();
        $favoriteWord = Word::firstWhere('word', $word);

        if (!$favoriteWord) {
            return response()->json(['message' => 'Palavra não encontrada'], 404);
        }

        $user->favoriteWords()->detach($favoriteWord->id);

        return response()->noContent();
    }

    /**
     * Verifica se uma palavra foi salva nos favoritos.
     */
    private function isFavorite(User $user, int $wordId): bool {
        return $user->favoriteWords()->wherePivot('word_id', $wordId)->exists();
    }
}
