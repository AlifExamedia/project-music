<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Livewire\MusicPlayer;
use App\Livewire\UploadMusic;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/player', MusicPlayer::class)->name('player');
    Route::get('/upload', UploadMusic::class)->name('music.upload');

    Route::get('/audio/{filename}', function (string $filename) {
        abort_unless(str_ends_with($filename, '.mp3'), 403);
        abort_unless(Storage::disk('music')->exists($filename), 404);

        return response()->file(
            storage_path('app/music/' . $filename),
            ['Content-Type' => 'audio/mpeg']
        );
    })->where('filename', '.+\.mp3')->name('audio.stream');

    Route::get('/audio/cover/{filename}', function (string $filename) {
        $filename = basename($filename);
        $path     = storage_path('app/music/cover/' . $filename);

        abort_unless(file_exists($path), 404);

        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response()->file($path, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=31536000']);
    })->where('filename', '[^/]+')->name('audio.cover');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
