<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(["prefix" => "v1"], function () {

    Route::get("/weather/{city_id}/{year}/{month}", \ApiController::class . "@index")
        ->where([
            "city_id" => "[0-9]+",
            "month" => "[0-9]{1,2}",
            "year" => "[0-9]{4}",
        ]);
    Route::get("/dictionaries", \ApiController::class . "@dictionaries");

});
