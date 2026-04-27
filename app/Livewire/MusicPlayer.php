<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use App\Models\Favourite;
use App\Models\Playlist;
use App\Models\PlaylistSong;

class MusicPlayer extends Component
{
    public array $songs = [];
    public array $favourites = [];
    public array $playlists = [];

    public function mount(): void
    {
        $id3 = new \getID3();
        $user = auth()->user();

        $this->songs = collect(Storage::disk('music')->files())
            ->filter(fn($file) => str_ends_with($file, '.mp3'))
            ->map(function ($file) use ($id3) {
                $path = storage_path('app/music/' . basename($file));
                $tags = $id3->analyze($path);
                \getid3_lib::CopyTagsToComments($tags);

                $comments = $tags['comments'] ?? [];
                $hasArt = isset($tags['id3v2']['APIC'][0]['data']);

                return [
                    'filename'    => basename($file),
                    'title'       => $comments['title'][0]  ?? basename($file, '.mp3'),
                    'artist'      => $comments['artist'][0] ?? null,
                    'album'       => $comments['album'][0]  ?? null,
                    'genre'       => $comments['genre'][0]  ?? null,
                    'year'        => $comments['year'][0]   ?? null,
                    'track'       => $comments['track_number'][0] ?? null,
                    'url'         => route('audio.stream',  ['filename' => basename($file)]),
                    'artwork_url' => $hasArt ? route('audio.artwork', ['filename' => basename($file)]) : null,
                    'duration'    => isset($tags['playtime_seconds'])
                                        ? gmdate('i:s', (int) $tags['playtime_seconds'])
                                        : null,
                ];
            })
            ->values()
            ->toArray();

        $this->favourites = Favourite::where('user_id', $user->id)
            ->pluck('song_path')
            ->toArray();

        $this->playlists = Playlist::where('user_id', $user->id)
            ->with('songs')
            ->get()
            ->map(fn($pl) => [
                'id'          => $pl->id,
                'title'       => $pl->title,
                'description' => $pl->description ?? '',
                'songs'       => $pl->songs->pluck('song_path')->toArray(),
            ])
            ->toArray();
    }

    public function toggleFavourite(string $filename): void
    {
        $user = auth()->user();
        $existing = Favourite::where('user_id', $user->id)->where('song_path', $filename)->first();

        if ($existing) {
            $existing->delete();
        } else {
            Favourite::create(['user_id' => $user->id, 'song_path' => $filename]);
        }
    }

    public function createPlaylist(string $title, string $description = ''): array
    {
        $playlist = Playlist::create([
            'user_id'     => auth()->id(),
            'title'       => trim($title),
            'description' => trim($description),
        ]);

        return [
            'id'          => $playlist->id,
            'title'       => $playlist->title,
            'description' => $playlist->description ?? '',
            'songs'       => [],
        ];
    }

    public function updatePlaylist(int $id, string $title, string $description): void
    {
        Playlist::where('id', $id)
            ->where('user_id', auth()->id())
            ->update([
                'title'       => trim($title),
                'description' => trim($description),
            ]);
    }

    public function deletePlaylist(int $id): void
    {
        Playlist::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();
    }

    public function addToPlaylist(int $playlistId, string $filename): void
    {
        Playlist::where('id', $playlistId)->where('user_id', auth()->id())->firstOrFail();

        $maxPos = PlaylistSong::where('playlist_id', $playlistId)->max('position') ?? 0;

        PlaylistSong::firstOrCreate(
            ['playlist_id' => $playlistId, 'song_path' => $filename],
            ['position' => $maxPos + 1]
        );
    }

    public function removeFromPlaylist(int $playlistId, string $filename): void
    {
        Playlist::where('id', $playlistId)->where('user_id', auth()->id())->firstOrFail();

        PlaylistSong::where('playlist_id', $playlistId)
            ->where('song_path', $filename)
            ->delete();
    }

    public function render()
    {
        return view('livewire.music-player')
            ->layout('layouts.player');
    }
}
