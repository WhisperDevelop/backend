<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * ユーザー情報管理を担当するコントローラー。
 *
 * 対応API:
 * - POST /api/v1/users/register
 * - GET  /api/v1/user
 * - POST /api/v1/users/profile/{id}
 * - POST /api/v1/users/delete/{id}
 */
class UserController extends Controller
{
    /**
     * ユーザー登録処理。
     *
     * usersテーブルとuser_profilesテーブルを同時に作成し、
     * 登録後にSanctumのAPIトークンを返す。
     */
    /**
     * 全ユーザー一覧取得処理。
     */
    public function index()
    {
        $users = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'profile'         => $u->profile,
                'follows_count'   => $u->follows_count,
                'followers_count' => $u->followers_count,
                'is_following'    => false,
            ]);

        return response()->json(['users' => $users]);
    }

    /**
     * 指定ユーザー情報取得処理。
     */
    public function getById(Request $request, $id)
    {
        $loginUser = $request->user();

        $user = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->findOrFail($id);

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'profile'         => $user->profile,
            'follows_count'   => $user->follows_count,
            'followers_count' => $user->followers_count,
            'is_following'    => $loginUser->isFollowing($user->id),
        ]);
    }

    public function register(Request $request)
    {
        // 入力値を検証する。
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'name' => ['required', 'string', 'max:255'],
        ]);

        // ユーザー作成、プロフィール作成、トークン発行を1つの処理として扱う。
        return DB::transaction(function () use ($validated) {
            // ユーザーを作成する。
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'name' => $validated['name'],
            ]);

            // 初期プロフィールを作成する。
            UserProfile::create([
                'user_id' => $user->id,
                'profile' => '',
                'icon_file_name' => null,
            ]);

            // APIトークンを発行する。
            $token = $user->createToken('mobile-api-token')->plainTextToken;

            $user->load('profile');

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 201);
        });
    }

    /**
     * ログインユーザー情報取得処理。
     *
     * ログイン中ユーザーの基本情報とプロフィール情報を返す。
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('profile');

        return response()->json([
            'userprofile' => $user,
        ]);
    }

    /**
     * ユーザー情報更新処理。
     *
     * ログインユーザー本人の名前、プロフィール、アイコンファイル名を更新する。
     * 他のユーザーIDを指定された場合は403を返す。
     */
    public function update(Request $request, $id)
    {
        $loginUser = $request->user();

        // 自分以外のユーザー情報は更新できない。
        if ((int) $loginUser->id !== (int) $id) {
            return response()->json([
                'message' => '自分以外のユーザー情報は更新できません。',
            ], 403);
        }

        // 入力値を検証する。
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:1000'],
            'iconfile' => ['nullable', 'file', 'image', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $loginUser, $validated) {
            // ユーザー名を更新する。
            if (array_key_exists('name', $validated)) {
                $loginUser->update([
                    'name' => $validated['name'],
                ]);
            }

            // プロフィールが存在しない場合は作成する。
            $profile = $loginUser->profile ?: new UserProfile([
                'user_id' => $loginUser->id,
            ]);

            // プロフィール本文を更新する。
            if (array_key_exists('profile', $validated)) {
                $profile->profile = $validated['profile'];
            }

            // アイコンファイルが送信された場合は保存する。
            if ($request->hasFile('iconfile')) {
                $path = $request->file('iconfile')->store('icons', 'public');
                $profile->icon_file_name = $path;
            }

            $profile->save();
        });

        $loginUser->load('profile');

        return response()->json([
            'user' => $loginUser,
        ]);
    }

    /**
     * ユーザー削除処理。
     *
     * ログインユーザー本人のみ削除できる。
     * 関連するフォロー、いいね、投稿も削除する。
     */
    public function destroy(Request $request, $id)
    {
        $loginUser = $request->user();

        // 自分以外のユーザー情報は削除できない。
        if ((int) $loginUser->id !== (int) $id) {
            return response()->json([
                'message' => '自分以外のユーザー情報は削除できません。',
            ], 403);
        }

        DB::transaction(function () use ($loginUser) {
            // ユーザーの投稿IDを取得する。
            $whisperIds = DB::table('whispers')
                ->where('user_id', $loginUser->id)
                ->pluck('id');

            // 投稿に付いたいいねを削除する。
            DB::table('likes')->whereIn('whisper_id', $whisperIds)->delete();

            // ユーザー自身が付けたいいねを削除する。
            DB::table('likes')->where('user_id', $loginUser->id)->delete();

            // フォロー・フォロワー関係を削除する。
            DB::table('follow_users')
                ->where('user_id', $loginUser->id)
                ->orWhere('follow_user_id', $loginUser->id)
                ->delete();

            // 投稿とプロフィールを削除する。
            DB::table('whispers')->where('user_id', $loginUser->id)->delete();
            DB::table('user_profiles')->where('user_id', $loginUser->id)->delete();

            // APIトークンを削除する。
            $loginUser->tokens()->delete();

            // ユーザーを削除する。
            $loginUser->delete();
        });

        return response()->json([
            'message' => 'ユーザーを削除しました。',
        ]);
    }
}
