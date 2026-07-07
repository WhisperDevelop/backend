<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WhisperController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\FollowerController;
use App\Http\Controllers\Api\V1\RegistrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| WhisperSystem の Web API ルーティング定義。
| 基本URLは /api/v1/... の形式で統一する。
|
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 認証API
    |--------------------------------------------------------------------------
    */

    // 新規ユーザー登録
    Route::post('/register', [UserController::class, 'register']);

    // ログイン
    Route::post('/login', [AuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | 認証が必要なAPI
    |--------------------------------------------------------------------------
    |
    | Sanctum の auth:sanctum ミドルウェアでログイン状態を確認する。
    |
    */

    Route::middleware('auth:sanctum')->group(function () {

        // ログアウト
        Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | ユーザーAPI
        |--------------------------------------------------------------------------
        */

        // ログイン中のユーザー情報を取得
        Route::get('/user', [UserController::class, 'show']);

        // 全ユーザー一覧を取得
        Route::get('/users', [UserController::class, 'index']);

        // 指定ユーザー情報を取得
        Route::get('/users/{id}', [UserController::class, 'getById']);

        // ログイン中ユーザーのプロフィールを更新
        Route::post('/users/profile/{id}', [UserController::class, 'update']);

        // ログインユーザーを削除
        Route::post('/users/delete/{id}', [UserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ささやきAPI
        |--------------------------------------------------------------------------
        */

        // タイムライン取得
        Route::get('/whispers', [WhisperController::class, 'index']);

        // 新しいささやきを投稿
        Route::post('/whispers', [WhisperController::class, 'store']);

        // 指定ユーザーのささやき一覧取得
        Route::get('/user/whispers/{id}', [WhisperController::class, 'show']);

        // 指定ユーザーがいいねしたささやき一覧取得
        Route::get('/user/liked/{id}', [WhisperController::class, 'liked']);

        // 自分のささやきを更新（画像の差し替え・削除にも対応）
        Route::post('/whispers/update/{id}', [WhisperController::class, 'update']);

        // 自分のささやきを削除
        Route::post('/whispers/{id}', [WhisperController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | 検索API
        |--------------------------------------------------------------------------
        */

        // ユーザー名検索
        Route::get('/search/users/{keyword}', [SearchController::class, 'usernameSearch']);

        // ささやき本文検索
        Route::get('/search/whispers/{keyword}', [SearchController::class, 'whisperSearch']);

        /*
        |--------------------------------------------------------------------------
        | フォローAPI
        |--------------------------------------------------------------------------
        */

        // 自分がフォローしているユーザー一覧を取得
        Route::get('/following', [FollowerController::class, 'following']);

        // 自分をフォローしているユーザー一覧を取得
        Route::get('/followers', [FollowerController::class, 'followers']);

        /*
        |--------------------------------------------------------------------------
        | 登録API (フォロー・いいね)
        |--------------------------------------------------------------------------
        |
        | shiyousho に基づき RegistrationController を使用。
        |
        */

        // フォロー登録・解除 (Toggle)
        Route::post('/followcheck', [RegistrationController::class, 'followRegister']);

        // いいね登録・解除 (Toggle)
        Route::post('/likecheck', [RegistrationController::class, 'likeRegister']);
    });
});