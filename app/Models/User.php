<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\Theme;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $gemini_api_key_encrypted
 * @property Difficulty $preferred_difficulty
 * @property int $daily_goal
 * @property int $streak_count
 * @property Carbon|null $last_studied_at
 * @property Theme $theme
 */
#[Fillable(['name', 'email', 'password', 'preferred_difficulty', 'daily_goal', 'theme'])]
#[Hidden(['password', 'remember_token', 'gemini_api_key_encrypted'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferred_difficulty' => Difficulty::class,
            'theme' => Theme::class,
            'last_studied_at' => 'datetime',
            'daily_goal' => 'integer',
            'streak_count' => 'integer',
        ];
    }
}
