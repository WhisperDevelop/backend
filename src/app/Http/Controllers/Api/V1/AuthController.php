<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        // ログイン処理の実装
        // バリデーション（JSON で返す）
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required'
        ]);
        // バリデーションエラーの場合は JSON でエラーメッセージを返す
        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 認証
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'ログイン情報が正しくありません'
            ], 401);
        }

        // 認証済みユーザー取得
        $user = Auth::user();

        // トークン発行（モバイルアプリ用）
        $token = $user->createToken('mobile')->plainTextToken;


        return response()->json([
            'token' => $token,
            'user'  => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        // ログアウト処理の実装
        // 現在使用中のトークンだけ削除
        $token = $request->user()->currentAccessToken();

        if (!$token) {
            return response()->json([
                'message' => '有効なトークンがありません'
            ], 400);
        }

        $token->delete();

        return response()->json([
            'message' => 'ログアウトしました'
        ], 200);
    }

}
