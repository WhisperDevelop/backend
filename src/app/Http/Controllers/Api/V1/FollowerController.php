<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * フォロー・フォロワー一覧を担当するコントローラー。
 *
 * 注意:
 * 設計書のコントローラー名に合わせて「FollwerController」としています。
 * 一般的な英語表記に直す場合は「FollowerController」に変更してください。
 *
 * 対応API:
 * - GET /api/v1/following
 * - GET /api/v1/followers
 */
class FollowerController extends Controller
{
    /**
     * フォロー一覧取得処理。
     *
     * ログインユーザーがフォローしているユーザー一覧を返す。
     */
    public function following(Request $request)
    {
        $following = $request->user()
            ->follows()
            ->with('profile')
            ->orderBy('name')
            ->get();

        return response()->json([
            'user_line' => $following,
        ]);
    }

    /**
     * フォロワー一覧取得処理。
     *
     * ログインユーザーをフォローしているユーザー一覧を返す。
     */
    public function followers(Request $request)
    {
        $followers = $request->user()
            ->followers()
            ->with('profile')
            ->orderBy('name')
            ->get();

        return response()->json([
            'user_line' => $followers,
        ]);
    }
}
