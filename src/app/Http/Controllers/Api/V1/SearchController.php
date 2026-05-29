<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Whisper;

/**
 * 検索処理を担当するコントローラー。
 *
 * 対応API:
 * - GET /api/v1/search/users/{keyword}
 * - GET /api/v1/search/whispers/{keyword}
 */
class SearchController extends Controller
{
    /**
     * ユーザー名検索処理。
     *
     * keywordを含むユーザー名を検索し、プロフィール付きで返す。
     */
    public function usernameSearch($keyword)
    {
        $users = User::with('profile')
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name')
            ->get();

        return response()->json([
            'users' => $users,      // ✅ Đổi từ 'user_line' → 'users'
            'whispers' => []        // ✅ Thêm field whispers rỗng để Android không crash
        ]);
    }

    /**
     * ささやき本文検索処理。
     *
     * keywordを含むささやきを検索し、作成日の新しい順で返す。
     */
    public function whisperSearch($keyword)
    {
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy as likes_count')
            ->where('content', 'like', '%' . $keyword . '%')
            ->latest()
            ->get();

        return response()->json([
            'users' => [],          // ✅ Thêm field users rỗng
            'whispers' => $whispers // ✅ Đổi từ 'whisper' → 'whispers' (số nhiều)
        ]);
    }
}
