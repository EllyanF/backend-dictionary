<?php

namespace App\Http\Controllers;

use App\Http\Resources\WordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Retorna o perfil do usuário.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse {
        return response()->json(Auth::user());
    }

    /**
     * Retorna o histórico de palavras visitadas.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSearchHistory(Request $request): JsonResponse {
        $results = Auth::user()->searchedWords()->paginate($request->get('per_page', 15));

        $formattedResults = $results->getCollection()->map(function ($word) {
            return [
                'id' => $word->id,
                'word' => $word->word,
                'searched_at' => $word->pivot->searched_at
            ];
        });

        return response()->json($this->paginatedFormat(
            $formattedResults, $results
        ));
    }

    /**
     * Retorna as palavras favoritas do usuário.
     */
    public function getFavoriteWords(Request $request): JsonResponse {
        $results = Auth::user()->favoriteWords()->paginate(
            $request->get('per_page', 15)
        );

        $formattedResults = $results->getCollection()->map(function ($word) {
            return [
                'id' => $word->id,
                'word' => $word->word,
                'added_at' => $word->pivot->added_at
            ];
        });

        return response()->json($this->paginatedFormat(
            $formattedResults, $results
        ));
    }

    private function paginatedFormat(
        Collection $data, LengthAwarePaginator $paginatedData
        ): array {
        return [
            'results' => $data,
            'totalDocs' => $paginatedData->total(),
            'page' => $paginatedData->currentPage(),
            'totalPages' => $paginatedData->lastPage(),
            'hasNext' => $paginatedData->hasMorePages(),
            'hasPrev' => $paginatedData->onFirstPage() ? false : true
        ];
    }
}
