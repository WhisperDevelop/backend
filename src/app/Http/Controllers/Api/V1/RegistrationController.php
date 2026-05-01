<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Whisper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * フォロー登録・解除、いいね登録・解除を担当するコントローラー。
 *
 * 対応API:
 * - POST /api/v1/followcheck
 * - POST /api/v1/likecheck
 */
class RegistrationController extends Controller
{
    /**
     * フォロー登録・解除処理。
     *
     * すでにフォローしている場合は解除し、
     * まだフォローしていない場合は登録する。
     */
    public function followRegister(Request $request)
    {
        // 入力値を検証する。
        $validated = $request->validate([
            'follow_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $loginUser = $request->user();
        $followUserId = (int) $validated['follow_user_id'];

        // 自分自身はフォローできない。
        if ((int) $loginUser->id === $followUserId) {
            return response()->json([
                'message' => '自分自身はフォローできません。',
            ], 422);
        }

        // すでにフォローしているか確認する。
        $exists = DB::table('follow_users')
            ->where('user_id', $loginUser->id)
            ->where('follow_user_id', $followUserId)
            ->exists();

        if ($exists) {
            // フォロー済みの場合は解除する。
            DB::table('follow_users')
                ->where('user_id', $loginUser->id)
                ->where('follow_user_id', $followUserId)
                ->delete();

            return response()->json([
                'message' => 'フォローを解除しました。',
                'following' => false,
            ]);
        }

        // フォローしていない場合は登録する。
        DB::table('follow_users')->insert([
            'user_id' => $loginUser->id,
            'follow_user_id' => $followUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'フォローしました。',
            'following' => true,
        ]);
    }

    /**
     * いいね登録・解除処理。
     *
     * すでにいいねしている場合は解除し、
     * まだいいねしていない場合は登録する。
     */
    public function likeRegister(Request $request)
    {
        // 入力値を検証する。
        $validated = $request->validate([
            'whisper_id' => ['required', 'integer', 'exists:whispers,id'],
        ]);

        $loginUser = $request->user();
        $whisperId = (int) $validated['whisper_id'];

        // 対象のささやきを取得する。
        $whisper = Whisper::findOrFail($whisperId);

        // すでにいいねしているか確認する。
        $exists = DB::table('likes')
            ->where('user_id', $loginUser->id)
            ->where('whisper_id', $whisper->id)
            ->exists();

        if ($exists) {
            // いいね済みの場合は解除する。
            DB::table('likes')
                ->where('user_id', $loginUser->id)
                ->where('whisper_id', $whisper->id)
                ->delete();

            return response()->json([
                'message' => 'いいねを解除しました。',
                'liked' => false,
            ]);
        }

        // いいねしていない場合は登録する。
        DB::table('likes')->insert([
            'user_id' => $loginUser->id,
            'whisper_id' => $whisper->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'いいねしました。',
            'liked' => true,
        ]);
    }
}
