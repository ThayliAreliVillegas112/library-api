<?php

use App\Http\Controllers\BookController; //IMPORTAR
use App\Http\Controllers\AuthorController; //IMPORTAR
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//SE CREAN RUTAS PARA QUE HAYA UN CONTROL DE QUIEN PUEDE ACCEDER A LA INFORMACIÓN Y PARA TENER MAYOR ORGANIZACIÓN
Route::prefix('book')->group(function (){
    Route::get('index', [BookController::class, 'index']);
    Route::post('store', [BookController::class, 'store']);
    Route::get('show/{id}', [BookController::class, 'show']); //SE DEBE PONER EL ID
    Route::put('update/{id}', [BookController::class, 'update']);
    Route::delete('destroy/{id}', [BookController::class, 'destroy']);
});

Route::prefix('author')->group(function() {
    Route::get('index', [AuthorController::class, 'index']);
    Route::post('store', [AuthorController::class, 'store']);
    Route::put('update/{id}', [AuthorController::class, 'update']);
    Route::get('show/{id}', [AuthorController::class, 'show']);
    Route::delete('destroy/{id}', [AuthorController::class, 'destroy']);
});

//Authentication is not required for these endpoints
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

//Authentication is required for these endpoints (apply middleware auth:sanctum)
Route::group(['middleware' => ["auth:sanctum"]], function () {
    Route::get('userProfile', [AuthController::class, 'userProfile']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::put('changePassword', [AuthController::class, 'changePassword']);
    Route::post('addBookReview/{book_id}', [BookController::class, 'addBookReview']);
    Route::put('updateBookReview/{id}',[BookController::class,'updateBookReview']);

});
