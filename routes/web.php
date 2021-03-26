<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {return view('welcome');});
Route::get('/services/from_xml_file', [LandController::class, 'from_xml_file']);
Route::get('/services/get_geo_data_aisgzk', [LandController::class, 'get_data']);
