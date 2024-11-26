<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Word;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_user_details() {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $this->getJson('api/user/me')->assertJsonStructure([
            'id',
            'name',
            'email',
            'email_verified_at',
            'created_at',
            'updated_at'
        ]);
    }

    public function test_get_user_searched_words() {
        $user = User::factory()->create();
        $words = Word::factory()->count(10)->create();

        $user->searchedWords()->attach($words->pluck('id')->toArray(), [
            'searched_at' => Carbon::now()
        ]);

        $this->actingAs($user, 'api');
        $this->getJson('/api/user/me/history')->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'word',
                    'searched_at'
                ]
            ],
            'totalDocs',
            'page',
            'totalPages',
            'hasNext',
            'hasPrev'
        ]);

        foreach ($words as $word) {
            $this->assertDatabaseHas('search_histories',[
                'user_id' => $user->id,
                'word_id' => $word->id
            ]);
        }
    }

    public function test_get_user_favorite_words() {
        $user = User::factory()->create();
        $words = Word::factory()->count(10)->create();

        $user->favoriteWords()->attach($words->pluck('id')->toArray(), [
            'added_at' => Carbon::now()
        ]);

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/user/me/favorites');

        $response->assertOk()->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'word',
                    'added_at'
                ]
            ],
            'totalDocs',
            'page',
            'totalPages',
            'hasNext',
            'hasPrev'
        ]);

        $this->assertCount(10, $response->json('results'));

        foreach ($words as $word) {
            $this->assertDatabaseHas('user_word_favorites', [
                'user_id' => $user->id,
                'word_id' => $word->id
            ]);
        }
    }
}
