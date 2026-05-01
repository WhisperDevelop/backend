<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    // 主キーの関連付け
    // createメソッドで一括代入できるように配列を作成

    protected $fillable = [
        'user_id',
        'profile',
        'icon_file_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
