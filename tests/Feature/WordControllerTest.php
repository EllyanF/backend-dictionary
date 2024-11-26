<?php

namespace Tests\Feature;

use App\Events\WordSearched;
use App\Listeners\SaveSearchHistory;
use App\Models\User;
use App\Models\Word;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_words() {
        $user = User::factory()->create();

        Word::factory()->count(20)->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/entries/en?per_page=10');

        $response->assertStatus(200)->assertJsonStructure([
            'results',
            'totalDocs',
            'page',
            'totalPages',
            'hasNext',
            'hasPrev'
        ])->assertJsonFragment([
            'totalDocs' => 20,
            'page' => 1,
            'totalPages' => 2,
            'hasNext' => true,
            'hasPrev' => false
        ]);

        $this->assertCount(10, $response->json('results'));
    }

    public function test_search_filter() {
        $user = User::factory()->create();
        
        Word::factory()->create(['word' => 'car']);
        Word::factory()->create(['word' => 'carabiner']);
        Word::factory()->create(['word' => 'carabiners']);
        Word::factory()->create(['word' => 'canadian']);

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/entries/en?filter[search]=car');

        $response->assertOk()->assertJsonStructure([
            'results',
            'totalDocs',
            'page',
            'totalPages',
            'hasNext',
            'hasPrev'
        ])->assertJsonFragment([
            'totalDocs' => 3,
            'page' => 1
        ]);

        $this->assertCount(3, $response->json('results'));
        $this->assertNotContainsEquals('canadian', $response->json('results'));
    }

    public function test_show_word_and_dispatch_event() {
        Event::fake();

        $user = User::factory()->create();
        $word = Word::factory()->create();

        $this->actingAs($user, 'api');

        $response = $this->getJson("/api/entries/en/$word->word")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'word',
                'created_at',
                'updated_at'
            ]);

        Event::assertDispatchedTimes(WordSearched::class, 1);
    }

    public function test_show_word_that_does_not_exist() {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $this->getJson('/api/entries/en/fakeWord')
            ->assertNotFound()
            ->assertJson(['message' => 'Palavra não encontrada']);
    }

    public function test_listener_saves_search_history() {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $event = new WordSearched($word, $user);

        $listener = new SaveSearchHistory();
        $listener->handle($event);

        $this->assertDatabaseCount('search_histories', 1);

        $this->assertDatabaseHas('search_histories', [
            'user_id' => $user->id,
            'word_id' => $word->id,
        ]);
    }

    public function test_save_favorite_word() {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        $this->actingAs($user, 'api');

        $this->post("/api/entries/en/$word->word/favorite")->assertNoContent();

        $this->assertDatabaseCount('user_word_favorites', 1);

        $this->assertDatabaseHas('user_word_favorites', [
            'user_id' => $user->id,
            'word_id' => $word->id,
        ]);
    }

    public function test_unfavorite_word_and_delete_from_table() {
        $user = User::factory()->create();
        $word = Word::factory()->create();

        DB::table('user_word_favorites')->insert([
            'user_id' => $user->id,
            'word_id' => $word->id,
            'added_at' => Carbon::now()
        ]);

        $this->actingAs($user, 'api');

        $this->delete("api/entries/en/$word->word/unfavorite")
            ->assertNoContent();

        $this->assertDatabaseEmpty('user_word_favorites');
    }
}
