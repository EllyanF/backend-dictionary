<?php

namespace App\Listeners;

use App\Events\WordSearched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SaveSearchHistory
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WordSearched $event): void
    {
         $user = $event->user;
         $word = $event->word;

         $user->searchedWords()->attach([
             $word->id => ['searched_at' => now()],
         ]);
    }
}
