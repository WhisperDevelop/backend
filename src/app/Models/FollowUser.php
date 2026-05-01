<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUser extends Model
{
    use HasFactory;

    /**
     * 対応するテーブル名。
     */
    protected $table = 'follow_users';

    /**
     * 一括代入を許可するカラム。
     */
    protected $fillable = [
        'user_id',
        'follow_user_id',
    ];
}