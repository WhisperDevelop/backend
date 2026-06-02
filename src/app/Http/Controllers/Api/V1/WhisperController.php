<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\Request;

/**
 * ささやきの登録、一覧取得、削除を担当するコントローラー。
 *
 * 対応API:
 * - GET  /api/v1/whispers
 * - POST /api/v1/whispers
 * - GET  /api/v1/user/whispers/{id}
 * - POST /api/v1/whispers/{id}
 */
class WhisperController extends Controller
{
    /**
     * タイムライン取得処理。
     *
     * ログインユーザー本人と、フォロー中ユーザーのささやきを
     * 作成日の新しい順で返す。
     */
    public function index(Request $request)
    {
        // 全ユーザーのささやきを新しい順で返す。
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy as likes_count')
            ->latest()
            ->get();

        return response()->json([
            'whisper' => $whispers,
        ]);
    }

    /**
     * ささやき登録処理。
     *
     * textを受け取り、ログインユーザーの投稿として保存する。
     */
    public function store(Request $request)
    {
        // 入力値を検証する。
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:280'],
        ]);

        // ささやきを作成する。
        $whisper = Whisper::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'ささやきを登録しました。',
            'whisper' => $whisper,
        ], 201);
    }

    /**
     * 特定ユーザー情報とささやき一覧取得処理。
     *
     * 指定されたユーザーの情報と投稿一覧を返す。
     */
    public function show(Request $request, $id)
    {
        $loginUser = $request->user();

        // ユーザー情報をフォロー数付きで取得する。
        $user = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->findOrFail($id);

        // 指定ユーザーのささやきを新しい順で取得する。
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy as likes_count')
            ->where('user_id', $id)
            ->latest()
            ->get();

        return response()->json([
            'user_line' => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'profile'          => $user->profile,
                'follows_count'    => $user->follows_count,
                'followers_count'  => $user->followers_count,
                'is_following'     => $loginUser->isFollowing($user->id),
            ],
            'whisper' => $whispers,
        ]);
    }

    /**
     * ささやき削除処理。
     *
     * 投稿者本人だけが削除できる。
     */
    /**
     * 指定ユーザーがいいねしたささやき一覧取得処理。
     */
    public function liked($id)
    {
        $user = User::findOrFail($id);

        $whispers = $user->likedWhispers()
            ->with(['user.profile'])
            ->withCount('likedBy as likes_count')
            ->latest()
            ->get();

        return response()->json([
            'whisper' => $whispers,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $whisper = Whisper::findOrFail($id);

        // 投稿者本人以外は削除できない。
        if ((int) $whisper->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => '自分のささやき以外は削除できません。',
            ], 403);
        }

        // ささやきを削除する。
        $whisper->delete();

        return response()->json([
            'message' => 'ささやきを削除しました。',
        ]);
    }
}
