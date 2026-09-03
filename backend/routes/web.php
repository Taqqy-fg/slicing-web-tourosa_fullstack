<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/login', function () {
    if (Auth::check()) {
        return redirect('/docs/api');
    }
    return view('docs-login');
})->name('login');

Route::post('/docs/login', function () {
    $credentials = request()->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials, request()->boolean('remember'))) {
        request()->session()->regenerate();

        if (!Auth::user()->is_superadmin) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Hanya Super Admin yang dapat mengakses dokumentasi API.',
            ]);
        }

        return redirect('/docs/api');
    }

    return back()->withErrors([
        'email' => 'Email atau password salah.',
    ]);
})->name('login');

Route::post('/docs/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/docs/login');
})->name('logout');
