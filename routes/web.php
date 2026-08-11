<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::controller(ContactController::class)->name('contacts.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/contacts/confirm', 'confirm')->name('confirm');
    Route::post('/contacts', 'store')->name('store');
    Route::get('/thanks', 'thanks')->name('thanks');

});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/contacts/{contactId}', [AdminController::class, 'show'])->name('show');
    Route::delete('/contacts/{contactId}', [AdminController::class, 'destroy'])->name('destroy');
    Route::resource('tags', TagController::class)->except(['index', 'create', 'show']);
});
