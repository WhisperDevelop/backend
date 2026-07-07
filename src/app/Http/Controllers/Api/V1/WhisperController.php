<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     * textまたは画像を受け取り、ログインユーザーの投稿として保存する。
     * 画像はmultipart/form-dataのimagefileで送信される。
     */
    public function store(Request $request)
    {
        // 入力値を検証する。テキストか画像のどちらかは必須。
        $validated = $request->validate([
            'content' => ['required_without:imagefile', 'nullable', 'string', 'max:280'],
            'imagefile' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        // 画像が送信された場合はstorageに保存する。
        $imageFileName = null;
        if ($request->hasFile('imagefile')) {
            $imageFileName = $request->file('imagefile')->store('whisper_images', 'public');
        }

        // ささやきを作成する。
        $whisper = Whisper::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'] ?? '',
            'image_file_name' => $imageFileName,
        ]);

        return response()->json([
            'message' => 'ささやきを登録しました。',
            'whisper' => $whisper,
        ], 201);
    }

    /**
     * ささやき更新処理。
     *
     * 投稿者本人だけが編集できる。
     * 画像を差し替え・削除した場合は古い画像ファイルをstorageから削除する。
     */
    public function update(Request $request, $id)
    {
        $whisper = Whisper::findOrFail($id);

        // 投稿者本人以外は編集できない。
        if ((int) $whisper->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => '自分のささやき以外は編集できません。',
            ], 403);
        }

        // 入力値を検証する。
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:280'],
            'imagefile' => ['nullable', 'file', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $newContent = $validated['content'] ?? '';

        // 更新後の画像の状態を決める。
        $newImageFileName = $whisper->image_file_name;
        if ($request->hasFile('imagefile')) {
            $newImageFileName = $request->file('imagefile')->store('whisper_images', 'public');
        } elseif ($request->boolean('remove_image')) {
            $newImageFileName = null;
        }

        // テキストも画像もない投稿にはできない。
        if ($newContent === '' && $newImageFileName === null) {
            return response()->json([
                'message' => 'ささやく内容または画像を入力してください。',
            ], 422);
        }

        // 差し替え・削除された古い画像をstorageから削除する。
        $oldImageFileName = $whisper->image_file_name;
        if ($oldImageFileName && $oldImageFileName !== $newImageFileName) {
            Storage::disk('public')->delete($oldImageFileName);
        }

        // ささやきを更新する。
        $whisper->update([
            'content' => $newContent,
            'image_file_name' => $newImageFileName,
        ]);

        return response()->json([
            'message' => 'ささやきを更新しました。',
            'whisper' => $whisper,
        ]);
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

        // 添付画像がある場合はstorageから削除する。
        if ($whisper->image_file_name) {
            Storage::disk('public')->delete($whisper->image_file_name);
        }

        // ささやきを削除する。
        $whisper->delete();

        return response()->json([
            'message' => 'ささやきを削除しました。',
        ]);
    }
}
