<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WhisperController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\FollowerController;

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
    |
    | ログイン、新規登録、ログアウトなどの認証処理。
    |
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

        // ログイン中のユーザー情報を取得
        Route::get('/user', [AuthController::class, 'user']);

        // ログアウト
        Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | ユーザーAPI
        |--------------------------------------------------------------------------
        */

        // ユーザー一覧を取得
        Route::get('/users', [UserController::class, 'index']);

        // 指定ユーザーの詳細を取得
        Route::get('/users/{id}', [UserController::class, 'show']);

        // ログイン中ユーザーのプロフィールを更新
        Route::put('/users/profile', [UserController::class, 'updateProfile']);

        /*
        |--------------------------------------------------------------------------
        | ささやきAPI
        |--------------------------------------------------------------------------
        */

        // ささやき一覧を取得
        Route::get('/whispers', [WhisperController::class, 'index']);

        // 新しいささやきを投稿
        Route::post('/whispers', [WhisperController::class, 'store']);

        // 指定したささやきの詳細を取得
        Route::get('/whispers/{id}', [WhisperController::class, 'show']);

        // 自分のささやきを更新
        Route::put('/whispers/{id}', [WhisperController::class, 'update']);

        // 自分のささやきを削除
        Route::delete('/whispers/{id}', [WhisperController::class, 'destroy']);

        // ささやきにいいねする
        Route::post('/whispers/{id}/like', [WhisperController::class, 'like']);

        // ささやきのいいねを解除する
        Route::delete('/whispers/{id}/like', [WhisperController::class, 'unlike']);

        /*
        |--------------------------------------------------------------------------
        | 検索API
        |--------------------------------------------------------------------------
        */

        // キーワードでユーザーやささやきを検索
        Route::get('/search', [SearchController::class, 'search']);

        /*
        |--------------------------------------------------------------------------
        | フォローAPI
        |--------------------------------------------------------------------------
        |
        | 設計書に合わせて FollwerController の名前を使用。
        | 後で修正する場合は FollowerController に変更する。
        |
        */

        // 指定ユーザーをフォローする
        Route::post('/users/{id}/follow', [FollowerController::class, 'follow']);

        // 指定ユーザーのフォローを解除する
        Route::delete('/users/{id}/follow', [FollowerController::class, 'unfollow']);

        // 自分がフォローしているユーザー一覧を取得
        Route::get('/follows', [FollowerController::class, 'follows']);

        // 自分をフォローしているユーザー一覧を取得
        Route::get('/followers', [FollowerController::class, 'followers']);
    });
});