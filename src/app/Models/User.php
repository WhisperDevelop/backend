<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function whispers()
    {
        return $this->hasMany(Whisper::class, 'user_id');
    }

    public function follows()
    {
        return $this->belongsToMany(User::class, 'follow_users', 'user_id', 'follow_user_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follow_users', 'follow_user_id', 'user_id');
    }

    public function isFollowing($userId)
    {
        return $this->follows()->where('follow_user_id', $userId)->exists();
    }

    public function likedWhispers()
    {
        return $this->belongsToMany(Whisper::class, 'likes', 'user_id', 'whisper_id');
    }
}
