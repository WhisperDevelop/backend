<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    /**
     * 対応するテーブル名。
     */
    protected $table = 'likes';

    /**
     * 一括代入を許可するカラム。
     */
    protected $fillable = [
        'user_id',
        'whisper_id',
    ];
}