<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Livewire\MusicPlayer;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/player', MusicPlayer::class)->name('player');

    Route::get('/audio/{filename}', function (string $filename) {
        abort_unless(str_ends_with($filename, '.mp3'), 403);
        abort_unless(Storage::disk('music')->exists($filename), 404);

        return response()->file(
            storage_path('app/music/' . $filename),
            ['Content-Type' => 'audio/mpeg']
        );
    })->where('filename', '.+\.mp3')->name('audio.stream');

    Route::get('/audio/{filename}/artwork', function (string $filename) {
        abort_unless(str_ends_with($filename, '.mp3'), 403);
        abort_unless(Storage::disk('music')->exists($filename), 404);

        $path = storage_path('app/music/' . $filename);
        $id3 = new \getID3();
        $tags = $id3->analyze($path);

        $art = $tags['id3v2']['APIC'][0]['data'] ?? null;
        $mime = $tags['id3v2']['APIC'][0]['image_mime'] ?? 'image/jpeg';

        abort_unless($art, 404);

        return response($art)->header('Content-Type', $mime);
    })->where('filename', '.+\.mp3')->name('audio.artwork');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
