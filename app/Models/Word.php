<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Word extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'word'
    ];

    public function usersWhoSearched(): BelongsToMany {
        return $this->belongsToMany(User::class, 'search_histories')
            ->withPivot('searched_at');
    }

    public function usersWhoFavorited(): BelongsToMany {
        return $this->belongsToMany(User::class, 'user_word_favorites')
        ->withPivot('added_at');
    }
}
