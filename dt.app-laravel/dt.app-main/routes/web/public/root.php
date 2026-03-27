<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::redirect('/register', '/login');
Route::post('/register', fn () => redirect('/login'));
